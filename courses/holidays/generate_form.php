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
 * Classe pour le formulaire permettant de générer une liste de jours fériés.
 *
 * @package   local_apsolu
 * @copyright 2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

/**
 * Classe pour le formulaire permettant de générer une liste de jours fériés.
 *
 * @package   local_apsolu
 * @copyright 2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_courses_holidays_generate_form extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    protected function definition() {
        global $CFG;

        $mform = $this->_form;
        $instance = $this->_customdata['holiday'];

        // Champ "à partir de".
        $mform->addElement('date_selector', 'from', get_string('from', 'local_apsolu'));
        $mform->setType('from', PARAM_INT);
        $mform->addRule('from', get_string('required'), 'required', null, 'client');

        // Chmap "jusqu'au".
        $mform->addElement('date_selector', 'until', get_string('until', 'local_apsolu'));
        $mform->setType('until', PARAM_INT);
        $mform->addRule('until', get_string('required'), 'required', null, 'client');

        // Supprime les sessions positionnées sur ce jour férié.
        $mform->addElement('checkbox', 'regensessions', get_string('delete_sessions_already_scheduled_for_those_days', 'local_apsolu'));
        $mform->setType('regensessions', PARAM_BOOL);

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('save', 'admin'));

        $attributes = new stdClass();
        $attributes->href = $CFG->wwwroot.'/local/apsolu/courses/index.php?tab=holidays';
        $attributes->class = 'btn btn-default';
        $buttonarray[] = &$mform->createElement('static', '', '', get_string('cancel_link', 'local_apsolu', $attributes));

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

        // Hidden fields.
        $mform->addElement('hidden', 'tab', 'holidays');
        $mform->setType('tab', PARAM_ALPHA);

        $mform->addElement('hidden', 'action', 'generate');
        $mform->setType('action', PARAM_ALPHA);

        // Set default values.
        $this->set_data($instance);
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
        $errors = parent::validation($data, $files);

        if ($data['from'] >= $data['until']) {
            $errors['until'] = get_string('enddatebeforestartdate', 'error');
        }

        return $errors;
    }
}
