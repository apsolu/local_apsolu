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
 * @copyright  2018 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

/**
 * Form class to create or to edit a course.
 */
class local_apsolu_reports_export_course_users_form extends moodleform {
    protected function definition() {
        global $CFG, $DB;

        $mform = $this->_form;
        list($defaults, $courses, $institutions, $roles, $semesters, $lists, $paids, $force_manager) = $this->_customdata;

        // Family names.
        $mform->addElement('text', 'lastnames', get_string('studentname', 'local_apsolu'), array('size' => '48'));
        $mform->setType('lastnames', PARAM_TEXT);
        $mform->addHelpButton('lastnames', 'studentname', 'local_apsolu');

        // Courses.
        $select = $mform->addElement('select', 'courses', get_string('mycourses'), $courses, array('size' => 10));
        $mform->setType('courses', PARAM_TEXT);
        $mform->addRule('courses', get_string('required'), 'required', null, 'client');
        $select->setMultiple(true);

        // Institutions.
        $select = $mform->addElement('select', 'institutions', get_string('institution'), $institutions, array('size' => 6));
        $mform->setType('institutions', PARAM_TEXT);
        $mform->addRule('institutions', get_string('required'), 'required', null, 'client');
        $select->setMultiple(true);

        // UFR.
        $mform->addElement('text', 'ufrs', get_string('ufrs', 'local_apsolu_courses'), array('size' => '48'));
        $mform->setType('ufrs', PARAM_TEXT);
        $mform->addHelpButton('ufrs', 'ufrs', 'local_apsolu_courses');

        // Departments.
        $mform->addElement('text', 'departments', get_string('department'), array('size' => '48'));
        $mform->setType('departments', PARAM_TEXT);
        $mform->addHelpButton('departments', 'departments', 'local_apsolu');

        // Roles (evaluate, free, etc).
        $select = $mform->addElement('select', 'roles', get_string('role'), $roles, array('size' => 4));
        $mform->setType('roles', PARAM_TEXT);
        $mform->addRule('roles', get_string('required'), 'required', null, 'client');
        $select->setMultiple(true);

        // Semesters.
        $select = $mform->addElement('select', 'semesters', get_string('semesters', 'local_apsolu_courses'), $semesters, array('size' => 4));
        $mform->setType('semesters', PARAM_TEXT);
        $mform->addRule('semesters', get_string('required'), 'required', null, 'client');

        // Lists (main list, wait list, etc).
        $select = $mform->addElement('select', 'lists', get_string('list'), $lists, array('size' => 4));
        $mform->setType('lists', PARAM_TEXT);
        $mform->addRule('lists', get_string('required'), 'required', null, 'client');
        $select->setMultiple(true);

        // Paids (yes or no).
        $select = $mform->addElement('select', 'paids', get_string('paid', 'local_apsolu'), $paids, array('size' => 4));
        $mform->setType('paids', PARAM_TEXT);
        $mform->addRule('paids', get_string('required'), 'required', null, 'client');

        // Submit buttons.
        $attributes = array('class' => 'btn btn-primary');
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('display', 'local_apsolu'), $attributes);
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('export', 'local_apsolu'), $attributes);
        // $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('notify', 'local_apsolu'), $attributes);

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

        if ($force_manager) {
            $mform->addElement('hidden', 'manager', '1');
            $mform->setType('manager', PARAM_INT);
        }

        // Set default values.
        $this->set_data($defaults);
    }
}
