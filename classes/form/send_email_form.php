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

namespace local_apsolu\form;

use context;
use context_system;
use core_form\dynamic_form;
use html_writer;
use local_apsolu\core\federation\adhesion as Adhesion;
use local_apsolu\external\email;
use moodle_url;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/message/externallib.php');

/**
 * Modal form to send email.
 *
 * @see TODO: refactoriser avec /local/apsolu/forms/notification_form.php
 *
 * @package   local_apsolu
 * @copyright 2023 Université Rennes 2 {@link https://www.univ-rennes2.fr}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_email_form extends dynamic_form {
    /**
     * Definition of the form
     */
    public function definition () {
        global $DB, $USER;

        $mform =& $this->_form;

        $body = $this->optional_param('body', '', PARAM_RAW);
        $contextid = $this->optional_param('contextid', '0', PARAM_INT);
        $subject = $this->optional_param('subject', '', PARAM_TEXT);
        $users = $this->optional_param('users', '', PARAM_SEQUENCE);
        $validation = $this->optional_param('targetvalidation', '0', PARAM_INT);

        // Champ validation.
        if ($validation === (int) Adhesion::MEDICAL_CERTIFICATE_STATUS_VALIDATED) {
            $label = get_string('validate', 'local_apsolu');
        } else if ($validation === (int) Adhesion::MEDICAL_CERTIFICATE_STATUS_PENDING ||
            $validation === (int) Adhesion::MEDICAL_CERTIFICATE_STATUS_EXEMPTED) {
            $label = get_string('refuse', 'local_apsolu');
        }
        $mform->addElement('static', 'validationstatic', get_string('validation', 'local_apsolu'), $label);

        // Champ du destinataire.
        $items = array();
        foreach (explode(',', $users) as $userid) {
            $user = $DB->get_record('user', array('id' => $userid));
            $items[] = $user->email;
        }
        $list = html_writer::alist($items, $attributes = array('class' => 'list-unstyled'), $tag = 'ul');
        $mform->addElement('static', 'user', get_string('to', 'local_apsolu'), $list);

        // Champ du sujet.
        $mform->addElement('text', 'subject', get_string('subject', 'local_apsolu'), array('size' => 50));
        $mform->addRule('subject', get_string('required'), 'required', null, 'client');
        $mform->setType('subject', PARAM_TEXT);
        $mform->setDefault('subject', $subject);

        // Champ carbone copie.
        $mform->addElement('checkbox', 'carboncopy', get_string('carboncopy_to', 'local_apsolu', $USER->email));
        $mform->addHelpButton('carboncopy', 'carboncopy', 'local_apsolu');

        // Champ message.
        if (isset($this->_customdata['editoroptions'])) {
            $editoroptions = $this->_customdata['editoroptions'];
            $mform->addElement('editor', 'message', get_string('message', 'local_apsolu'), null, $editoroptions);
        } else {
            $mform->addElement('editor', 'message', get_string('message', 'local_apsolu'));
        }
        $mform->addRule('message', '', 'required', null, 'server');
        $mform->setType('message', PARAM_RAW);
        $mform->setDefault('message', array('text' => $body, 'format' => FORMAT_HTML));

        // Users.
        $mform->addElement('hidden', 'users', $users);
        $mform->setType('users', PARAM_SEQUENCE);

        // Contextid.
        $mform->addElement('hidden', 'contextid', $contextid);
        $mform->setType('contextid', PARAM_INT);

        // Validation.
        $mform->addElement('hidden', 'validation', $validation);
        $mform->setType('validation', PARAM_INT);

        // Submit buttons (No need to show buttons in modal mform).
        if (isset($this->_customdata['submitlabel'])) {
            $this->add_action_buttons($cancel = true, $submitlabel = $this->_customdata['submitlabel']);
        }
    }

    /**
     * Return form context
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        $contextid = $this->optional_param('contextid', 0, PARAM_INT);

        return context::instance_by_id($contextid, MUST_EXIST);
    }

    /**
     * Check if current user has access to this form, otherwise throw exception
     *
     * @throws moodle_exception
     */
    protected function check_access_for_dynamic_submission(): void {
        $capabilities = array('moodle/site:sendmessage', 'moodle/course:bulkmessaging');
        $context = $this->get_context_for_dynamic_submission();

        require_all_capabilities($capabilities, $context);
    }

    /**
     * Process the form submission, used if form was submitted via AJAX
     *
     * @return array
     */
    public function process_dynamic_submission() {
        $data = $this->get_data();

        $receivers = explode(',', $data->users);

        // Validation du certificat.
        $validation = $data->validation;
        foreach ($receivers as $userid) {
            if ($validation === (int) Adhesion::MEDICAL_CERTIFICATE_STATUS_VALIDATED) {
                foreach (Adhesion::get_records(array('userid' => $userid)) as $adhesion) {
                    $adhesion->medicalcertificatestatus = $validation;
                    $adhesion->save($mdata = null, $mform =null, $check = false);
                }
            } else if ($validation === (int) Adhesion::MEDICAL_CERTIFICATE_STATUS_PENDING) {
                foreach (Adhesion::get_records(array('userid' => $userid)) as $adhesion) {
                    if ($adhesion->have_to_upload_medical_certificate() === true) {
                        $adhesion->medicalcertificatestatus = $adhesion::MEDICAL_CERTIFICATE_STATUS_PENDING;
                    } else {
                        $adhesion->medicalcertificatestatus = $adhesion::MEDICAL_CERTIFICATE_STATUS_EXEMPTED;
                    }
                    $adhesion->federationnumberrequestdate = null;

                    $adhesion->save($mdata = null, $mform =null, $check = false);
                }
            } else if ($validation === (int) Adhesion::MEDICAL_CERTIFICATE_STATUS_EXEMPTED) {
                // On annule juste la date de demande de validation.
                foreach (Adhesion::get_records(['userid' => $userid]) as $adhesion) {
                    $adhesion->federationnumberrequestdate = null;
                    $adhesion->save($mdata = null, $mform = null, $check = false);
                }
            }
        }

        // Envoi de la notification.
        $message = array();
        $message['subject'] = $data->subject;
        $message['carboncopy'] = isset($data->carboncopy);
        $message['body'] = $data->message['text'];
        $message['receivers'] = $receivers;

        $messages = array($message);

        return email::send_instant_emails($messages);
    }

    /**
     * Load in existing data as form defaults (not applicable)
     */
    public function set_data_for_dynamic_submission(): void {
        return;
    }

    /**
     * Returns url to set in $PAGE->set_url() when form is being rendered or submitted via AJAX
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        $context = $this->get_context_for_dynamic_submission();

        if ($context->contextlevel == CONTEXT_SYSTEM) {
            return new moodle_url('/admin/user/user_bulk_email.php');
        }

        if ($context->contextlevel == CONTEXT_COURSE) {
            return new moodle_url('/user/index.php', array('id' => $context->instanceid));
        }
    }
}
