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

require __DIR__.'/../../../../config.php';

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

$cards = $DB->get_records('apsolu_payments_cards', array('centerid' => 1), $sort = 'name, fullname');

$filename = 'liste_des_paiements';
$headers = array(
    get_string('lastname'),
    get_string('firstname'),
    get_string('idnumber'),
    get_string('institution'),
    get_string('department'),
    get_string('fields_apsoluhighlevelathlete', 'local_apsolu'),
    get_string('method', 'local_apsolu'),
    get_string('date', 'local_apsolu'),
    get_string('amount', 'local_apsolu'),
    get_string('payment_number', 'local_apsolu')
);

foreach ($cards as $card) {
    $headers[] = $card->fullname;
}

$fp = fopen($storages[1].'/extraction_paiement.csv', 'w');
fputcsv($fp, $headers);

$sql = "SELECT uid.userid".
    " FROM {user_info_data} uid".
    " JOIN {user_info_field} uif ON uif.id = uid.fieldid".
    " WHERE uif.shortname = 'apsoluhighlevelathlete'".
    " AND uid.data = 1";
$highlevelathletes = $DB->get_records_sql($sql);

$sql = "SELECT ap.*, u.lastname, u.firstname, u.idnumber, u.institution, u.department".
    " FROM {apsolu_payments} ap".
    " JOIN {user} u ON u.id = ap.userid".
    " WHERE ap.status = 1".
    " AND ap.paymentcenterid = 1". // Caisse Rennes SIUAPS.
    " ORDER BY ap.timepaid DESC, u.lastname, u.firstname, u.institution";
$payments = $DB->get_records_sql($sql);
foreach ($payments as $payment) {
    raise_memory_limit(MEMORY_EXTRA);

    $usercards = $DB->get_records('apsolu_payments_items', array('paymentid' => $payment->id), $sort = null, $fields = 'cardid');

    if (isset($highlevelathletes[$payment->userid]) === true) {
        $payment->highlevelathlete = get_string('yes');
    } else {
        $payment->highlevelathlete = get_string('no');
    }

    try {
        $timepaid = new DateTime($payment->timepaid);
        $timepaid = $timepaid->format('d-m-Y H:i:s');
    } catch(Exception $exception) {
        $timepaid = '';
    }

    if ($payment->method !== 'paybox') {
        $payment->id = '';
    }

    $data = array(
        $payment->lastname,
        $payment->firstname,
        $payment->idnumber,
        $payment->institution,
        $payment->department,
        $payment->highlevelathlete,
        get_string('method_'.$payment->method, 'local_apsolu'),
        $timepaid,
        $payment->amount,
        $payment->id,
        );

    foreach ($cards as $card) {
        if (isset($usercards[$card->id]) === true) {
            $data[] = get_string('yes');
        } else {
            $data[] = get_string('no');
        }
    }

    fputcsv($fp, $data);
}

fclose($fp);

if (!copy($storages[1].'/extraction_paiement.csv', $storages[0].'/extraction_paiement.csv')) {
    exit(1);
}
