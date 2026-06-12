<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_apsolu;

use coding_exception;
use local_apsolu\core\reset;
use local_apsolu\observer\reset as observer;
use moodle_exception;
use Throwable;
use Exception;
use local_apsolu\event\reset_enabled;
use local_apsolu\event\reset_disabled;
use local_apsolu\event\reset_updated;
use local_apsolu\task\reset_courses as resetTask;
use local_apsolu\task\reset_courses_notify as notifyTask;
use core\task\manager;
use stdClass;
use DateTime;
use local_apsolu\core\federation\course as ffsucourse;
use core\output\html_writer;
use local_apsolu\tests\phpunit\dataset_provider;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/local/apsolu/tests/phpunit/dataset_provider.php');

/**
 * Classe de tests pour les fonctions de réinitialisation. Couvre certaines fonctions des classes :
 *      - local_apsolu\core\reset
 *      - local_apsolu\observer\reset
 *
 * @package    local_apsolu
 * @copyright  2026 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
final class reset_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
        $this->set_config(['allusers' => true, 'oldusers' => false, 'selectenrolments' => false]);

        $this->resetAfterTest();
    }

    /** Initialise la configuration (valeurs en cache) avec des valeurs par défaut (réinitialisation non active)
     *
     * @param array $attributes list of setting -> value pairs to overcome default ones.
     * @return void
     */
    protected function set_config($attributes = []): void {
        reset::init_config($attributes);

        set_config('noemailever', 0);
    }

    /**
     * Teste reset get_config().
     *
     * @covers \local_apsolu\core\reset::get_config
     */
    public function test_get_config(): void {
        // Initialisé avec manualusers = 0 et allusers = 1.
        $this->assertEquals(reset::get_config('allusers'), 1);
        $this->assertEquals(reset::get_config('manualusers'), 0);

        unset_config(reset::get_prefixed_setting('allusers'), reset::DBPLUGIN);
        $this->assertFalse(reset::get_config('allusers')); // Variable non initialisée doit renvoyer false.
    }

    /**
     * Teste reset load_default_settings().
     *
     * @covers \local_apsolu\core\reset::load_default_settings
     */
    public function test_load_default_settings(): void {
        $reset = new reset();

        // La fonction doit retourner true si toutes les variables sont initialisées dans la configuration.
        $this->assertTrue($reset->load_default_settings());

        // Les valeurs doivent être récupérées sous forme de booleens.
        $this->assertFalse($reset->oldusers);
        $this->assertTrue($reset->allusers);

        // Sauf pour le timestamp d'exécution.
        $this->assertEquals($reset->nextdatetime, 0);

        // La fonction doit retourner false si une des variables n'est pas initialisée dans la configuration.
        unset_config(reset::get_prefixed_setting('allusers'), reset::DBPLUGIN);
        $this->assertFalse($reset->load_default_settings());
    }

    /**
     * Teste reset is_active().
     *
     * @covers \local_apsolu\core\reset::is_active
     */
    public function test_is_active(): void {
        // Initialisé avec nextactive et nextdatetime = 0 donc is_active doit être faux.
        $this->assertFalse(reset::is_active());

        reset::set_config('nextactive', 1);
        reset::set_config('nextdatetime', time() - 1000); // Nextactive est true mais nextdatetime est dépassé.
        $this->assertFalse(reset::is_active());

        reset::set_config('nextdatetime', time() + 1000); // Nextdatetime est à venir : statut actif ok.
        $this->assertTrue(reset::is_active());
    }

    /**
     * Teste reset update_setting().
     *
     * @covers \local_apsolu\core\reset::update_setting
     */
    public function test_update_setting(): void {
        $reset = new reset();
        $reset->load_default_settings();

        // Pas de changement de valeur.
        $this->assertFalse($reset->update_setting('allusers'));

        $reset->allusers = false;

        // Valeur modifiée.
        $this->assertTrue($reset->update_setting('allusers'));
        $this->assertEquals(reset::get_config('allusers'), 0);

        unset_config(reset::get_prefixed_setting('allusers'), reset::DBPLUGIN);

        // Valeur créée.
        $this->assertTrue($reset->update_setting('allusers'));
        $this->assertEquals(reset::get_config('allusers'), 0);
    }

    /**
     * Teste reset save_settings().
     *
     * @covers \local_apsolu\core\reset::save_settings
     */
    public function test_save_settings(): void {
        $reset = new reset();
        $reset->load_default_settings();

        // Capture des événements.
        $sink = $this->redirectEvents();

        $reset->nextdatetime = reset::get_minimum_datetime();

        // Reset enabled. Pas d'autre changements.
        $this->assertTrue($reset->save_settings());
        $this->assertTrue($reset->nextactive);

        $events = $sink->get_events();

        $this->assertCount(3, $events); // 2 config logs ( nextdatetime et nextactive) + 1 event.
        $event = array_pop($events);
        $this->assertInstanceOf(reset_enabled::class, $event);
        $this->assertNull($event->other);

        $sink->clear();

        // Reset updated : nextdatetime changée (statut actif est resté le même).
        $reset->nextdatetime = $reset->nextdatetime + (72 * 3600 - 60 );
        $reset->allusers = false;
        $reset->oldusers = true;

        $this->assertTrue($reset->save_settings());

        $events = $sink->get_events();

        $this->assertCount(4, $events); // 3 config logs + 1 event.
        $event = array_pop($events);
        $this->assertInstanceOf(reset_updated::class, $event);
        $this->assertEquals($event->other, ["nextdatetime", "allusers", "oldusers"]);

        $sink->clear();

        // Reset disabled et reset updated.
        $reset->nextdatetime = 0;
        $reset->manualusers = true;

        $this->assertTrue($reset->save_settings());
        $this->assertFalse($reset->nextactive);

        $events = $sink->get_events();

        $this->assertCount(5, $events); // 3 config logs + 2 events.
        $event = array_pop($events);
        $this->assertInstanceOf(reset_updated::class, $event);
        $this->assertEquals($event->other, ["manualusers", "disabled"]);

        $event = array_pop($events);
        $this->assertInstanceOf(reset_disabled::class, $event);
        $this->assertNull($event->other);

        $sink->clear();

        // Pas de changements.
        $this->assertFalse($reset->save_settings());

        $events = $sink->get_events();
        $this->assertCount(0, $events);

        $sink->close();
    }

    /**
     * test observer enabled()
     *
     * @covers \local_apsolu\observer\reset::enabled
     */
    public function test_enabled(): void {
        $reset = new reset();
        $reset->load_default_settings();

        // Capture des événements.
        $sinkevent = $this->redirectEvents();
        // Capture des mails sortants.
        $sinkmail = $this->redirectEmails();

        $reset->nextdatetime = reset::get_minimum_datetime();
        $reset->save_settings();
        $events = $sinkevent->get_events();
        $event = array_pop($events);
        $sinkevent->close();

        observer::enabled($event);

        // On vérifie qu'une tâche a été créée pour l'éxécution de la réinitialisation, avec le run time correspondant.
        $queued = manager::get_queued_adhoc_task_record(new resetTask());
        $this->assertNotFalse($queued);
        $resetruntime = $queued->nextruntime;
        $this->assertEquals($resetruntime, $reset->nextdatetime);

        // On vérifie qu'une tâche a été créée pour l'envoi d'un email de rappel la veille du jour de l'exécution à 8h .
        $task = new notifyTask();
        $queued = manager::get_queued_adhoc_task_record($task);
        $this->assertNotFalse($queued);

        $notifyruntime = new DateTime();
        $daybefore = $resetruntime - (24 * 3600);
        $notifyruntime->setTimestamp($daybefore)->setTime(8, 0);
        $strdate = userdate($notifyruntime->getTimeStamp(), get_string('strftimedate', 'local_apsolu')) . ' ' .
            userdate($notifyruntime->getTimeStamp(), get_string('strftimeyear', 'local_apsolu')) . ' à 08h00';

        $this->assertEquals($strdate, userdate($queued->nextruntime, get_string('strftimedatetimewithyear', 'local_apsolu')));

        // On vérifie qu'un email a été envoyé aux personnes autorisés à programmer la réinitialisation.
        $messages = $sinkmail->get_messages();
        $sinkmail->close();

        $this->assertNotEmpty($messages);

        $mail = reset($messages);

        // Vérifier le sujet du mail 'Réinitialisation des espace-cours programmée'.
        $this->assertStringContainsString(get_string('settings_reset_courses_enabled', 'local_apsolu'), $mail->subject);

        // Vérifier que le corps du mail contient la phrase 'La réinitialisation des espaces-cours a été programmée par '.
        $mailplaintext = $this->get_mail_body($mail);
        $expected = rtrim(get_string('reset_was_enabled', 'local_apsolu', ''), ".");
        $this->assertStringContainsString($expected, $mailplaintext);
    }

     /**
      * test observer disabled()
      *
      * @covers \local_apsolu\observer\reset::disabled
      */
    public function test_disabled(): void {
        $reset = new reset();
        $reset->load_default_settings();

        // D'abord activer la réinitialisation.
        $reset->nextdatetime = reset::get_minimum_datetime();
        $reset->save_settings();

        // Capture des événements.
        $sinkevent = $this->redirectEvents();
        // Capture des mails sortants.
        $sinkmail = $this->redirectEmails();

        $reset->nextdatetime = 0;
        $reset->save_settings();
        $events = $sinkevent->get_events();
        $event = array_pop($events);
        $sinkevent->close();

        observer::disabled($event);

        // On vérifie que la tâche d'exécution a bien été supprimée.
        $queued = manager::get_queued_adhoc_task_record(new resetTask());
        $this->assertFalse($queued);

        // On vérifie que la tâche de notification a bien été supprimée.
        $queued = manager::get_queued_adhoc_task_record(new notifyTask());
        $this->assertFalse($queued);

        // On vérifie qu'un email a été envoyé aux personnes autorisés à programmer la réinitialisation.
        $messages = $sinkmail->get_messages();
        $sinkmail->close();

        $this->assertNotEmpty($messages);

        $mail = reset($messages);

        // Vérifier le sujet du mail 'Réinitialisation des espace-cours suspendue'.
        $this->assertStringContainsString(get_string('settings_reset_courses_disabled', 'local_apsolu'), $mail->subject);

        // Vérifier que le corps du mail contient la phrase
        // 'La tâche de réinitialisation des espaces-cours (rentrée {$a->year}) a été déprogrammée par '.
        $mailplaintext = $this->get_mail_body($mail);
        $mailinfos = new stdClass();
        $mailinfos->year = userdate(time(), get_string('strftimeyear', 'local_apsolu'));
        $mailinfos->userinfos = "";

        $expected = rtrim(get_string('reset_was_disabled', 'local_apsolu', $mailinfos), '.');

        $this->assertStringContainsString($expected, $mailplaintext);
    }

     /**
      * test observer updated()
      *
      * @covers \local_apsolu\observer\reset::updated
      */
    public function test_updated(): void {
        $reset = new reset();
        $reset->load_default_settings();

        // D'abord activer la réinitialisation.
        $reset->nextdatetime = reset::get_minimum_datetime();
        $reset->save_settings();

        // Capture des événements.
        $sinkevent = $this->redirectEvents();
        // Capture des mails sortants.
        $sinkmail = $this->redirectEmails();

        // On vérifie si la date de la tâche de réinitialisatinon a été modifiée.
        $reset->allusers = false;
        $reset->manualusers = true;
        $reset->oldusers = true;
        $reset->nextdatetime = $reset->nextdatetime + (72 * 3600); // On modifie la date d'exécution.
        $reset->save_settings();
        $events = $sinkevent->get_events();
        $event = array_pop($events);

        // 1. Les tâches doivent être modifiées car la date a été changée.
        observer::updated($event);

        // On vérifie que la date de la tâche d'exécution de la réinitialisation a été modifiée.
        $task = manager::get_queued_adhoc_task_record(new resetTask());
        $this->assertNotFalse($task);
        $this->assertEquals($reset->nextdatetime, $task->nextruntime);

        // On vérifie que la date de la tâche d'envoi d'email de rappel a été modifiée.
        $task = manager::get_queued_adhoc_task_record(new notifyTask());
        $this->assertNotFalse($task);
        $notify = new notifyTask();
        $notify->set_notify_runtime($reset->nextdatetime);
        $notify = manager::record_from_adhoc_task($notify);
        $this->assertEquals($notify->nextruntime, $task->nextruntime);

        // On vérifie qu'un email a été envoyé aux personnes autorisés à programmer la réinitialisation.
        $messages = $sinkmail->get_messages();

        $this->assertNotEmpty($messages);

        $mail = reset($messages);

        // Vérifier le sujet du mail 'Réinitialisation des espace-cours modifiée'.
        $this->assertStringContainsString(get_string('settings_reset_courses_updated', 'local_apsolu'), $mail->subject);

        // Vérifier que le corps du mail contient la phrase
        // 'La configuration de la tâche de réinitialisation des espaces-cours a été modifiée par '.
        $mailplaintext = $this->get_mail_body($mail);
        $expected = rtrim(get_string('reset_was_updated', 'local_apsolu', ''), '.');

        $this->assertStringContainsString($expected, $mailplaintext);

        $sinkevent->clear();
        $sinkmail->clear();

        // 2. Si la date n'est pas valide il y a une exception qui est levée.
        $reset->nextdatetime = time() - (48 * 3600);
        $reset->save_settings();
        try {
            $events = $sinkevent->get_events();
            $event = array_pop($events);
            observer::updated($event);
        } catch (moodle_exception $exception) {
            $this->assertInstanceOf(moodle_exception::class, $exception);
        }

        // Aucun mail envoyé ?
        $messages = $sinkmail->get_messages();
        $this->assertEmpty($messages);

        $sinkmail->clear();
        $sinkevent->clear();

        // 3. De même si le statut est inactif.
        // D'abord activer de nouveau la réinitialisation avec une date valide.
        $reset->nextdatetime = reset::get_minimum_datetime();
        $reset->save_settings();

        // Ensuite on change la date pour provoquer une mise à jour de la tâche.
        $reset->nextdatetime = time() + (72 * 3600);
        $reset->save_settings();

        try {
            $events = $sinkevent->get_events();
            $event = array_pop($events);
            reset::set_config('nextactive', 0); // On change la valeur de la variable en DB / cache.
            observer::updated($event);
        } catch (moodle_exception $exception) {
            $this->assertInstanceOf(moodle_exception::class, $exception);
        }

        $messages = $sinkmail->get_messages();
        $this->assertEmpty($messages);

        $sinkmail->close();
        $sinkevent->close();
    }

    /**
     * test execute()
     *
     * @covers \local_apsolu\task\reset_courses::execute
     */
    public function test_execute(): void {
        global $DB;

        $task = new resetTask();

        // Ouverture des buffers.
        $sinkmail = $this->redirectEmails();
        ob_start();

        // On lance la tâche mais la configuration indique qu'elle n'est pas active.
        try {
            $task->execute();
        } catch (Throwable $exception) {
            $this->assertInstanceOf(Exception::class, $exception);
        }

        // Fermeture des buffers et récupération des valeurs émises.
        $echos = ob_get_clean();
        $messages = $sinkmail->get_messages();
        $mail = reset($messages);
        $sinkmail->clear();

        // Vérifier la sortie mtrace.
        $this->assertStringContainsString('La tâche de réinitialisation a été abandonnée', $echos);

        // Vérifier l'envoi d'emails et le sujet du mail : 'Réinitialisation des espace-cours effectuée'.
        $this->assertNotEmpty($messages);
        $this->assertStringContainsString(get_string('reset_task_failed', 'local_apsolu'), $mail->subject);

        reset::set_config('nextactive', 1);
        reset::set_config('nextdatetime', reset::get_minimum_datetime());
        reset::set_config('selectenrolments', 1);
        reset::set_config('metaenrolments', 1);

        // La purge des utilisateurs est effectuée et testée dans une fonction dédiée.
        reset::set_config('oldusers', 0);
        reset::set_config('manualusers', 0);
        reset::set_config('allusers', 0);

        $reset = new reset();
        $reset->load_default_settings();

        // Mise en place de la BDD de tests.
        dataset_provider::execute();// Le provider de données de test est susceptible d'envoyer un email.

        // Tables qui seront vidées.
        $sqlpurges = [];
        $sqlpurges[] = 'SELECT COUNT(*) as nb FROM {user_info_data} WHERE fieldid NOT IN ' .
            '(SELECT id FROM {user_info_field} WHERE shortname IN ' .
            '("apsoluidcardnumber", "apsoluidcardnumberexternal", "apsolufederationnumber"))';
        $sqlpurges[] = 'SELECT COUNT(*) as nb FROM {course} WHERE visible=1 AND id IN (SELECT id FROM {apsolu_courses})';
        $sqlpurges[] = 'SELECT COUNT(*) as nb FROM {apsolu_attendance_presences}';
        $sqlpurges[] = 'SELECT COUNT(*) as nb FROM {apsolu_attendance_qrcodes}';
        $sqlpurges[] = 'SELECT COUNT(*) as nb FROM {apsolu_attendance_sessions}';
        $sqlpurges[] = 'SELECT COUNT(*) as nb FROM {apsolu_payments}';
        $sqlpurges[] = 'SELECT COUNT(*) as nb FROM {apsolu_payments_items}';
        $sqlpurges[] = 'SELECT COUNT(*) as nb FROM {apsolu_payments_transactions}';
        $sqlpurges[] = 'SELECT COUNT(*) as nb FROM {apsolu_payments_addresses}';
        $sqlpurges[] = 'SELECT COUNT(*) as nb FROM {user} WHERE policyagreed=1';
        $sqlpurges[] = 'SELECT COUNT(*) as nb FROM {apsolu_federation_adhesions}';
        $sqlpurges[] = 'SELECT COUNT(*) as nb FROM {cohort_members} WHERE cohortid IN (SELECT id FROM {cohort})';
        $sqlpurges[] = 'SELECT COUNT(*) as nb FROM {user_enrolments} WHERE enrolid IN ' .
            '(SELECT id FROM {enrol} WHERE enrol = "select")';
        $sqlpurges[] = 'SELECT COUNT(*) as nb FROM {enrol} WHERE enrol = "select"';
        $sqlpurges[] = 'SELECT COUNT(*) as nb FROM {grade_grades}';

        // Pas de tests sur les cours meta. A ajouter ?

        // On vérifie que des données étaient présentes avant la purge .
        foreach ($sqlpurges as $sql) {
            $result = $DB->get_record_sql($sql);
            if ($result->nb == 0) {
                $this->markTestIncomplete('Les données de tests n\'ont pas été correctement générées : ' .
                '0 entrée correspondant à la requête : ' . $sql .
                '. Impossible de tester la fonction reset_courses->execute()');
            }
        }

        $purgeresults = [];

        // Federation.
        $federationcourse = new ffsucourse();
        $federationcourseid = $federationcourse->get_courseid();
        if (empty($federationcourseid)) {
            $this->markTestIncomplete('Les données de tests n\'ont pas été correctement générées : ' .
            '0 cours correspondant à l\'entrée FFSU : impossible de tester la fonction reset_courses->execute()');
        }
        $sqlenrolffsuid = 'SELECT COUNT(*) as nb FROM {enrol} WHERE enrol = "select" AND courseid = :ffsu';
        $result = $DB->get_record_sql($sqlenrolffsuid, ['ffsu' => $federationcourseid]);
        $purgeresults[$sqlenrolffsuid] = $result;

        $sqlffsuenrolments = 'SELECT COUNT(*) as nb FROM {user_enrolments} WHERE enrolid IN ' .
        '(SELECT id FROM {enrol} WHERE enrol = "select" AND courseid = :ffsu)';
        $result = $DB->get_record_sql($sqlffsuenrolments, ['ffsu' => $federationcourseid]);
        $purgeresults[$sqlffsuenrolments] = $result;

        foreach ($purgeresults as $sql => $result) {
            if ($result->nb == 0) {
                $this->markTestIncomplete('Les données de tests n\'ont pas été correctement générées : ' .
                '0 entrée correspondant à la requête : ' . $sql .
                '. Impossible de tester la fonction reset_courses->execute()');
            }
        }

        // Ouverture des buffers (le sinkmail est déjà ouvert).
        $sinkevent = $this->redirectEvents();
        ob_start();

        // Lancement de la tâche.
        $task->execute();

        // Fermeture des buffers.
        $echos = ob_get_clean();
        $messages = $sinkmail->get_messages();
        $sinkmail->close();

        $this->assertStringContainsStringIgnoringLineEndings(
            'Lancement de la tâche de réinitialisation des espaces cours (rentrée ' . date('Y', time()) . ')',
            $echos
        );

        // On vérifie que les mails de confirmation ont bien été envoyés aux 3 admins + 1 gestionnaire.
        // Cela permet aussi de tester si les mails ont bien été désactivés pour la suppression des notes.
        $this->assertCount(4, $messages);

        // On vérifie que les tables ont bien été purgées.
        foreach ($sqlpurges as $sql) {
            $result = $DB->get_record_sql($sql);
            $this->assertEquals(0, $result->nb);
        }

        $result = $DB->get_record_sql($sqlenrolffsuid, ['ffsu' => $federationcourseid]);
        $purgeresults[$sqlenrolffsuid] = $result;

        $result = $DB->get_record_sql($sqlffsuenrolments, ['ffsu' => $federationcourseid]);
        $purgeresults[$sqlffsuenrolments] = $result;

        foreach ($purgeresults as $sql => $result) {
            $this->assertEquals(0, $result->nb);
        }
    }

    /**
     * test completed()
     *
     * @covers \local_apsolu\task\reset_courses::completed
     */
    public function test_completed(): void {
        // Activer la réinitialisation dans la configuration.
        reset::set_config('nextactive', 1);
        reset::set_config('nextdatetime', reset::get_minimum_datetime());

        $reset = new reset();
        $reset->load_default_settings();

        $task = new resetTask();

        // Ouverture des buffers.
        $sinkevent = $this->redirectEvents();
        $sinkmail = $this->redirectEmails();
        ob_start();

        // Fonction testée doit clôre la tâche de réinitialisation en effectuant les actions suivantes :
        // - écrire dans la trace.
        // - envoyer un mail pour notifier du succès de la procédure
        // - positionner les valeurs nextactive et nextdatime à 0 dans la configuration.
        // - émettre un log.
        $task->completed($reset);

        // Fermeture des buffers et récupération des events et emails émis.
        $events = $sinkevent->get_events();
        $sinkevent->close();
        $messages = $sinkmail->get_messages();
        $sinkmail->close();
        $echos = ob_get_clean();

        // Vérification de la trace émise.
        $this->assertStringContainsStringIgnoringLineEndings('Tâche de réinitialisation terminée sans incident', $echos);

        // Vérifier si le mail envoyé a bien pour sujet 'Réinitialisation des espace-cours effectuée'.
        $mail = reset($messages);
        $this->assertCount(1, $messages); // Un seul user admin en base car le dataset provider n'est pas exécuté pour ce test.
        $this->assertStringContainsString(get_string('reset_task_success', 'local_apsolu'), $mail->subject);

        // 3 événements ont du être capturés : 3 changements de configuration et un log reset_completed.
        $this->assertCount(4, $events);
        $this->assertInstanceOf(\local_apsolu\event\reset_completed::class, $events[2]);

        // Vérifier si la configuration a bien été modifiée.
        $this->assertFalse(get_config('local_apsolu', 'nextactive'));
        $this->assertEquals(0, get_config('local_apsolu', 'nextdatetime'));
        $this->assertNotNull(get_config('local_apsolu', 'lastruntime'));
    }

    /**
     * test purge_users()
     *
     * @covers \local_apsolu\task\reset_courses::purge_users
     */
    public function test_purge_users(): void {
        global $DB;
        $reset = new reset();
        $reset->load_default_settings();

        // Reset all users.
        // Mise en place de la BDD de tests.
        dataset_provider::execute();

        $sql = 'SELECT COUNT(*) as nb from {user}';
        $allusers = $DB->get_record_sql($sql);

        // Utilisateurs 'protégés' : ne doivent jamais être supprimés
        // Rôle gestionnaire, créateur de cours et enseignant.
        $sql = 'SELECT DISTINCT ra.userid FROM {role_assignments} ra WHERE ra.roleid IN (1,2,3)';
        $superuserids = $DB->get_records_sql($sql);

        // Administrateurs.
        $admins = get_admins();

        // Comptes associés à des webservices.
        $sql = "SELECT DISTINCT userid FROM {external_services_users}" .
                " WHERE externalserviceid IN (SELECT id FROM {external_services} WHERE component IS NULL)";
        $wsusers = $DB->get_records_sql($sql);

        if ((int) $allusers->nb == 2 || count($superuserids) == 0 || count($admins) == 1 || count($wsusers) == 0) {
            $this->markTestIncomplete('Les données de tests (comptes utilisateurs) n\'ont pas été correctement générées : ' .
                'impossible de tester la fonction reset_courses->purge_users()');
        }

        // Utilisateurs non supprimés.
        $sql = 'SELECT id from {user} WHERE deleted = 1';
        $alreadydeleted = $DB->get_records_sql($sql);
        if (empty($alreadydeleted) == false) {
            $sql = 'UPDATE {user} SET deleted=0 WHERE 1';
            $DB->execute($sql);
        }
        $notremovableids = array_unique(array_merge(
            array_keys($superuserids), // Rôles gestionnaires, créateurs de cours et enseignants.
            array_keys($admins), // Administrateurs.
            array_keys($wsusers), // Webservices.
            [1, 2] // Utilisateurs créés par défaut (guest et admin).
        ));

        // Le résultat = nombre d'users initiaux moins le nombre de comptes protégés.
        $expected = $allusers->nb - count($notremovableids);

        $task = new resetTask();
        ob_start();
        $task->purge_users($reset);
        $echos = ob_get_clean();

        $this->assertStringContainsString('conserve l\'utilisateur admin: ', $echos);
        $this->assertStringContainsString('conserve l\'utilisateur webservice: ', $echos);

        $sql = 'SELECT COUNT(*) as nb from {user} WHERE deleted = 1';
        $deletedusers = $DB->get_record_sql($sql);
        $this->assertEquals($expected, $deletedusers->nb);

        // On vérifie également si les utilisateurs qui ne devaient pas être supprimés sont toujours là.
        $sql = 'SELECT COUNT(*) as nb from {user} WHERE deleted = 0';
        $undeletedusers = $DB->get_record_sql($sql);
        $this->assertEquals($allusers->nb, $deletedusers->nb + $undeletedusers->nb);

        // Reset manual et old users.
        $reset->allusers = false;
        $reset->manualusers = true;
        $reset->oldusers = true;

        // On reset le jeu de données (utilisateurs seulement).
        $sql = 'UPDATE {user} SET deleted=0 WHERE 1';
        $DB->execute($sql);

        // On modifie la méthode d'authentification pour quelques étudiants pour tester la discrimination manual/cas.
        $sql = 'UPDATE {user} SET auth="shibboleth" WHERE institution = "ENC Paris"';
        $DB->execute($sql);

        $sql = 'SELECT id from {user} WHERE auth="manual"';
        $manualusers = $DB->get_records_sql($sql);

        $sql  = 'SELECT id FROM {user} WHERE lastaccess > 0 AND lastaccess < :dateold';
        $oldusers = $DB->get_records_sql($sql, ['dateold' => time() - (365 * 24 * 3600)]);

        // Le résultat = nombre d'users obsolètes et comptes manuels qui ne sont pas gestionnaires, administrateurs etc...
        $todelete = array_unique(array_merge(array_keys($manualusers), array_keys($oldusers)));
        $expected = count($todelete) - count(array_intersect($todelete, $notremovableids));

        ob_start();
        $task->purge_users($reset);
        $echos = ob_get_clean();

        $sql = 'SELECT COUNT(*) as nb from {user} WHERE deleted = 1';
        $deletedusers = $DB->get_record_sql($sql);
        $this->assertEquals($expected, $deletedusers->nb);

        // On vérifie également si les utilisateurs qui ne devaient pas être supprimés sont toujours là.
        $sql = 'SELECT COUNT(*) as nb from {user} WHERE deleted = 0';
        $undeletedusers = $DB->get_record_sql($sql);
        $this->assertEquals($allusers->nb, $deletedusers->nb + $undeletedusers->nb);
    }

    /**
     * Extrait et parse le contenu du body d'un email encodé MIME/quoted-printable pour retourner du texte brut.
     *
     * @param object $mail
     * @return string contenu du mail en texte brut.
     */
    private function get_mail_body(object $mail): string {

        $body = $mail->body;

        // Extraie le contenu de la partie text/plain (après headers MIME).
        if (preg_match('/Content-Transfer-Encoding: quoted-printable\r?\n\r?\n(.*?)(?:--|\z)/s', $body, $matches)) {
            $body = $matches[1];
        }

        // Décode le quoted-printable.
        $body = quoted_printable_decode($body);

        // Nettoie les entités HTML résiduelles.
        $body = html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Supprimer les caractères de fin de ligne.
        $body = str_replace(["\r\n", "\r", "\n"], " ", $body);

        return trim($body);
    }
}
