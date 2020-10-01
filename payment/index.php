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
 * Interface for paybox payment.
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use UniversiteRennes2\Apsolu as apsolu;
use UniversiteRennes2\Apsolu\Payment;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot.'/local/apsolu/locallib.php');
require_once($CFG->dirroot.'/local/apsolu/classes/apsolu/payment.php');
require_once($CFG->dirroot.'/enrol/select/lib.php');
require_once($CFG->dirroot.'/enrol/select/locallib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');

require_login();

$context = context_user::instance($USER->id);

$PAGE->set_url('/local/apsolu/payment/index.php');
$PAGE->set_pagelayout('course');

$PAGE->set_context($context);

$PAGE->set_title(get_string('payment', 'local_apsolu'));

// Vérifie la période d'ouverture des paiements.
$payments_startdate = get_config('local_apsolu', 'payments_startdate');
$payments_enddate = get_config('local_apsolu', 'payments_enddate');
if (time() < $payments_startdate || time() > $payments_enddate) {
    // Module de paiement fermé !

    // Navigation.
    $PAGE->navbar->add(get_string('payment', 'local_apsolu'));

    // Display page.
    echo $OUTPUT->header();

    $data = new stdClass();
    if (time() < $payments_startdate) {
        $date = userdate($payments_startdate, get_string('strftimedaydatetime', 'langconfig'));
        $data->complement = get_string('opened_period', 'local_apsolu', (object) ['date' => $date]);
    }

    echo $OUTPUT->render_from_template('local_apsolu/payment_closed', $data);

    echo $OUTPUT->footer();

    exit(0);
}

// Gère les utilisateurs non autorisés à payer.
$customfields = profile_user_record($USER->id);
if (isset($customfields->apsolusesame) === false || $customfields->apsolusesame !== '1') {
    // Navigation.
    $PAGE->navbar->add(get_string('payment', 'local_apsolu'));

    // Display page.
    echo $OUTPUT->header();

    $contact = get_config('local_apsolu', 'functional_contact');
    if (empty($contact) === false) {
        $options = (object) ['email' => $contact];
    } else {
        $admin = current(get_admins());
        $options = (object) ['email' => $admin->email];
    }

    $data = new stdClass();
    $data->alert = get_string('invalid_user_invalid_sesame', 'local_apsolu', $options);

    echo $OUTPUT->render_from_template('local_apsolu/payment_invalid_user', $data);

    echo $OUTPUT->footer();

    exit(0);
}

// Gère le retour de PayBox.
$status = optional_param('status', null, PARAM_ALPHA);

if ($status !== null) {
    $data = new stdClass();
    $data->wwwroot = $CFG->wwwroot;

    switch ($status) {
        case 'accepted':
            $data->status = get_string('status_'.$status, 'local_apsolu');
            $data->state = 'alert-success';
            break;
        case 'cancel':
        case 'wait':
            $data->status = get_string('status_'.$status, 'local_apsolu');
            $data->state = 'alert-info';
            break;
        case 'refused':
            $data->status = get_string('status_'.$status, 'local_apsolu');
            $data->state = 'alert-danger';
            break;
        default:
            $data->status = get_string('status_unknown', 'local_apsolu');
            $data->state = 'alert-info';
    }

    // Navigation.
    $PAGE->navbar->add(get_string('payment', 'local_apsolu'));

    // Display page.
    echo $OUTPUT->header();

    echo $OUTPUT->render_from_template('local_apsolu/payment_paybox_return', $data);

    echo $OUTPUT->footer();

    exit(0);
}

// Navigation.
$PAGE->navbar->add(get_string('payment', 'local_apsolu'));

// Javascript.
$PAGE->requires->js_call_amd('local_apsolu/payment', 'initialise');

// Données à afficher.
$paymentcenters = $DB->get_records('apsolu_payments_centers');
$cards = Payment::get_user_cards();
foreach ($cards as $card) {
    if (isset($paymentcenters[$card->centerid]) === false) {
        // Gni ?
        continue;
    }

    if (isset($paymentcenters[$card->centerid]->cards) === false) {
        $paymentcenters[$card->centerid]->cards = array();
        $paymentcenters[$card->centerid]->count_cards = 0;
        $paymentcenters[$card->centerid]->total_amount = 0;
        $paymentcenters[$card->centerid]->paid_amount = 0;
        $paymentcenters[$card->centerid]->due_amount = 0;
    }

    $card->price_format = number_format($card->price, 2);
    $card->status = Payment::get_user_card_status($card);

    switch ($card->status) {
        case Payment::DUE:
            $paymentcenters[$card->centerid]->total_amount += $card->price;
            $paymentcenters[$card->centerid]->due_amount += $card->price;
            break;
        case Payment::PAID:
            $paymentcenters[$card->centerid]->total_amount += $card->price;
            $paymentcenters[$card->centerid]->paid_amount += $card->price;
            break;
        case Payment::FREE:
            // On n'affiche pas cette carte.
            $card->price_format = '0.00';
        case Payment::GIFT:
        default:
            break;
    }

    $paymentcenters[$card->centerid]->cards[] = $card;
    $paymentcenters[$card->centerid]->count_cards++;
}

foreach ($paymentcenters as $centerid => $paymentcenter) {
    if (isset($paymentcenter->cards) === false) {
        unset($paymentcenters[$centerid]);
        continue;
    }

    if ($paymentcenter->count_cards === 0) {
        unset($paymentcenters[$centerid]);
        continue;
    }

    $paymentcenters[$centerid]->total_amount_format = number_format($paymentcenter->total_amount, 2);
    $paymentcenters[$centerid]->paid_amount_format = number_format($paymentcenter->paid_amount, 2);
    $paymentcenters[$centerid]->due_amount_format = number_format($paymentcenter->due_amount, 2);

    if ($paymentcenter->due_amount > 0) {
        // Enregistre une "commande".
        $transaction = $DB->start_delegated_transaction();

        $payment = new \stdClass();
        $payment->method = 'paybox';
        $payment->source = 'apsolu';
        $payment->amount = $paymentcenter->due_amount;
        $payment->status = '0'; // Pas payé !
        $payment->timepaid = null;
        $payment->timecreated = strftime('%FT%T');
        $payment->timemodified = $payment->timecreated;
        $payment->userid = $USER->id;
        $payment->paymentcenterid = $paymentcenter->id;
        $payment->id = $DB->insert_record('apsolu_payments', $payment);
        $payment->prefix = $paymentcenter->prefix;

        foreach ($paymentcenter->cards as $card) {
            if ($card->status === Payment::DUE) {
                $item = new \stdClass();
                $item->paymentid = $payment->id;
                $item->cardid = $card->id;

                $itemid = $DB->insert_record('apsolu_payments_items', $item);
            }
        }

        $paymentcenters[$centerid]->paybox = Payment::get_paybox_settings($payment);

        $transaction->allow_commit();
    }
}
$paymentcenters = array_values($paymentcenters);

// Display.
echo $OUTPUT->header();

$data = new stdClass();
$data->wwwroot = $CFG->wwwroot;
$data->is_siuaps_rennes = isset($CFG->is_siuaps_rennes);
$data->payment_centers = array_values($paymentcenters);
$data->count_payment_centers = count($data->payment_centers);
$data->functional_contact = get_config('local_apsolu', 'functional_contact');
if (empty($data->functional_contact) === true) {
    $admin = get_admin();
    $data->functional_contact = $admin->email;
}

$payboxserver = false;

$servers = explode(',', get_config('local_apsolu', 'paybox_servers_outgoing'));
foreach ($servers as $server) {
    $options = array();

    $server = trim($server);

    if (empty($server)) {
        continue;
    }

    if (!empty($CFG->proxyhost) && !is_proxybypass('https://'.$server)) {
        if (!empty($CFG->proxyport)) {
            $proxy = $CFG->proxyhost.':'.$CFG->proxyport;
        } else {
            $proxy = $CFG->proxyhost;
        }

        $options = array(
            'http' => array (
                'proxy' => $proxy,
            ),
            'https' => array (
                'proxy' => $proxy,
            )
        );
    }

    // Création du contexte de transaction.
    $ctx = stream_context_create($options);

    // Récupération des données.
    $content = file_get_contents('https://'.$server.'/load.html', false, $ctx);
    if (strpos($content, '<div id="server_status" style="text-align:center;">OK</div>') !== false) {
        // Le serveur est prêt et les services opérationnels.
        $payboxserver = $server;
        break;
    }
    // La machine est disponible mais les services ne le sont pas.
}

if ($payboxserver === false) {
    $data->can_paid = false;
} else {
    $data->can_paid = true;
    $data->action_url = 'https://'.$payboxserver.'/cgi/MYchoix_pagepaiement.cgi';
}

echo $OUTPUT->render_from_template('local_apsolu/payment_index', $data);

echo $OUTPUT->footer();
