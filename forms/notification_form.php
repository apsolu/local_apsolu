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

/**
 * Classe pour le formulaire permettant de notifier les utilisateurs.
 *
 * @package    local_apsolu
 * @copyright  2021 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

/**
 * Classe pour le formulaire permettant de notifier les utilisateurs.
 *
 * @package    local_apsolu
 * @copyright  2021 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_notification_form extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    protected function definition() {
        global $DB, $CFG, $USER;

        $mform = $this->_form;

        list($defaultdata, $recipients, $redirecturl) = $this->_customdata;

        // Destinataires.
        $users = array();
        foreach ($recipients as $user) {
            if (!empty($user->numberid)) {
                $numberid = ' ('.$user->numberid.')';
            } else {
                $numberid = '';
            }

            $users[] = sprintf('<li>%s %s%s</li>', $user->firstname, $user->lastname, $numberid);

            $mform->addElement('hidden', 'users['.$user->id.']', $user->id);
            $mform->setType('users['.$user->id.']', PARAM_INT);
        }

        $label = get_string('recipients', 'local_apsolu');
        $static = sprintf('<ul class="list list-unstyled">%s</ul>', implode('', $users));
        $mform->addElement('static', 'users', $label, $static);

        // Sujet.
        $label = get_string('subject', 'local_apsolu');
        $mform->addElement('text', 'subject', $label, array('size' => 250));
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', get_string('required'), 'required', null, 'client');

        // Message.
        $label = get_string('message', 'local_apsolu');
        $mform->addElement('editor', 'message', $label, array('rows' => '15', 'cols' => '50'));
        $mform->setType('message', PARAM_RAW);
        $mform->addRule('message', get_string('required'), 'required', null, 'client');

        // Carbone copie.
        $label = get_string('carboncopy', 'local_apsolu');
        $mform->addElement('checkbox', 'carboncopy', $label);
        $mform->addHelpButton('carboncopy', 'carboncopy', 'local_apsolu', $USER->email);
        $mform->setType('carboncopy', PARAM_INT);

        // Notifier le contact fonctionnel.
        $functional_contact = get_config('local_apsolu', 'functional_contact');
        if (empty($functional_contact) === false) {
            $label = get_string('notify_functional_contact', 'local_apsolu');
            $mform->addElement('checkbox', 'notify_functional_contact', $label);
            $mform->setType('notify_functional_contact', PARAM_INT);
        }

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('notify', 'local_apsolu'));

        $attributes = new stdClass();
        $attributes->href = (string) $redirecturl;
        $attributes->class = 'btn btn-default btn-secondary';
        $buttonarray[] = &$mform->createElement('static', '', '', get_string('cancel_link', 'local_apsolu', $attributes));

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

        // Définit les valeurs par défaut.
        $this->set_data($defaultdata);
    }

    public function local_apsolu_notify($users = array(), $courseid = null) {
        global $DB, $USER;

        if ($courseid === null) {
            $context = context_system::instance();
        } else {
            $context = context_course::instance($courseid);
        }

        $data = $this->get_data();

        if (isset($data->carboncopy)) {
            $users[$USER->id] = $USER->id;
        }

        foreach ($users as $user) {
            $eventdata = new \core\message\message();
            $eventdata->name = 'notification';
            $eventdata->component = 'local_apsolu';
            $eventdata->userfrom = $USER;
            $eventdata->userto = $user;
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
                $event = \local_apsolu\event\notification_sent::create(array(
                    'relateduserid' => $userid,
                    'context' => $context,
                    'other' => json_encode(array('sender' => $USER->id, 'receiver' => $userid, 'subject' => $eventdata->subject)),
                ));
                $event->trigger();
            }
        }

        // Gestion de la copie à l'adresse de contact fonctionnel.
        $functional_contact = get_config('local_apsolu', 'functional_contact');
        if (!empty($functional_contact) && isset($data->notify_functional_contact)) {
            $messagetext = $data->message['text'];
            $messagehtml = $data->message['text'];

            // Solution de contournement pour pouvoir envoyer un message à une adresse mail n'appartenant pas à un utilisateur Moodle.
            $admin = get_admin();
            $admin->email = $functional_contact;
            email_to_user($admin, $USER, $data->subject, $messagetext, $messagehtml);

            $event = \local_apsolu\event\notification_sent::create(array(
                'relateduserid' => $admin->id,
                'context' => $context,
                'other' => json_encode(array('sender' => $USER->id, 'receiver' => $admin->email, 'subject' => $eventdata->subject)),
                ));
            $event->trigger();
        }
    }
}
