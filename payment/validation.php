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
use local_apsolu\core\paybox;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot.'/local/apsolu/locallib.php');
require_once($CFG->dirroot.'/local/apsolu/classes/apsolu/payment.php');
require_once($CFG->dirroot.'/enrol/select/lib.php');
require_once($CFG->dirroot.'/enrol/select/locallib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');

require_login();

$context = context_user::instance($USER->id);

$PAGE->set_url('/local/apsolu/payment/validation.php');
$PAGE->set_pagelayout('base');

$PAGE->set_context($context);

$PAGE->set_title(get_string('payment', 'local_apsolu'));

// Navigation.
$PAGE->navbar->add(get_string('payment', 'local_apsolu'));

// Vérifie si les paiements sont ouverts.
if (Payment::is_open() === false) {
    // Module de paiement fermé !

    // Display page.
    echo $OUTPUT->header();

    $payments_startdate = get_config('local_apsolu', 'payments_startdate');

    $data = new stdClass();
    if (time() < $payments_startdate) {
        $date = userdate($payments_startdate, get_string('strftimedaydatetime', 'langconfig'));
        $data->complement = get_string('opened_period', 'local_apsolu', (object) ['date' => $date]);
    }

    echo $OUTPUT->render_from_template('local_apsolu/payment_closed', $data);

    echo $OUTPUT->footer();

    exit(0);
}

// Javascript.
$PAGE->requires->js_call_amd('local_apsolu/payment', 'initialise');

// Données à afficher.
$address = $DB->get_record('apsolu_payments_addresses', ['userid' => $USER->id], '*', MUST_EXIST);

$paymentcenters = $DB->get_records('apsolu_payments_centers');
$cards = Payment::get_user_cards();
foreach ($cards as $card) {
    if (isset($paymentcenters[$card->centerid]) === false) {
        // On vérifie que le centre de paiement associé à la carte existe.
        continue;
    }

    if (isset($paymentcenters[$card->centerid]->cards) === false) {
        $paymentcenters[$card->centerid]->cards = [];
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
        $payment->timecreated = core_date::strftime('%FT%T');
        $payment->timemodified = $payment->timecreated;
        $payment->userid = $USER->id;
        $payment->paymentcenterid = $paymentcenter->id;
        $payment->id = $DB->insert_record('apsolu_payments', $payment);
        $payment->prefix = $paymentcenter->prefix;

        $quantity = 0;
        foreach ($paymentcenter->cards as $card) {
            if ($card->status === Payment::DUE) {
                $item = new \stdClass();
                $item->paymentid = $payment->id;
                $item->cardid = $card->id;

                $itemid = $DB->insert_record('apsolu_payments_items', $item);
                $quantity++;
            }
        }

        if ($quantity === 0) {
            $quantity = 1;
        }

        $payment->quantity = $quantity;
        $payment->address = $address;

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

$payboxserver = paybox::get_server();
if ($payboxserver === false) {
    echo $OUTPUT->render_from_template('local_apsolu/payment_unavailable_server', $data = null);
} else {
    $data->action_url = 'https://'.$payboxserver.'/cgi/MYchoix_pagepaiement.cgi';

    echo $OUTPUT->render_from_template('local_apsolu/payment_validation', $data);
}

echo $OUTPUT->footer();
