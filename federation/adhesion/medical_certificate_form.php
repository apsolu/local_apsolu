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

require_once($CFG->libdir . '/formslib.php');

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
    protected function definition() {
        global $USER;

        $mform = $this->_form;

        list($adhesion, $course, $context, $validityperiod, $freeze) = $this->_customdata;

        $data = $adhesion->decode_data();
        if (empty($data->medicalcertificatedate)) {
            $data->medicalcertificatedate = time() - YEARSECS * 2;
        }

        // Nom du médecin.
        $mform->addElement('text', 'doctorname', get_string('doctor_name', 'local_apsolu'));
        $mform->setType('doctorname', PARAM_TEXT);
        $mform->addRule('doctorname', get_string('required'), 'required', null, 'client');

        // RPPS du médecin.
        $mform->addElement('text', 'doctorrpps', get_string('doctor_rpps', 'local_apsolu'));
        $mform->setType('doctorrpps', PARAM_TEXT);
        $mform->addRule('doctorrpps', get_string('required'), 'required', null, 'client');

        // Date du certificat médical.
        $mform->addElement('date_selector', 'medicalcertificatedate', get_string('medical_certificate_date', 'local_apsolu'));
        $mform->setType('medicalcertificatedate', PARAM_TEXT);
        $mform->addRule('medicalcertificatedate', get_string('required'), 'required', null, 'client');

        // Certificat médical.
        $label = get_string('medical_certificate', 'local_apsolu');
        if ($freeze === true) {
            $mform->hardFreeze();

            $fs = get_file_storage();
            $context = context_course::instance($course->id, MUST_EXIST);
            list($component, $filearea, $itemid) = ['local_apsolu', 'medicalcertificate', $USER->id];
            $sort = 'itemid, filepath, filename';
            $files = $fs->get_area_files($context->id, $component, $filearea, $itemid, $sort, $includedirs = false);
            $items = [];
            foreach ($files as $file) {
                $url = moodle_url::make_pluginfile_url($context->id, $component, $filearea, $itemid, '/',
                    $file->get_filename(), $forcedownload = false, $includetoken = false);
                $items[] = html_writer::link($url, $file->get_filename());
            }

            if (empty($items) === true) {
                $mform->addElement('static', 'medicalcertificate', $label, get_string('no_files', 'local_apsolu'));
            } else {
                $attributes = ['class' => 'list-unstyled'];
                $mform->addElement('static', 'medicalcertificate', $label, html_writer::alist($items, $attributes));
            }
        } else {
            $attributes = null;
            $options = self::get_filemanager_options($course, $context);

            $mform->addElement('filemanager', 'medicalcertificate_filemanager', $label, $attributes, $options);
        }

        // Champs cachés.
        $mform->addElement('hidden', 'step', APSOLU_PAGE_MEDICAL_CERTIFICATE);
        $mform->setType('step', PARAM_INT);

        // Période de validité du certificat médical (en mois).
        $mform->addElement('hidden', 'validityperiod', $validityperiod);
        $mform->setType('validityperiod', PARAM_INT);

        // Submit buttons.
        $attributes = ['class' => 'btn btn-default'];
        $buttonarray[] = &$mform->createElement('submit', 'save', get_string('save'), $attributes);

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);

        // Set default values.
        $this->set_data($data);
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

        $options = [];
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

        if ($data['medicalcertificatedate'] > time()) {
            $errors['medicalcertificatedate'] = get_string('the_date_of_the_medical_certificate_cannot_be_later_than_today', 'local_apsolu'); // phpcs:ignore
            return $errors;
        }

        // Note : il est possible de modifier la période lors de l'envoi du formulaire. Pour bien faire, il faudrait calculer
        // ici la validité attendue.
        // Avec cette vérification, au pire, une personne avec des contraintes médicales pourraient valider le dépôt d'un
        // certificat de moins d'un an (au lieu de 6 mois).
        if (in_array($data['validityperiod'], [6, 12], $strict = true) == false) {
            $errors['medicalcertificatedate'] = get_string('errorinvaliddate', 'calendar');
            return $errors;
        }

        $now = new DateTime();
        $date = clone $now;
        $date->setTimestamp($data['medicalcertificatedate']);
        $date->add(new DateInterval(sprintf('P%sM', $data['validityperiod'])));
        if ($date < $now) {
            $errors['medicalcertificatedate'] = get_string('you_must_present_a_medical_certificate_of_less_than_X_months', 'local_apsolu', $data['validityperiod']); // phpcs:ignore
            return $errors;
        }

        return $errors;
    }
}
