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
 * @copyright  2022 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use UniversiteRennes2\Apsolu\Payment;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot.'/local/apsolu/classes/apsolu/payment.php');
require_once($CFG->dirroot.'/local/apsolu/federation/adhesion/request_federation_number_form.php');

$canrequestfederationnumber = true;
$hasrequestedfederationnumber = false;

$items = array();

// Répondre au questionnaire médical.
$item = new stdClass();
$item->label = get_string('answer_the_medical_questionnaire', 'local_apsolu');
$item->status = ($adhesion->questionnairestatus !== null);
$items[] = $item;

// Remplir le formulaire d'adhésion.
$item = new stdClass();
$item->label = get_string('fill_out_the_membership_form', 'local_apsolu');
$item->status = (empty($adhesion->mainsport) === false);
$items[] = $item;

// Déposer un certificat médical.
if ($adhesion->have_to_upload_medical_certificate() === true) {
    $item = new stdClass();
    $item->label = get_string('upload_a_medical_certificate', 'local_apsolu');
    $item->status = false;

    // On récupère les certificats.
    $fs = get_file_storage();
    $context = context_course::instance($courseid, MUST_EXIST);
    list($component, $filearea, $itemid) = array('local_apsolu', 'medicalcertificate', $USER->id);
    $sort = 'itemid, filepath, filename';
    $files = $fs->get_area_files($context->id, $component, $filearea, $itemid, $sort, $includedirs = false);

    foreach ($files as $file) {
        // Au moins un certificat a été déposé, cette partie est validée.
        $item->status = true;
        break;
    }

    $items[] = $item;

    $canrequestfederationnumber = $item->status;
}

// Payer sa licence.
$item = new stdClass();
$item->label = get_string('pay_for_license', 'local_apsolu');
$item->status = true;
foreach (Payment::get_user_cards_status_per_course($courseid, $USER->id) as $card) {
    if ($card->status !== Payment::DUE) {
        continue;
    }

    $item->status = false;
    break;
}
$items[] = $item;

if ($item->status === false) {
    $canrequestfederationnumber = false;
}

// Obtenir un numéro de licence.
if (empty($adhesion->federationnumberrequestdate) === false) {
    $canrequestfederationnumber = false;
    $hasrequestedfederationnumber = true;
}

if (empty($adhesion->federationnumber) === true) {
    $customdata = array($items, $canrequestfederationnumber, $hasrequestedfederationnumber);
    $mform = new local_apsolu_request_federation_number_form(null, $customdata);

    // Traite le formulaire.
    if ($mdata = $mform->get_data()) {
        $adhesion->federationnumberrequestdate = time();
        $adhesion->save(null, null, $check = false);

        // Notifie l'adresse du contact fonctionnel pour valider l'adhésion.
        $functional_contact = get_config('local_apsolu', 'functional_contact');
        if (!empty($functional_contact)) {
            $subject = get_string('request_of_federation_number', 'local_apsolu');

            $parameters = array();
            $parameters['fullname'] = fullname($USER);
            $parameters['export_url'] = (string) new moodle_url('/local/apsolu/federation/index.php', array('page' => 'export'));
            if ($adhesion->have_to_upload_medical_certificate() === true && empty($adhesion->medicalcertificatestatus) === true) {
                // Le certificat médical doit être validé.
                $parameters['validation_url'] = (string) new moodle_url('/local/apsolu/federation/index.php', array('page' => 'certificates_validation'));
                $messagetext = get_string('request_of_federation_number_with_medical_certificate_message', 'local_apsolu', $parameters);
            } else {
                $messagetext = get_string('request_of_federation_number_without_medical_certificate_message', 'local_apsolu', $parameters);
            }

            // Solution de contournement pour pouvoir envoyer un message à une adresse mail n'appartenant pas à un utilisateur Moodle.
            $admin = get_admin();
            $admin->auth = 'manual'; // Force l'authentification en manual, car la fonction email_to_user() ignore les comptes en nologin.
            $admin->email = $functional_contact;

            email_to_user($admin, $USER, $subject, $messagetext);

            $event = \local_apsolu\event\notification_sent::create(array(
                'relateduserid' => $USER->id,
                'context' => $context,
                'other' => json_encode(array('sender' => $USER->id, 'receiver' => $admin->email, 'subject' => $subject)),
                ));
            $event->trigger();
        }

        echo $OUTPUT->notification(get_string('federation_number_request_sent', 'local_apsolu'), 'notifysuccess');

        // Régénère le formulaire.
        $hasrequestedfederationnumber = true;
        $canrequestfederationnumber = false;
        $customdata = array($items, $canrequestfederationnumber, $hasrequestedfederationnumber);
        $mform = new local_apsolu_request_federation_number_form(null, $customdata);
    }

    // Affiche le formulaire.
    $mform->display();
} else {
    $label = get_string('a_license_number_has_been_assigned_to_you_by_the_federation', 'local_apsolu');
    echo html_writer::div($label, 'alert alert-success');
}
