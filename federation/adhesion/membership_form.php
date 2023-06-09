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

use local_apsolu\core\federation\adhesion as Adhesion;

defined('MOODLE_INTERNAL') || die();

require_once $CFG->libdir.'/formslib.php';

/**
 * Classe pour le formulaire permettant d'exporter les licences FFSU.
 *
 * @package    local_apsolu
 * @copyright  2022 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_federation_membership extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    function definition () {
        global $USER;

        $mform = $this->_form;
        list($adhesion, $sexes, $disciplines, $mainsports, $managertypes, $starlicensevalues,
            $sportswithconstraints, $readonly) = $this->_customdata;

        if ($readonly === true) {
            $messages = $adhesion::get_contacts();
            $mform->addElement('html', sprintf('<div class="alert alert-info">%s</div>', implode(' ', $messages)));
            $mform->hardFreeze();
        }

        // Prénom.
        $mform->addElement('text', 'firstname', get_string('firstname'), $attributes = array('readonly' => 1));
        $mform->setType('firstname', PARAM_TEXT);
        $mform->setDefault('firstname', $USER->firstname);

        // Nom.
        $mform->addElement('text', 'lastname', get_string('lastname'), $attributes = array('readonly' => 1));
        $mform->setType('lastname', PARAM_TEXT);
        $mform->setDefault('lastname', $USER->lastname);

        // Sport principal.
        $mform->addElement('select', 'mainsport', get_string('main_sport', 'local_apsolu'), $mainsports);
        $mform->setType('mainsport', PARAM_INT);

        // Sport complémentaire.
        $list = html_writer::alist($sportswithconstraints);
        $mform->addElement('selectyesno', 'complementaryconstraintsport', get_string('do_i_plan_to_practice_one_of_the_following_sports_in_addition', 'local_apsolu', $list));
        $mform->setType('complementaryconstraintsport', PARAM_INT);

        // Autre fédération.
        $visibility = get_config('local_apsolu', 'otherfederation_field_visibility');
        if ($visibility !== Adhesion::FIELD_HIDDEN) {
            $mform->addElement('text', 'otherfederation', get_string('other_federation', 'local_apsolu'), array('size' => 50));
            $mform->setType('otherfederation', PARAM_TEXT);
            if ($visibility === Adhesion::FIELD_LOCKED) {
                $mform->freeze('otherfederation');
            }
        }

        // Licence sport.
        $visibility = get_config('local_apsolu', 'sportlicense_field_visibility');
        if ($visibility !== Adhesion::FIELD_HIDDEN) {
            $mform->addElement('selectyesno', 'sportlicense', get_string('sport_license', 'local_apsolu'));
            $mform->setType('sportlicense', PARAM_INT);

            if ($visibility === Adhesion::FIELD_LOCKED) {
                $mform->freeze('sportlicense');
            }
        }

        // Licence dirigeant.
        $visibility = get_config('local_apsolu', 'managerlicense_field_visibility');
        if ($visibility !== Adhesion::FIELD_HIDDEN) {
            $mform->addElement('selectyesno', 'managerlicense', get_string('manager_license', 'local_apsolu'));
            $mform->setType('managerlicense', PARAM_INT);

            if ($visibility === Adhesion::FIELD_LOCKED) {
                $mform->freeze('managerlicense');
            }
        }

        // Type de licence dirigeant.
        $visibility = get_config('local_apsolu', 'managerlicensetype_field_visibility');
        if ($visibility !== Adhesion::FIELD_HIDDEN) {
            $mform->addElement('select', 'managerlicensetype', get_string('manager_license_type', 'local_apsolu'), $managertypes);
            $mform->setType('managerlicensetype', PARAM_INT);
            $mform->disabledIf('managerlicensetype', 'managerlicense', 'eq', 0);

            if ($visibility === Adhesion::FIELD_LOCKED) {
                $mform->freeze('managerlicensetype');
            }
        }

        // Licence arbitre.
        $visibility = get_config('local_apsolu', 'refereelicense_field_visibility');
        if ($visibility !== Adhesion::FIELD_HIDDEN) {
            $mform->addElement('selectyesno', 'refereelicense', get_string('referee_license', 'local_apsolu'));
            $mform->setType('refereelicense', PARAM_INT);

            if ($visibility === Adhesion::FIELD_LOCKED) {
                $mform->freeze('refereelicense');
            }
        }

        // Licence étoile.
        $visibility = get_config('local_apsolu', 'starlicense_field_visibility');
        if ($visibility !== Adhesion::FIELD_HIDDEN) {
            $mform->addElement('select', 'starlicense', get_string('star_license', 'local_apsolu'), $starlicensevalues);
            $mform->setType('starlicense', PARAM_TEXT);

            if ($visibility === Adhesion::FIELD_LOCKED) {
                $mform->freeze('starlicense');
            }
        }

        // Assurance.
        $visibility = get_config('local_apsolu', 'insurance_field_visibility');
        if ($visibility !== Adhesion::FIELD_HIDDEN) {
            $mform->addElement('selectyesno', 'insurance', get_string('insurance', 'local_apsolu'));
            $mform->setType('insurance', PARAM_INT);

            if ($visibility === Adhesion::FIELD_LOCKED) {
                $mform->freeze('insurance');
            }
        }

        // Date de naissance.
        $mform->addElement('date_selector', 'birthday', get_string('birthday', 'local_apsolu'));
        $mform->setType('birthday', PARAM_TEXT);

        // Sexe.
        $mform->addElement('select', 'sex', get_string('sex', 'local_apsolu'), $sexes);
        $mform->setType('sex', PARAM_TEXT);
        $mform->addRule('sex', get_string('required'), 'required', null, 'client');

        // Discipline.
        $mform->addElement('select', 'disciplineid', get_string('discipline_cursus', 'local_apsolu'), $disciplines);
        $mform->setType('disciplineid', PARAM_INT);
        $mform->addRule('disciplineid', get_string('required'), 'required', null, 'client');

        // Adresse postale 1.
        $mform->addElement('text', 'address1', get_string('address1', 'local_apsolu'));
        $mform->setType('address1', PARAM_TEXT);
        $mform->addRule('address1', get_string('required'), 'required', null, 'client');

        // Adresse postale 2.
        $mform->addElement('text', 'address2', get_string('address2', 'local_apsolu'));
        $mform->setType('address2', PARAM_TEXT);

        // Code postal.
        $mform->addElement('text', 'postalcode', get_string('postal_code', 'local_apsolu'));
        $mform->setType('postalcode', PARAM_TEXT);
        $mform->addRule('postalcode', get_string('required'), 'required', null, 'client');

        // Ville.
        $mform->addElement('text', 'city', get_string('city'));
        $mform->setType('city', PARAM_TEXT);
        $mform->addRule('city', get_string('required'), 'required', null, 'client');

        // Téléphone.
        $mform->addElement('text', 'phone', get_string('phone', 'local_apsolu'));
        $mform->setType('phone', PARAM_TEXT);

        // Instagram.
        $visibility = get_config('local_apsolu', 'instagram_field_visibility');
        if ($visibility !== Adhesion::FIELD_HIDDEN) {
            $mform->addElement('text', 'instagram', get_string('instagram', 'local_apsolu'));
            $mform->setType('instagram', PARAM_TEXT);
            if ($visibility === Adhesion::FIELD_LOCKED) {
                $mform->freeze('instagram');
            }
        }

        // Autorisation / droit à l'image.
        $mform->addElement('selectyesno', 'usepersonaldata', get_string('permission_to_use_my_personal_data', 'local_apsolu'));
        $mform->addHelpButton('usepersonaldata', 'permission_to_use_my_personal_data', 'local_apsolu');
        $mform->setType('usepersonaldata', PARAM_INT);

        // Champs cachés.
        $mform->addElement('hidden', 'step', 1);
        $mform->setType('step', PARAM_INT);

        // Submit buttons.
        $attributes = array('class' => 'btn btn-default');
        $buttonarray[] = &$mform->createElement('submit', 'save', get_string('save'), $attributes);

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

        // Set default values.
        $this->set_data($adhesion);
    }
}
