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
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

/**
 * Form class to create or to edit a course.
 */
class local_apsolu_courses_courses_edit_form extends moodleform {
    protected function definition() {
        global $CFG, $DB;

        $mform = $this->_form;
        list($course, $categories, $skills, $locations, $periods, $weekdays) = $this->_customdata;

        // Category field (Sport).
        $mform->addElement('select', 'category', get_string('sport', 'local_apsolu'), $categories);
        $mform->setType('category', PARAM_INT);
        $mform->addRule('category', get_string('required'), 'required', null, 'client');
        // See MDL-53725.
        // Hope to use instead : $mform->addDatalist('category', $categories);.

        // Event field.
        $mform->addElement('text', 'event', get_string('event', 'local_apsolu'), array('size' => '48'));
        $mform->setType('event', PARAM_TEXT);
        $mform->setDefault('event', $course->event);

        // Skill field.
        $mform->addElement('select', 'skillid', get_string('skill', 'local_apsolu'), $skills);
        $mform->setType('skillid', PARAM_INT);
        $mform->addRule('skillid', get_string('required'), 'required', null, 'client');
        // See MDL-53725.
        // Hope to use instead : $mform->addDatalist('skill', $skills);.

        // Location field.
        $mform->addElement('select', 'locationid', get_string('location', 'local_apsolu'), $locations);
        $mform->setType('locationid', PARAM_INT);
        $mform->addRule('locationid', get_string('required'), 'required', null, 'client');
        // See MDL-53725.
        // Hope to use instead : $mform->addDatalist('location', $locations);.

        // Weekday field.
        $mform->addElement('select', 'weekday', get_string('weekday', 'local_apsolu'), $weekdays);
        $mform->setType('weekday', PARAM_TEXT);
        $mform->addRule('weekday', get_string('required'), 'required', null, 'client');

        // Starttime field.
        $attributes = array('size' => '8', 'maxlength' => 5, 'pattern' => '(0[0-9]|1[0-9]|2[0-3])(:[0-5][0-9])', 'placeholder' => 'hh:mm');
        $mform->addElement('text', 'starttime', get_string('starttime', 'local_apsolu'), $attributes);
        $mform->setType('starttime', PARAM_TEXT);
        $mform->addRule('starttime', get_string('required'), 'required', null, 'client');

        // Endtime field.
        $mform->addElement('text', 'endtime', get_string('endtime', 'local_apsolu'), $attributes);
        $mform->setType('endtime', PARAM_TEXT);
        $mform->addRule('endtime', get_string('required'), 'required', null, 'client');

        // License field.
        if (isset($CFG->is_siuaps_rennes) === true) {
            $mform->addElement('selectyesno', 'license', get_string('license', 'local_apsolu'));
            $mform->addRule('license', get_string('required'), 'required', null, 'client');
        } else {
            $mform->addElement('hidden', 'license', 0);
        }
        $mform->setType('license', PARAM_INT);

        // On homepage field.
        $mform->addElement('selectyesno', 'on_homepage', get_string('on_homepage', 'local_apsolu'));
        $mform->setType('on_homepage', PARAM_INT);
        $mform->addRule('on_homepage', get_string('required'), 'required', null, 'client');

        // Periods field.
        $mform->addElement('select', 'periodid', get_string('period', 'local_apsolu'), $periods);
        $mform->setType('periodid', PARAM_INT);
        $mform->addRule('periodid', get_string('required'), 'required', null, 'client');
        // See MDL-53725.
        // Hope to use instead : $mform->addDatalist('period', $periods);.

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('save', 'admin'));

        $attributes = new stdClass();
        $attributes->href = $CFG->wwwroot.'/local/apsolu/courses/index.php?tab=courses';
        $attributes->class = 'btn btn-default';
        $buttonarray[] = &$mform->createElement('static', '', '', get_string('cancel_link', 'local_apsolu', $attributes));

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

        // Hidden fields.
        $mform->addElement('hidden', 'tab', 'courses');
        $mform->setType('tab', PARAM_TEXT);

        $mform->addElement('hidden', 'action', 'edit');
        $mform->setType('action', PARAM_TEXT);

        $mform->addElement('hidden', 'courseid', $course->id);
        $mform->setType('courseid', PARAM_INT);

        // Set default values.
        $this->set_data($course);
    }
}
