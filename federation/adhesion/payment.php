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

use local_apsolu\core\federation\adhesion;
use UniversiteRennes2\Apsolu\Payment;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/apsolu/classes/apsolu/payment.php');
require_once($CFG->dirroot . '/local/apsolu/federation/adhesion/payment_form.php');

$havetosubmitdocuments = [];

// Contrôle si des documents ont été déposés pour l'autorisation parentale.
if ($adhesion->have_to_upload_parental_authorization() === true) {
    // On récupère les autorisations.
    $fs = get_file_storage();
    $context = context_course::instance($federationcourse->id, MUST_EXIST);
    [$component, $filearea, $itemid] = ['local_apsolu', 'parentalauthorization', $USER->id];
    $sort = 'itemid, filepath, filename';
    $files = $fs->get_area_files($context->id, $component, $filearea, $itemid, $sort, $includedirs = false);

    if (count($files) === 0) {
        // L'utilisateur doit déposer une autorisation parentale.
        $havetosubmitdocuments[] = get_string('upload_a_parental_authorization', 'local_apsolu');
    }
}

// Contrôle si des documents ont été déposés pour le certificat médical.
if ($adhesion->have_to_upload_medical_certificate() === true) {
    // On récupère les certificats.
    $fs = get_file_storage();
    $context = context_course::instance($federationcourse->id, MUST_EXIST);
    [$component, $filearea, $itemid] = ['local_apsolu', 'medicalcertificate', $USER->id];
    $sort = 'itemid, filepath, filename';
    $files = $fs->get_area_files($context->id, $component, $filearea, $itemid, $sort, $includedirs = false);

    if (count($files) === 0) {
        // L'utilisateur doit déposer un certificat médical.
        $havetosubmitdocuments[] = get_string('upload_a_medical_certificate', 'local_apsolu');
    }
}

$cards = [];
$due = false;
$images = Payment::get_statuses_images();
foreach (Payment::get_user_cards_status_per_course($federationcourse->id, $USER->id) as $card) {
    $card->image = $images[$card->status]->image;

    $cards[] = sprintf('%s %s', $card->image, $card->name);

    if ($card->status !== Payment::DUE) {
        continue;
    }

    $due = true;
}

// Initialise le formulaire.
$readonly = ($adhesion->can_edit() === false);

$contacts = [];
if ($readonly === true) {
    $contacts = implode(' ', Adhesion::get_contacts());
}

$customdata = [$contacts, $cards, $havetosubmitdocuments, $due, $readonly, $adhesion];
$mform = new local_apsolu_federation_payment(null, $customdata);
if ($data = $mform->get_data()) {
    // On traite les données envoyées au formulaire.
    if (isset($data->enablepasssport) === true && empty($data->passsportnumber) === false) {
        $adhesion->passsportnumber = $data->passsportnumber;
        $adhesion->passsportstatus = Adhesion::PASS_SPORT_STATUS_PENDING;
    }

    $adhesion->federationnumberrequestdate = time();
    $adhesion->save(null, null, $check = false);

    if ($due === true && isset($data->enablepasssport) === false) {
        // Si un paiement est dû, l'utilisateur est redirigé vers la page des paiements.
        $paymenturl = new moodle_url('/local/apsolu/payment/index.php');
        redirect($paymenturl);
    }

    // Si aucun paiement est dû, on notifie le contact fonctionnel.
    $adhesion->notify_functional_contact();

    redirect(new moodle_url('/local/apsolu/federation/adhesion/index.php', ['step' => APSOLU_PAGE_SUMMARY]));
}

$mform->display();
