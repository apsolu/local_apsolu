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

use local_apsolu\core\federation\adhesion;
use local_apsolu\core\federation\activity;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

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
    protected function definition() {
        global $USER;

        $mform = $this->_form;
        [$adhesion, $readonly] = $this->_customdata;

        if ($readonly === true) {
            $messages = $adhesion::get_contacts();
            $mform->addElement('html', sprintf('<div class="alert alert-info">%s</div>', implode(' ', $messages)));
            $mform->hardFreeze();
        }

        // IDENTITÉ.
        $mform->addElement('header', 'identity', get_string('identity', 'local_apsolu'));

        // Civilité.
        $mform->addElement('select', 'title', get_string('user_title', 'local_apsolu'), Adhesion::get_user_titles());
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', get_string('required'), 'required', null, 'client');

        // Nom.
        $mform->addElement('text', 'lastname', get_string('lastname'), $attributes = ['disabled' => 1]);
        $mform->setType('lastname', PARAM_TEXT);
        $mform->setDefault('lastname', $USER->lastname);

        // Prénom.
        $mform->addElement('text', 'firstname', get_string('firstname'), $attributes = ['disabled' => 1]);
        $mform->setType('firstname', PARAM_TEXT);
        $mform->setDefault('firstname', $USER->firstname);

        // Date de naissance.
        $mform->addElement('date_selector', 'birthday', get_string('birthday', 'local_apsolu'));
        $mform->setType('birthday', PARAM_TEXT);
        $mform->addRule('birthday', get_string('required'), 'required', null, 'client');

        // Nationalité.
        $values = array_merge(['' => ''], Adhesion::get_nationalities());
        $mform->addElement('autocomplete', 'nationality', get_string('nationality', 'local_apsolu'), $values);
        $mform->setType('nationality', PARAM_TEXT);

        // COORDONNÉES PERSONNELLES.
        $mform->addElement('header', 'personal_address', get_string('personal_address', 'local_apsolu'));

        if (false) {
            // N° de voie.
            $mform->addElement('text', 'tracknumber', get_string('track_number', 'local_apsolu'));
            $mform->setType('tracknumber', PARAM_TEXT);

            // Type de voie.
            $mform->addElement('select', 'tracktype', get_string('track_type', 'local_apsolu'), Adhesion::get_track_type());
            $mform->setType('tracktype', PARAM_TEXT);

            // Nom de voie.
            $mform->addElement('text', 'trackname', get_string('track_name', 'local_apsolu'));
            $mform->setType('trackname', PARAM_TEXT);

            // Bâtiment.
            $mform->addElement('text', 'building', get_string('building', 'local_apsolu'));
            $mform->setType('building', PARAM_TEXT);

            // Escalier.
            $mform->addElement('text', 'staircase', get_string('staircase', 'local_apsolu'));
            $mform->setType('staircase', PARAM_TEXT);

            // Lieu dit.
            $mform->addElement('text', 'lieudit', get_string('lieudit', 'local_apsolu'));
            $mform->setType('lieudit', PARAM_TEXT);

            // Code postal.
            $mform->addElement('text', 'postalcode', get_string('postal_code', 'local_apsolu'));
            $mform->setType('postalcode', PARAM_TEXT);

            // Ville.
            $mform->addElement('text', 'city', get_string('city'));
            $mform->setType('city', PARAM_TEXT);

            // Pays.
            $mform->addElement('autocomplete', 'country', get_string('country'), Adhesion::get_countries());
            $mform->setType('country', PARAM_TEXT);
        }

        // Mail.
        $mform->addElement('text', 'mail', get_string('mail', 'local_apsolu'), $attributes = ['disabled' => 1, 'size' => '50']);
        $mform->setType('mail', PARAM_TEXT);
        $mform->setDefault('mail', $USER->email);

        if (false) {
            // Mail pro.
            $mform->addElement('text', 'workmail', get_string('work_mail', 'local_apsolu'), $attributes = ['size' => '50']);
            $mform->setType('workmail', PARAM_TEXT);
            $mform->setDefault('workmail', $USER->eworkmail);
            $mform->addRule('workmail', get_string('required'), 'required', null, 'client');
        }

        if (false) {
            // Téléphone fixe.
            $mform->addElement('text', 'phone1', get_string('phone1', 'local_apsolu'));
            $mform->setType('phone1', PARAM_TEXT);
            $mform->addRule('phone1', get_string('required'), 'required', null, 'client');
            $mform->addHelpButton('phone1', 'phone', 'local_apsolu');
        }

        // Téléphone mobile.
        $mform->addElement('text', 'phone2', get_string('phone2', 'local_apsolu'));
        $mform->setType('phone2', PARAM_TEXT);
        $mform->addRule('phone2', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('phone2', 'phone', 'local_apsolu');

        // INSCRIPTION.
        $mform->addElement('header', 'federation', get_string('federation', 'local_apsolu'));

        // Etudiant.
        $visibility = get_config('local_apsolu', 'licenseetype_field_visibility');
        if ($visibility !== Adhesion::FIELD_HIDDEN) {
            $mform->addElement('select', 'licenseetype', get_string('i_am', 'local_apsolu'), Adhesion::get_licensee_types());
            $mform->setType('licenseetype', PARAM_INT);
            $mform->addRule('licenseetype', get_string('required'), 'required', null, 'client');

            if ($visibility === Adhesion::FIELD_LOCKED && $readonly === false) {
                // Note : on teste la variable $readonly afin de ne pas freeze() le champ d'un formulaire hardFreeze().
                // Si c'est le cas, moodleform lève un warning PHP.
                $mform->freeze('licenseetype');
            }
        }

        // Handicap.
        $mform->addElement('selectyesno', 'handicap', get_string('do_you_have_a_disability', 'local_apsolu'));
        $mform->addRule('handicap', get_string('required'), 'required', null, 'client');

        // Type de licence.
        $visibility = get_config('local_apsolu', 'licensetype_field_visibility');
        if ($visibility !== Adhesion::FIELD_HIDDEN) {
            $options = ['multiple' => true];
            $mform->addElement(
                'autocomplete',
                'licensetype',
                get_string('license_type', 'local_apsolu'),
                Adhesion::get_license_types(),
                $options
            );
            $mform->setType('licensetype', PARAM_TEXT);
            $mform->addRule('licensetype', get_string('required'), 'required', null, 'client');

            if ($visibility === Adhesion::FIELD_LOCKED && $readonly === false) {
                // Note : on teste la variable $readonly afin de ne pas freeze() le champ d'un formulaire hardFreeze().
                // Si c'est le cas, moodleform lève un warning PHP.
                $mform->freeze('licensetype');
            }
        }

        // Honorability.
        $mform->addElement('hidden', 'honorability', 0);
        $mform->setType('honorability', PARAM_INT);

        // Nom de naissance.
        $mform->addElement('text', 'birthname', get_string('birthname', 'local_apsolu'));
        $mform->setType('birthname', PARAM_TEXT);
        $mform->hideIf('birthname', 'honorability', 'eq', '0');

        // Pays de naissance.
        $values = array_merge(['' => ''], Adhesion::get_countries());
        $mform->addElement('autocomplete', 'birthcountry', get_string('birthcountry', 'local_apsolu'), $values);
        $mform->setType('birthcountry', PARAM_TEXT);
        $mform->hideIf('birthcountry', 'honorability', 'eq', '0');

        // Commune de naissance (pour les français).
        $options = [
            'ajax' => 'local_apsolu/federation_adhesion_municipalities_form',
            'multiple' => false,
            'valuehtmlcallback' => function ($value) {
                global $DB;

                $municipalities = $DB->get_records('apsolu_municipalities', ['inseecode' => $value]);
                if (count($municipalities) === 0) {
                    return false;
                }

                $municipality = current($municipalities);

                return sprintf('%s (%s)', $municipality->name, $municipality->departmentid);
            },
        ];
        $mform->addElement('autocomplete', 'birthtown', get_string('birthtown', 'local_apsolu'), [], $options);
        $mform->setType('birthtown', PARAM_TEXT);
        $mform->hideIf('birthtown', 'honorability', 'eq', '0');
        $mform->hideIf('birthtown', 'birthcountry', 'neq', 'FR');

        // Lieu de naissance (pour les non français).
        $mform->addElement('text', 'birthplace', get_string('birthplace', 'local_apsolu'));
        $mform->setType('birthplace', PARAM_TEXT);
        $mform->hideIf('birthplace', 'honorability', 'eq', '0');
        $mform->hideIf('birthplace', 'birthcountry', 'eq', 'FR');

        // Activités.
        $options = ['multiple' => true];
        $helmetwhitecross = '&#9937;';
        $heavygreekcross = '&#10010;';
        $stethoscope = '&#129658;';
        $values = [];
        $restrictionactivities = [];
        foreach (Activity::get_records([], $sort = 'name') as $record) {
            $values[$record->code] = $record->name;

            if (empty($record->restriction) === false) {
                $restrictionactivities[] = $record->name;
                $values[$record->code] .= ' ' . $stethoscope;
            }
        }
        $mform->addElement('autocomplete', 'activity', get_string('discipline', 'local_apsolu'), $values, $options);
        $mform->addRule('activity', get_string('required'), 'required', null, 'client');
        $mform->setType('activity', PARAM_TEXT);
        $label = get_string('discipline_additional_information', 'local_apsolu', implode(', ', $restrictionactivities));
        $mform->addElement('static', 'activityinfo', '', $label);

        // Avec IA (Individuelle Accident).
        $visibility = get_config('local_apsolu', 'insurance_field_visibility');
        if ($visibility !== Adhesion::FIELD_HIDDEN) {
            $mform->addElement('selectyesno', 'insurance', get_string('insurance', 'local_apsolu'));
            $mform->addRule('insurance', get_string('required'), 'required', null, 'client');

            if ($visibility === Adhesion::FIELD_LOCKED && $readonly === false) {
                // Note : on teste la variable $readonly afin de ne pas freeze() le champ d'un formulaire hardFreeze().
                // Si c'est le cas, moodleform lève un warning PHP.
                $mform->freeze('insurance');
            }
        }

        // Autre fédération.
        $visibility = get_config('local_apsolu', 'otherfederation_field_visibility');
        if ($visibility !== Adhesion::FIELD_HIDDEN) {
            $values = array_merge(['' => ''], Adhesion::get_other_federations());
            $mform->addElement('select', 'otherfederation', get_string('other_federation', 'local_apsolu'), $values);
            $mform->setType('otherfederation', PARAM_TEXT);
            if ($visibility === Adhesion::FIELD_LOCKED && $readonly === false) {
                // Note : on teste la variable $readonly afin de ne pas freeze() le champ d'un formulaire hardFreeze().
                // Si c'est le cas, moodleform lève un warning PHP.
                $mform->freeze('otherfederation');
            }
        }

        // ETUDES.
        $mform->addElement('header', 'studies', get_string('studies', 'local_apsolu'));
        $mform->setExpanded('studies', $expanded = true);

        // Discipline / Cursus.
        $values = array_merge(['' => ''], Adhesion::get_disciplines());
        $mform->addElement('select', 'cursus', get_string('discipline_cursus', 'local_apsolu'), $values);
        $mform->setType('cursus', PARAM_TEXT);

        // Niveau.
        $values = array_merge(['' => ''], Adhesion::get_study_cycles());
        $mform->addElement('select', 'studycycle', get_string('study_cycle', 'local_apsolu'), $values);
        $mform->setType('studycycle', PARAM_TEXT);

        // AUTORISATIONS.
        $mform->addElement('header', 'autorization', get_string('authorizations', 'local_apsolu'));

        // Acceptation des textes fédéraux.
        $mform->addElement('selectyesno', 'federaltexts', get_string('federal_texts', 'local_apsolu'));
        $mform->setType('federaltexts', PARAM_INT);
        $mform->addRule('federaltexts', get_string('required'), 'required', null, 'client');
        $mform->addElement('static', 'federaltextsinfo', '', get_string('federal_texts_help', 'local_apsolu'));

        // Acceptation des conditions d’utilisation des données.
        $mform->addElement('selectyesno', 'policyagreed', get_string('terms_of_use_for_data', 'local_apsolu'));
        $mform->setType('policyagreed', PARAM_INT);
        $mform->addRule('policyagreed', get_string('required'), 'required', null, 'client');
        $mform->addElement('static', 'policyagreedinfo', '', get_string('terms_of_use_for_data_help', 'local_apsolu'));

        // Autorisation offres commerciales.
        $mform->addElement('selectyesno', 'commercialoffers', get_string('commercial_offers', 'local_apsolu'));
        $mform->addHelpButton('commercialoffers', 'commercial_offers', 'local_apsolu');
        $mform->setType('commercialoffers', PARAM_INT);

        // Autorisation / droit à l'image.
        $mform->addElement('selectyesno', 'usepersonalimage', get_string('image_rights', 'local_apsolu'));
        $mform->addHelpButton('usepersonalimage', 'image_rights', 'local_apsolu');
        $mform->setType('usepersonalimage', PARAM_INT);

        // Autorisation newsletter.
        $mform->addElement('selectyesno', 'newsletter', get_string('newsletter', 'local_apsolu'));
        $mform->addHelpButton('newsletter', 'newsletter', 'local_apsolu');
        $mform->setType('newsletter', PARAM_INT);

        // Champs cachés.
        $mform->addElement('hidden', 'step', APSOLU_PAGE_MEMBERSHIP);
        $mform->setType('step', PARAM_INT);

        // Submit buttons.
        $attributes = ['class' => 'btn btn-default'];
        $buttonarray[] = &$mform->createElement('submit', 'save', get_string('save'), $attributes);

        if ($readonly === false) {
            // Bouton d'annulation et de désinscription.
            $attributes = new stdClass();
            $attributes->href = (string) new moodle_url('/local/apsolu/federation/adhesion/cancel.php');
            $attributes->class = 'btn btn-danger';
            $label = get_string('cancel_and_unenrol_link', 'local_apsolu', $attributes);
            $buttonarray[] = &$mform->createElement('static', '', '', $label);
        }

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);

        // Si la date de naissance est saisie et valide dans le profil, on passe le champ date de naissance en lecteur seule.
        $birthday = '';
        $customfields = profile_user_record($USER->id);
        if (isset($customfields->apsolubirthday) === true) {
            $datetime = DateTime::createFromFormat('d/m/Y', $customfields->apsolubirthday);
            if ($datetime !== false) {
                $datetime->setTime(0, 0, 0);
                $birthday = $datetime->getTimestamp();
            }
        }

        $data = $adhesion->decode_data();
        if ($birthday === '') {
            $data->birthday = $adhesion->birthday;
        } else {
            $data->birthday = $birthday;
            $mform->freeze('birthday');
        }

        // Set default values.
        $this->set_data($data);
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

        // Contrôle la validité du champ date de naissance.
        if ($data['birthday'] > (time() - DAYSECS)) {
            $label = get_string('birthday', 'local_apsolu');

            $errors['birthday'] = get_string('the_field_X_has_an_invalid_value', 'local_apsolu', $label);
        }

        // Contrôle que le champ téléphone contient uniquement 10 chiffres.
        $phone = preg_replace('/[^0-9]/', '', $data['phone2']);
        if (strlen($phone) !== 10) {
            $label = get_string('phone2', 'local_apsolu');

            $errors['phone2'] = get_string('the_field_X_has_an_invalid_value', 'local_apsolu', $label);
        }

        // Si une licence dirigeant, arbitre ou encadrant a été prise...
        if (Adhesion::require_honorability($data['licensetype']) === true) {
            // Contrôle que l'utilisateur n'a pas essayé de mettre une date de naissance au lieu d'un nom de naissance.
            if (preg_match('#[0-9]/#', $data['birthname']) === 1 || empty($data['birthname']) === true) {
                $label = get_string('birthname', 'local_apsolu');

                $errors['birthname'] = get_string('the_field_X_has_an_invalid_value', 'local_apsolu', $label);
            }

            // Contrôle que le pays de naissance a été saisi.
            if (empty($data['birthcountry']) === true) {
                $label = get_string('birthcountry', 'local_apsolu');

                $errors['birthcountry'] = get_string('the_field_X_has_an_invalid_value', 'local_apsolu', $label);
            } else if ($data['birthcountry'] === 'FR') {
                // Contrôle que la commune de naissance a été saisie.
                if (empty($data['birthtown']) === true) {
                    $label = get_string('birthtown', 'local_apsolu');

                    $errors['birthtown'] = get_string('the_field_X_has_an_invalid_value', 'local_apsolu', $label);
                }
            } else {
                // Contrôle que le lieu de naissance a été saisi.
                if (empty($data['birthplace']) === true) {
                    $label = get_string('birthplace', 'local_apsolu');

                    $errors['birthplace'] = get_string('the_field_X_has_an_invalid_value', 'local_apsolu', $label);
                }
            }
        }

        if (empty($data['federaltexts']) === true) {
            $errors['federaltexts'] = get_string('you_must_accept_the_federal_texts', 'local_apsolu');
        }

        if (empty($data['policyagreed']) === true) {
            $errors['policyagreed'] = get_string('you_must_accept_the_terms_of_use_for_data', 'local_apsolu');
        }

        return $errors;
    }
}
