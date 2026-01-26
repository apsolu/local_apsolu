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
 * Page listant les activités FFSU.
 *
 * @package    local_apsolu
 * @copyright  2022 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\federation\activity;
use local_apsolu\core\federation\adhesion;

defined('MOODLE_INTERNAL') || die();

require(__DIR__ . '/medical_certificate_form.php');

$data = new stdClass();
$data->wwwroot = $CFG->wwwroot;
$data->notifications = [];

if ($adhesion->have_to_upload_medical_certificate() === false) {
    // L'étudiant a répondu NON à toutes les questions du questionnaire médical ou ne pratique pas un sport à risque.
    $message = get_string('i_answered_no_to_all_the_questions_in_the_health_questionnaire', 'local_apsolu');
    $data->notifications[] = $message;
    $data->nextstep = APSOLU_PAGE_PAYMENT;
} else if ($adhesion->can_edit() === false) {
    $messages = $adhesion::get_contacts();
    $data->notifications[] = implode(' ', $messages);

    // Validité attendue en mois du certificat médical.
    if ($adhesion->has_constraint_sports() === true) {
        $validityperiod = 12;
    }

    if ($adhesion->questionnairestatus === $adhesion::HEALTH_QUESTIONNAIRE_ANSWERED_YES_ONCE) {
        $validityperiod = 6;
    }

    $customdata = [$adhesion, $course, $context, $validityperiod, $freeze = true];
    $mform = new local_apsolu_federation_medical_certificate(null, $customdata);
    $data->content = $mform->render();
} else {
    // Validité attendue en mois du certificat médical.
    $validityperiod = 0;

    // L'étudiant pratique un sport à risque.
    if ($adhesion->has_constraint_sports() === true) {
        $validityperiod = 12;
        $message = get_string('i_wish_to_practice_an_complementary_activity_with_particular_constraints_and_certify_that_i_have_presented_a_medical_certificate', 'local_apsolu'); // phpcs:ignore
        $data->notifications[] = $message;
    }

    // L'étudiant a répondu OUI à au moins une question du questionnaire médical.
    if ($adhesion->questionnairestatus === $adhesion::HEALTH_QUESTIONNAIRE_ANSWERED_YES_ONCE) {
        $validityperiod = 6;
        $message = get_string('i_answered_yes_to_a_section_of_the_health_questionnaire_and_attest_to_having_presented_a_medical_certificate', 'local_apsolu'); // phpcs:ignore
        $data->notifications[] = $message;
    }

    // Construit le formulaire.
    $customdata = [$adhesion, $course, $context, $validityperiod, $freeze = false];
    $mform = new local_apsolu_federation_medical_certificate(null, $customdata);

    // Charge les fichiers éventuellement déposés précédemment.
    $mdata = new stdClass();
    $filemanageroptions = $mform::get_filemanager_options($course, $context);
    $fieldname = 'medicalcertificate';
    $component = 'local_apsolu';
    $filearea = 'medicalcertificate';
    $itemid = $USER->id;
    file_prepare_standard_filemanager($mdata, $fieldname, $filemanageroptions, $context, $component, $filearea, $itemid);
    $mform->set_data($mdata);

    if ($mdata = $mform->get_data()) {
        // Enregistre les fichiers en base de données.
        file_postupdate_standard_filemanager($mdata, $fieldname, $filemanageroptions, $context, $component, $filearea, $itemid);

        // Met à jour l'adhésion en base de données.
        $adhesion->save($mdata);

        $returnurl = new moodle_url('/local/apsolu/federation/adhesion/index.php', ['step' => APSOLU_PAGE_PAYMENT]);
        redirect($returnurl);
    }

    $data->form = $mform->render();
}

echo $OUTPUT->render_from_template('local_apsolu/federation_adhesion_medical_certificate', $data);
