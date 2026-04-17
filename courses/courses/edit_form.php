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
use local_apsolu\core\customfields;
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

        $customfields = customfields::get_course_custom_fields($coursetypeid);

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

            if (in_array($shortname, ['event'], $strict = true) === false && $type !== 'textarea') {
                $mform->addRule($fieldid, get_string('required'), 'required', null, 'client');
            }

            switch ($type) {
                case 'textarea':
                    $fieldid = sprintf('customfield_%s_editor', $shortname);

                    $defaultvalue->{$fieldid} = [
                        'text' => $course->customfields[$shortname]->get_value(),
                        'format' => $data->get('valueformat'),
                    ];
                    break;
                case 'time':
                    $value = $course->customfields[$shortname]->get_value();

                    $hour = intval($value / HOURSECS);
                    $minute = intval(($value - $hour * HOURSECS) / MINSECS);

                    $defaultvalue->{sprintf('%s[hour]', $fieldid)} = $hour;
                    $defaultvalue->{sprintf('%s[minute]', $fieldid)} = $minute;
                    break;
                default:
                    $defaultvalue->{$fieldid} = $course->customfields[$shortname]->get_value();
            }
        }

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('save', 'admin'));

        $attributes = new stdClass();
        $attributes->href = new moodle_url('/local/apsolu/courses/index.php', ['tab' => 'courses', 'coursetypeid' => $coursetypeid]);
        $attributes->class = 'btn btn-default btn-secondary';
        $buttonarray[] = &$mform->createElement('static', '', '', get_string('cancel_link', 'local_apsolu', $attributes));

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);

        // Hidden fields.
        $mform->addElement('hidden', 'courseid', $course->id);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('hidden', 'id', $course->id);
        $mform->setType('id', PARAM_INT);

        // $mform->addElement('hidden', 'coursetypeid', $course->typeid);
        // $mform->setType('coursetypeid', PARAM_INT);

        // Set default values.
        $this->set_data($defaultvalue);
    }

    /**
     * Retourne les options passées aux éléments du formulaire de type editor.
     *
     * @param int $courseid Identifiant du cours.
     *
     * @return array
     */
    public static function get_editor_options($courseid = null) {
        if ($courseid === null) {
            $context = context_system::instance();
        } else {
            $context = context_course::instance($courseid);
        }

        $options = [];
        $options['subdirs'] = false;
        $options['maxbytes'] = 0; // Taille limite par défaut.
        $options['maxfiles'] = -1; // Nombre de fichiers attachés illimités.
        $options['context'] = $context;
        $options['noclean'] = true;
        $options['trusttext'] = false;

        return $options;
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

        // Valide la cohérence des heures de début et de fin.
        if (isset($data['customfield_start_time'], $data['customfield_end_time']) === true) {
            $starttime = $data['customfield_start_time']['hour'] * HOURSECS + $data['customfield_start_time']['minute'] * MINSECS;
            $endtime = $data['customfield_end_time']['hour'] * HOURSECS + $data['customfield_end_time']['minute'] * MINSECS;

            if ($starttime >= $endtime) {
                $errors['customfield_end_time'] = 'La date est antérieur au debut'; // TODO.
            }
        }

        // Valide la cohérence des heures de début et de fin.
        if (isset($data['customfield_start_date'], $data['customfield_end_date']) === true) {
            if ($data['customfield_start_date'] >= $data['customfield_end_date']) {
                $errors['customfield_end_date'] = 'La date est antérieur au debut'; // TODO.
            }
        }

        return $errors;
    }
}
