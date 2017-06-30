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
 * @package    local_apsolu
 * @copyright  2017 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

class local_apsolu_calendar_form extends moodleform {
    protected function definition() {
        $mform = $this->_form;

        list($defaults) = $this->_customdata;

		// Enrols semester 1.
        $mform->addElement('header', 'semester1', get_string('semester1', 'local_apsolu'));
		$mform->setExpanded('semester1');

        $mform->addElement('date_time_selector', 'semester1_enrol_startdate', get_string('semester1_enrol_startdate', 'local_apsolu'));
        $mform->addElement('date_time_selector', 'semester1_enrol_enddate', get_string('semester1_enrol_enddate', 'local_apsolu'));
        $mform->addElement('date_time_selector', 'semester1_startdate', get_string('semester1_startdate', 'local_apsolu'));
        $mform->addElement('date_time_selector', 'semester1_enddate', get_string('semester1_enddate', 'local_apsolu'));
        $mform->addElement('date_time_selector', 'semester1_reenrol_startdate', get_string('semester1_reenrol_startdate', 'local_apsolu'));
        $mform->addElement('date_time_selector', 'semester1_reenrol_enddate', get_string('semester1_reenrol_enddate', 'local_apsolu'));

		// Enrols semester 2.
        $mform->addElement('header', 'semester2', get_string('semester2', 'local_apsolu'));
		$mform->setExpanded('semester2');

        $mform->addElement('date_time_selector', 'semester2_enrol_startdate', get_string('semester2_enrol_startdate', 'local_apsolu'));
        $mform->addElement('date_time_selector', 'semester2_enrol_enddate', get_string('semester2_enrol_enddate', 'local_apsolu'));
        $mform->addElement('date_time_selector', 'semester2_startdate', get_string('semester2_startdate', 'local_apsolu'));
        $mform->addElement('date_time_selector', 'semester2_enddate', get_string('semester2_enddate', 'local_apsolu'));

		// Payments.
        $mform->addElement('header', 'payments', get_string('payments'));
		$mform->setExpanded('payments');

        $mform->addElement('date_time_selector', 'payments_startdate', get_string('payments_startdate', 'local_apsolu'));
        $mform->addElement('date_time_selector', 'payments_enddate', get_string('payments_enddate', 'local_apsolu'));

		// Grades.
        $mform->addElement('header', 'grades', get_string('grades'));
		$mform->setExpanded('grades');

        $mform->addElement('date_time_selector', 'semester1_grading_deadline', get_string('semester1_grading_deadline', 'local_apsolu'));
        $mform->addElement('date_time_selector', 'semester2_grading_deadline', get_string('semester2_grading_deadline', 'local_apsolu'));

        // Submit buttons.
        $attributes = array('class' => 'btn btn-primary');
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'), $attributes);

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

        // Hidden fields.
        $mform->addElement('hidden', 'tab', 'configure');
        $mform->setType('tab', PARAM_TEXT);

        // Set default values.
        $this->set_data($defaults);
    }
}
