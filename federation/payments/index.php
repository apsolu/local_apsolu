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
 * Script d'exportation des paiements FFSU.
 *
 * @package    local_apsolu
 * @copyright  2023 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use UniversiteRennes2\Apsolu\Payment;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/csvlib.class.php');
require_once($CFG->dirroot.'/local/apsolu/classes/apsolu/payment.php');

$returnurl = new moodle_url('/local/apsolu/federation/index.php', ['page' => 'payments']);

// Récupère toutes les cartes associées à ce cours.
$cards = [];
foreach (Payment::get_course_cards($courseid) as $card) {
    $cards[] = $card->id;
}

// Récupère tous les adhérants ayant payé.
list($insql, $params) = $DB->get_in_or_equal($cards, SQL_PARAMS_NAMED, 'cardid_');

$sql = "SELECT u.lastname, u.firstname, u.email, u.idnumber, u.institution, u.department,
               ap.method, ap.timepaid, ap.amount, ap.id, apc.prefix, afa.mainsport
          FROM {user} u
          JOIN {apsolu_federation_adhesions} afa ON u.id = afa.userid
          JOIN {apsolu_payments} ap ON ap.userid = afa.userid
          JOIN {apsolu_payments_centers} apc ON apc.id = ap.paymentcenterid
          JOIN {apsolu_payments_items} api ON ap.id = api.paymentid
         WHERE api.cardid $insql
           AND ap.status != :status
      ORDER BY u.lastname, u.firstname, ap.timepaid DESC";
$params['status'] = Payment::DUE;
$recordset = $DB->get_recordset_sql($sql, $params);

if ($recordset->valid()) {
    // Récupère la liste des sports.
    $federationactivities = $DB->get_records('apsolu_federation_activities');

    // Construit le fichier csv.
    $filename = clean_filename(get_string('federation_payments', 'local_apsolu'));
    $csvexport = new \csv_export_writer();
    $csvexport->set_filename($filename);

    $headers = [
        get_string('lastname'),
        get_string('firstname'),
        get_string('email'),
        get_string('idnumber'),
        get_string('institution'),
        get_string('department'),
        get_string('main_sport', 'local_apsolu'),
        get_string('method', 'local_apsolu'),
        get_string('date', 'local_apsolu'),
        get_string('amount', 'local_apsolu'),
        get_string('payment_number', 'local_apsolu'),
    ];
    $csvexport->add_data($headers);

    foreach ($recordset as $record) {
        if (empty($record->prefix) === false) {
            $record->id = $record->prefix.$record->id;
        }

        if ($record->method !== 'paybox') {
            $record->id = '';
        }

        try {
            $timepaid = new DateTime($record->timepaid);
            $timepaid = $timepaid->format('d-m-Y H:i:s');
        } catch(Exception $exception) {
            $timepaid = '';
        }

        $row = [];
        $row[] = $record->lastname;
        $row[] = $record->firstname;
        $row[] = $record->email;
        $row[] = $record->idnumber;
        $row[] = $record->institution;
        $row[] = $record->department;
        $row[] = $federationactivities[$record->mainsport]->name;
        $row[] = get_string('method_'.$record->method, 'local_apsolu');
        $row[] = $timepaid;
        $row[] = $record->amount;
        $row[] = $record->id;

        $csvexport->add_data($row);
    }
    $recordset->close();

    $csvexport->download_file();
    exit(0);
}

$recordset->close();

echo $OUTPUT->header();
echo $OUTPUT->tabtree($tabtree, $page);
echo html_writer::div(get_string('nodata', 'local_apsolu'), 'alert alert-info');
echo $OUTPUT->footer();
