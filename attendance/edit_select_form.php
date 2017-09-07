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
 * Select session
 *
 * @package    local_apsolu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');

class edit_select_form extends moodleform {

    /**
     * Form definition.
     */
    function definition() {
        $mform = $this->_form;

        $data = new stdClass();
        $data->courseid = $this->_customdata['courseid']; // this contains the data of this form
        $data->sessionid = $this->_customdata['sessionid'];

        if (isset($this->_customdata['invalid_enrolments']) == true) {
            $data->invalid_enrolments = $this->_customdata['invalid_enrolments'];
        }

        if (isset($this->_customdata['inactive_enrolments']) == true) {
            $data->unactive_enrolments = $this->_customdata['inactive_enrolments'];
        }

        // Sessions.
        $sessions = array();
        foreach ($this->_customdata['sessions'] as $session) {
            $sessions[$session->id] = $session->name;

        }
        $mform->addElement('select', 'sessionid', get_string('attendance_select_session', 'local_apsolu'), $sessions);
        $mform->setType('sessionid', PARAM_INT);

        // Invalid enrolments.
        $mform->addElement('checkbox', 'invalid_enrolments', get_string('attendance_display_invalid_enrolments', 'local_apsolu'));
        $mform->setType('invalid_enrolments', PARAM_INT);

        // Inactive enrolments.
        $mform->addElement('checkbox', 'inactive_enrolments', get_string('attendance_display_inactive_enrolments', 'local_apsolu'));
        $mform->setType('inactive_enrolments', PARAM_INT);

        // Courseid.
        $mform->addElement('hidden', 'courseid', null);
        $mform->setType('courseid', PARAM_INT);

        // Submit buttons.
        $mform->addElement('submit', 'submitbutton', get_string('show'));

        // Finally set the current form data
        $this->set_data($data);
    }
}
