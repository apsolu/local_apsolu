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

use local_apsolu\core\federation\activity as Activity;
use local_apsolu\core\federation\adhesion as Adhesion;
use UniversiteRennes2\Apsolu\Payment;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot.'/local/apsolu/classes/apsolu/payment.php');
require_once($CFG->dirroot.'/local/apsolu/federation/adhesion/request_federation_number_form.php');

$canrequestfederationnumber = true;
$hasrequestedfederationnumber = false;

$items = [];

// Répondre au questionnaire médical.
$item = new stdClass();
$item->label = get_string('answer_the_medical_questionnaire', 'local_apsolu');
$item->status = ($adhesion->questionnairestatus !== null);
$items[] = $item;

// Accepter la charte.
$item = new stdClass();
$item->label = get_string('accept_the_agreement', 'local_apsolu');
$item->status = (empty($adhesion->agreementaccepted) === false);
$items[] = $item;

// Remplir le formulaire d'adhésion.
$item = new stdClass();
$item->label = get_string('fill_out_the_membership_form', 'local_apsolu');
$item->status = (empty($adhesion->mainsport) === false);
$items[] = $item;

// Déposer une autorisation parentale.
if ($adhesion->have_to_upload_parental_authorization() === true) {
    $item = new stdClass();
    $item->label = get_string('upload_a_parental_authorization', 'local_apsolu');
    $item->status = false;

    // On récupère les certificats.
    $fs = get_file_storage();
    $context = context_course::instance($federationcourse->id, MUST_EXIST);
    list($component, $filearea, $itemid) = ['local_apsolu', 'parentalauthorization', $USER->id];
    $sort = 'itemid, filepath, filename';
    $files = $fs->get_area_files($context->id, $component, $filearea, $itemid, $sort, $includedirs = false);

    foreach ($files as $file) {
        // Au moins une autorisation parentale a été déposée, cette partie est validée.
        $item->status = true;
        break;
    }

    $items[] = $item;

    $canrequestfederationnumber = $item->status;
}

// Déposer un certificat médical.
if ($adhesion->have_to_upload_medical_certificate() === true) {
    $item = new stdClass();
    $item->label = get_string('upload_a_medical_certificate', 'local_apsolu');
    $item->status = false;

    // On récupère les certificats.
    $fs = get_file_storage();
    $context = context_course::instance($federationcourse->id, MUST_EXIST);
    list($component, $filearea, $itemid) = ['local_apsolu', 'medicalcertificate', $USER->id];
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
foreach (Payment::get_user_cards_status_per_course($federationcourse->id, $USER->id) as $card) {
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
    // Autorise l'utilisateur à faire une demande de numéro FFSU.
    $customdata = [$items, $canrequestfederationnumber, $hasrequestedfederationnumber];
    $mform = new local_apsolu_request_federation_number_form(null, $customdata);

    // Traite le formulaire.
    if ($mdata = $mform->get_data()) {
        $adhesion->federationnumberrequestdate = time();
        $adhesion->save(null, null, $check = false);

        // Notifie l'adresse du contact fonctionnel pour valider l'adhésion.
        $functional_contact = get_config('local_apsolu', 'functional_contact');
        if (!empty($functional_contact)) {
            $subject = get_string('request_of_federation_number', 'local_apsolu');

            $parameters = [];
            $parameters['fullname'] = fullname($USER);
            $parameters['export_url'] = (string) new moodle_url('/local/apsolu/federation/index.php', ['page' => 'export']);
            if ($adhesion->have_to_upload_medical_certificate() === true && empty($adhesion->medicalcertificatestatus) === true) {
                // Le certificat médical doit être validé.
                $parameters['validation_url'] = (string) new moodle_url('/local/apsolu/federation/index.php', ['page' => 'certificates_validation']);
                $messagetext = get_string('request_of_federation_number_with_medical_certificate_message', 'local_apsolu', $parameters);
            } else {
                $messagetext = get_string('request_of_federation_number_without_medical_certificate_message', 'local_apsolu', $parameters);
            }

            // Solution de contournement pour pouvoir envoyer un message à une adresse mail n'appartenant pas à un utilisateur Moodle.
            $admin = get_admin();
            $admin->auth = 'manual'; // Force l'authentification en manual, car la fonction email_to_user() ignore les comptes en nologin.
            $admin->email = $functional_contact;

            email_to_user($admin, $USER, $subject, $messagetext);

            $event = \local_apsolu\event\notification_sent::create([
                'relateduserid' => $USER->id,
                'context' => $context,
                'other' => json_encode(['sender' => $USER->id, 'receiver' => $admin->email, 'subject' => $subject]),
                ]);
            $event->trigger();
        }

        echo $OUTPUT->notification(get_string('federation_number_request_sent', 'local_apsolu'), 'notifysuccess');

        // Régénère le formulaire.
        $hasrequestedfederationnumber = true;
        $canrequestfederationnumber = false;
        $customdata = [$items, $canrequestfederationnumber, $hasrequestedfederationnumber];
        $mform = new local_apsolu_request_federation_number_form(null, $customdata);
    }

    // Affiche le formulaire.
    $mform->display();
} else {
    // Affiche le récapitulatif de l'adhésion FFSU.
    $getconfig = get_config('local_apsolu');

    $data = new stdClass();
    $data->fields = [];
    $data->fields[] = ['label' => get_string('federation_number', 'local_apsolu'), 'value' => $adhesion->federationnumber];
    $data->fields[] = ['label' => get_string('lastname'), 'value' => $USER->lastname];
    $data->fields[] = ['label' => get_string('firstname'), 'value' => $USER->firstname];
    $data->fields[] = ['label' => get_string('email'), 'value' => $USER->email];
    $data->fields[] = ['label' => get_string('sex', 'local_apsolu'), 'value' => $adhesion->sex];
    $data->fields[] = ['label' => get_string('birthday', 'local_apsolu'), 'value' => userdate($adhesion->birthday, get_string('strftimedate'))];
    $data->fields[] = ['label' => get_string('address1', 'local_apsolu'), 'value' => $adhesion->address1];
    $data->fields[] = ['label' => get_string('address2', 'local_apsolu'), 'value' => $adhesion->address2];
    $data->fields[] = ['label' => get_string('postal_code', 'local_apsolu'), 'value' => $adhesion->postalcode];
    $data->fields[] = ['label' => get_string('city'), 'value' => $adhesion->city];
    $data->fields[] = ['label' => get_string('phone', 'local_apsolu'), 'value' => $adhesion->phone];

    // On récupère la discipline.
    $value = '';
    $disciplines = Adhesion::get_disciplines();
    if (isset($disciplines[$adhesion->disciplineid]) === true) {
        $value = $disciplines[$adhesion->disciplineid];
    }
    $data->fields[] = ['label' => get_string('discipline', 'local_apsolu'), 'value' => $value];

    // On récupère le champ autre fédération.
    if (empty($getconfig->otherfederation) === false) {
        $data->fields[] = ['label' => get_string('other_federation', 'local_apsolu'), 'value' => $adhesion->otherfederation];
    }

    // On récupère le champ sport principal.
    $value = '';
    $activities = $DB->get_records('apsolu_federation_activities');
    if (isset($activities[$adhesion->mainsport]) === true) {
        $value = $activities[$adhesion->mainsport]->name;
    }
    $data->fields[] = ['label' => get_string('main_sport', 'local_apsolu'), 'value' => $value];

    // On récupère la liste des champs oui/non.
    $yesnofields = [];
    $yesnofields['sportlicense'] = get_string('sport_license', 'local_apsolu');
    $yesnofields['managerlicense'] = get_string('manager_license', 'local_apsolu');
    $yesnofields['managerlicensetype'] = get_string('manager_license_type', 'local_apsolu');
    $yesnofields['refereelicense'] = get_string('referee_license', 'local_apsolu');
    $yesnofields['starlicense'] = get_string('star_license', 'local_apsolu');
    $yesnofields['insurance'] = get_string('insurance', 'local_apsolu');
    $yesnofields['instagram'] = get_string('instagram', 'local_apsolu');
    $yesnofields['usepersonaldata'] = get_string('use_personal_data', 'local_apsolu');
    foreach ($yesnofields as $field => $label) {
        $visibilitystrname = sprintf('%s_field_visibility', $field);
        if (empty($getconfig->$visibilitystrname) === true) {
            continue;
        }

        if ($field === 'managerlicensetype') {
            if (empty($adhesion->managerlicense) === true || empty($getconfig->managerlicense) === true) {
                // N'affiche pas la licence dirigeante si elle est positionnée à Non et/ou non visible.
                continue;
            }
            $value = get_string('not_student', 'local_apsolu');
        } else {
            $value = get_string('no');
        }

        if ($field === 'starlicense') {
            if ($adhesion->starlicense === 'O') {
                $value = get_string('yes');
            }
        } else if (empty($adhesion->{$field}) === false) {
            if ($field === 'managerlicensetype') {
                $value = get_string('student');
            } else {
                $value = get_string('yes');
            }
        }

        $data->fields[] = ['label' => $label, 'value' => $value];
    }

    // On récupère la liste des sports autorisés par le certificat médical.
    $fieldname = 'sport';
    $label = get_string('activity_without_constraint', 'local_apsolu');
    if ($adhesion->sport1 === Adhesion::SPORT_NONE) {
        $fieldname = 'constraintsport';
        $label = get_string('activity_with_specific_constraints', 'local_apsolu');
    }

    for ($i = 1; $i <= 5; $i++) {
        $field = $fieldname.$i;
        if ($adhesion->$field === Adhesion::SPORT_NONE) {
            break;
        }

        if (isset($activities[$adhesion->$field]->name) === false) {
            break;
        }

        $data->fields[] = ['label' => $label, 'value' => $activities[$adhesion->$field]->name];
    }

    // On récupère la date du certificat médical.
    if (empty($adhesion->medicalcertificatedate) === false) {
        $value = userdate($adhesion->medicalcertificatedate, get_string('strftimedate'));
        $data->fields[] = ['label' => get_string('medical_certificate_date', 'local_apsolu'), 'value' => $value];
    }

    // On récupère les certificats.
    $fs = get_file_storage();
    $context = context_course::instance($federationcourse->id, MUST_EXIST);
    list($component, $filearea, $itemid) = ['local_apsolu', 'medicalcertificate', $USER->id];
    $sort = 'itemid, filepath, filename';
    $files = $fs->get_area_files($context->id, $component, $filearea, $itemid, $sort, $includedirs = false);

    $items = [];
    foreach ($files as $file) {
        $url = moodle_url::make_pluginfile_url($context->id, $component, $filearea, $itemid, '/', $file->get_filename(), $forcedownload = false, $includetoken = false);
        $items[] = html_writer::link($url, $file->get_filename());
    }

    if (isset($items[0]) === true) {
        $data->fields[] = ['label' => get_string('medical_certificate', 'local_apsolu'), 'htmlvalue' => html_writer::alist($items)];
    }

    echo $OUTPUT->render_from_template('local_apsolu/federation_adhesion_summary', $data);
}
