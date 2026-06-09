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

namespace local_apsolu\observer;

use local_apsolu\core\reset as resetConf;
use local_apsolu\task\reset_courses as resetTask;
use local_apsolu\task\reset_courses_notify as notifyTask;
use core\task\manager;
use local_apsolu\event\reset_disabled;
use local_apsolu\event\reset_enabled;
use local_apsolu\event\reset_updated;
use moodle_exception;
use core\user;
use core\output\html_writer;
use local_apsolu\core\messaging as mailer;
use stdClass;

/**
 * Classe permettant d'écouter les évènements diffusés par Moodle.
 *
 * @package   local_apsolu
 * @copyright 2026 Université Rennes 2
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reset {
    /**
     * Génère la tâche de réinitialisation des espaces-cours.
     *
     * @return mixed renvoie la tâche créée / false si la création a échoué.
     */
    public static function create_reset_adhoc_task(): mixed {
        // On vérifie si la configuration de la tâche est correcte et le statut actif.
        $resetconf = new resetConf();
        if ($resetconf->load_default_settings() == false || $resetconf->nextactive == false) {
            return false;
        }

        // On teste la date d'exécution (ne doit pas être passée).
        if (empty($resetconf->nextdatetime) == true || $resetconf->nextdatetime < time()) {
            return false;
        }

        // Création de la tâche.
        $task = new resetTask();
        $task->set_next_run_time($resetconf->nextdatetime);

        // On vérifie si la tâche existe déjà et on supprime si c'est le cas.
        $alreadyqueued = manager::get_queued_adhoc_task_record($task);
        if (false !== $alreadyqueued) {
            manager::delete_adhoc_task($alreadyqueued->id);
        }

        // On tente d'insérer la tâche (normalement il ne devrait plus y avoir de tâche similaire).
        if (manager::queue_adhoc_task($task, $checkforexisting = true)) {
            return manager::record_from_adhoc_task($task);
        }
    }

    /**
     * Génère la tâche pour prévenir de la réinitialisation (la veille de son exécution).
     *
     * @param object $resettask la tâche de réinitialisation.
     *
     * @return bool la tâche a été correctement créée ?
     */
    public static function create_notify_adhoc_task(object $resettask): bool {

        $task = new notifyTask();

        // La tâche doit être exécutée à 8h00 le jour précédent la tâche de réinitialisation.
        $task->set_notify_runtime($resettask->nextruntime);

        // On vérifie si la tâche existe déjà et on supprime si c'est le cas.
        $alreadyqueued = manager::get_queued_adhoc_task_record($task);
        if (false !== $alreadyqueued) {
            manager::delete_adhoc_task($alreadyqueued->id);
        }

        // On tente d'insérer la tâche (normalement il ne devrait plus y avoir de tâche similaire).
        return manager::queue_adhoc_task($task, $checkforexisting = true);
    }

    /**
     * Envoie les mails pour notifier les gestionnaires que la réinitialisation a été programmée.
     *
     * @param reset_enabled $event Évènement diffusé par Moodle.
     * @param object $resettask la tâche de réinitialisation.
     *
     * @return void
     */
    public static function send_reset_enabled_notification(reset_enabled $event, object $resettask): void {
        // 1. On avise l'utilisateur que la tâche de réinitialisation a été programmée et par qui.
        $resetuserinfos = get_string('unknownuser');
        if ($event->userid) {
            $resetuser = user::get_user($event->userid, "firstname,lastname,email");
            if ($resetuser === false) {
                $resetuserinfos = $resetuserinfos . ' ( user id : ' . $event->userid . ' )';
            } else {
                $resetuserinfos = $resetuser->firstname . ' ' . $resetuser->lastname . ' ( ' . $resetuser->email . ' )';
            }
        }

        $htmlinfos = html_writer::tag('p', get_string('reset_was_enabled', 'local_apsolu', $resetuserinfos));

        // 2. On indique à quelle date la tâche sera exécutée.
        $runtime = userdate($resettask->nextruntime, get_string('strftimedatetimewithyear', 'local_apsolu'));
        $taskstatus = rtrim(get_string('reset_is_activated', 'local_apsolu', $runtime), '.') .
            get_string('reset_settings_for_activation', 'local_apsolu', '');

        $htmlstatus = html_writer::tag('p', $taskstatus);

        // 3. On récapitule les variables de configuration actuelles.
        $resetconf = new resetConf();
        $resetconf->load_default_settings();

        // Les variables sont chaînées dans une liste html.
        $htmllist = $resetconf->get_settings_html_list("", ['nextdatetime', 'nextactive'], [], true);

        $htmlsettings = html_writer::tag('p', $htmllist);

        // On concatène les 3 éléments dans une <div>.
        $htmltext = html_writer::tag('div', $htmlinfos . $htmlstatus . $htmlsettings);

        // Envoie du mail à tous les utilisateurs ayant la permission de modifier ces infos + administrateurs.
        mailer::notify_users_by_capability(
            \core\context\system::instance(),
            'local/apsolu:resetsettings',
            get_string('settings_reset_courses_enabled', 'local_apsolu'),
            $htmltext
        );
    }

    /**
     * Écoute l'évènement reset_enabled envoyé après validation du formulaire, génère les tâches et envoie les notifications.
     *
     * @param reset_enabled $event Évènement diffusé par Moodle.
     *
     * @return void
     */
    public static function enabled(reset_enabled $event): void {
        $resettask = self::create_reset_adhoc_task();
        if ($resettask !== false) {
            // Création de la tâche pour prévenir la veille de l'exécution.
            self::create_notify_adhoc_task($resettask);
            // On envoie un mail aux personnes avec la permission de modifier
            // la réinitialisation pour prévenir de la création de la tâche.
            self::send_reset_enabled_notification($event, $resettask);
        } else {
            // Si la création a échoué, on tente de remettre les paramètres de conf en cohérence avec l'état des tâches.
            resetConf::unactivate();
            throw new moodle_exception('reset_activation_failed', 'local_apsolu');
        }
    }

    /**
     * Supprime la tâche de réinitialisation des espaces-cours.
     *
     * @return void
     */
    public static function delete_reset_adhoc_task(): void {
        $task = manager::get_queued_adhoc_task_record(new resetTask());
        if (false !== $task) {
            manager::delete_adhoc_task($task->id);
        }
    }

    /**
     * Supprime la tâche pour prévenir de la réinitialisation (la veille de son exécution).
     *
     * @return void
     */
    public static function delete_notify_adhoc_task(): void {
        $task = manager::get_queued_adhoc_task_record(new notifyTask());
        if (false !== $task) {
            manager::delete_adhoc_task($task->id);
        }
    }

    /**
     * Envoie les mails pour notifier les gestionnaires que la réinitialisation a été déprogrammée.
     *
     * @param reset_disabled $event Évènement diffusé par Moodle.
     *
     * @return void
     */
    public static function send_reset_disabled_notification(reset_disabled $event): void {

        // On avise l'utilisateur que la tâche de réinitialisation a été déprogrammée et par qui (on précise l'année en cours).
        $mailinfos = new stdClass();
        $resetuserinfos = get_string('unknownuser');
        if ($event->userid) {
            $resetuser = user::get_user($event->userid, "firstname,lastname,email");
            if ($resetuser === false) {
                $resetuserinfos = $resetuserinfos . '( user id : ' . $event->userid . ' )';
            } else {
                $resetuserinfos = $resetuser->firstname . ' ' . $resetuser->lastname . ' ( ' . $resetuser->email . ' )';
            }
        }

        $mailinfos->userinfos = $resetuserinfos;
        $mailinfos->year = userdate(time(), get_string('strftimeyear', 'local_apsolu'));

        $htmltext = html_writer::tag('p', get_string('reset_was_disabled', 'local_apsolu', $mailinfos));

        // Envoie du mail à tous les utilisateurs ayant la permission de modifier ces infos + administrateurs.
        mailer::notify_users_by_capability(
            \core\context\system::instance(),
            'local/apsolu:resetsettings',
            get_string('settings_reset_courses_disabled', 'local_apsolu'),
            $htmltext
        );
    }

    /**
     * Écoute l'évènement reset_disabled envoyé après validation du formulaire, supprime les tâches et envoie les notifications.
     *
     * @param reset_disabled $event Évènement diffusé par Moodle.
     *
     * @return void
     */
    public static function disabled(reset_disabled $event): void {
        // Supprime la tâche.
        self::delete_reset_adhoc_task();

        // Elle a bien été supprimée ?
        $resettask = manager::get_queued_adhoc_task_record(new resetTask());
        if ($resettask === false) {
            // On supprime la tâche pour prévenir de l'exécution de la réinitialisation.
            self::delete_notify_adhoc_task();
            // On envoie un mail aux personnes avec la permission de modifier
            // la réinitialisation pour prévenir de la suppression de la tâche.
            self::send_reset_disabled_notification($event);
        } else {
            // Si la suppression a échoué on tente de remettre les paramètres de conf en cohérence avec l'état des tâches.
            resetConf::activate($resettask->nextruntime);
            throw new moodle_exception('reset_desactivation_failed', 'local_apsolu');
        }
    }

    /**
     * Met à jour la date d'exécution de la tâche de réinitialisation des espaces-cours.
     *
     * @return mixed renvoie la tâche modifiée / false si la mise à jour a échoué.
     */
    public static function update_reset_adhoc_task(): mixed {
        $users = get_users_by_capability(\context_system::instance(), 'local/apsolu:resetsettings');
        // On vérifie si la configuration de la tâche est correcte et le statut actif.
        $resetconf = new resetConf();
        if ($resetconf->load_default_settings() == false || $resetconf->nextactive == false) {
            return false;
        }

        // On teste la date d'exécution (ne doit pas être passée).
        if (empty($resetconf->nextdatetime) == true || $resetconf->nextdatetime < time()) {
            return false;
        }

        // Création de la tâche.
        $task = new resetTask();
        $task->set_next_run_time($resetconf->nextdatetime);

        // On modifie la tâche si elle existe et que la date est différente, on créé la tâche si elle n'existe pas.
        manager::reschedule_or_queue_adhoc_task($task);
        return manager::record_from_adhoc_task($task);
    }

    /**
     * Met à jour la date d'exécution de la tâche pour prévenir de la réinitialisation (la veille de son exécution).
     *
     * @param object $resettask la tâche de réinitialisation.
     *
     * @return void
     */
    public static function update_notify_adhoc_task(object $resettask): void {

        $task = new notifyTask();

        // La tâche doit être exécutée à 8h00 le jour précédent la tâche de réinitialisation.
        $task->set_notify_runtime($resettask->nextruntime);

        // On modifie la tâche si elle existe et que le runtime est différent, on la créée sinon.
        manager::reschedule_or_queue_adhoc_task($task);
    }

    /**
     * Envoie les mails pour notifier les gestionnaires que la réinitialisation a été reconfigurée et/ou reprogrammée.
     *
     * @param reset_updated $event Évènement diffusé par Moodle.
     * @param bool $rescheduled si la tâche de réinitialisation a été reprogrammée avec succès.
     *
     * @return void
     */
    public static function send_reset_updated_notification(reset_updated $event, bool $rescheduled): void {
        // 1. On avise l'utilisateur que la configuration a été modifiée et par qui.
        $resetuserinfos = get_string('unknownuser');
        if ($event->userid) {
            $resetuser = user::get_user($event->userid, "firstname,lastname,email");
            if ($resetuser === false) {
                $resetuserinfos = $resetuserinfos . ' ( user id : ' . $event->userid . ' )';
            } else {
                $resetuserinfos = $resetuser->firstname . ' ' . $resetuser->lastname . ' ( ' . $resetuser->email . ' )';
            }
        }

        $htmlinfos = html_writer::tag('p', get_string('reset_was_updated', 'local_apsolu', $resetuserinfos));

        // 2. On indique si la tâche est active ou non avec la date si active.
        $resettask = manager::get_queued_adhoc_task_record(new resetTask());
        if ($resettask !== false) {
            $runtime = userdate($resettask->nextruntime, get_string('strftimedatetimewithyear', 'local_apsolu'));
            $taskstatus = rtrim(get_string('reset_is_activated', 'local_apsolu', $runtime), '.') .
                get_string('reset_settings_for_activation', 'local_apsolu', get_string('reset_settings_bold', 'local_apsolu'));
        } else {
            $taskstatus = get_string('reset_not_activated', 'local_apsolu') .
                ' ' . get_string('reset_settings_currently_set', 'local_apsolu', get_string('reset_settings_bold', 'local_apsolu'));
        }

        $htmlstatus = html_writer::tag('p', $taskstatus);

        // 3. On récapitule les variables de configuration avec en gras les champs modifiés.
        $resetconf = new resetConf();
        $resetconf->load_default_settings();

        // Premier paramètre à indiquer : si la date a été modifiée (et est donc active) on met la date en gras.
        // Si la date n'a pas été modifiée on met le statut de la tâche, et celui-ci en gras s'il a changé.
        $emphasizelist = $event->other;
        if ($rescheduled) {
            $runtime = html_writer::tag('strong', $runtime);
            $first = html_writer::tag('li', get_string('settings_reset_nextruntime', 'local_apsolu') . ' : ' . $runtime);
            $ignore = ['nextdatetime', 'nextactive'];
        } else {
            $first = "";
            if (in_array('disabled', $event->other) || in_array('enabled', $event->other)) {
                $emphasizelist[] = 'nextactive';
            }
            $ignore = ['nextdatetime'];
        }

        // Ajoute tous les paramètres souhaités dans une liste html.
        $htmllist = $resetconf->get_settings_html_list($first, $ignore, $emphasizelist);

        $htmlsettings = html_writer::tag('p', $htmllist);

        // On concatène les 3 éléments dans une <div>.
        $htmltext = html_writer::tag('div', $htmlinfos . $htmlstatus . $htmlsettings);

        // Envoie du mail à tous les utilisateurs ayant la permission de modifier ces infos + administrateurs.
        mailer::notify_users_by_capability(
            \core\context\system::instance(),
            'local/apsolu:resetsettings',
            get_string('settings_reset_courses_updated', 'local_apsolu'),
            $htmltext,
            true
        );

        // Note : si quelqu'un a modifié une variable (en dehors de la date) et changé le statut de la tâche dans le même temps,
        // cela créé 2 événements et envoie 2 mails (l'un pour l'activation / désactivation, l'autre pour la modification).
    }

    /**
     * Écoute l'évènement reset_updated envoyé après validation du formulaire,
     * met à jour la date d'exécution si besoin et envoie les notifications.
     *
     * @param reset_updated $event Évènement diffusé par Moodle.
     *
     * @return void
     */
    public static function updated(reset_updated $event): void {
        // On met à jour la tâche si la date d'exécution de la réinitialisation a été modifiée.
        if (in_array('nextdatetime', $event->other)) {
            $resettask = self::update_reset_adhoc_task();
            if ($resettask !== false) {
                // On met à jour également la tâche qui envoie un mail la veille de l'exécution.
                self::update_notify_adhoc_task($resettask);
            } else {
                throw new moodle_exception('reset_update_failed', 'local_apsolu');
            }
        }

        // On envoie un mail aux personnes avec la permission de modifier la réinitialisation pour prévenir
        // de la modification de la tâche et/ou des paramètres de la réinitialisation.
        self::send_reset_updated_notification($event, isset($resettask) && $resettask !== false);
    }
}
