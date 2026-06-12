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

use core\output\html_writer;
use local_apsolu\core\federation\course as ffsucourse;
use local_apsolu\core\reset;

require_once($CFG->libdir . '/formslib.php');

/**
 * Classe pour le formulaire permettant de définir les options de réinitialisation annuelle des espaces cours.
 *
 * @package    local_apsolu
 * @copyright  2026 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_reset_form extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    protected function definition() {
        global $CFG;

        $mform = $this->_form;
        [$default, $minimumdatetime] = $this->_customdata;

        // 1. Suppression des utilisateurs et des inscriptions.
        $mform->addElement('header', 'users_and_enrolments', get_string('settings_reset_users_and_enrolments', 'local_apsolu'));
        $mform->setExpanded('users_and_enrolments', $expanded = true);

        // Suppression de tous les utilisateurs sauf gestionnaires et enseignants.
        $mform->addElement('selectyesno', 'allusers', get_string('settings_reset_allusers', 'local_apsolu'));
        $mform->addHelpButton('allusers', 'settings_reset_allusers', 'local_apsolu');
        $mform->setType('allusers', PARAM_INT);

        // Suppression des comptes utilisateurs inactifs (> un an).
        $mform->addElement('selectyesno', 'oldusers', get_string('settings_reset_oldusers', 'local_apsolu'));
        $mform->addHelpButton('oldusers', 'settings_reset_oldusers', 'local_apsolu');
        $mform->setType('oldusers', PARAM_INT);
        $mform->disabledIf('oldusers', 'allusers', 'eq', '1');

        // Suppression des comptes utilisateurs locaux (auth = "manual").
        $mform->addElement('selectyesno', 'manualusers', get_string('settings_reset_manualusers', 'local_apsolu'));
        $mform->addHelpButton('manualusers', 'settings_reset_manualusers', 'local_apsolu');
        $mform->setType('manualusers', PARAM_INT);
        $mform->disabledIf('manualusers', 'allusers', 'eq', '1');

        // Suppression des inscriptions réalisées avec la méthode "select.
        $mform->addElement(
            'selectyesno',
            'userselectenrolments',
            get_string('settings_reset_userselectenrolments', 'local_apsolu')
        );
        $mform->setType('userselectenrolments', PARAM_INT);

        // Suppression des méthodes d'inscriptions par voeux (enrol "select").
        $mform->addElement(
            'selectyesno',
            'selectenrolments',
            get_string('settings_reset_selectenrolments', 'local_apsolu')
        );
        $mform->setType('selectenrolments', PARAM_INT);

        // Suppression des membres des cohortes.
        $mform->addElement('selectyesno', 'cohortmembers', get_string('settings_reset_cohortmembers', 'local_apsolu'));
        $mform->setType('cohortmembers', PARAM_INT);

        // 2. Suppression des données personnelles.
        $mform->addElement('header', 'user_infos', get_string('settings_reset_user_infos', 'local_apsolu'));
        $mform->setExpanded('user_infos', $expanded = true);

        // Réinitialiser le témoin d'acceptation des recommandations médicales.
        $mform->addElement('selectyesno', 'userpolicies', get_string('settings_reset_userpolicies', 'local_apsolu'));
        $mform->setType('userpolicies', PARAM_INT);

        // Suppression des infos des profils utilisateurs.
        $mform->addElement('selectyesno', 'userprofiles', get_string('settings_reset_userprofiles', 'local_apsolu'));
        $mform->setType('userprofiles', PARAM_INT);
        $mform->addHelpButton('userprofiles', 'settings_reset_userprofiles', 'local_apsolu');
        $mform->disabledIf('userprofiles', 'allusers', 'eq', '1');

        // Suppression des présences.
        $mform->addElement('selectyesno', 'userattendances', get_string('settings_reset_userattendances', 'local_apsolu'));
        $mform->setType('userattendances', PARAM_INT);

        // Suppression des notes.
        $mform->addElement('selectyesno', 'usergrades', get_string('settings_reset_usergrades', 'local_apsolu'));
        $mform->setType('usergrades', PARAM_INT);

        // Suppression des payments des utilisateurs.
        $mform->addElement('selectyesno', 'userpayments', get_string('settings_reset_userpayments', 'local_apsolu'));
        $mform->setType('userpayments', PARAM_INT);

        // 3. Réinitialisation des activités et contenus.
        $mform->addElement('header', 'courses_global', get_string('settings_reset_courses_global', 'local_apsolu'));
        $mform->setExpanded('courses_global', $expanded = true);

        // Masquer les créneaux des activités.
        $mform->addElement(
            'selectyesno',
            'coursesvisibility',
            get_string('settings_reset_coursesvisibility', 'local_apsolu')
        );
        $mform->addHelpButton('coursesvisibility', 'settings_reset_coursesvisibility', 'local_apsolu');
        $mform->setType('coursesvisibility', PARAM_INT);

        // Suppression des sessions de cours.
        $mform->addElement('selectyesno', 'sessions', get_string('settings_reset_sessions', 'local_apsolu'));
        $mform->setType('sessions', PARAM_INT);

        // Suppression du cours FFSU.
        $federationcourse = new ffsucourse();
        $federationcourseid = $federationcourse->get_courseid();
        // Pour les instances qui en disposent uniquement.
        if (empty($federationcourseid) === false) {
            // Masquer les créneaux des activités.
            $mform->addElement(
                'selectyesno',
                'ffsu',
                get_string('settings_reset_ffsu', 'local_apsolu')
            );
            $mform->addHelpButton('ffsu', 'settings_reset_ffsu', 'local_apsolu');
            $mform->setType('ffsu', PARAM_INT);
        }

        // Conserver les liens méta-cours.
        $mform->addElement('selectyesno', 'metaenrolments', get_string('settings_reset_metaenrolments', 'local_apsolu'));
        $mform->setType('metaenrolments', PARAM_INT);

        $mform->closeHeaderBefore('buttonar');

        // Submit : enregistrer et activer la réinitialisation à une date pendant l'été.

        // Date minimum (délai minimum calculé à partir du chargement de la page).
        $mform->addElement('hidden', 'minimumdatetime', $minimumdatetime);
        $buttonarray[] = &$mform->createElement(
            'date_time_selector',
            'nextdatetime',
            '',
            ['startyear' => date("Y"), 'stopyear'  => date("Y"), 'optional' => true]
        );

        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('save'), ['class' => 'submitbtn']);

        // Message pour prévenir qu'un email sera envoyé lors de la soumission du formulaire
        // (élément affiché au survol du bouton Enregistrer).
        $i = html_writer::tag('i', '', ['class' => 'fa-regular fa-envelope text-info fw mr-2']);
        $notificationmsg = html_writer::tag(
            'span',
            $i . get_string('reset_notifications_on_save', 'local_apsolu'), // Icone enveloppe + message.
            ['id' => 'savenotif']
        );

        $buttonarray[] = &$mform->createElement('html', $notificationmsg);

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);

        $mform->addHelpButton(
            'buttonar',
            'settings_reset_nextdatetime',
            'local_apsolu',
            '',
            false,
            ceil(reset::MINIMUMPERIOD / 3600)
        );

        // Set default values.
        $this->set_data($default);
    }

    /**
     * Validation.
     *
     * @param array $data
     * @param array $files
     *
     * @return array The errors that were found.
     */
    public function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);

        if (empty($data['nextdatetime']) == false) {
            // Vérification de la date : doit respecter le délai imposé pour programmer la réinitialisation.
            // On vérifie aussi que la date n'est pas dans le passé par sécurité.
            if ($data['nextdatetime'] < $data['minimumdatetime'] || $data['nextdatetime'] < time()) {
                // Le message d'erreur est attaché au groupe de boutons et non au champ date lui-même.
                $mindate = new stdClass();
                $mindate->datetime = userdate($data['minimumdatetime'], get_string('strftimedatetimewithyear', 'local_apsolu'));
                $mindate->period = ceil(reset::MINIMUMPERIOD / 3600);
                $errors['buttonar'] = get_string('reset_minimumdelayerror', 'local_apsolu', $mindate);
            }

            // On n'accepte pas d'activer la tâche si tous les paramètres sont positionnés sur Non.
            $reset = new reset();
            $allvars = array_keys(get_object_vars($reset));
            $activable = false;
            $i = 0;
            while ($activable == false && $i < count($allvars)) {
                // Au moins un paramètre, autre que nextactive et nextdatetime, sera positionné sur Oui ?
                if (
                    isset($data[$allvars[$i]]) &&
                    (int) $data[$allvars[$i]] == 1 &&
                    $allvars[$i] != 'nextactive' &&
                    $allvars[$i] != 'nextdatetime'
                ) {
                    $activable = true;
                }

                $i++;
            }
            if ($activable == false) {
                $errors['buttonar'] = get_string('reset_minimumfieldserror', 'local_apsolu', $mindate);
            }
        }

        return $errors;
    }
}
