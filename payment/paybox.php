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
 * Handler for paybox responses.
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:disable moodle.Files.RequireLogin.Missing

use UniversiteRennes2\Apsolu\Payment;

require(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/local/apsolu/classes/apsolu/payment.php');
require_once($CFG->dirroot . '/local/apsolu/locallib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

$PAGE->set_context(context_system::instance());

$ip = getremoteaddr();
$response = json_encode($_GET);

$outputsuccesscontent = core_date::strftime('%c') . ' ' . $ip . ' :: ' . $response . PHP_EOL;
$payboxaddresses = explode(',', get_config('local_apsolu', 'paybox_servers_incoming'));

try {
    $outputerror = get_config('local_apsolu', 'paybox_log_error_path');
    if (empty($outputerror) === true) {
        throw new Exception('La variable "paybox_log_error_path" n\'est pas configurée.');
    }

    $outputsuccess = get_config('local_apsolu', 'paybox_log_success_path');
    if (empty($outputsuccess) === true) {
        throw new Exception('La variable "paybox_log_success_path" n\'est pas configurée.');
    }

    if (in_array($ip, $payboxaddresses, true) === false) {
        throw new Exception('Bad ip: ' . $ip);
    }

    if (!isset($_GET['Mt'], $_GET['Ref'], $_GET['Auto'], $_GET['Erreur'])) {
        throw new Exception('Invalid args: ' . var_export($_GET, true));
    }

    // Récupère l'id dans le numéro de commande.
    $id = Payment::get_id_from_refid($_GET['Ref']);
    if ($id === false) {
        throw new Exception('Unknown payment (cannot parse id): ' . $_GET['Ref']);
    }

    $payment = $DB->get_record('apsolu_payments', ['id' => $id]);
    if (!$payment) {
        throw new Exception('Unknown payment (not found): ' . $_GET['Ref']);
    }

    $user = $DB->get_record('user', ['id' => $payment->userid]);
    if (!$user) {
        $user = new stdClass();
        $user->id = $payment->userid;
        $user->firstname = '';
        $user->lastname = '';
        $user->username = 'inconnu';
        $userstr = $user->username;
    } else {
        $userstr = $user->id . ' - ' . $user->firstname . ' ' . $user->lastname . ' (' . $user->username . ')';
    }

    $transaction = new stdClass();
    $transaction->timecreated = core_date::strftime('%FT%T');
    $transaction->amount = $_GET['Mt'];
    $transaction->reference = $_GET['Ref'];
    $transaction->auto = $_GET['Auto'];
    $transaction->error = $_GET['Erreur'];

    $transactionid = $DB->insert_record('apsolu_payments_transactions', $transaction);
    if (!$transactionid) {
        throw new Exception('Unable to write in apsolu_payments_transactions: ' . var_export($transaction, true));
    }

    if ($_GET['Erreur'] !== '00000') {
        throw new Exception('Error from paybox: ' . $_GET['Erreur']);
    }

    $payment->status = 1;
    $payment->timepaid = core_date::strftime('%FT%T');
    if (!$DB->update_record('apsolu_payments', $payment)) {
        throw new Exception('Unable to write in apsolu_payments: ' . var_export($payment, true));
    }

    $outputsuccesscontent .= core_date::strftime('%c') . ' ' . $ip . ' :: OK for user ' . $userstr . PHP_EOL;

    if (!file_put_contents($outputsuccess, $outputsuccesscontent, FILE_APPEND | LOCK_EX)) {
        throw new Exception('Can\'t write in ' . $outputsuccess);
    }
} catch (Exception $exception) {
    $trace = $exception->getMessage() . PHP_EOL . $outputsuccesscontent;

    if (empty($outputerror) === false) {
        file_put_contents($outputerror, $trace . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
