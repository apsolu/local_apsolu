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

namespace local_apsolu\form\communication;

use context_system;
use local_apsolu\core\messaging;
use local_apsolu\core\grouping;
use local_apsolu\core\course;
use local_apsolu\core\role;
use enrol_select_plugin;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/enrol/select/lib.php');
require_once($CFG->dirroot . '/local/apsolu/forms/notification_form.php');

/**
 * Classe pour le formulaire permettant de communiquer auprès des utilisateurs.
 *
 * @package    local_apsolu
 * @copyright  2024 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notify extends \local_apsolu_notification_form {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    protected function definition() {
        global $DB;

        parent::definition();

        $mform = $this->_form;

        $mform->removeElement('users');
        $mform->removeElement('buttonar');

        $options = ['multiple' => true, 'noselectionstring' => get_string('whatever', 'local_apsolu')];

        // Filtres.
        $mform->addElement('header', 'filters', get_string('filters'));
        $mform->setExpanded('filters', false);

        // Filtre: groupements d'activités.
        $groupings = [];
        foreach (grouping::get_records(null, $sort = 'cc.name') as $grouping) {
            $groupings[$grouping->id] = $grouping->name;
        }

        if (count($groupings) !== 0) {
            $mform->addElement('autocomplete', 'groupings', get_string('groupings', 'local_apsolu'), $groupings, $options);
        }

        // Filtre: activités.
        $sql = "SELECT acc.id, cc.name
                  FROM {apsolu_courses_categories} acc
                  JOIN {course_categories} cc ON cc.id = acc.id
              ORDER BY cc.name";
        $categories = [];
        foreach ($DB->get_records_sql($sql) as $categoryid => $category) {
            $categories[$categoryid] = $category->name;
        }

        if (count($categories) !== 0) {
            $mform->addElement('autocomplete', 'categories', get_string('categories', 'local_apsolu'), $categories, $options);
        }

        // Filtre: cours.
        $sql = "SELECT c.id, c.fullname
                  FROM {course} c
                  JOIN {apsolu_courses} ac ON c.id = ac.id
              ORDER BY c.fullname";
        $courses = [];
        foreach ($DB->get_records_sql($sql) as $course) {
            $courses[$course->id] = $course->fullname;
        }

        if (count($courses) !== 0) {
            $mform->addElement('autocomplete', 'courses', get_string('courses', 'local_apsolu'), $courses, $options);
        }

        // Filtre: enseignants.
        $sql = "SELECT DISTINCT u.id, u.lastname, u.firstname
                  FROM {user} u
                  JOIN {role_assignments} ra ON u.id = ra.userid
                  JOIN {context} c ON c.id = ra.contextid
                 WHERE c.contextlevel = 50
                   AND ra.roleid = 3
              ORDER BY u.lastname, u.firstname";
        $teachers = [];
        foreach ($DB->get_records_sql($sql) as $teacher) {
            $teachers[$teacher->id] = $teacher->lastname.' '.$teacher->firstname;
        }

        if (count($teachers) !== 0) {
            $mform->addElement('autocomplete', 'teachers', get_string('teachers'), $teachers, $options);
        }

        // Filtre: listes d'inscriptions.
        $enrolments = [];
        foreach (enrol_select_plugin::$states as $stateid => $state) {
            $enrolments[$stateid] = get_string(sprintf('%s_list', $state), 'enrol_select');
        }
        $mform->addElement('autocomplete', 'enrollists', get_string('enrolment_list', 'enrol_select'), $enrolments, $options);

        // Filtre: calendriers.
        $calendars = [];
        foreach ($DB->get_records('apsolu_calendars', $conditions = [], $sort = 'name') as $calendar) {
            $calendars[$calendar->id] = $calendar->name;
        }

        if (count($calendars) !== 0) {
            $mform->addElement('autocomplete', 'calendars', get_string('calendars', 'local_apsolu'), $calendars, $options);
        }

        // Filtre: rôles.
        $roles = [];
        foreach (role::get_records() as $role) {
            $roles[$role->id] = $role->name;
        }

        if (count($roles) !== 0) {
            $mform->addElement('autocomplete', 'roles', get_string('roles'), $roles, $options);
        }

        // Filtre: cohortes.
        $cohorts = [];
        foreach ($DB->get_records('cohort', $conditions = [], $sort = 'name') as $cohort) {
            $cohorts[$cohort->id] = $cohort->name;
        }

        if (count($cohorts) !== 0) {
            $mform->addElement('autocomplete', 'cohorts', get_string('cohorts', 'cohort'), $cohorts, $options);
        }

        // Filtre: lieux de pratique.
        $locations = [];
        foreach ($DB->get_records('apsolu_locations', $conditions = [], $sort = 'name') as $location) {
            $locations[$location->id] = $location->name;
        }

        if (count($locations) !== 0) {
            $mform->addElement('autocomplete', 'locations', get_string('locations', 'local_apsolu'), $locations, $options);
        }

        // Option pour enregistrer le modèle.
        list($defaultdata, $recipients, $redirecturl) = $this->_customdata;
        if (empty($defaultdata->template) === true) {
            $mform->addElement('header', 'saving', get_string('template', 'local_apsolu'));
            $mform->setExpanded('saving', true);

            $mform->addElement('selectyesno', 'saveastemplate', get_string('save_as_new_template', 'local_apsolu'));
        } else {
            // On n'empêche la modification du message si un template est défini.
            $mform->hardFreeze('subject');
            $mform->hardFreeze('message');
        }

        $mform->closeHeaderBefore('buttonar');

        // Champs cachés.
        $mform->addElement('hidden', 'template', $defaultdata->template);
        $mform->setType('template', PARAM_INT);

        // Boutons de validation du formulaire.
        $attributes = [];
        if (isset($defaultdata->submitpreview) === false) {
            $attributes = ['disabled' => 'disabled'];
        }

        $buttonarray[] = &$mform->createElement('submit', 'preview', get_string('preview', 'local_apsolu'));
        $buttonarray[] = &$mform->createElement('submit', 'notify', get_string('notify', 'local_apsolu'), $attributes);
        $buttonarray[] = &$mform->createElement('submit', 'exportcsv', get_string('export_to_csv_format', 'local_apsolu'));
        $buttonarray[] = &$mform->createElement('submit', 'exportexcel', get_string('export_to_excel_format', 'local_apsolu'));

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
    }

    /**
     * Retourne une liste d'utilisateurs filtrés selon les conditions passées en paramètre.
     *
     * @param stdClass $data Un objet contenant les valeurs retournées par le formulaire.
     *
     * @return array
     */
    public function get_filtered_users($data): array {
        global $DB;

        // Récupère tous les utilisateurs inscrits à au moins un créneau.
        $sql = "SELECT DISTINCT u.id, u.firstname, u.lastname, u.idnumber, u.email
                  FROM {user} u
                  JOIN {user_enrolments} ue ON u.id = ue.userid
                  JOIN {enrol} e ON e.id = ue.enrolid
                  JOIN {course} c ON c.id = e.courseid
                  JOIN {apsolu_courses} ac ON ac.id = c.id
                  JOIN {course_categories} cc ON cc.id = c.category
                  JOIN {context} ctx ON c.id = ctx.instanceid AND ctx.contextlevel = 50
                  JOIN {role_assignments} ra ON u.id = ra.userid AND ctx.id = ra.contextid
                                            AND ra.component = 'enrol_select' AND ra.itemid = e.id";
        $conditions = [];
        $params = [];

        // Filtre: groupements d'activités.
        if (isset($data->groupings) === true && count($data->groupings) > 0) {
            list($insql, $namedparams) = $DB->get_in_or_equal($data->groupings, SQL_PARAMS_NAMED, 'groupingid_');

            $conditions[] = 'cc.parent '.$insql;
            $params = array_merge($params, $namedparams);
        }

        // Filtre: activités.
        if (isset($data->categories) === true && count($data->categories) > 0) {
            list($insql, $namedparams) = $DB->get_in_or_equal($data->categories, SQL_PARAMS_NAMED, 'catid_');

            $conditions[] = 'cc.id '.$insql;
            $params = array_merge($params, $namedparams);
        }

        // Filtre: cours.
        if (isset($data->courses) === true && count($data->courses) > 0) {
            list($insql, $namedparams) = $DB->get_in_or_equal($data->courses, SQL_PARAMS_NAMED, 'courseid_');

            $conditions[] = 'c.id '.$insql;
            $params = array_merge($params, $namedparams);
        }

        // Filtre: enseignants.
        if (isset($data->teachers) === true && count($data->teachers) > 0) {
            list($insql, $namedparams) = $DB->get_in_or_equal($data->teachers, SQL_PARAMS_NAMED, 'courseid_');

            $conditions[] = 'ctx.id IN (SELECT ra.contextid
                                          FROM {role_assignments} ra
                                         WHERE ra.roleid = 3 -- editingteacher
                                           AND ra.userid '.$insql.')';
            $params = array_merge($params, $namedparams);
        }

        // Filtre: listes d'inscriptions.
        if (isset($data->enrollists) === true && count($data->enrollists) > 0) {
            list($insql, $namedparams) = $DB->get_in_or_equal($data->enrollists, SQL_PARAMS_NAMED, 'enrollistid_');

            $conditions[] = 'ue.status '.$insql;
            $params = array_merge($params, $namedparams);
        }

        // Filtre: calendriers.
        if (isset($data->calendars) === true && count($data->calendars) > 0) {
            list($insql, $namedparams) = $DB->get_in_or_equal($data->calendars, SQL_PARAMS_NAMED, 'calendarid_');

            $conditions[] = 'e.customchar1 '.$insql;
            $params = array_merge($params, $namedparams);
        }

        // Filtre: rôles.
        if (isset($data->roles) === true && count($data->roles) > 0) {
            list($insql, $namedparams) = $DB->get_in_or_equal($data->roles, SQL_PARAMS_NAMED, 'roleid_');

            $conditions[] = 'ra.roleid '.$insql;
            $params = array_merge($params, $namedparams);
        }

        // Filtre: cohortes.
        if (isset($data->cohorts) === true && count($data->cohorts) > 0) {
            list($insql, $namedparams) = $DB->get_in_or_equal($data->cohorts, SQL_PARAMS_NAMED, 'cohortid_');

            $conditions[] = 'u.id IN (SELECT DISTINCT userid FROM {cohort_members} cm WHERE cm.cohortid '.$insql.')';
            $params = array_merge($params, $namedparams);
        }

        // Filtre: lieux de pratique.
        if (isset($data->locations) === true && count($data->locations) > 0) {
            list($insql, $namedparams) = $DB->get_in_or_equal($data->locations, SQL_PARAMS_NAMED, 'locationid_');

            $conditions[] = 'ac.locationid '.$insql;
            $params = array_merge($params, $namedparams);
        }

        if (isset($conditions[0]) === true) {
            $sql .= PHP_EOL.' WHERE '.implode(PHP_EOL.' AND ', $conditions);
        }

        $sql .= " ORDER BY u.lastname, u.firstname";

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Envoie une notification aux utilisateurs.
     *
     * @param array $users      Tableau contenant les identifiants numériques des utilisateurs à notifier.
     * @param int   $templateid Identifiant du modèle de message.
     *
     * @return void
     */
    public function local_apsolu_notify($users = [], $templateid = null) {
        global $DB, $USER;

        $communicationid = str_replace('.', '', uniqid('', $moreentropy = true));

        $context = context_system::instance();

        $data = $this->get_data();

        if (isset($data->carboncopy)) {
            $users[$USER->id] = $USER->id;
        }

        if (isset($data->replyto) === true && $data->replyto === messaging::USE_REPLYTO_ADDRESS) {
            $replyto = $USER->email;
            $replytoname = fullname($USER);
        }

        foreach ($users as $user) {
            $eventdata = new \core\message\message();
            $eventdata->name = 'notification';
            $eventdata->component = 'local_apsolu';
            $eventdata->userfrom = $USER;
            $eventdata->userto = $user;
            if (isset($replyto) === true) {
                $eventdata->replyto = $replyto;
                $eventdata->replytoname = $replytoname;
            }
            $eventdata->subject = $data->subject;
            $eventdata->fullmessage = $data->message['text'];
            $eventdata->fullmessageformat = $data->message['format'];
            $eventdata->fullmessagehtml = $data->message['text'];
            $eventdata->smallmessage = '';
            $eventdata->notification = 1;

            if ($courseid !== null) {
                $eventdata->courseid = $courseid;
            }

            if (isset($user->id)) {
                $userid = $user->id;
            } else {
                $userid = $user;
            }

            if (message_send($eventdata) !== false) {
                // Ajoute une trace dans les logs.
                $other = json_encode(['communicationid' => $communicationid, 'sender' => $USER->id, 'receiver' => $userid,
                    'template' => $templateid]);

                $event = \local_apsolu\event\communication_sent::create([
                    'relateduserid' => $userid,
                    'context' => $context,
                    'other' => $other,
                ]);
                $event->trigger();
            }
        }

        // Gestion de la copie à l'adresse de contact fonctionnel.
        $functionalcontact = get_config('local_apsolu', 'functional_contact');
        if (!empty($functionalcontact) && isset($data->notify_functional_contact)) {
            $messagetext = $data->message['text'];
            $messagehtml = $data->message['text'];

            // Solution de contournement pour pouvoir envoyer un message à une adresse n'appartenant pas à un utilisateur Moodle.
            $admin = get_admin();
            $admin->auth = 'manual'; // Change l'authentification car la fonction email_to_user() ignore les comptes en nologin.
            $admin->email = $functionalcontact;

            if (isset($replyto) === true) {
                email_to_user($admin, $USER, $data->subject, $messagetext, $messagehtml, $attachment = '', $attachname = '',
                    $usetrueaddress = true, $replyto, $replytoname);
            } else {
                email_to_user($admin, $USER, $data->subject, $messagetext, $messagehtml);
            }

            $other = json_encode(['communicationid' => $communicationid, 'sender' => $USER->id, 'receiver' => $admin->email,
                'template' => $templateid]);

            $event = \local_apsolu\event\communication_sent::create([
                'relateduserid' => $admin->id,
                'context' => $context,
                'other' => $other,
                ]);
            $event->trigger();
        }
    }
}
