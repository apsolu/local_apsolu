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
 * Form class to...
 */
class local_apsolu_grades_form extends moodleform {
    protected function definition() {
        global $CFG, $DB;

        $mform = $this->_form;
        list($courses) = $this->_customdata;

        $select = $mform->addElement('select', 'courseid', get_string('mycourses'), $courses);
        $mform->setType('courseid', PARAM_TEXT);
        $mform->addRule('courseid', get_string('required'), 'required', null, 'client');

        // Submit buttons.
        $attributes = array('class' => 'btn btn-primary');
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('display', 'local_apsolu'), $attributes);

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
    }
}
