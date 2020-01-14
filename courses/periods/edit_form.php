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
 * Form class to create or to edit a period.
 */
class local_apsolu_courses_periods_edit_form extends moodleform {
    protected function definition() {
        global $CFG, $DB;

        $mform = $this->_form;
        $instance = $this->_customdata['period'];

        // Name field.
        $mform->addElement('text', 'name', get_string('name'), array('size' => '48'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');

        // Generic name field.
        $mform->addElement('text', 'generic_name', get_string('generic_name', 'local_apsolu'), array('size' => '48'));
        $mform->setType('generic_name', PARAM_TEXT);
        $mform->addRule('generic_name', get_string('required'), 'required', null, 'client');

        // Weeks field.
        if (date('n') >= 5) {
               $year = date('Y');
        } else {
               $year = date('Y') - 1;
        }
        $start = new DateTime($year.'-08-15T00:00:00');
        $start->sub(new DateInterval('P'.($start->format('N') - 1).'D'));
        $end = new DateTime(($year + 1).'-06-30T00:00:00');

        $weeks = array();
        while ($start < $end) {
            $range = 'du lun. '.$start->format('d').' au sam. '.strftime('%d %b %Y', $start->getTimestamp() + 5 * 24 * 60 * 60);
            $weeks[$start->format('Y-m-d')] = 'Sem. '.$start->format('W').' ('.$range.')';
            $start = $start->add(new DateInterval('P7D'));
        }
        $select = $mform->addElement('select', 'weeks', get_string('week'), $weeks, array('size' => 20, 'width' => 30));
        $mform->setType('weeks', PARAM_TEXT);
        $mform->addRule('weeks', get_string('required'), 'required', null, 'client');
        $select->setMultiple(true);

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('save', 'admin'));

        $attributes = new stdClass();
        $attributes->href = $CFG->wwwroot.'/local/apsolu/courses/index.php?tab=periods';
        $attributes->class = 'btn btn-default';
        $buttonarray[] = &$mform->createElement('static', '', '', get_string('cancel_link', 'local_apsolu', $attributes));

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

        // Hidden fields.
        $mform->addElement('hidden', 'tab', 'periods');
        $mform->setType('tab', PARAM_ALPHA);

        $mform->addElement('hidden', 'action', 'edit');
        $mform->setType('action', PARAM_ALPHA);

        $mform->addElement('hidden', 'periodid', $instance->id);
        $mform->setType('periodid', PARAM_INT);

        // Set default values.
        $this->set_data($instance);
    }

    /**
     * Validation.
     *
     * @param array $data
     * @param array $files
     * @return array the errors that were found
     */
    public function validation($data, $files) {
        global $DB;

        $errors = array();
        $errors = parent::validation($data, $files);

        // Is unique ?
        $period = $DB->get_record('apsolu_periods', array('name' => $data['name']));
        if ($period && $period->id != $data['periodid']) {
            $errors['name'] = get_string('shortnametaken', '', $data['name']);
        }

        return $errors;
    }
}
