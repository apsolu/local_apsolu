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

use local_apsolu\core\messaging;

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

        [$defaultdata, $recipients, $redirecturl] = $this->_customdata;

        // Expéditeur.
        $noreplyuser = core_user::get_noreply_user();
        $label = get_string('sender', 'local_apsolu');
        $value = sprintf('%s <%s>', fullname($noreplyuser), $noreplyuser->email);
        $mform->addElement('static', 'sender', $label, s($value));

        // Répondre à.
        $replytoaddresspreference = get_config('local_apsolu', 'replytoaddresspreference');
        if ($replytoaddresspreference === messaging::FORCE_REPLYTO_ADDRESS) {
            // On force à utiliser l'adresse de l'utilisateur en "réponse à".
            $label = get_string('replyto', 'local_apsolu');
            $mform->addElement('static', 'replytolabel', $label, $USER->email);
            $mform->addElement('hidden', 'replyto', messaging::USE_REPLYTO_ADDRESS);
            $mform->setType('replyto', PARAM_TEXT);
        } else if ($replytoaddresspreference === messaging::ALLOW_REPLYTO_ADDRESS_CHOICE) {
            // On propose le choix d'utiliser en "réponse à".
            $options = messaging::get_default_replyto_options();
            $options[messaging::USE_REPLYTO_ADDRESS] = $USER->email;

            $label = get_string('replyto', 'local_apsolu');
            $mform->addElement('select', 'replyto', $label, $options);
            $mform->addRule('replyto', get_string('required'), 'required', null, 'client');
            if (isset($defaultdata->replyto) === false) {
                $mform->setDefault('replyto', get_config('local_apsolu', 'defaultreplytoaddresspreference'));
            }
            $mform->setType('replyto', PARAM_TEXT);
        }

        // Destinataires.
        $users = [];
        foreach ($recipients as $user) {
            if (!empty($user->numberid)) {
                $numberid = ' (' . $user->numberid . ')';
            } else {
                $numberid = '';
            }

            $users[] = sprintf('<li>%s %s%s</li>', $user->firstname, $user->lastname, $numberid);

            $mform->addElement('hidden', 'users[' . $user->id . ']', $user->id);
            $mform->setType('users[' . $user->id . ']', PARAM_INT);
        }

        $label = get_string('recipients', 'local_apsolu');
        $static = sprintf('<ul class="list list-unstyled">%s</ul>', implode('', $users));
        $mform->addElement('static', 'users', $label, $static);

        // Sujet.
        $label = get_string('subject', 'local_apsolu');
        $mform->addElement('text', 'subject', $label, ['size' => 250]);
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', get_string('required'), 'required', null, 'client');

        // Message.
        $label = get_string('message', 'local_apsolu');
        $mform->addElement('editor', 'message', $label, ['rows' => '15', 'cols' => '50']);
        $mform->setType('message', PARAM_RAW);
        $mform->addRule('message', get_string('required'), 'required', null, 'client');

        // Carbone copie.
        $label = get_string('carboncopy', 'local_apsolu');
        $mform->addElement('checkbox', 'carboncopy', $label);
        $mform->addHelpButton('carboncopy', 'carboncopy', 'local_apsolu', $USER->email);
        $mform->setType('carboncopy', PARAM_INT);

        // Notifier le contact fonctionnel.
        $functionalcontact = get_config('local_apsolu', 'functional_contact');
        if (empty($functionalcontact) === false) {
            $label = get_string('notify_functional_contact', 'local_apsolu', $functionalcontact);
            $checkbox = $mform->addElement('checkbox', 'notify_functional_contact', $label);
            $mform->setType('notify_functional_contact', PARAM_INT);

            $functionalcontactdefault = get_config('local_apsolu', 'functional_contact_preference');
            if (in_array($functionalcontactdefault, [messaging::DEFAULT_YES, messaging::DEFAULT_ALWAYS], $strict = true) === true) {
                $defaultdata->notify_functional_contact = 1;
            }
            if ($functionalcontactdefault === messaging::DEFAULT_ALWAYS) {
                $checkbox->freeze();
            }
        }

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('notify', 'local_apsolu'));

        $attributes = new stdClass();
        $attributes->href = (string) $redirecturl;
        $attributes->class = 'btn btn-default btn-secondary';
        $buttonarray[] = &$mform->createElement('static', '', '', get_string('cancel_link', 'local_apsolu', $attributes));

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);

        // Définit les valeurs par défaut.
        $this->set_data($defaultdata);
    }

    /**
     * Envoie une notification aux utilisateurs.
     *
     * @param array    $users    Tableau contenant les identifiants numériques des utilisateurs à notifier.
     * @param int|null $courseid Identifiant numérique du cours d'où est envoyé la notification. Si la valeur est null, ce sera un
     *  message envoyé dans un contexte système.
     *
     * @return void
     */
    public function local_apsolu_notify($users = [], $courseid = null) {
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
                $event = \local_apsolu\event\notification_sent::create([
                    'relateduserid' => $userid,
                    'context' => $context,
                    'other' => ['sender' => $USER->id, 'receiver' => $userid, 'subject' => $eventdata->subject],
                ]);
                $event->trigger();
            }
        }

        // Gestion de la copie à l'adresse de contact fonctionnel.
        $functionalcontact = get_config('local_apsolu', 'functional_contact');
        if (!empty($functionalcontact) && isset($data->notify_functional_contact)) {
            $messagetext = $data->message['text'];
            $messagehtml = $data->message['text'];

            // Solution de contournement pour pouvoir envoyer un message à une adresse mail n'appartenant pas
            // à un utilisateur Moodle.
            $admin = get_admin();
            $admin->auth = 'manual'; // Force l'auth. en manual, car email_to_user() ignore le traitement des comptes en nologin.
            $admin->email = $functionalcontact;

            if (isset($replyto) === true) {
                email_to_user(
                    $admin,
                    $USER,
                    $data->subject,
                    $messagetext,
                    $messagehtml,
                    $attachment = '',
                    $attachname = '',
                    $usetrueaddress = true,
                    $replyto,
                    $replytoname
                );
            } else {
                email_to_user($admin, $USER, $data->subject, $messagetext, $messagehtml);
            }

            $event = \local_apsolu\event\notification_sent::create([
                'relateduserid' => $admin->id,
                'context' => $context,
                'other' => ['sender' => $USER->id, 'receiver' => $admin->email, 'subject' => $eventdata->subject],
                ]);
            $event->trigger();
        }
    }
}
