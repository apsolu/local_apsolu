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
 * Interface de validation et tunnel de paiement (Paybox + Atouts Normandie).
 *
 * Ce script est la page centrale où l'étudiant :
 * 1. Visualise le récapitulatif de ses activités dues.
 * 2. Applique éventuellement sa réduction Atouts Normandie via une modale AJAX.
 * 3. Paie le reliquat par Carte Bancaire via Paybox.
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2 / Adapté pour Atouts Normandie 2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use UniversiteRennes2\Apsolu;
use UniversiteRennes2\Apsolu\Payment;
use local_apsolu\core\paybox;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/local/apsolu/locallib.php');
require_once($CFG->dirroot . '/local/apsolu/classes/apsolu/payment.php');
require_once($CFG->dirroot . '/enrol/select/lib.php');
require_once($CFG->dirroot . '/enrol/select/locallib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

// Chargement explicite du gestionnaire Atouts.
require_once($CFG->dirroot . '/local/apsolu/classes/core/atouts_manager.php');

require_login($courseorid = null, $autologinguest = false);

$context = context_user::instance($USER->id);

$PAGE->set_url('/local/apsolu/payment/validation.php');
$PAGE->set_pagelayout('base');
$PAGE->set_context($context);
$PAGE->set_title(get_string('payment', 'local_apsolu'));

// Navigation.
$PAGE->navbar->add(get_string('payment', 'local_apsolu'));

// Vérifie si les paiements sont ouverts.
if (Payment::is_open() === false) {
    echo $OUTPUT->header();
    $paymentsstartdate = get_config('local_apsolu', 'payments_startdate');
    $data = new stdClass();
    if (time() < $paymentsstartdate) {
        $date = userdate($paymentsstartdate, get_string('strftimedaydatetime', 'langconfig'));
        $data->complement = get_string('opened_period', 'local_apsolu', (object) ['date' => $date]);
    }
    echo $OUTPUT->render_from_template('local_apsolu/payment_closed', $data);
    echo $OUTPUT->footer();
    exit(0);
}

// --- CHARGEMENT DU JAVASCRIPT (AMD) ---
// Chargement du module principal (gestion du panier/UI).
$PAGE->requires->js_call_amd('local_apsolu/payment', 'initialise');

// Initialisation du module Atouts pour gérer la fenêtre modale et le numéro de carte.
// --- RÉCUPÉRATION DU RÉGLAGE ADMIN ---
$enableatouts = get_config('local_apsolu', 'enable_atouts');
$isatoutsenabled = !empty($enableatouts);

if ($isatoutsenabled) {
    $PAGE->requires->js_call_amd('local_apsolu/atouts', 'init');
}

// Données à afficher.
$address = $DB->get_record('apsolu_payments_addresses', ['userid' => $USER->id], '*', MUST_EXIST);
$paymentcenters = $DB->get_records('apsolu_payments_centers');
$cards = Payment::get_user_cards();

// 1. Ventilation des cartes par centre de paiement (SUAPS, FFSU, etc.).
foreach ($cards as $card) {
    if (isset($paymentcenters[$card->centerid]) === false) {
        continue;
    }

    if (isset($paymentcenters[$card->centerid]->cards) === false) {
        $paymentcenters[$card->centerid]->cards = [];
        $paymentcenters[$card->centerid]->count_cards = 0;
        $paymentcenters[$card->centerid]->total_amount = 0;
        $paymentcenters[$card->centerid]->paid_amount = 0;
        $paymentcenters[$card->centerid]->due_amount = 0;
    }

    $card->price_format = number_format($card->price, 2, ',', ' ');
    $card->status = Payment::get_user_card_status($card);

    switch ($card->status) {
        case Payment::DUE:
            $paymentcenters[$card->centerid]->total_amount += $card->price;
            $paymentcenters[$card->centerid]->due_amount += $card->price;

            // On n'ajoute la carte à la liste d'affichage QUE si elle est due.
            $paymentcenters[$card->centerid]->cards[] = $card;
            $paymentcenters[$card->centerid]->count_cards++;
            break;

        case Payment::PAID:
            $paymentcenters[$card->centerid]->total_amount += $card->price;
            $paymentcenters[$card->centerid]->paid_amount += $card->price;
            // Note : Ici on n'ajoute PAS la carte à $paymentcenters->cards
            break;
    }
}

// 2. Génération des ordres de paiement et intégration Atouts.
foreach ($paymentcenters as $centerid => $paymentcenter) {
    if (empty($paymentcenter->cards) || $paymentcenter->count_cards === 0) {
        unset($paymentcenters[$centerid]);
        continue;
    }

    $paymentcenters[$centerid]->total_amount_format = number_format($paymentcenter->total_amount, 2, ',', ' ');
    $paymentcenters[$centerid]->paid_amount_format = number_format($paymentcenter->paid_amount, 2, ',', ' ');
    $paymentcenters[$centerid]->due_amount_format = number_format($paymentcenter->due_amount, 2, ',', ' ');

    if ($paymentcenter->due_amount > 0) {
        $transaction = $DB->start_delegated_transaction();

        // On récupère ou on crée l'enregistrement de paiement principal (mdl_apsolu_payments).
        $payment = $DB->get_record('apsolu_payments', [
            'userid' => $USER->id,
            'paymentcenterid' => $paymentcenter->id,
            'status' => 0
        ], '*', IGNORE_MULTIPLE);

        if (!$payment) {
            // Création si inexistant.
            $payment = new \stdClass();
            $payment->method = 'paybox';
            $payment->source = 'apsolu';
            $payment->amount = $paymentcenter->due_amount;
            $payment->status = 0;
            $payment->timecreated = time();
            $payment->timemodified = $payment->timecreated;
            $payment->userid = $USER->id;
            $payment->paymentcenterid = $paymentcenter->id;
            $payment->id = $DB->insert_record('apsolu_payments', $payment);
        } else {
            // Mise à jour du montant si le panier a changé.
            $payment->amount = $paymentcenter->due_amount;
            $payment->timemodified = time();
            $DB->update_record('apsolu_payments', $payment);

            // Nettoyage des anciens items pour les recréer proprement.
            $DB->delete_records('apsolu_payments_items', ['paymentid' => $payment->id]);
        }

        // --- GESTION ATOUTS NORMANDIE ---
        // On cherche une réduction Atouts (status 0 ou 1) liée à cet ID de paiement.
        $atout_record = $DB->get_record('apsolu_atouts_payments', ['paymentid' => $payment->id]);

        $atout_deduction = 0;
        if ($atout_record) {
            $atout_deduction = $atout_record->amount;
        }

        // Calcul du reliquat pour la Carte Bancaire (Paybox).
        $amount_for_paybox = $paymentcenter->due_amount - $atout_deduction;
        if ($amount_for_paybox < 0) {
            $amount_for_paybox = 0;
        }

        // Préparation des données pour le template Mustache.
        $paymentcenter->payment_id = $payment->id;
        $paymentcenter->has_atouts_support = true;
        $paymentcenter->atout_deduction_format = number_format($atout_deduction, 2, ',', ' ');
        $paymentcenter->atout_deduction_raw = $atout_deduction;
        $paymentcenter->remaining_for_paybox_format = number_format($amount_for_paybox, 2, ',', ' ');
        $paymentcenter->remaining_for_paybox_raw = $amount_for_paybox;
        $paymentcenter->remaining_for_paybox = number_format($amount_for_paybox, 2, ',', ' ');
        $paymentcenter->is_fully_paid_by_atouts = ($amount_for_paybox <= 0);
        $paymentcenter->is_partially_paid_by_atouts = ($atout_deduction > 0 && $amount_for_paybox > 0);

        // Liaison des items de commande.
        $quantity = 0;
        $payment->codes = [];
        foreach ($paymentcenter->cards as $card) {
            if ($card->status === Payment::DUE) {
                $item = new \stdClass();
                $item->paymentid = $payment->id;
                $item->cardid = $card->id;
                if ($card->code !== '') {
                    $payment->codes[] = $card->code;
                }
                $DB->insert_record('apsolu_payments_items', $item);
                $quantity++;
            }
        }

        $payment->quantity = ($quantity === 0) ? 1 : $quantity;
        $payment->address = $address;
        $payment->prefix = $paymentcenter->prefix;

        // Signature Paybox sur le reliquat uniquement.
        if ($amount_for_paybox > 0) {
            // On clone pour ne pas modifier l'objet original en base.
            $paybox_payment = clone $payment;
            $paybox_payment->amount = $amount_for_paybox;
            $paymentcenter->paybox = Payment::get_paybox_settings($paybox_payment);
        }

        $transaction->allow_commit();
    }
}

$paymentcenters = array_values($paymentcenters);

// Affichage de la page.
echo $OUTPUT->header();

$data = new stdClass();
$data->is_atouts_enabled = $isatoutsenabled;
$data->wwwroot = $CFG->wwwroot;
$data->sesskey = sesskey();
$data->is_siuaps_rennes = isset($CFG->is_siuaps_rennes);
$data->payment_centers = $paymentcenters;
$data->count_payment_centers = count($data->payment_centers);

// Gestion du contact fonctionnel.
$functionalcontact = get_config('local_apsolu', 'functional_contact');
if (empty($functionalcontact)) {
    $admin = get_admin();
    $data->functional_contact = sprintf('<a href="mailto:%s">%s</a>', $admin->email, $admin->email);
} else {
    $contacts = [];
    foreach (explode(';', $functionalcontact) as $contact) {
        $contacts[] = sprintf('<a href="mailto:%s">%s</a>', trim($contact), trim($contact));
    }
    $data->functional_contact = implode(' ou ', $contacts);
}

// Vérification du serveur Paybox.
$payboxserver = paybox::get_server();
if ($payboxserver === false) {
    echo $OUTPUT->render_from_template('local_apsolu/payment_unavailable_server', $data);
} else {
    $data->action_url = 'https://' . $payboxserver . '/cgi/MYchoix_pagepaiement.cgi';
    echo $OUTPUT->render_from_template('local_apsolu/payment_validation', $data);
}

echo $OUTPUT->footer();
