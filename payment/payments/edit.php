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
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use UniversiteRennes2\Apsolu\Payment;

defined('MOODLE_INTERNAL') || die;

require(__DIR__.'/edit_form.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot.'/local/apsolu/locallib.php');
require_once($CFG->dirroot.'/local/apsolu/classes/apsolu/payment.php');

// Get user id.
$userid = required_param('userid', PARAM_INT);
$user = $DB->get_record('user', array('id' => $userid, 'deleted' => '0'));

if ($user === false) {
    print_error('invaliduser');
}

// TODO: vérifier le témoin : sesame valide.

$backurl = $CFG->wwwroot.'/local/apsolu/payment/admin.php?tab=payments&userid='.$userid;

$paymentid = optional_param('paymentid', null, PARAM_INT);
if ($paymentid !== null) {
    $payment = $DB->get_record('apsolu_payments', array('id' => $paymentid, 'userid' => $userid));
    if ($payment === false) {
        $paymentid = null;
    } else if (empty($payment->timepaid) === false) {
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
} else {
    foreach ($DB->get_records('apsolu_payments_items', array('paymentid' => $payment->id)) as $item) {
        $cardname = 'card'.$item->cardid;
        $payment->{$cardname} = 1;
    }
}

// Build form.
$methods = array(
    'card' => get_string('method_card', 'local_apsolu'),
    'check' => get_string('method_check', 'local_apsolu'),
    'coins' => get_string('method_coins', 'local_apsolu'),
    'paybox' => get_string('method_paybox', 'local_apsolu'),
    );

$sources = array(
    'apogee' => get_string('source_apogee', 'local_apsolu'),
    'apsolu' => get_string('source_apsolu', 'local_apsolu'),
    'manual' => get_string('source_manual', 'local_apsolu'),
    );

$statuses = array(
    Payment::PAID => get_string('paymentpaid', 'local_apsolu'),
    Payment::DUE => get_string('paymentdue', 'local_apsolu'),
    Payment::GIFT => get_string('paymentgift', 'local_apsolu'),
    );

$centers = array();
foreach ($DB->get_records('apsolu_payments_centers') as $center) {
    $centers[$center->id] = $center->name;
}

$cards = array();
foreach ($DB->get_records('apsolu_payments_cards', $conditions = array(), $sort = 'fullname') as $card) {
    $sql = "SELECT *".
        " FROM {apsolu_payments} ap".
        " JOIN {apsolu_payments_items} api ON ap.id = api.paymentid".
        " WHERE ap.timepaid IS NOT NULL".
        " AND api.cardid = :cardid".
        " AND ap.userid = :userid";
    if ($DB->get_record_sql($sql, array('cardid' => $card->id, 'userid' => $userid)) !== false) {
        continue;
    }
    $cards[$card->id] = $card->fullname;
}

$customdata = array('payment' => $payment, 'methods' => $methods, 'sources' => $sources, 'statuses' => $statuses, 'centers' => $centers, 'cards' => $cards);
$mform = new local_apsolu_payment_payments_edit_form(null, $customdata);

if ($data = $mform->get_data()) {
    // Save data.
    $items = array();
    foreach ($cards as $cardid => $cardname) {
        $name = 'card'.$cardid;
        if (isset($data->{$name}) === true) {
            $items[] = $cardid;
        }
    }

    if (count($items) === 0) {
        print_error('error_missing_items', 'local_apsolu', $backurl);
    }

    $payment->method = $data->method;
    $payment->source = $data->source;
    $payment->amount = $data->amount;
    $payment->status = intval($data->status);
    $payment->timemodified = strftime('%FT%T');
    $payment->paymentcenterid = $data->center;

    switch ($payment->status) {
        case Payment::PAID:
        case Payment::GIFT:
            if ($payment->status === Payment::GIFT) {
                $payment->amount = 0;
            }
            $payment->timepaid = $payment->timemodified;
            break;
        default:
            $payment->timepaid = null;
    }

    try {
        $transaction = $DB->start_delegated_transaction();

        if (empty($payment->id) === true) {
            $payment->timecreated = strftime('%FT%T');

            unset($payment->id);
            $payment->id = $DB->insert_record('apsolu_payments', $payment);
        } else {
            $DB->update_record('apsolu_payments', $payment);
        }

        $sql = "DELETE FROM {apsolu_payments_items} WHERE paymentid = :paymentid";
        $DB->execute($sql, array('paymentid' => $payment->id));

        foreach ($items as $cardid) {
            $item = new stdClass();
            $item->paymentid = $payment->id;
            $item->cardid = $cardid;

            $DB->insert_record('apsolu_payments_items', $item);
        }

        $event = \local_apsolu\event\update_user_payment::create(array(
            'relateduserid' => $userid,
            'context' => context_system::instance(),
            'other' => json_encode(array('payment' => $payment, 'items' => $items)),
        ));
        $event->trigger();

        $success = true;

        $transaction->allow_commit();
    } catch (Exception $exception) {
        $success = false;
        $transaction->rollback($exception);
    }

    if ($success === true) {
        // Display notification and display elements list.
        $notification = $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');

        require(__DIR__.'/view.php');
    } else {
        // Display form.
        echo '<h1>'.get_string('add_payment', 'local_apsolu').'</h1>';
        echo $OUTPUT->notification(get_string('cannotsavedata', 'error'));
        $mform->display();
    }
} else {
    // Display form.
    echo '<h1>'.get_string('add_payment', 'local_apsolu').'</h1>';

    $mform->display();
}
