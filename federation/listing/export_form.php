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
 * Classe le formulaire permettant d'extraire les étudiants inscrits à la FFSU.
 *
 * @package    local_apsolu
 * @copyright  2023 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_federation_export_form extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    protected function definition() {
        $mform = $this->_form;
        [$activities, $sexes, $institutions, $ufrs, $departments] = $this->_customdata;

        $multiple = ['multiple' => true];

        $mform->addElement('autocomplete', 'activities', get_string('activities', 'local_apsolu'), $activities, $multiple);
        $mform->setType('activities', PARAM_TEXT);

        $mform->addElement('autocomplete', 'sexes', get_string('user_title', 'local_apsolu'), $sexes, $multiple);
        $mform->setType('sexes', PARAM_TEXT);
        $mform->setDefault('sexes', ['Mme', 'M']);

        $mform->addElement('autocomplete', 'institutions', get_string('institution'), $institutions, $multiple);
        $mform->setType('institutions', PARAM_TEXT);

        $mform->addElement('autocomplete', 'ufrs', get_string('ufrs', 'local_apsolu'), $ufrs, $multiple);
        $mform->setType('ufrs', PARAM_TEXT);

        $mform->addElement('autocomplete', 'departments', get_string('department'), $departments, $multiple);
        $mform->setType('departments', PARAM_TEXT);

        $mform->addElement('text', 'lastnames', get_string('studentname', 'local_apsolu'), ['size' => '48']);
        $mform->setType('lastnames', PARAM_TEXT);
        $mform->addHelpButton('lastnames', 'studentname', 'local_apsolu');

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('show'));
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('export', 'local_apsolu'));

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
    }
}
