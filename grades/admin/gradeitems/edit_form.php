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

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

/**
 * Classe pour le formulaire permettant d'éditer un élément d'évaluation.
 *
 * @package    local_apsolu
 * @copyright  2020 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_grades_gradeitems_edit_form extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    protected function definition() {
        global $CFG;

        $mform = $this->_form;
        [$gradeitem, $roles, $calendars] = $this->_customdata;

        // Name field.
        $mform->addElement('text', 'name', get_string('gradeitem_name', 'local_apsolu'), ['size' => '48']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('name', 'gradeitem_name', 'local_apsolu');

        // Roles field.
        $mform->addElement('select', 'roleid', get_string('role'), $roles);
        $mform->setType('roleid', PARAM_TEXT);
        $mform->addRule('roleid', get_string('required'), 'required', null, 'client');

        // Calendars field.
        $mform->addElement('select', 'calendarid', get_string('calendar', 'local_apsolu'), $calendars);
        $mform->setType('calendarid', PARAM_TEXT);
        $mform->addRule('calendarid', get_string('required'), 'required', null, 'client');

        // Note maximum.
        $mform->addElement('text', 'grademax', get_string('maxgrade', 'grades'));
        $mform->setType('grademax', PARAM_FLOAT);
        $mform->addRule('grademax', get_string('required'), 'required', null, 'client');

        // Ré-évaluer les notes.
        $choices = ['0' => get_string('no'), '1' => get_string('yes')];
        $mform->addElement('select', 'rescalegrades', get_string('modgraderescalegrades', 'grades'), $choices);
        $mform->addHelpButton('rescalegrades', 'modgraderescalegrades', 'grades');

        // Date de publication.
        $label = get_string('publication_date', 'local_apsolu');
        $mform->addElement('date_time_selector', 'publicationdate', $label, ['optional' => true]);

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('save', 'admin'));

        $attributes = new stdClass();
        $attributes->href = $CFG->wwwroot . '/local/apsolu/grades/admin/index.php?tab=gradeitems';
        $attributes->class = 'btn btn-default btn-secondary';
        $buttonarray[] = &$mform->createElement('static', '', '', get_string('cancel_link', 'local_apsolu', $attributes));

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);

        // Hidden fields.
        $mform->addElement('hidden', 'tab', 'gradeitems');
        $mform->setType('tab', PARAM_ALPHA);

        $mform->addElement('hidden', 'action', 'edit');
        $mform->setType('action', PARAM_ALPHA);

        $mform->addElement('hidden', 'gradeitemid', $gradeitem->id);
        $mform->setType('gradeitemid', PARAM_INT);

        // Set default values.
        $this->set_data($gradeitem);
    }

    /**
     * Validation.
     *
     * @param array $data
     * @param array $files
     *
     * @return array The errors that were found.
     */
    public function validation($data, $files) {
        global $DB;

        $errors = [];
        $errors = parent::validation($data, $files);

        // Is unique ?
        $params = ['name' => $data['name'], 'calendarid' => $data['calendarid'], 'roleid' => $data['roleid']];
        $gradeitem = $DB->get_record('apsolu_grade_items', $params);
        if ($gradeitem !== false && $gradeitem->id != $data['gradeitemid']) {
            $errors['name'] = get_string('error_name_is_already_used_for_another_context', 'local_apsolu');
        }

        return $errors;
    }
}
