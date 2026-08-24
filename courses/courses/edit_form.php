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

use core_customfield\api;
use local_apsolu\core\coursetype;
use local_apsolu\customfields\course as CustomfieldsCourse;
use local_apsolu\core\federation\activity;
use local_apsolu\core\federation\course as FederationCourse;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

/**
 * Classe pour le formulaire permettant de configurer les créneaux horaires.
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_courses_courses_edit_form extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    protected function definition() {
        $mform = $this->_form;
        [$coursetypeid, $course] = $this->_customdata;

        $customfields = CustomfieldsCourse::get_course_custom_fields($coursetypeid);

        $handler = core_course\customfield\course_handler::create();

        $editablefields = $handler->get_fields($course->id);
        $fieldswithdata = api::get_instance_fields_data($editablefields, $course->id);

        $defaultvalue = new stdClass();
        foreach ($fieldswithdata as $data) {
            $shortname = $data->get_field()->get('shortname');

            if (isset($customfields[$shortname]) === false) {
                // On ignore les champs qui ne sont pas associés à ce type de créneau.
                continue;
            }

            if ($shortname === 'type') {
                // On cache le champ "format de cours".
                $mform->addElement('hidden', 'customfield_type', $coursetypeid);
                $mform->setType('customfield_type', PARAM_INT);
                continue;
            }

            $data->instance_form_definition($mform);

            $fieldid = sprintf('customfield_%s', $shortname);
            $type = $data->get_field()->get('type');

            switch ($type) {
                case 'apsolu_category':
                    // Traitement particulier pour les customfields de type "apsolu_category".
                    $value = json_decode($course->customfields[$shortname]->get_value(), $associative = true);

                    $categoryidfield = sprintf('%s[categoryid]', $fieldid);
                    $mform->addRule($categoryidfield, get_string('required'), 'required', null, 'client');
                    $defaultvalue->{$categoryidfield} = $value['categoryid'];

                    $additionalstrfield = sprintf('%s[additionalstr]', $fieldid);
                    $defaultvalue->{$additionalstrfield} = $value['additionalstr'];

                    // Champ "N° d’identification du cours".
                    $mform->addElement('text', 'idnumber', get_string('idnumbercourse'), ['maxlength' => '100', 'size' => '48']);
                    $mform->addHelpButton('idnumber', 'idnumbercourse');
                    $mform->setType('idnumber', PARAM_RAW);
                    $mform->setDefault('idnumber', $course->idnumber);
                    break;
                case 'daterange':
                case 'timerange':
                    $mform->addRule(sprintf('%s[start]', $fieldid), get_string('required'), 'required', null, 'client');
                    $mform->addRule(sprintf('%s[end]', $fieldid), get_string('required'), 'required', null, 'client');
                    $defaultvalue->{$fieldid} = json_decode($course->customfields[$shortname]->get_value(), $associative = true);
                    break;
                case 'textarea':
                    $fieldid = sprintf('customfield_%s_editor', $shortname);

                    $defaultvalue->{$fieldid} = [
                        'text' => $course->customfields[$shortname]->get_value(),
                        'format' => $data->get('valueformat'),
                    ];
                    break;
                default:
                    $mform->addRule($fieldid, get_string('required'), 'required', null, 'client');
                    $defaultvalue->{$fieldid} = $course->customfields[$shortname]->get_value();
            }
        }

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('save', 'admin'));

        $attributes = new stdClass();
        $attributes->href = new moodle_url(
            '/local/apsolu/courses/index.php',
            ['tab' => 'courses', 'coursetypeid' => $coursetypeid]
        );
        $attributes->class = 'btn btn-default btn-secondary';
        $buttonarray[] = &$mform->createElement('static', '', '', get_string('cancel_link', 'local_apsolu', $attributes));

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);

        // Hidden fields.
        $mform->addElement('hidden', 'courseid', $course->id);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('hidden', 'id', $course->id);
        $mform->setType('id', PARAM_INT);

        // Set default values.
        $this->set_data($defaultvalue);
    }

    /**
     * Validation.
     *
     * @param array $data
     * @param array $files
     * @return array the errors that were found
     */
    public function validation($data, $files): array {
        global $DB;

        $errors = parent::validation($data, $files);

        // Contrôle manuellement que les champs personnalisés ne sont pas initialisés à "0".
        if (empty($data['customfield_category']['categoryid']) === true) {
            $errors['customfield_category[categoryid]'] = get_string('err_required', 'form');
        }

        foreach (['skill', 'location', 'federation', 'on_homepage', 'period', 'show_policy'] as $attribute) {
            $fieldname = sprintf('customfield_%s', $attribute);

            if (isset($data[$fieldname]) === false) {
                continue;
            }

            if (empty($data[$fieldname]) === false) {
                continue;
            }

            $errors[$fieldname] = get_string('err_required', 'form');
        }

        // Add the custom fields validation.
        $customfields = CourseType::get_custom_fields(required_param('coursetypeid', PARAM_INT));

        $handler = core_course\customfield\course_handler::create();
        $instanceid = empty($data['id']) ? 0 : $data['id'];
        $editablefields = $handler->get_editable_fields($instanceid);
        $fields = $handler->get_instance_fields_data($editablefields, $instanceid);
        foreach ($fields as $formfield) {
            $fieldid = $formfield->get('fieldid');
            if (isset($customfields[$fieldid]) === false) {
                // Ce format de cours n'utilise pas ce champ.
                continue;
            }
            $errors += $formfield->instance_form_validation($data, $files);
        }

        return $errors;
    }
}
