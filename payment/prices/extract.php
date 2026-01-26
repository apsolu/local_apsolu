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
 * Page pour exporter une vue d'ensemble d'utilisation des tarifs.
 *
 * @package    local_apsolu
 * @copyright  2024 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$exportformat = optional_param('format', 'csv', PARAM_ALPHA);

require_once($CFG->libdir . '/csvlib.class.php');
require_once($CFG->libdir . '/excellib.class.php');

$filename = get_string('payment_cards', 'local_apsolu');

$headers = [
    get_string('courses'),
    get_string('payment_cards', 'local_apsolu'),
    get_string('enrolmentinstances', 'enrol'),
];

$rows = [];

$sql = "SELECT apc.fullname AS cardname, c.fullname AS coursename, e.name AS method
          FROM {apsolu_payments_cards} apc
          JOIN {enrol_select_cards} esc ON esc.cardid = apc.id
          JOIN {enrol} e ON e.id = esc.enrolid
          JOIN {course} c ON c.id = e.courseid
         WHERE e.enrol = 'select'
      ORDER BY coursename, cardname, method";
$recordset = $DB->get_recordset_sql($sql);
foreach ($recordset as $record) {
    $rows[] = [$record->coursename, $record->cardname, $record->method];
}
$recordset->close();

switch ($exportformat) {
    case 'xls':
        // Creating a workbook.
        $workbook = new MoodleExcelWorkbook("-");
        // Sending HTTP headers.
        $workbook->send($filename);
        // Adding the worksheet.
        $myxls = $workbook->add_worksheet();
        $excelformat = new MoodleExcelFormat(['border' => PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]);

        // Set headers.
        foreach ($headers as $column => $value) {
            $myxls->write_string(0, $column, $value, $excelformat);
        }

        // Set data.
        foreach ($rows as $line => $row) {
            $line++;
            foreach ($row as $column => $value) {
                $myxls->write_string($line, $column, $value, $excelformat);
            }
        }

        // MDL-83543: positionne un cookie pour qu'un script js déverrouille le bouton submit après le téléchargement.
        setcookie('moodledownload_' . sesskey(), time());

        // Transmet le fichier au navigateur.
        $workbook->close();
        break;
    case 'csv':
    default:
        $csvexport = new csv_export_writer();
        $csvexport->set_filename($filename);
        $csvexport->add_data($headers);

        foreach ($rows as $row) {
            $csvexport->add_data($row);
        }
        $csvexport->download_file();
}

exit(0);
