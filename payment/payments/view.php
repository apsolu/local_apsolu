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

use UniversiteRennes2\Apsolu\Payment;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/local/apsolu/locallib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot.'/local/apsolu/classes/apsolu/payment.php');

$userid = optional_param('userid', null, PARAM_INT);

if (isset($userid)) {
    $user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
    $customfields = profile_user_record($userid);

    $data = new stdClass();
    $data->wwwroot = $CFG->wwwroot;
    $data->userid = $userid;
    $data->useridentity = $OUTPUT->render(new user_picture($user)).' '.fullname($user);
    $data->payments = array();
    $data->count_payments = 0;
    $data->has_sesame = (isset($customfields->apsolusesame) && $customfields->apsolusesame == 1);
    $data->user_auth = get_string('pluginname', 'auth_'.$user->auth);

    $payments = $DB->get_records('apsolu_payments', array('userid' => $userid), $sort = 'timemodified');
    foreach ($payments as $payment) {
        if (!empty($payment->timepaid)) {
            try {
                $timepaid = new DateTime($payment->timepaid);
                $payment->timepaid = strftime('%c', $timepaid->getTimestamp());
            } catch (Exception $exception) {

            }
        }
        $payment->amount_string = money_format('%i', $payment->amount).' €';
        $payment->method_string = get_string('method_'.$payment->method, 'local_apsolu');
        $payment->source_string = get_string('source_'.$payment->source, 'local_apsolu');

        switch ($payment->status) {
            case Payment::PAID:
                $payment->status_style = 'success';
                $payment->status_string = get_string('paymentpaid', 'local_apsolu');
                break;
            case Payment::GIFT:
                $payment->status_style = 'success';
                $payment->status_string = get_string('paymentgift', 'local_apsolu');
                break;
            case Payment::DUE:
            default:
                $payment->status_style = 'danger';
                $payment->status_string = get_string('paymentdue', 'local_apsolu');
        }

        $payment->items = array();
        $payment->count_items = 0;
        $sql = "SELECT api.*, apc.fullname".
            " FROM {apsolu_payments_items} api".
            " JOIN {apsolu_payments_cards} apc ON apc.id = api.cardid".
            " WHERE api.paymentid = :paymentid";
        $items = $DB->get_records_sql($sql, array('paymentid' => $payment->id));
        foreach ($items as $item) {
            $payment->items[] = $item->fullname;
            $payment->count_items++;
        }

        $data->payments[] = $payment;
        $data->count_payments++;
    }

    $data->backurl = $CFG->wwwroot.'/local/apsolu/payment/admin.php?tab=payments';
} else {
    // Create the user selector objects.
    $options = array('multiselect' => false);
    $userselector = new \UniversiteRennes2\Apsolu\local_apsolu_payment_user_selector('userid', $options);
    ob_start();
    $userselector->display();
    $userselector = ob_get_contents();
    ob_end_clean();

    $data = new stdClass();
    $data->wwwroot = $CFG->wwwroot;
    $data->action = $CFG->wwwroot.'/local/apsolu/payment/admin.php?tab=payments';
    $data->user_selector = $userselector;
}

if (isset($notification)) {
    echo $notification;
}

echo $OUTPUT->render_from_template('local_apsolu/payment_payments', $data);
