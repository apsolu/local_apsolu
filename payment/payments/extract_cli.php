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

define('CLI_SCRIPT', true);

require __DIR__.'/../../../config.php';

$storages = array(
    $CFG->dataroot.'/apsolu/local_apsolu_payment/',
    $CFG->localcachedir.'/apsolu/local_apsolu_payment/',
    );
foreach ($storages as $storage) {
    if (!is_dir($storage)) {
        if (!mkdir($storage, $CFG->directorypermissions, true)) {
            exit(1);
        }
    }
}

require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->libdir . '/csvlib.class.php');

$filename = 'liste_des_paiements';
$headers = array(
    get_string('lastname'),
    get_string('firstname'),
    get_string('idnumber'),
    get_string('institution'),
    get_string('department'),
    get_string('method', 'local_apsolu'),
    get_string('date', 'local_apsolu'),
    get_string('amount', 'local_apsolu'),
    get_string('payment_number', 'local_apsolu'),
    get_string('sportcard', 'local_apsolu'),
    get_string('bodybuilding', 'local_apsolu'),
    get_string('highlevelathlete', 'theme_apsolu'),
    get_string('source_apogee', 'local_apsolu'),
    get_string('payments', 'local_apsolu'),
    );

$fp = fopen($storages[1].'/extraction_paiement.csv', 'w');
fputcsv($fp, $headers);

$sql = "SELECT ap.*, u.lastname, u.firstname, u.idnumber, u.institution, u.department".
    " FROM {user} u".
    " JOIN {apsolu_payments} ap ON u.id = ap.userid".
    " WHERE ap.status = 1".
    " AND ap.paymentcenterid = 1". // Caisse Rennes 1.
    " AND ap.amount > 0". // Bug R2.
    " ORDER BY ap.timepaid DESC, u.lastname, u.firstname, u.institution";
$payments = $DB->get_records_sql($sql);
foreach ($payments as $payment) {
    raise_memory_limit(MEMORY_EXTRA);

    $customfields = profile_user_record($payment->userid);

    if (isset($customfields->muscupaid) && $customfields->muscupaid == 1) {
        $bodybuilding = get_string('yes');
    } else {
        $bodybuilding = get_string('no');
    }

    if (isset($customfields->cardpaid) && $customfields->cardpaid == 1) {
        $card = get_string('yes');
    } else {
        $card = get_string('no');
    }

    if (isset($customfields->highlevelathlete) && $customfields->highlevelathlete == 1) {
        $highathlete = get_string('yes');
    } else {
        $highathlete = get_string('no');
    }

    $got_apogee = ((isset($customfields->optionpaid) && $customfields->optionpaid == 1) ||
        (isset($customfields->bonificationpaid) && $customfields->bonificationpaid == 1) ||
        (isset($customfields->librepaid) && $customfields->librepaid == 1));
    if ($got_apogee) {
        $apogee = get_string('yes');
    } else {
        $apogee = get_string('no');
    }

    try {
        $timepaid = new DateTime($payment->timepaid);
        $timepaid = $timepaid->format('d-m-Y H:i:s');
    } catch(Exception $exception) {
        $timepaid = '';
    }

    if ($payment->method === 'paybox') {
        $transactionid = $payment->id;
    } else {
        $transactionid = '';
    }

    // Autres transactions.
    $others = array();

    $sql = "SELECT ap.*".
        " FROM {apsolu_payments} ap".
        " WHERE ap.status = 1".
        " AND ap.paymentcenterid = 1". // Caisse Rennes 1.
        " AND ap.amount > 0". // Bug R2.
        " AND ap.userid = :userid".
        " AND ap.id != :id";
    foreach ($DB->get_records_sql($sql, array('id' => $payment->id, 'userid' => $payment->userid)) as $other) {
        try {
            $othertimepaid = new DateTime($other->timepaid);
            $othertimepaid = $othertimepaid->format('d-m-Y H:i');
        } catch(Exception $exception) {
            $othertimepaid = '';
        }

        if ($other->source === 'apsolu') {
            $others[] = $other->amount.' € le '.$othertimepaid;
        } else {
            $others[] = $other->amount.' € le '.$othertimepaid.' (via '.$other->source.')';
        }
    }

    $data = array(
        $payment->lastname,
        $payment->firstname,
        $payment->idnumber,
        $payment->institution,
        $payment->department,
        get_string('method_'.$payment->method, 'local_apsolu'),
        $timepaid,
        $payment->amount,
        $transactionid,
        $card,
        $bodybuilding,
        $highathlete,
        $apogee,
        implode(', ', $others),
        );
    fputcsv($fp, $data);
}

fclose($fp);

if (!copy($storages[1].'/extraction_paiement.csv', $storages[0].'/extraction_paiement.csv')) {
    exit(1);
}
