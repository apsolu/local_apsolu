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

namespace local_apsolu\form\courses\course_type;

use core_course\customfield\course_handler;
use html_writer;
use moodle_url;
use moodleform;
use stdClass;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

/**
 * Classe pour le formulaire permettant de configurer les types de créneaux.
 *
 * @package    local_apsolu
 * @copyright  2026 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    protected function definition() {
        global $CFG;

        $mform = $this->_form;
        $coursetype = $this->_customdata['coursetype'];

        $handler = course_handler::create();
        $fields = $handler->get_fields();

        $samplefields = ['category' => '%00', 'skill' => '%00', 'weekday' => '%00'];
        foreach ($fields as $field) {
            $shortname = $field->get('shortname');

            if (isset($samplefields[$shortname]) === false) {
                continue;
            }

            $samplefields[$shortname] = sprintf('%%%02d', $field->get('id'));
        }

        $attributes = ['size' => '48'];

        // Nom.
        $mform->addElement('text', 'name', get_string('course_type_name', 'local_apsolu'), $attributes);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        // $mform->setDefault('name', $coursetype->name);
        $mform->setType('name', PARAM_TEXT);

        // Nom abrégé.
        $mform->addElement('text', 'shortname', get_string('shortname'), $attributes);
        $mform->addHelpButton('shortname', 'shortname', 'customfield');
        $mform->addRule('shortname', get_string('required'), 'required', null, 'client');
        // $mform->setDefault('shortname', $coursetype->shortname);
        $mform->setType('shortname', PARAM_TEXT);

        // Format du nom de cours.
        $mform->addElement('text', 'fullnametemplate', get_string('template_for_the_fullname_course', 'local_apsolu'), $attributes);
        $mform->addHelpButton('fullnametemplate', 'template_for_the_fullname_course', 'local_apsolu');
        $mform->addRule('fullnametemplate', get_string('required'), 'required', null, 'client');
        // $mform->setDefault('coursefullnametemplate', $coursetype->coursefullnametemplate);
        $mform->setType('fullnametemplate', PARAM_TEXT);
        $mform->addElement(
            'static',
            'fullnametemplateexample',
            '',
            get_string('template_for_the_fullname_course_example', 'local_apsolu', $samplefields)
        );

        // Couleur.
        $attributes = ['data-apsolu' => 'colorpicker'];
        $mform->addElement('text', 'color', get_string('color', 'local_apsolu'), $attributes);
        $mform->setType('color', PARAM_TEXT);

        // Add custom fields to the form.
        $mform->addElement('header', 'customfield', get_string('fields_associated_with_this_format_type', 'local_apsolu'));
        foreach ($fields as $field) {
            $shortname = $field->get('shortname');
            $prefix = sprintf('fields[%s]', $shortname);
            $name = sprintf('%s[fieldid]', $prefix);

            if ($shortname === 'type') {
                // Le champ 'Type' est obligatoire.
                $mform->addElement('hidden', $name, $field->get('id'));
                $mform->setType($name, PARAM_INT);
                continue;
            }

            $values = [0, $field->get('id')];
            $leftlabel = get_string('field_fieldname', 'local_apsolu', $field->get('name'));
            $mform->addElement('advcheckbox', $name, $leftlabel, $rightlabel = $field->get('name'), $groups = null, $values);

            if ($shortname === 'category') {
                // Le champ 'Category' (activité) est obligatoire. On passe le champ en lecture seule.
                $mform->setDefault($name, $field->get('id'));
                $mform->freeze($name);

                $staticname = sprintf('%s[static]', $name);
                $staticlabel = get_string(
                    'code_to_use_in_the_fullname_course_template',
                    'local_apsolu',
                    sprintf('%%%02d', $field->get('id'))
                );
                $mform->addElement('static', $staticname, '', html_writer::tag('small', $staticlabel));

                continue;
            }

            // Affiche les options pour configurer la visibilité des champs selon les contextes.
            $visibilities = [];
            $visibilities['admin'] = 'visible_in_administration';
            $visibilities['public'] = 'visible_on_public_pages';
            foreach ($visibilities as $scope => $labelid) {
                $checkboxname = sprintf('%s[%s]', $prefix, $scope);
                $mform->addElement('checkbox', $checkboxname, $leftlabel = '', $rightlabel = get_string($labelid, 'local_apsolu'));
                $mform->addHelpButton($checkboxname, $labelid, 'local_apsolu', '', false, $field->get('name'));
                $mform->hideIf($checkboxname, $name, 'eq', '0');
            }

            $staticname = sprintf('%s[static]', $prefix);
            $staticlabel = get_string(
                'code_to_use_in_the_fullname_course_template',
                'local_apsolu',
                sprintf('%%%02d', $field->get('id'))
            );
            $mform->addElement('static', $staticname, '', html_writer::tag('small', $staticlabel));
            $mform->hideIf($staticname, $name, 'eq', '0');
        }

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('save', 'admin'));

        $attributes = new stdClass();
        $attributes->href = new moodle_url('/local/apsolu/courses/index.php', ['tab' => 'course_types']);
        $attributes->class = 'btn btn-default btn-secondary';
        $buttonarray[] = &$mform->createElement('static', '', '', get_string('cancel_link', 'local_apsolu', $attributes));

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
        $mform->closeHeaderBefore('buttonar');

        // Hidden fields.
        $mform->addElement('hidden', 'coursetypeid', $coursetype->id);
        $mform->setType('coursetypeid', PARAM_INT);

        // Set default values.
        $this->set_data($coursetype);
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

        $errors = parent::validation($data, $files);

        // Is unique ?
        $coursetype = $DB->get_record('apsolu_courses_types', ['shortname' => $data['shortname']]);
        if ($coursetype && $coursetype->id != $data['coursetypeid']) {
            $errors['shortname'] = get_string('erroruniquevalues', 'customfield');
        }

        // Valide la couleur.
        if (preg_match('/^#[a-fA-F0-9]{6}$/', $data['color']) !== 1) {
            $errors['color'] = get_string('the_field_X_has_an_invalid_value', 'local_apsolu', get_string('color', 'local_apsolu'));
        }

        // Vérifier que le modèle de cours utilise uniquement des champs activés.
        $fields = [];
        $handler = course_handler::create();
        foreach ($handler->get_fields() as $field) {
            $fields[sprintf('%%%02d', $field->get('id'))] = $field;
        }

        preg_match_all('/%[0-9]{2}/', $data['fullnametemplate'], $matches);
        foreach ($matches[0] as $code) {
            if (isset($fields[$code]) === false) {
                $errors['fullnametemplate'] = get_string('the_field_code_is_not_valid', 'local_apsolu', $code);
                continue;
            }

            $field = $fields[$code];
            $shortname = $field->get('shortname');
            if (isset($data['fields'][$shortname]) === true && empty($data['fields'][$shortname]) === true) {
                $fieldname = sprintf('fields[%s]', $shortname);
                $errors[$fieldname] = get_string(
                    'the_field_X_must_be_checked_to_be_used_in_fullname_course_template',
                    'local_apsolu',
                    $field->get('name')
                );
            }
        }

        return $errors;
    }
}
