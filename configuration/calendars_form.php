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

class local_apsolu_calendar_edit_form extends moodleform {
    protected function definition() {
        $mform = $this->_form;

        $datetimeoptions = array('optional' => true);

        list($defaults, $calendarstypes) = $this->_customdata;

        if (empty($defaults->id) === false) {
            // Avertissement affiché lorsqu'on modifie un calendrier existant.
            $mform->addElement('html', '<div class="alert alert-info">'.get_string('calendar_modification_warning', 'local_apsolu').'</div>');
        }

        $mform->addElement('header', 'general', get_string('general'));
        $mform->setExpanded('general');
        $attributes = array('size' => '20', 'maxlength' => '255');
        $mform->addElement('text', 'name', get_string('calendarname', 'local_apsolu'), $attributes);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'server');

        $options = array();
        foreach ($calendarstypes as $type) {
            $options[$type->id] = $type->name;
        }
        $mform->addElement('select', 'typeid', get_string('calendartype', 'local_apsolu'), $options);

        // Inscriptions.
        $mform->addElement('header', 'enrolments', get_string('enrolments', 'enrol'));
        $mform->setExpanded('enrolments');
        $mform->addElement('date_time_selector', 'enrolstartdate', get_string('enrolstartdate', 'local_apsolu'), $datetimeoptions);
        $mform->addElement('date_time_selector', 'enrolenddate', get_string('enrolenddate', 'local_apsolu'), $datetimeoptions);

        // Cours.
        $mform->addElement('header', 'courses', get_string('courses'));
        $mform->setExpanded('courses');
        $mform->addElement('date_time_selector', 'coursestartdate', get_string('coursestartdate', 'local_apsolu'), $datetimeoptions);
        $mform->addElement('date_time_selector', 'courseenddate', get_string('courseenddate', 'local_apsolu'), $datetimeoptions);

        // Réinscriptions.
        $mform->addElement('header', 'reenrolments', get_string('reenrolments', 'local_apsolu'));
        $mform->setExpanded('reenrolments');
        $mform->addElement('date_time_selector', 'reenrolstartdate', get_string('reenrolstartdate', 'local_apsolu'), $datetimeoptions);
        $mform->addElement('date_time_selector', 'reenrolenddate', get_string('reenrolenddate', 'local_apsolu'), $datetimeoptions);

        // Grades.
        $mform->addElement('header', 'grades', get_string('grades'));
        $mform->setExpanded('grades');
        $mform->addElement('date_time_selector', 'gradestartdate', get_string('gradestartdate', 'local_apsolu'), $datetimeoptions);
        $mform->addElement('date_time_selector', 'gradeenddate', get_string('gradeenddate', 'local_apsolu'), $datetimeoptions);

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'));

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

        // Hidden fields.
        $mform->addElement('hidden', 'action', 'edit');
        $mform->setType('action', PARAM_ALPHANUM);

        $mform->addElement('hidden', 'page', 'calendars');
        $mform->setType('page', PARAM_ALPHANUM);

        $mform->addElement('hidden', 'calendarid', $defaults->id);
        $mform->setType('calendarid', PARAM_INT);

        // Set default values.
        $this->set_data($defaults);
    }
}
