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
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// intégrer les contraintes demander par Agnes

// Avant tout, loguer la réponse de paybox !
$outputsuccess = '/var/log/applis/paybox.log';
$outputinfo = '/var/log/applis/info.paybox.log';
$outputerror = '/var/log/applis/error.paybox.log';

$outputsuccesscontent = '';
$outputinfocontent = '';

if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    // Check ip from share internet.
    $ip = $_SERVER['HTTP_CLIENT_IP'];
} else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    // To check ip is pass from proxy.
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
} else {
    $ip = $_SERVER['REMOTE_ADDR'];
}

$response = str_replace("\n", '', var_export($_GET, true));
$outputinfocontent = strftime('%c').' '.$ip.' :: '.$response.PHP_EOL;

file_put_contents($outputinfo, $outputinfocontent, FILE_APPEND | LOCK_EX);

require(__DIR__.'/../../../config.php');
require_once($CFG->dirroot.'/local/apsolu/locallib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');

$ip = getremoteaddr();
$outputsuccesscontent = strftime('%c').' '.$ip.' :: '.$response.PHP_EOL;
$payboxaddresses = explode(',', get_config('local_apsolu', 'paybox_servers_incoming'));

try {
    if (in_array($ip, $payboxaddresses, true) === false) {
        throw new Exception('Bad ip: '.$ip);
    }

    if (!isset($_GET['Mt'], $_GET['Ref'], $_GET['Auto'], $_GET['Erreur'])) {
        throw new Exception('Invalid args: '.var_export($_GET, true));
    }

    $payment = $DB->get_record('apsolu_payments', array('id' => $_GET['Ref']));
    if (!$payment) {
        throw new Exception('Unknown payment: '.$_GET['Ref']);
    }

    $user = $DB->get_record('user', array('id' => $payment->userid));
    if (!$user) {
        $user = new stdClass();
        $user->id = $payment->userid;
        $user->firstname = '';
        $user->lastname = '';
        $user->username = 'inconnu';
        $userstr = $user->username;
    } else {
        $userstr = $user->id.' - '.$user->firstname.' '.$user->lastname.' ('.$user->username.')';
    }

    $transaction = new stdClass();
    $transaction->timecreated = strftime('%FT%T');
    $transaction->amount = $_GET['Mt'];
    $transaction->reference = $_GET['Ref'];
    $transaction->auto = $_GET['Auto'];
    $transaction->error = $_GET['Erreur'];

    $transactionid = $DB->insert_record('apsolu_payments_transactions', $transaction);
    if (!$transactionid) {
        throw new Exception('Unable to write in apsolu_payments_transactions: '.var_export($transaction, true));
    }

    if ($_GET['Erreur'] !== '00000') {
        throw new Exception('Error from paybox: '.$_GET['Erreur']);
    }

    $payment->status = 1;
    $payment->timepaid = strftime('%FT%T');
    if (!$DB->update_record('apsolu_payments', $payment)) {
        throw new Exception('Unable to write in apsolu_payments: '.var_export($payment, true));
    }

    $outputsuccesscontent .= strftime('%c').' '.$ip.' :: OK for user '.$userstr.PHP_EOL;

    // Set user payment flag.
    $items = $DB->get_records('apsolu_payments_items', array('paymentid' => $_GET['Ref']));
    foreach ($items as $itemid => $item) {
        // TODO: à supprimer... on ne devrait plus avoir besoin des champs apsolufederationpaid, apsolumuscupaid et apsolucardpaid.
        switch ($item->cardid) {
            case 4:
                // FFSU.
                $userfield = (object) ['id' => $user->id, 'profile_field_apsolufederationpaid' => 1];
                profile_save_data($userfield);

                $outputsuccesscontent .= strftime('%c').' '.$ip.' :: set apsolufederationpaid attribute to value 1 for user '.$userstr.PHP_EOL;
                break;
            case 3:
                // Musculation.
                $userfield = (object) ['id' => $user->id, 'profile_field_apsolumuscupaid' => 1];
                profile_save_data($userfield);

                $outputsuccesscontent .= strftime('%c').' '.$ip.' :: set apsolumuscupaid attribute to value 1 for user '.$userstr.PHP_EOL;
                break;
            case 6:
            case 2:
            case 1:
                // Carte sport.
                $userfield = (object) ['id' => $user->id, 'profile_field_apsolucardpaid' => 1];
                profile_save_data($userfield);

                $outputsuccesscontent .= strftime('%c').' '.$ip.' :: set apsolucardpaid attribute to value 1 for user '.$userstr.PHP_EOL;
        }
    }

    if (!file_put_contents($outputsuccess, $outputsuccesscontent, FILE_APPEND | LOCK_EX)) {
        throw new Exception('Can\'t write in '.$outputsuccess);
    }
} catch (Exception $exception) {
    $trace = $exception->getMessage().PHP_EOL.$outputsuccesscontent;

    if (empty($CFG->debug) === false) {
        $users = get_admins();
        $from = $CFG->noreplyaddress;
        foreach ($users as $user) {
            email_to_user($user, $from, 'SIUAPS: anomalie paiement', $trace);
        }
    }

    file_put_contents($outputerror, $trace.PHP_EOL, FILE_APPEND | LOCK_EX);
}
