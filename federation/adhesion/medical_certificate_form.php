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
 * Classe pour le formulaire permettant d'exporter les licences FFSU.
 *
 * @package    local_apsolu
 * @copyright  2022 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_form\filetypes_util;

defined('MOODLE_INTERNAL') || die();

require_once $CFG->libdir.'/formslib.php';

/**
 * Classe pour le formulaire permettant d'exporter les licences FFSU.
 *
 * @package    local_apsolu
 * @copyright  2022 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_federation_medical_certificate extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    function definition () {
        global $USER;

        $mform = $this->_form;

        list($adhesion, $course, $context, $validityperiod, $sportswithoutconstraint, $sportswithconstraints, $freeze) = $this->_customdata;

        // Certificat médical.
        $label = get_string('medical_certificate', 'local_apsolu');
        if ($freeze === true) {
            $mform->freeze();

            $fs = get_file_storage();
            $context = context_course::instance($course->id, MUST_EXIST);
            list($component, $filearea, $itemid) = array('local_apsolu', 'medicalcertificate', $USER->id);
            $sort = 'itemid, filepath, filename';
            $files = $fs->get_area_files($context->id, $component, $filearea, $itemid, $sort, $includedirs = false);
            $items = array();
            foreach ($files as $file) {
                $url = moodle_url::make_pluginfile_url($context->id, $component, $filearea, $itemid, '/', $file->get_filename(), $forcedownload = false, $includetoken = false);
                $items[] = html_writer::link($url, $file->get_filename());
            }

            if (empty($items) === true) {
                $mform->addElement('static', 'medicalcertificate', $label, get_string('no_files', 'local_apsolu'));
            } else {
                $attributes = array('class' => 'list-unstyled');
                $mform->addElement('static', 'medicalcertificate', $label, html_writer::alist($items, $attributes));
            }
        } else {
            $attributes = null;
            $options = self::get_filemanager_options($course, $context);

            $mform->addElement('filemanager', 'medicalcertificate_filemanager', $label, $attributes, $options);
        }

        // Date du certificat médical.
        $mform->addElement('date_selector', 'medicalcertificatedate', get_string('medical_certificate_date', 'local_apsolu'));
        $mform->setType('medicalcertificatedate', PARAM_TEXT);
        $mform->addRule('medicalcertificatedate', get_string('required'), 'required', null, 'client');

        if ($validityperiod === 6) {
            // Sport 1 (sans contrainte).
            $mform->addElement('select', 'sport1', get_string('activity_X_without_constraint', 'local_apsolu', 1), $sportswithoutconstraint);
            $mform->setType('sport1', PARAM_INT);

            // Sport 2 (sans contrainte).
            $mform->addElement('select', 'sport2', get_string('activity_X_without_constraint', 'local_apsolu', 2), $sportswithoutconstraint);
            $mform->setType('sport2', PARAM_INT);

            // Sport 3 (sans contrainte).
            $mform->addElement('select', 'sport3', get_string('activity_X_without_constraint', 'local_apsolu', 3), $sportswithoutconstraint);
            $mform->setType('sport3', PARAM_INT);

            // Sport 4 (sans contrainte).
            $mform->addElement('select', 'sport4', get_string('activity_X_without_constraint', 'local_apsolu', 4), $sportswithoutconstraint);
            $mform->setType('sport4', PARAM_INT);

            // Sport 5 (sans contrainte).
            $mform->addElement('select', 'sport5', get_string('activity_X_without_constraint', 'local_apsolu', 5), $sportswithoutconstraint);
            $mform->setType('sport5', PARAM_INT);
        }

        if ($validityperiod === 12) {
            // Sport 1 (avec contrainte).
            $mform->addElement('select', 'constraintsport1', get_string('activity_X_with_specific_constraints', 'local_apsolu', 1), $sportswithconstraints);
            $mform->setType('constraintsport1', PARAM_INT);

            // Sport 2 (avec contrainte).
            $mform->addElement('select', 'constraintsport2', get_string('activity_X_with_specific_constraints', 'local_apsolu', 2), $sportswithconstraints);
            $mform->setType('constraintsport2', PARAM_INT);

            // Sport 3 (avec contrainte).
            $mform->addElement('select', 'constraintsport3', get_string('activity_X_with_specific_constraints', 'local_apsolu', 3), $sportswithconstraints);
            $mform->setType('constraintsport3', PARAM_INT);

            // Sport 4 (avec contrainte).
            $mform->addElement('select', 'constraintsport4', get_string('activity_X_with_specific_constraints', 'local_apsolu', 4), $sportswithconstraints);
            $mform->setType('constraintsport4', PARAM_INT);

            // Sport 5 (avec contrainte).
            $mform->addElement('select', 'constraintsport5', get_string('activity_X_with_specific_constraints', 'local_apsolu', 5), $sportswithconstraints);
            $mform->setType('constraintsport5', PARAM_INT);
        }

        // Champs cachés.
        $mform->addElement('hidden', 'step', APSOLU_PAGE_MEDICAL_CERTIFICATE);
        $mform->setType('step', PARAM_INT);

        // Période de validité du certificat médical (en mois).
        $mform->addElement('hidden', 'validityperiod', $validityperiod);
        $mform->setType('validityperiod', PARAM_INT);

        // Submit buttons.
        $attributes = array('class' => 'btn btn-default');
        $buttonarray[] = &$mform->createElement('submit', 'save', get_string('save'), $attributes);

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

        // Set default values.
        $this->set_data($adhesion);
    }

    /**
     * Retourne les options passées aux éléments du formulaire de type filemanager.
     *
     * @param stdClass $course  Objet représentant un cours.
     * @param context  $context Objet représentant un contexte.
     *
     * @return array
     */
    public static function get_filemanager_options($course, $context) {
        global $CFG;

        $maxbytes = get_user_max_upload_file_size($context, $CFG->maxbytes, $course->maxbytes);

        $options = array();
        $options['areamaxbytes'] = $maxbytes;
        $options['context'] = $context;
        $options['maxbytes'] = $maxbytes;
        $options['maxfiles'] = get_config('local_apsolu', 'ffsu_maxfiles');;
        $options['subdirs'] = 0;

        $acceptedfiles = (string) get_config('local_apsolu', 'ffsu_acceptedfiles');
        $util = new filetypes_util();
        $options['accepted_types'] = $util->normalize_file_types($acceptedfiles);

        return $options;
    }

    /**
     * Valide les données envoyées dans le formulaire.
     *
     * @param array $data
     * @param array $files
     *
     * @return array The errors that were found.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $medicalsports = array();
        $medicalsports[6] = 'sport';
        $medicalsports[12] = 'constraintsport';
        foreach ($medicalsports as $validityperiod => $type) {
            if ($data['validityperiod'] !== $validityperiod) {
                continue;
            }

            $undefined = true;
            for ($i = 1; $i < 6; $i++) {
                $name = sprintf('%s%s', $type, $i);
                if (isset($data[$name]) === false) {
                    continue;
                }

                if ($data[$name] == 1) {
                    continue;
                }

                $undefined = false;
                break;
            }

            if ($undefined === true) {
                $name = sprintf('%s1', $type);
                $errors[$name] = get_string('you_must_select_at_least_one_activity', 'local_apsolu');
            }
        }

        if ($data['medicalcertificatedate'] > time()) {
            $errors['medicalcertificatedate'] = get_string('the_date_of_the_medical_certificate_cannot_be_later_than_today', 'local_apsolu');
            return $errors;
        }

        // Note : il est possible de modifier la période lors de l'envoi du formulaire. Pour bien faire, il faudrait calculer ici la validité attendue.
        // Avec cette vérification, au pire, une personne avec des contraintes médicales pourraient valider le dépôt d'un certificat de moins d'un an (au lieu de 6 mois).
        if (in_array($data['validityperiod'], array(6, 12), $strict = true) == false) {
            $errors['medicalcertificatedate'] = get_string('errorinvaliddate', 'calendar');
            return $errors;
        }

        $now = new DateTime();
        $date = clone $now;
        $date->setTimestamp($data['medicalcertificatedate']);
        $date->add(new DateInterval(sprintf('P%sM', $data['validityperiod'])));
        if ($date < $now) {
            $errors['medicalcertificatedate'] = get_string('you_must_present_a_medical_certificate_of_less_than_X_months', 'local_apsolu', $data['validityperiod']);
            return $errors;
        }

        return $errors;
    }
}
