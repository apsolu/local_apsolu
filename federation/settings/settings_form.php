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
 * Classe pour le formulaire permettant de configurer les paramètres du module FFSU.
 *
 * @package    local_apsolu
 * @copyright  2023 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\federation\adhesion as Adhesion;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

/**
 * Classe pour le formulaire permettant de configurer les paramètres du module FFSU.
 *
 * @package    local_apsolu
 * @copyright  2023 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_settings_form extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    protected function definition() {
        $mform = $this->_form;

        list($defaults) = $this->_customdata;

        // Partie formulaire d'adhésion.
        $mform->addElement('header', 'membership_form', get_string('membership_form', 'local_apsolu'));

        // Positionnement des valeurs par défaut.
        $fields = array();
        $fields['insurance'] = get_string('insurance', 'local_apsolu');
        $fields['sportlicense'] = get_string('sport_license', 'local_apsolu');
        $fields['managerlicense'] =  get_string('manager_license', 'local_apsolu');
        $fields['managerlicensetype'] = get_string('manager_license_type', 'local_apsolu');
        $fields['refereelicense'] =  get_string('referee_license', 'local_apsolu');
        $fields['starlicense'] =  get_string('star_license', 'local_apsolu');

        $mform->addElement('html', sprintf('<h4>%s</h4>', get_string('default_value_of_fields', 'local_apsolu')));
        foreach ($fields as $field => $labelname) {
            $name = sprintf('%s_field_default', $field);
            $label = get_string('default_value_of_field_X', 'local_apsolu', $labelname);

            if ($field === 'managerlicensetype') {
                $managertypes = Adhesion::get_manager_types();
                $mform->addElement('select', $name, $label, $managertypes);
            } else {
                $mform->addElement('selectyesno', $name, $label);
            }
            $mform->setType($name, PARAM_INT);
        }

        // Positionnement de la visibilité par défaut.
        $fields = array();
        $fields['insurance'] = get_string('insurance', 'local_apsolu');
        $fields['instagram'] = get_string('instagram', 'local_apsolu');
        $fields['otherfederation'] = get_string('other_federation', 'local_apsolu');
        $fields['sportlicense'] = get_string('sport_license', 'local_apsolu');
        $fields['managerlicense'] =  get_string('manager_license', 'local_apsolu');
        $fields['managerlicensetype'] = get_string('manager_license_type', 'local_apsolu');
        $fields['refereelicense'] =  get_string('referee_license', 'local_apsolu');
        $fields['starlicense'] =  get_string('star_license', 'local_apsolu');

        $options = array();
        $options[Adhesion::FIELD_VISIBLE] = get_string('visible', 'local_apsolu');
        $options[Adhesion::FIELD_HIDDEN] = get_string('hidden', 'local_apsolu');
        $options[Adhesion::FIELD_LOCKED] = get_string('locked', 'local_apsolu');

        $mform->addElement('html', sprintf('<h4>%s</h4>', get_string('visibility_of_fields', 'local_apsolu')));
        foreach ($fields as $field => $labelname) {
            $name = sprintf('%s_field_visibility', $field);
            $label = get_string('visibility_of_field_X', 'local_apsolu', $labelname);
            $mform->addElement('select', $name, $label, $options);
        }

        // Partie certificat médical.
        $mform->addElement('header', 'medical_certificate', get_string('medical_certificate', 'local_apsolu'));

        // Types de fichiers acceptés pour le dépot de certificat médical.
        $label = get_string('accepted_file_types', 'local_apsolu');
        $mform->addElement('filetypes', 'ffsu_acceptedfiles', $label);
        $mform->addHelpButton('ffsu_acceptedfiles', 'accepted_file_types', 'local_apsolu');
        $mform->setType('ffsu_acceptedfiles', PARAM_TEXT);
        $mform->addRule('ffsu_acceptedfiles', get_string('required'), 'required', null, 'client');

        // Nombre maximum de fichiers à remettre pour le dépot de certificat médical.
        $options = array();
        for ($i = 1; $i <= get_config('assignsubmission_file', 'maxfiles'); $i++) {
            $options[$i] = $i;
        }

        $label = get_string('maximum_number_of_uploaded_files', 'local_apsolu');
        $mform->addElement('select', 'ffsu_maxfiles', $label, $options);
        $mform->setType('ffsu_maxfiles', PARAM_INT);
        $mform->addRule('ffsu_maxfiles', get_string('required'), 'required', null, 'client');

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'));

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

        // Hidden fields.
        $mform->addElement('hidden', 'page', 'settings');
        $mform->setType('page', PARAM_ALPHANUM);

        // Set default values.
        $this->set_data($defaults);
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

        if ($data['managerlicense_field_visibility'] != Adhesion::FIELD_HIDDEN) {
            return $errors;
        }

        if ($data['managerlicense_field_visibility'] !== $data['managerlicensetype_field_visibility']) {
            $params = array();
            $params['field1'] = get_string('manager_license', 'local_apsolu');
            $params['field2'] = get_string('manager_license_type', 'local_apsolu');
            $errors['managerlicensetype_field_visibility'] = get_string('the_field_X_is_hidden_the_field_Y_must_be_hidden', 'local_apsolu', $params);
        }

        return $errors;
    }
}