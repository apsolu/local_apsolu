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
 * Classe pour le formulaire permettant de filtrer les options d'affichage ou d'exportation des notes.
 *
 * @package    local_apsolu
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

/**
 * Classe pour le formulaire permettant de filtrer les options d'affichage ou d'exportation des notes.
 *
 * @package    local_apsolu
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_grades_gradebooks_filters_form extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    protected function definition() {
        $mform = $this->_form;

        list($defaults, $courses, $roles, $calendarstypes, $gradeitems, $cities,
            $institutions, $ufrs, $departments, $cycles, $teachers) = $this->_customdata;

        $fields = [];
        $fields['emails'] = get_string('email');
        $fields['calendarstypes'] = get_string('calendartype', 'local_apsolu');
        $fields['courses'] = get_string('course');
        $fields['roles'] = get_string('role');
        $fields['graders'] = get_string('grader', 'local_apsolu');
        $fields['categories'] = get_string('activity', 'local_apsolu');
        $fields['groupings'] = get_string('grouping', 'local_apsolu');
        $fields['sexes'] = get_string('sex', 'local_apsolu');

        $multiple = ['multiple' => true];

        $mform->addElement('header', 'required_fields', get_string('default_filters', 'local_apsolu'));
        $mform->setExpanded('required_fields', true);

        // Cours.
        $coursesoptions = $multiple;
        $coursesoptions['noselectionstring'] = get_string('all_courses', 'local_apsolu');
        $select = $mform->addElement('autocomplete', 'courses', get_string('courses'), $courses, $coursesoptions);
        $mform->setType('courses', PARAM_TEXT);

        // Rôles.
        $mform->addElement('autocomplete', 'roles', get_string('roles'), $roles, $multiple);
        $mform->setType('roles', PARAM_INT);
        $mform->addRule('roles', get_string('required'), 'required', null, 'client');

        // Calendriers.
        $label = get_string('calendars_types', 'local_apsolu');
        $mform->addElement('autocomplete', 'calendarstypes', $label, $calendarstypes, $multiple);
        $mform->setType('calendarstypes', PARAM_INT);
        $mform->addRule('calendarstypes', get_string('required'), 'required', null, 'client');

        // Elements de notations.
        $mform->addElement('autocomplete', 'gradeitems', get_string('gradeitems', 'local_apsolu'), $gradeitems, $multiple);
        $mform->setType('gradeitems', PARAM_INT);

        $mform->addElement('header', 'other_filters', get_string('other_filters', 'local_apsolu'));
        $mform->setExpanded('other_filters', false);

        // Sites de pratique.
        if (count($cities) > 1) {
            $select = $mform->addElement('autocomplete', 'cities', get_string('cities', 'local_apsolu'), $cities, $multiple);
            $mform->setType('cities', PARAM_INT);

            $fields['cities'] = get_string('city', 'local_apsolu');
        }

        // Etablissements.
        if (count($institutions) > 1) {
            $label = get_string('institutions', 'local_apsolu');
            $select = $mform->addElement('autocomplete', 'institutions', $label, $institutions, $multiple);
            $mform->setType('institutions', PARAM_TEXT);

            $fields['institutions'] = get_string('institution', 'local_apsolu');
        }

        // UFR.
        if (count($ufrs) > 1) {
            $mform->addElement('autocomplete', 'ufrs', get_string('ufrs', 'local_apsolu'), $ufrs, $multiple);
            $mform->setType('ufrs', PARAM_TEXT);

            $fields['ufrs'] = get_string('ufr', 'local_apsolu');
        }

        // Départements.
        if (count($departments) > 1) {
            $mform->addElement('autocomplete', 'departments', get_string('departments', 'local_apsolu'), $departments, $multiple);
            $mform->setType('departments', PARAM_TEXT);

            $fields['departments'] = get_string('department');
        }

        // Niveaux d'études.
        if (count($cycles) > 1) {
            $mform->addElement('autocomplete', 'cycles', get_string('cycles', 'local_apsolu'), $cycles, $multiple);
            $mform->setType('cycles', PARAM_TEXT);

            $fields['cycles'] = get_string('cycle', 'local_apsolu');
        }

        // Enseignant.
        if ($teachers !== null) {
            $mform->addElement('autocomplete', 'teachers', get_string('teachers', 'local_apsolu'), $teachers, $multiple);
            $mform->setType('teachers', PARAM_TEXT);

            $fields['teachers'] = get_string('teacher', 'local_apsolu');
        }

        // Nom de l'étudiant.
        $mform->addElement('text', 'fullnameuser', get_string('fullnameuser'));
        $mform->setType('fullnameuser', PARAM_TEXT);

        // Numéro de l'étudiant.
        $mform->addElement('text', 'idnumber', get_string('idnumber'));
        $mform->setType('idnumber', PARAM_TEXT);

        // Champs utilisateurs.
        $mform->addElement('header', 'additional_fields_to_display', get_string('additional_fields_to_display', 'local_apsolu'));
        $mform->setExpanded('additional_fields_to_display', false);

        asort($fields);
        $mform->addElement('autocomplete', 'fields', get_string('fields', 'local_apsolu'), $fields, $multiple);
        $mform->setType('fields', PARAM_TEXT);
        $mform->addHelpButton('fields', 'additional_fields_to_display', 'local_apsolu');

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'displaybutton', get_string('display', 'local_apsolu'));
        $buttonarray[] = &$mform->createElement('submit', 'exportcsvbutton', get_string('export_to_csv_format', 'local_apsolu'));
        $buttonarray[] = &$mform->createElement('submit', 'exportxlsbutton', get_string('export_to_excel_format', 'local_apsolu'));

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
        $mform->closeHeaderBefore('buttonar');

        // Hidden fields.
        $mform->addElement('hidden', 'tab', 'gradebooks');
        $mform->setType('tab', PARAM_TEXT);

        // Set default values.
        $this->set_data($defaults);
    }
}
