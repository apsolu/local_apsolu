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
 * Classe pour le formulaire permettant d'éditer des sessions.
 *
 * @package    local_apsolu
 * @copyright  2017 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_attendance_sessions_edit_form extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    protected function definition() {
        global $CFG;

        $mform = $this->_form;
        $session = $this->_customdata['session'];
        $locations = $this->_customdata['locations'];

        // Name field.
        $mform->addElement('text', 'name', get_string('name'), ['size' => '48']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');

        // Sessiontime field.
        $mform->addElement('date_time_selector', 'sessiontime', get_string('date'));
        $mform->setType('sessiontime', PARAM_INT);
        $mform->addRule('sessiontime', get_string('required'), 'required', null, 'client');

        // Location field.
        $mform->addElement('select', 'locationid', get_string('location', 'local_apsolu'), $locations);
        $mform->setType('locationid', PARAM_INT);
        $mform->addRule('locationid', get_string('required'), 'required', null, 'client');

        // Notification field.
        $mform->addElement('selectyesno', 'notify', get_string('attendance_forum_notify', 'local_apsolu'));
        $mform->setType('notify', PARAM_INT);
        $mform->addRule('notify', get_string('required'), 'required', null, 'client');

        // Notifier le contact fonctionnel.
        $functionalcontact = get_config('local_apsolu', 'functional_contact');
        if (empty($functionalcontact) === false) {
            $label = get_string('notify_functional_contact', 'local_apsolu', $functionalcontact);
            $checkbox = $mform->addElement('checkbox', 'notify_functional_contact', $label);
            $mform->setType('notify_functional_contact', PARAM_INT);

            // Force la notification auprès de l'adresse de contact fonctionnel.
            $session->notify_functional_contact = 1;
            $checkbox->freeze();
        }

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('save', 'admin'));

        $attributes = new stdClass();
        $attributes->href = new moodle_url(
            '/local/apsolu/attendance/index.php',
            ['page' => 'sessions', 'courseid' => $session->courseid]
        );
        $attributes->class = 'btn btn-default btn-secondary';
        $buttonarray[] = &$mform->createElement('static', '', '', get_string('cancel_link', 'local_apsolu', $attributes));

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);

        // Hidden fields.
        $mform->addElement('hidden', 'page', 'sessions');
        $mform->setType('page', PARAM_ALPHA);

        $mform->addElement('hidden', 'action', 'edit');
        $mform->setType('action', PARAM_ALPHA);

        $mform->addElement('hidden', 'sessionid');
        $mform->setType('sessionid', PARAM_INT);

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        // Set default values.
        $this->set_data($session);
    }

    /**
     * Validation.
     *
     * @param array $data
     * @param array $files
     *
     * @return array the errors that were found
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Is valid URL ?
        if (!empty($data['url'])) {
            if (filter_var($data['url'], FILTER_VALIDATE_URL) === false) {
                $errors['area'] = get_string('courses_bad_url', 'local_apsolu', get_string('url'));
            }
        }

        return $errors;
    }
}
