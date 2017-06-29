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
 * Affiche les effectifs du SIUAPS.
 *
 * @package    local_apsolu
 * @copyright  2017 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/excellib.class.php');
require_once($CFG->dirroot.'/enrol/select/locallib.php');
require_once($CFG->dirroot.'/local/apsolu/statistics/locallib.php');

$format = optional_param('format', null, PARAM_TEXT);
$institution = optional_param('institution', null, PARAM_TEXT);

// Set sub-tabs.
$roleid = optional_param('role', null, PARAM_INT);
$roles = UniversiteRennes2\Apsolu\get_custom_student_roles();

if (isset($roles[$roleid]) === false) {
	$roleid = null;
}

$subtabtree = array();
foreach ($roles as $role) {
    $url = new moodle_url('/local/apsolu/statistics/index.php', array('page' => $page, 'role' => $role->id));
    $subtabtree[] = new tabobject($role->id, $url, $role->name);
}

if (isset($roleid) === false) {
	$role = reset($roles);
	$roleid = $role->id;
}

$statistics = get_rosters_statistics($roleid, $institution);

$notification = (count($statistics) === 0);
if ($notification === true) {
	$statistics = get_rosters_statistics($roleid, null);
}

if (isset($format) === true && $notification === false) {
	// Download xls.

	// Creating a workbook.
	if (empty($institution) === true) {
		$filename = preg_replace('/[^a-zA-Z0-9_\.]/', '', 'tous_les_établissements_'.$roles[$roleid]->shortname.'.xls');
	} else {
		$filename = preg_replace('/[^a-zA-Z0-9_\.]/', '', trim($institution).'_'.$roles[$roleid]->shortname.'.xls');
	}

	$workbook = new MoodleExcelWorkbook($filename);

	// Adding the worksheet.
	$myxls = $workbook->add_worksheet();

	$excelformat = new MoodleExcelFormat(array('border' => PHPExcel_Style_Border::BORDER_THIN));

	// Set headers.
	$headers = array();
	$headers[] = 'Activités';
	$headers[] = 'S1 acceptés';
	$headers[] = 'S1 LP';
	$headers[] = 'S1 LC';
	$headers[] = 'S1 refusés';
	$headers[] = 'S2 acceptés';
	$headers[] = 'S2 LP';
	$headers[] = 'S2 LC';
	$headers[] = 'S2 refusés';

	foreach ($headers as $position => $value) {
		$myxls->write_string(0, $position, $value, $excelformat);
	}

	// Set data.
	$line = 1;
	foreach ($statistics as $statistic) {
		$myxls->write_string($line, 0, $statistic->name, $excelformat);
		$myxls->write_string($line, 1, $statistic->semester1_accepted, $excelformat);
		$myxls->write_string($line, 2, $statistic->semester1_main, $excelformat);
		$myxls->write_string($line, 3, $statistic->semester1_wait, $excelformat);
		$myxls->write_string($line, 4, $statistic->semester1_refused, $excelformat);
		$myxls->write_string($line, 5, $statistic->semester2_accepted, $excelformat);
		$myxls->write_string($line, 6, $statistic->semester2_main, $excelformat);
		$myxls->write_string($line, 7, $statistic->semester2_wait, $excelformat);
		$myxls->write_string($line, 8, $statistic->semester2_refused, $excelformat);

		$line++;
	}

	$workbook->close();
	exit(0);
}

// Display table.

$total = (object) [
	'semester1_accepted' => 0,
	'semester1_main' => 0,
	'semester1_wait' => 0,
	'semester1_refused' => 0,
	'semester2_accepted' => 0,
	'semester2_main' => 0,
	'semester2_wait' => 0,
	'semester2_refused' => 0,
	];

foreach ($statistics as $statistic) {
	$total->semester1_accepted += $statistic->semester1_accepted;
	$total->semester1_main += $statistic->semester1_main;
	$total->semester1_wait += $statistic->semester1_wait;
	$total->semester1_refused += $statistic->semester1_refused;

	$total->semester2_accepted += $statistic->semester2_accepted;
	$total->semester2_main += $statistic->semester2_main;
	$total->semester2_wait += $statistic->semester2_wait;
	$total->semester2_refused += $statistic->semester2_refused;
}

$data = new stdClass();
$data->statistics = $statistics;
$data->total = $total;

echo $OUTPUT->header();
echo $OUTPUT->tabtree($tabtree, $page);
echo $OUTPUT->tabtree($subtabtree, $roleid);

if ($notification === true) {
	echo $OUTPUT->notification('Aucune donnée à télécharger', 'notifysuccess');
}

// Set institutions.
$url = new moodle_url('/local/apsolu/statistics/index.php', array('page' => $page, 'role' => $roleid, 'format' => 'xls'));

echo html_writer::start_tag('div', array('class' => 'institutionpicker'));
echo '<ul class="list-inline text-right">';
echo '<li><a class="btn btn-primary " href="'.$url.'" title="Télécharger les données au format excel">Tous les établissements <span class="glyphicon glyphicon-download" aria-hidden="true"></span></a></li>';
foreach ($DB->get_records_sql('SELECT DISTINCT u.institution FROM {user} u WHERE u.auth="shibboleth" AND u.deleted = 0 ORDER BY u.institution') as $record) {
	if (empty($record->institution) === true || strpos($record->institution, '{') !== false) {
		continue;
	}

	$url = new moodle_url('/local/apsolu/statistics/index.php', array('page' => $page, 'role' => $roleid, 'institution' => $record->institution, 'format' => 'xls'));
	echo '<li><a class="btn btn-primary" href="'.$url.'" title="Télécharger les données au format excel">'.trim($record->institution).' <span class="glyphicon glyphicon-download" aria-hidden="true"></span></a></li>';
}
echo '</ul>';
echo html_writer::end_tag('div');

echo $OUTPUT->render_from_template('local_apsolu/statistics_people', $data);
echo $OUTPUT->footer();
