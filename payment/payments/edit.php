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
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require(__DIR__.'/edit_form.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot.'/local/apsolu/locallib.php');

// Get user id.
$userid = required_param('userid', PARAM_INT);
$user = $DB->get_record('user', array('id' => $userid, 'auth' => 'shibboleth'));

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
    } else {
        foreach ($DB->get_records('apsolu_payments_items', array('paymentid' => $paymentid)) as $item) {
            switch ($item->courseid) {
                case 249:
                    $payment->course249 = 1;
                    break;
                case 250:
                    $payment->course250 = 1;
                    break;
                default:
                    $payment->course0 = 1;
            }
        }
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
}

// Build form.
$methods = array(
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
    '1' => get_string('status_success', 'local_apsolu'),
    '0' => get_string('status_error', 'local_apsolu'),
    );

$centers = array();
foreach ($DB->get_records('apsolu_payments_centers') as $center) {
    $centers[$center->id] = $center->name;
}

$courses = array(
    '0' => get_string('activities', 'local_apsolu'),
    '250' => get_string('bodybuilding', 'local_apsolu'),
    '249' => get_string('association', 'local_apsolu'),
    );

$customdata = array('payment' => $payment, 'methods' => $methods, 'sources' => $sources, 'statuses' => $statuses, 'centers' => $centers, 'courses' => $courses);
$mform = new local_apsolu_payment_payments_edit_form(null, $customdata);

if ($data = $mform->get_data()) {
    // Save data.
    $items = array();
    foreach ($courses as $courseid => $coursename) {
        $name = 'course'.$courseid;
        if (isset($data->{$name}) === true) {
            $items[] = $courseid;
        }
    }

    if (count($items) === 0) {
        print_error('error_missing_items', 'local_apsolu', $backurl);
    }

    $payment->method = $data->method;
    $payment->source = $data->source;
    $payment->amount = $data->amount;
    $payment->status = $data->status;
    $payment->timecreated = strftime('%FT%T');
    $payment->timemodified = $payment->timecreated;
    if ($payment->status === '1') {
        $payment->timepaid = $payment->timemodified;
    } else {
        $payment->timepaid = null;
    }
    $payment->paymentcenterid = $data->center;

    try {
        $transaction = $DB->start_delegated_transaction();

        if (empty($payment->id) === true) {
            unset($payment->id);
            $paymentid = $DB->insert_record('apsolu_payments', $payment);
        } else {
            $paymentid = $payment->id;
            $DB->update_record('apsolu_payments', $payment);
        }

        foreach ($items as $courseid) {
            if (empty($payment->id) === true) {
                $item = new stdClass();
                $item->paymentid = $paymentid;
                $item->courseid = $courseid;
                $item->roleid = 0;

                $DB->insert_record('apsolu_payments_items', $item);
            }

            if ($payment->status === '1') {
                switch ($courseid) { // TODO: à refaire !
                    case '249':
                        $fieldid = 9;
                        break;
                    case '250':
                        $fieldid = 10;
                        break;
                    default:
                        $fieldid = 12;
                }

                $profile = $DB->get_record('user_info_data', array('fieldid' => $fieldid, 'userid' => $userid));
                if ($profile === false) {
                    $profile = new stdClass();
                    $profile->userid = $userid;
                    $profile->fieldid = $fieldid;
                    $profile->data = 1;

                    $DB->insert_record('user_info_data', $profile);
                } else {
                    $profile->data = 1;
                    $DB->update_record('user_info_data', $profile);
                }
            }
        }

        $event = \local_apsolu_payment\event\update_user_payment::create(array(
            'relateduserid' => $userid,
            'context' => context_system::instance(),
            'other' => json_encode(array('payment' => $payment)),
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
