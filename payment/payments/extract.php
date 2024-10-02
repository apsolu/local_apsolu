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
 * Page gérant l'extraction des paiements.
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$centerid = required_param('centerid', PARAM_INT);
$format = optional_param('format', 'csv', PARAM_ALPHA);

require_once($CFG->dirroot.'/user/profile/lib.php');

$center = $DB->get_record('apsolu_payments_centers', ['id' => $centerid], $fields = '*', MUST_EXIST);
$cards = $DB->get_records('apsolu_payments_cards', ['centerid' => $center->id], $sort = 'name, fullname');

$filename = clean_filename($center->name);

$headers = [
    get_string('lastname'),
    get_string('firstname'),
    get_string('idnumber'),
    get_string('institution'),
    get_string('department'),
    get_string('method', 'local_apsolu'),
    get_string('date', 'local_apsolu'),
    get_string('amount', 'local_apsolu'),
    get_string('payment_number', 'local_apsolu'),
];

foreach ($cards as $card) {
    $headers[] = $card->fullname;
}

if ($format === 'xls') {
    // Export au format excel.
    require_once($CFG->libdir.'/excellib.class.php');

    $workbook = new MoodleExcelWorkbook('-');
    $workbook->send($filename);
    $myxls = $workbook->add_worksheet();

    if (class_exists('PHPExcel_Style_Border') === true) {
        // Jusqu'à Moodle 3.7.x.
        $properties = ['border' => PHPExcel_Style_Border::BORDER_THIN];
    } else {
        // Depuis Moodle 3.8.x.
        $properties = ['border' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN];
    }

    $excelformat = new MoodleExcelFormat($properties);

    foreach ($headers as $position => $value) {
        $myxls->write_string(0, $position, $value, $excelformat);
    }
} else {
    // Export au format csv.
    require_once($CFG->libdir.'/csvlib.class.php');

    $csvexport = new \csv_export_writer();
    $csvexport->set_filename($filename);
    $csvexport->add_data($headers);
}

$sql = "SELECT ap.*, apc.prefix, u.lastname, u.firstname, u.idnumber, u.institution, u.department
          FROM {apsolu_payments} ap
          JOIN {apsolu_payments_centers} apc ON apc.id = ap.paymentcenterid
          JOIN {user} u ON u.id = ap.userid
         WHERE ap.status = 1
           AND ap.paymentcenterid = :centerid
      ORDER BY ap.timepaid DESC, u.lastname, u.firstname, u.institution";
$payments = $DB->get_records_sql($sql, ['centerid' => $center->id]);
$line = 1;
foreach ($payments as $payment) {
    raise_memory_limit(MEMORY_EXTRA);

    $usercards = $DB->get_records('apsolu_payments_items', ['paymentid' => $payment->id], $sort = null, $fields = 'cardid');

    try {
        $timepaid = new DateTime($payment->timepaid);
        $timepaid = $timepaid->format('d-m-Y H:i:s');
    } catch (Exception $exception) {
        $timepaid = '';
    }

    // Affiche le préfixe PayBox.
    if (empty($payment->prefix) === false) {
        $payment->id = $payment->prefix.$payment->id;
    }

    if ($payment->method !== 'paybox') {
        $payment->id = '';
    }

    $data = [
        $payment->lastname,
        $payment->firstname,
        $payment->idnumber,
        $payment->institution,
        $payment->department,
        get_string('method_'.$payment->method, 'local_apsolu'),
        $timepaid,
        $payment->amount,
        $payment->id,
        ];

    foreach ($cards as $card) {
        if (isset($usercards[$card->id]) === true) {
            $data[] = get_string('yes');
        } else {
            $data[] = get_string('no');
        }
    }

    if ($format === 'xls') {
        foreach ($data as $position => $value) {
            if ($position === 7) {
                $myxls->write_number($line, $position, $value, ['num_format' => '0.00']);
            } else {
                $myxls->write_string($line, $position, $value, $excelformat);
            }
        }
        $line++;
    } else {
        $csvexport->add_data($data);
    }
}

if ($format === 'xls') {
    $workbook->close();
} else {
    $csvexport->download_file();
}

exit(0);
