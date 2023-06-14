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
 * Classe pour le formulaire permettant de configurer les types de présences.
 *
 * @package    local_apsolu
 * @copyright  2023 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

/**
 * Classe pour le formulaire permettant de configurer les types de présences.
 *
 * @package    local_apsolu
 * @copyright  2023 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_attendance_statuses_edit_form extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    protected function definition() {
        global $CFG;

        $mform = $this->_form;

        $datetimeoptions = array('optional' => true);

        list($defaults) = $this->_customdata;

        // Shortlabel field.
        $mform->addElement('text', 'shortlabel', get_string('short_label', 'local_apsolu'), array('maxlength' => 3, 'size' => '48'));
        $mform->setType('shortlabel', PARAM_TEXT);
        $mform->addRule('shortlabel', get_string('required'), 'required', null, 'client');

        // Longlabel field.
        $mform->addElement('text', 'longlabel', get_string('long_label', 'local_apsolu'), array('size' => '48'));
        $mform->setType('longlabel', PARAM_TEXT);
        $mform->addRule('longlabel', get_string('required'), 'required', null, 'client');

        // Sumlabel field.
        $mform->addElement('text', 'sumlabel', get_string('sum_label', 'local_apsolu'), array('size' => '48'));
        $mform->setType('sumlabel', PARAM_TEXT);
        $mform->addRule('sumlabel', get_string('required'), 'required', null, 'client');

        // Color field.
        $colors = array();
        $colors['success'] = get_string('green', 'local_apsolu');
        $colors['warning'] = get_string('orange', 'local_apsolu');
        $colors['info'] = get_string('blue', 'local_apsolu');
        $colors['danger'] = get_string('red', 'local_apsolu');

        $mform->addElement('select', 'color', get_string('color', 'local_apsolu'), $colors);
        $mform->setType('color', PARAM_TEXT);
        $mform->addRule('color', get_string('required'), 'required', null, 'client');

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'));

        $attributes = new stdClass();
        $attributes->href = $CFG->wwwroot.'/local/apsolu/configuration/index.php?page=attendancestatuses';
        $attributes->class = 'btn btn-default btn-secondary';
        $buttonarray[] = &$mform->createElement('static', '', '', get_string('cancel_link', 'local_apsolu', $attributes));

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

        // Hidden fields.
        $mform->addElement('hidden', 'action', 'edit');
        $mform->setType('action', PARAM_ALPHANUM);

        $mform->addElement('hidden', 'page', 'attendancestatuses');
        $mform->setType('page', PARAM_ALPHANUM);

        $mform->addElement('hidden', 'statusid', $defaults->id);
        $mform->setType('statusid', PARAM_INT);

        // Set default values.
        $this->set_data($defaults);
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

        $errors = parent::validation($data, $files);

        $fields = array();
        $fields['short_label'] = 'shortlabel';
        $fields['long_label'] = 'longlabel';
        $fields['sum_label'] = 'sumlabel';

        foreach ($fields as $labelname => $field) {
            $label = get_string($labelname, 'local_apsolu');
            if (isset($data[$field]) === false) {
                $errors[$field] = get_string('fieldrequired', 'error', $label);
                continue;
            }

            $data[$field] = trim($data[$field]);
            if (empty($data[$field]) === true) {
                $errors[$field] = get_string('field_X_cannot_be_empty', 'local_apsolu', $label);
                continue;
            }

            $record = $DB->get_record('apsolu_attendance_statuses', array($field => $data[$field]));

            if ($record === false) {
                continue;
            }

            if ($record->id == $data['statusid']) {
                continue;
            }

            $errors[$field] = get_string('value_X_is_already_in_use_by_another_record', 'local_apsolu', s($data[$field]));
        }

        return $errors;
    }
}
