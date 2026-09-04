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
 * Page d'édition des paiements.
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\payment\method;
use UniversiteRennes2\Apsolu\Payment;

defined('MOODLE_INTERNAL') || die;

require(__DIR__ . '/edit_form.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/local/apsolu/locallib.php');
require_once($CFG->dirroot . '/local/apsolu/classes/apsolu/payment.php');

// Get user id.
$userid = required_param('userid', PARAM_INT);
$user = $DB->get_record('user', ['id' => $userid, 'deleted' => '0']);

if ($user === false) {
    throw new moodle_exception('invaliduser');
}

$backurl = $CFG->wwwroot . '/local/apsolu/payment/admin.php?tab=payments&userid=' . $userid;

$paymentid = optional_param('paymentid', null, PARAM_INT);
if ($paymentid !== null) {
    $payment = $DB->get_record('apsolu_payments', ['id' => $paymentid, 'userid' => $userid]);
    if ($payment === false) {
        $paymentid = null;
    } else if (empty($payment->timepaid) === false || $payment->method == 'atouts') {
        // Les paiements déjà validés (statut PAID & GIFT), et les paiements Atouts Normandie ne peuvent pas être modifiés.
        redirect($backurl, get_string('error_payment_not_editable', 'local_apsolu'), null, \core\output\notification::NOTIFY_ERROR);
        exit(1);
    }
}

// Generate object.
if ($paymentid === null) {
    $payment = new stdClass();
    $payment->id = 0;
    $payment->method = 'coins';
    $payment->source = 'apsolu';
    $payment->amount = '';
    $payment->status = '';
    $payment->timepaid = '';
    $payment->timecreated = '';
    $payment->timemodified = '';
    $payment->userid = $userid;
    $payment->paymentcenterid = '1';

    $formtitle = get_string('add_payment', 'local_apsolu');
} else {
    foreach ($DB->get_records('apsolu_payments_items', ['paymentid' => $payment->id]) as $item) {
        $cardname = 'card' . $item->cardid;
        $payment->{$cardname} = 1;
    }

    $formtitle = get_string('edit_payment', 'local_apsolu');
}

// Build form.
$enabledmethod = method::get_enabled_methods();
$nopaymentmethod = method::get_no_payment_method();
$methods = $enabledmethod + $nopaymentmethod;

$sources = [
    'apogee' => get_string('source_apogee', 'local_apsolu'),
    'apsolu' => get_string('source_apsolu', 'local_apsolu'),
    'manual' => get_string('source_manual', 'local_apsolu'),
    ];

$statuses = [
    Payment::PAID => get_string('paymentpaid', 'local_apsolu'),
    Payment::GIFT => get_string('paymentgift', 'local_apsolu'),
    ];

if ($paymentid !== null) {
    $statuses[Payment::DUE] = get_string('paymentdue', 'local_apsolu'); // Mode édition uniquement.
}

$centers = [];
foreach ($DB->get_records('apsolu_payments_centers') as $center) {
    $centers[$center->id] = $center->name;
}

$cards = [];
$checkedcards = [];
foreach ($DB->get_records('apsolu_payments_cards', $conditions = [], $sort = 'fullname') as $card) {
    $sql = "SELECT *" .
        " FROM {apsolu_payments} ap" .
        " JOIN {apsolu_payments_items} api ON ap.id = api.paymentid" .
        " WHERE ap.timepaid IS NOT NULL" .
        " AND api.cardid = :cardid" .
        " AND ap.userid = :userid";

    if ($DB->get_record_sql($sql, ['cardid' => $card->id, 'userid' => $userid]) == false) {
        $cards[$card->id] = $card->fullname; // La carte n'a pas encore été payée.
    }

    if ($payment->id != null) {
        $sql = "SELECT *" .
            " FROM {apsolu_payments_items} api" .
            " WHERE api.paymentid = :paymentid" .
            " AND api.cardid = :cardid";
        $paymentitem = $DB->get_record_sql($sql, ['cardid' => $card->id, 'paymentid' => $payment->id]);
        if ($DB->get_record_sql($sql, ['cardid' => $card->id, 'paymentid' => $payment->id]) != false) {
            // Mode édition : la carte avait été sélectionnée lors de la saisie initiale du paiement.
            $checkedcards[] = $card->id;
            $cards[$card->id] = $card->fullname; // La carte n'a pas encore été payée.
        }
    }
}

// Atouts Normandie (activé dans la configuration, et hors contexte d'édition de paiement).
$enableatouts = get_config('local_apsolu', 'enable_atouts') && $payment->id == null;
$atoutsopts = [];
if (empty($enableatouts) == false) {
    $atoutsopts = ['noatouts' => get_string('do_not_use', 'local_apsolu'), 'allatouts' => get_string('total_amount', 'local_apsolu'), 'partatouts' => get_string('partial_amount', 'local_apsolu')];
}

$customdata = [
    'payment' => $payment,
    'methods' => $methods,
    'sources' => $sources,
    'statuses' => $statuses,
    'centers' => $centers,
    'cards' => $cards,
    'nopaymentmethod' => array_key_first($nopaymentmethod),
    'atoutsopts' => $atoutsopts,
    'checkedcards' => $checkedcards,
];

$mform = new local_apsolu_payment_payments_edit_form(null, $customdata);

if ($data = $mform->get_data()) {
    // Save data.
    $items = [];
    foreach ($cards as $cardid => $cardname) {
        $name = 'card' . $cardid;
        if (empty($data->{$name}) === false) {
            $items[] = $cardid;
        }
    }

    if (count($items) === 0) {
        throw new moodle_exception('error_missing_items', 'local_apsolu', $backurl);
    }

    $payment->method = $data->method;
    $payment->source = $data->source;
    $payment->amount = $data->amount;
    $payment->status = intval($data->status);
    $payment->timemodified = core_date::strftime('%FT%T');
    $payment->paymentcenterid = $data->center;

    switch ($payment->status) {
        case Payment::PAID:
        case Payment::GIFT:
            $payment->timepaid = $payment->timemodified;
            break;
        default:
            $payment->timepaid = null;
    }

    // Atouts Normandie.
    $complement = false;
    if (empty($enableatouts) == false && $data->atouts['atoutsopt'] !== 'noatouts') {
        if ($data->atouts['atoutsopt'] === 'allatouts') {
            $payment->method = 'atouts'; // Le paiement effectué en intégralité via Atouts Normandie.
            $payment->status = Payment::PAID; // Devrait toujours être le cas (règle de validation).
            $payment->timepaid = $payment->timemodified;
            $payment->timecreated = $payment->timemodified;
            unset($payment->id);
        } else {
            $complement = clone $payment; // Complément de paiement (ex. Atouts Normandie).
            $complement->method = 'atouts';
            $complement->amount = $data->atouts['amountatouts'];
            $complement->status = Payment::PAID;
            $complement->timepaid = $payment->timemodified;
            $complement->timecreated = $payment->timemodified;
            unset($complement->id);
        }
    }

    try {
        $transaction = $DB->start_delegated_transaction();

        if (empty($payment->id) === true) {
            $payment->timecreated = core_date::strftime('%FT%T');

            unset($payment->id);
            $payment->id = $DB->insert_record('apsolu_payments', $payment);
            $eventclassname = '\local_apsolu\event\payment_created';
        } else {
            $DB->update_record('apsolu_payments', $payment);
            $eventclassname = '\local_apsolu\event\payment_updated';
        }

        $sql = "DELETE FROM {apsolu_payments_items} WHERE paymentid = :paymentid";
        $DB->execute($sql, ['paymentid' => $payment->id]);

        foreach ($items as $cardid) {
            $item = new stdClass();
            $item->paymentid = $payment->id;
            $item->cardid = $cardid;

            $DB->insert_record('apsolu_payments_items', $item);
        }

        $event = $eventclassname::create([
            'objectid' => $payment->id,
            'relateduserid' => $payment->userid,
            'context' => context_system::instance(),
            'other' => ['items' => $items],
        ]);
        $event->trigger();


        // Complément de paiement ex. Atouts Normandie.
        if ($complement !== false) {
            // Uniquement en saisie (nouveau paiement, interface gestionnaire) : statut PAID accepté uniquement.
            // On ne lie pas le paiement partiel à un item (carte). Seul le paiement principal y fait référence.
            $complement->id = $DB->insert_record('apsolu_payments', $complement);
            $eventclassname = '\local_apsolu\event\payment_created';
        }

        if ($payment->status !== Payment::DUE) {
            // Enregistre l'évènement de réussite du paiement.
            $event = \local_apsolu\event\payment_approved::create([
                'objectid' => $payment->id,
                'relateduserid' => $payment->userid,
                'context' => context_system::instance(),
            ]);
            $event->trigger();
        }

        $success = true;

        $transaction->allow_commit();
    } catch (Exception $exception) {
        $success = false;
        $transaction->rollback($exception);
    }

    if ($success === true) {
        // Display notification and go back to user's paiement list.
        $notification = get_string('changessaved');
        redirect($backurl, $notification, $delay = null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        // Display form.
        echo '<h1>' . $formtitle . '</h1>';
        echo $OUTPUT->notification(get_string('cannotsavedata', 'error'));
        $mform->display();
    }
} else {
    // Display form.
    echo '<h1>' . $formtitle . '</h1>';

    $mform->display();
}
