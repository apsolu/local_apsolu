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

namespace local_apsolu\task;

use Throwable;
use stdClass;
use local_apsolu\core\reset;
use local_apsolu\event\reset_completed as event_completed;
use local_apsolu\core\federation\course as ffsucourse;
use core\context\course as context_course;
use core\context\system as context_system;
use local_apsolu\core\messaging as mailer;
use core\output\html_writer;

/**
 * Classe représentant la tâche permettant d'exécuter la procédure de réinitalisation annuelle de la plateforme.
 *
 * Elle utilise les variables enregistrées dans la table de configuration du plugin local_apsolu.
 *
 * @package   local_apsolu
 * @copyright 2026 Université Rennes 2
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reset_courses extends \core\task\adhoc_task {
    /**
     * Retourne le nom de la tâche.
     *
     * @return string
     */
    public function get_name(): string {
        // Shown in admin screens.
        return get_string('reset_courses', 'local_apsolu');
    }

     /**
      * Execute la tâche.
      *
      * @return void
      */
    public function execute(): void {
        global $CFG, $DB;

        // Partie Moodle.
        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/lib/gradelib.php');
        require_once($CFG->dirroot . '/cohort/lib.php');
        require_once($CFG->dirroot . '/enrol/meta/lib.php');
        require_once($CFG->dirroot . '/enrol/select/lib.php');

        // Récupère la configuration.
        $reset = $this->get_reset_config();

        $noemailever = $CFG->noemailever; // Stocke la valeur qui sert à empêcher l'envoi de mails pour un rollback.

        if ($reset !== false) {
            try {
                // Construit la liste des purges à réaliser en fonction de la configuration.
                $purges = [];

                // Supprimer les infos des profils utilisateurs ? inutile si on purge les comptes utilisateurs.
                // Concerne tous les utilisateurs (étudiants, gestionnaires, admin...).
                // Ne supprime pas les champs qui stockent les ids des carte étudiants (ex. Korrigo à Rennes), adhésion ffsu.
                if ($reset->allusers === false && $reset->userprofiles === true) {
                    $purges['vide les informations utilisateurs (ufr, sexe, date de naissance, carte payée, etc).'] =
                        'DELETE FROM {user_info_data} ' .
                            'WHERE fieldid NOT IN (' .
                            'SELECT id FROM {user_info_field} WHERE shortname IN ' .
                            '("apsoluidcardnumber", "apsoluidcardnumberexternal", "apsolufederationnumber"))';
                }

                // Masquer la liste des créneaux dans l'interface ?
                if ($reset->coursesvisibility === true) {
                    $purges['masque les créneaux horaires'] = 'UPDATE {course} ' .
                                                                'SET visible = 0, visibleold = 0 ' .
                                                                'WHERE id IN (SELECT id FROM {apsolu_courses})';
                }

                // Supprimer les relevés de présences ?
                if ($reset->userattendances === true) {
                    $purges['vide les présences.'] = 'TRUNCATE {apsolu_attendance_presences}';

                    // Supprimer les QR codes.
                    // TODO : passer la réinitialisation des QRcodes pour les sessions passées dans une tâche scheduled.
                    $purges['vide les qrcodes.'] = 'TRUNCATE {apsolu_attendance_qrcodes}';
                }

                // Supprimer les sessions définies pour les cours ?
                if ($reset->sessions === true) {
                    $purges['vide les sessions.'] = 'TRUNCATE {apsolu_attendance_sessions}';
                }

                // Supprimer les infos relatives aux paiements des utilisateurs ?
                if ($reset->userpayments === true) {
                    $purges['supprime toutes les écritures de paiements.'] = 'TRUNCATE {apsolu_payments}';
                    $purges['supprime toutes les sous-écritures de paiements.'] = 'TRUNCATE {apsolu_payments_items}';
                    $purges['supprime toutes les écritures de transactions de paiement.'] =
                        'TRUNCATE {apsolu_payments_transactions}';
                    $purges['supprime toutes les adresses associées aux paiements.'] = 'TRUNCATE {apsolu_payments_addresses}';
                    $purges['ferme les paiements.'] = 'UPDATE {config_plugins} ' .
                                                        'SET value="' . time() . '" ' .
                                                        'WHERE plugin="local_apsolu" ' .
                                                        'AND name="payments_enddate"';
                    if ($DB->get_manager()->table_exists('apsolu_atout_payments')) {
                        $purges['supprime toutes les écritures de paiements Atouts Normandie'] =
                        'TRUNCATE {apsolu_atouts_payments}';
                    }
                }

                // Supprimer les infos sur l'acceptation des recommandations médicales ?
                if ($reset->userpolicies === true) {
                    $purges['réinitialise le témoin d\'acceptation des recommandations médicales.'] =
                        'UPDATE {user} SET policyagreed=0';
                }

                mtrace('Lancement de la tâche de réinitialisation des espaces cours (rentrée ' . date('Y', time()) . ')');

                // Exécute les purges déjà listées.
                foreach ($purges as $title => $sql) {
                    $this->purge($title, $sql);
                }

                // Réinitialiser la FFSU ?
                if ($reset->ffsu) {
                    $federationcourse = new ffsucourse();
                    $federationcourseid = $federationcourse->get_courseid();
                    // Pour les instances qui en disposent uniquement.
                    if (empty($federationcourseid) === false) {
                        mtrace('Réinitialise le cours FFSU #' . $federationcourseid);

                        // Supprime les adhésions.
                        $this->purge('vide les adhésions FFSU.', 'TRUNCATE {apsolu_federation_adhesions}');

                        // Supprime les autorisations parentales.
                        $context = context_course::instance($federationcourseid, MUST_EXIST);
                        $component = 'local_apsolu';
                        $filearea = 'parentalauthorization';

                        $fs = get_file_storage();
                        $areafiles = $fs->get_area_files($context->id, $component, $filearea);
                        $fs->delete_area_files($context->id, $component, $filearea);
                        mtrace(str_repeat(' ', 6) . count($areafiles) . ' autorisations parentales supprimées');

                        // Supprime les certificats déposés.
                        $context = context_course::instance($federationcourseid, MUST_EXIST);
                        $component = 'local_apsolu';
                        $filearea = 'medicalcertificate';

                        $fs = get_file_storage();
                        $areafiles = $fs->get_area_files($context->id, $component, $filearea);
                        $fs->delete_area_files($context->id, $component, $filearea);
                        mtrace(str_repeat(' ', 6) . count($areafiles) . ' certificats médicaux supprimés');

                        // Vide les méthodes d'inscription enrol_select du cours de FFSU.
                        $enrolselectplugin = new \enrol_select_plugin();
                        $instances = $DB->get_records('enrol', ['enrol' => 'select', 'courseid' => $federationcourseid]);
                        $i = 0;
                        $j = 0;
                        foreach ($instances as $instance) {
                            $users = $DB->get_records('user_enrolments', ['enrolid' => $instance->id]);
                            foreach ($users as $user) {
                                $enrolselectplugin->unenrol_user($instance, $user->userid);
                                $j++;
                            }
                            $i++;
                        }
                        mtrace(str_repeat(' ', 6 . $j) . ' utilisateurs désinscrits sur ' . $i . ' instances . ');

                        // Ferme les inscriptions de la FFSU.
                        $sql = "UPDATE {enrol} SET enrolenddate = " . time() .
                            " WHERE enrol = 'select' AND courseid = " . $federationcourseid;

                        $this->purge('ferme les inscriptions à la FFSU', $sql);
                    }
                }

                // Vider les cohortes de leurs membres ?
                if ($reset->cohortmembers === true) {
                    echo ' - vide les cohortes . ' . PHP_EOL;
                    $cohorts = $DB->get_records('cohort');
                    foreach ($cohorts as $cohort) {
                        $members = $DB->get_records('cohort_members', ['cohortid' => $cohort->id]);
                        foreach ($members as $member) {
                            cohort_remove_member($cohort->id, $member->userid);
                        }
                    }
                    echo 'OK ...' . PHP_EOL;
                }

                // Supprimer toutes les méthodes "select" (et leurs utilisateurs) ?
                if ($reset->selectenrolments === true) {
                    mtrace(' - supprime toutes les méthodes d\'inscription de type "select"');
                    $enrolselectplugin = new \enrol_select_plugin();
                    $instances = $DB->get_records('enrol', ['enrol' => 'select']);
                    $i = 0;
                    foreach ($instances as $instance) {
                        $enrolselectplugin->delete_instance($instance);
                        $i++;
                    }
                    mtrace(str_repeat(' ', 6) . $i . ' instances supprimées . ');
                } else if ($reset->userselectenrolments === true) {
                    // Désinscrire tous les étudiants inscrits avec la méthode "select" ?
                    echo ' - désinscrit tous les étudiants inscrits avec la méthode "select".' . PHP_EOL;
                    $enrolselectplugin = new \enrol_select_plugin();
                    $instances = $DB->get_records('enrol', ['enrol' => 'select']);
                    $i = 0;
                    $j = 0;
                    foreach ($instances as $instance) {
                        $users = $DB->get_records('user_enrolments', ['enrolid' => $instance->id]);
                        foreach ($users as $user) {
                            $enrolselectplugin->unenrol_user($instance, $user->userid);
                            $j++;
                        }
                        $i++;
                    }
                    echo str_repeat(' ', 6) . $j . ' utilisateurs désinscrits sur ' . $i . ' instances . ' . PHP_EOL;
                    echo 'OK ...' . PHP_EOL;
                }

                // Supprimer toutes les méthodes de meta-cours (et leurs utilisateurs) ?
                if ($reset->metaenrolments === true) {
                    mtrace(' - supprime toutes les méthodes d\'inscription de type "meta-cours"');
                    $enrolmetaplugin = new \enrol_meta_plugin();
                    $instances = $DB->get_records('enrol', ['enrol' => 'meta']);
                    $i = 0;
                    foreach ($instances as $instance) {
                        $enrolmetaplugin->delete_instance($instance);
                        $i++;
                    }
                    mtrace(str_repeat(' ', 6) . $i . ' instances supprimées . ');
                }

                // Purge des comptes utilisateurs.
                $this->purge_users($reset);

                // Supprimer les notes ?
                // effectue un nettoyage des infos liées aux participations des étudiants dans les activités du cours,
                // évaluations, état des devoirs rendus, inscriptions aux forums, roles particuliers etc..
                if ($reset->usergrades === true) {
                    // Le module checklist semble envoyer des notifications pendant la réinitialisation.
                    $CFG->noemailever = 1;

                    $data = new stdClass();
                    $data->reset_gradebook_item = 0;
                    $data->reset_gradebook_grades = 1;
                    $data->reset_checklist_progress = 0;

                    $sql = "SELECT c.* FROM {course} c JOIN {apsolu_courses} ac ON c.id = ac.id";
                    $courses = $DB->get_records_sql($sql);
                    foreach ($courses as $course) {
                        $gradeitems = \grade_item::fetch_all(['courseid' => $course->id]);

                        if ($gradeitems === false) {
                            continue;
                        }

                        $data->id = $course->id;
                        $data->courseid = $course->id;

                        $result = reset_course_userdata($data);
                        foreach ($result as $key => $value) {
                            if ($value['error'] === false) {
                                $success = get_string('ok');
                            } else {
                                $success = $value['error'];
                            }

                            mtrace(sprintf("%s - %s : %s - %s", $value['component'], $value['item'], $success, $course->fullname));
                        }
                    }
                }

                // On repositionne la valeur de noemailever pour l'envoi des mails de notification du succès de la tâche.
                $CFG->noemailever = $noemailever;

                // Fin de tâche avec succès.
                $this->completed($reset);
            } catch (Throwable $e) {
                mtrace($e->getMessage());

                // On repositionne la valeur de noemailever pour l'envoi des mails de notification de l'échec de la tâche.
                $CFG->noemailever = $noemailever;

                $errormsg = get_class($e) . ': <strong>' . $e->getMessage() . '</strong>. ' .
                    'In <strong>' . $e->getFile() . ' line ' . $e->getLine() . '</strong>';

                $this->notify_failure($errormsg, $reset);
                throw $e;
            }
        }
    }

    /**
     * Applique les instructions de purge en BD.
     *
     * @param [string] $title
     * @param [string] $sql
     * @return void
     */
    protected function purge($title, $sql) {
        global $DB;

        echo ' - ' . $title . PHP_EOL;

        if ($DB->execute($sql) === true) {
            echo 'OK ...' . PHP_EOL;
        } else {
            echo 'Error ...' . PHP_EOL;
        }
    }

    /**
     * Purge des comptes utilisateurs, selon l'option choisie (soit tous les utilisateurs, soit comptes manuels et/ou obsolètes)
     *  à l'exception des gestionnaires, enseignants, créateurs de cours et administrateurs + utilisateurs de webservices.
     *
     * @param \local_apsolu\core\reset $reset
     * @return void
     */
    public function purge_users(reset $reset) {
        global $DB;

        // Sélection des utilisateurs à supprimer.
        $userselection = [];

        // Projection des ids utilisateurs créateur du cours, enseignants et gestionnaires qu'il ne faut pas récupérer.
        $superuserids = '(SELECT userid FROM {role_assignments} WHERE roleid IN (1, 2, 3))';

        // Supprimer tous les comptes utilisateurs ?
        if ($reset->allusers === true) {
            $userselection['de tous les utilisateurs'] =
                'SELECT * FROM {user} WHERE id NOT IN ' . $superuserids . ' AND id > 2 AND deleted = 0';
        } else {
            // Supprimer les comptes utilisateurs obsolètes (aucune connexion depuis + d'un an) ?
            if ($reset->oldusers === true) {
                $userselection['des utilisateurs ne s\'étant pas connectés depuis plus d\'un an'] =
                    'SELECT * FROM {user} WHERE (lastaccess + (365*24*60*60)) < UNIX_TIMESTAMP() AND lastaccess > 0 ' .
                    'AND deleted = 0 AND id > 2 AND id NOT IN ' . $superuserids;
            }

            // Supprimer les comptes utilisateurs locaux (authentification hors CAS, Shibboleth...) ?
            if ($reset->manualusers === true) {
                $userselection['des utilisateurs sans compte université'] =
                    'SELECT * FROM {user} WHERE id NOT IN ' . $superuserids . ' AND id > 2 AND deleted = 0 ' .
                    'AND auth NOT IN ("cas", "shibboleth", "saml2")';
            }
        }

        // Cherche les administrateurs pour empêcher la suppresion de leurs comptes.
        $admins = get_admins();

        // Cherche les utilisateurs associés à des webservices pour empêcher la suppresion de leurs comptes.
        $sql = "SELECT userid FROM {external_services_users}" .
                " WHERE externalserviceid IN (SELECT id FROM {external_services} WHERE component IS NULL)";
        $wsusers = $DB->get_records_sql($sql);

        // Parcourt les différentes sélections.
        foreach ($userselection as $title => $sql) {
            echo ' - supprime les comptes ' . $title .
            ' (sauf les utilisateurs ayant un rôle gestionnaire, créateur de cours ou enseignant).' . PHP_EOL;

            // On joue les requêtes de sélection des comptes utilisateurs puis on parcourt la liste des users.
            $users = $DB->get_records_sql($sql);
            $i = 0;
            foreach ($users as $user) {
                if (isset($admins[$user->id]) === true) {
                    // On conserve les administrateurs.
                    mtrace('   - conserve l\'utilisateur admin: ' . $user->username . ' (#' . $user->id . ')');
                    continue;
                }

                if (isset($wsusers[$user->id]) === true) {
                    // On conserve les utilisateurs pour les webservices.
                    mtrace('   - conserve l\'utilisateur webservice: ' . $user->username . ' (#' . $user->id . ')');
                    continue;
                }

                // On supprime les données associées aux utilisateurs (comptes utilisateurs, données de profil..)
                delete_user($user);
                $i++;
            }
            echo str_repeat(' ', 6) . $i . ' comptes supprimés . ' . PHP_EOL;
        }
    }

    /**
     * Envoie un mail aux administrateurs, gestionaires de la plateforme pour les notifier du succès de la tâche.
     *
     * @param reset $reset la configuration utilisée
     * @return void
     */
    protected function notify_success(reset $reset) {
        // Corps de l'email : on notifie l'utilisateur que la tâche a échoué, quand elle a été lancée et quelle erreur l'a stoppée.
        $rundatetime = userdate($this->get_timestarted(), get_string('strftimedatetimewithyear', 'local_apsolu'));

        $htmlinfos = html_writer::tag('p', get_string('reset_task_success_details', 'local_apsolu', $rundatetime));

        // Rappel des paramètres utilisé pour la tâche.
        $htmllist = $reset->get_settings_html_list("", ['nextdatetime', 'nextactive'], [], true);

        $htmltext = $htmlinfos . $htmllist;

        // Envoie du mail.
        mailer::notify_users_by_capability(
            context_system::instance(),
            'local/apsolu:resetsettings',
            get_string('reset_task_success', 'local_apsolu'),
            $htmltext
        );
    }

    /**
     * Envoie un mail aux administrateurs, gestionaires de la plateforme pour les notifier de l'échec ou abandon de la tâche.
     *
     * @param string $msg la raison de l'abandon de la tâche.
     * @param reset $reset la configuration utilisée
     * @return void
     */
    protected function notify_failure(string $msg, reset $reset) {

        // Corps de l'email : on notifie l'utilisateur que la tâche a échoué, quand elle a été lancée et quelle erreur l'a stoppée.
        $rundatetime = userdate($this->get_timestarted(), get_string('strftimedatetimewithyear', 'local_apsolu'));

        $htmlinfos = html_writer::tag('p', get_string('reset_task_failed_details', 'local_apsolu', $rundatetime));

        $htmlerrormsg = html_writer::tag('p', $msg);

        // Rappel des paramètres utilisé pour la tâche.
        $htmllist = $reset->get_settings_html_list("", ['nextdatetime', 'nextactive']);

        $htmltext = $htmlinfos . $htmlerrormsg . $htmllist;

        // Envoie du mail.
        mailer::notify_users_by_capability(
            context_system::instance(),
            'local/apsolu:resetsettings',
            get_string('reset_task_failed', 'local_apsolu'),
            $htmltext
        );
    }

    /**
     * Récupère les valeurs de configuration et teste si elle est valide, sinon procède à l'abandon de la tâche.
     * vérifie si tous les champs sont renseignés et si la réinitialisation est activée.
     * @return reset|bool $reset la configuration utilisée, false si elle est invalide
     */
    protected function get_reset_config() {

        // Récupère la configuration.
        $reset = new reset();

        if ($reset->load_default_settings() == false) {
            $errormsg = 'La tâche de réinitialisation a été abandonnée car la configuration n\'est pas valide.';
            mtrace($errormsg);
            $this->notify_failure($errormsg, $reset);
            return false;
        }

        // On vérifie les paramètres pour l'exécution (statut et date programmée).
        if ($reset->nextactive == false || $reset->nextdatetime == null) {
            $errormsg = 'La tâche de réinitialisation a été abandonnée car
                les valeurs dans la table de configuration indiquent que celle-ci n\'est pas programmée.';
            mtrace($errormsg);
            $this->notify_failure($errormsg, $reset);
            return false;
        }

        return $reset;
    }

     /**
      * Effectue les actions nécessaires pour clôre la tâche :
      * Envoie d'un email, positionnement des valeurs de nextactive et nextdatetime à 0 et création d'un log.
      *
      * @param \local_apsolu\core\reset $reset
      * @return void
      */
    public function completed(reset $reset) {
        mtrace('Tâche de réinitialisation terminée sans incident.');
        // Envoyer un mail pour aviser les administrateurs et gestionnaires de la réinitialisation de l'espace-cours.
        $this->notify_success($reset);

        reset::set_config('nextactive', 0);
        reset::set_config('nextdatetime', 0);

        $settings = (array) $reset;

        $other = [];

        foreach ($settings as $setting => $value) {
            if ($setting !== 'nextactive' && $setting !== 'nextdatetime' && $value) {
                $other[] = $setting;
            }
        }

        $event = event_completed::create([
                'context' => context_system::instance(),
                'other' => $other,
                ]);
        $event->trigger();

        reset::set_config('lastruntime', $event->timecreated);
    }
}
