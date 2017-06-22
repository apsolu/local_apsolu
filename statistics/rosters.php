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

require_once($CFG->dirroot.'/enrol/select/locallib.php');

// Set sub-tabs.
$role = optional_param('role', null, PARAM_INT);
$roles = UniversiteRennes2\Apsolu\get_custom_student_roles();

if (isset($roles[$role]) === false) {
	$role = null;
}

$subtabtree = array();
foreach ($roles as $datarole) {
    $url = new moodle_url('/local/apsolu/statistics/index.php', array('page' => $page, 'role' => $datarole->id));
    $subtabtree[] = new tabobject($datarole->id, $url, $datarole->name);
}

if (isset($role) === false) {
	$role = current($roles)->id;
}

echo $OUTPUT->tabtree($subtabtree, $role);

$semester1 = array(mktime(0, 0, 0, 8, 1, 2016), mktime(0, 0, 0, 1, 1, 2017));
$semester2 = array(mktime(0, 0, 0, 1, 1, 2017), mktime(0, 0, 0, 7, 1, 2017));

$stats = array();
$total = (object) [
	'semester1_accepted' => 0,
	'semester1_main' => 0,
	'semester1_second' => 0,
	'semester1_refused' => 0,
	'semester2_accepted' => 0,
	'semester2_main' => 0,
	'semester2_second' => 0,
	'semester2_refused' => 0,
	];

foreach (array(1 => $semester1, 2 => $semester2) as $name => $semester) {
	$sql = "SELECT COUNT(u.id) AS total, cc.name, ue.status".
		" FROM {user} u".
		" JOIN {user_enrolments} ue ON u.id = ue.userid".
		" JOIN {enrol} e ON e.id = ue.enrolid".
		" JOIN {course} c ON c.id = e.courseid".
		" JOIN {course_categories} cc ON cc.id = c.category".
		" JOIN {apsolu_courses} ac ON c.id = ac.id".
		" JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = 50".
		" JOIN {role_assignments} ra ON ra.contextid = ctx.id AND u.id = ra.userid AND ra.itemid = e.id".
		" JOIN {role} r ON r.id = ra.roleid".
		" WHERE e.enrol = 'select'".
		" AND e.customint7 >= :starttime".
		" AND e.customint8 <= :endtime";
	$parameters = array('starttime' => $semester[0], 'endtime' => $semester[1]);

	if (isset($role) === true) {
		$sql .= " AND r.id = :roleid";
		$parameters['roleid'] = $role;
	}

	$sql .= " GROUP BY cc.name, ue.status";
	$records = $DB->get_recordset_sql($sql, $parameters);

	foreach ($records as $record) {
		if (isset($stats[$record->name]) === false) {
			$stats[$record->name] = new stdClass();
			$stats[$record->name]->name = $record->name;
			$stats[$record->name]->semester1_accepted = 0;
			$stats[$record->name]->semester1_main = 0;
			$stats[$record->name]->semester1_second = 0;
			$stats[$record->name]->semester1_refused = 0;
			$stats[$record->name]->semester2 = new stdClass();
			$stats[$record->name]->semester2_accepted = 0;
			$stats[$record->name]->semester2_main = 0;
			$stats[$record->name]->semester2_second = 0;
			$stats[$record->name]->semester2_refused = 0;
		}

		switch($record->status) {
			case '0':
				$stats[$record->name]->{'semester'.$name.'_accepted'} = $record->total;
				$total->{'semester'.$name.'_accepted'} += $record->total;
				break;
			case '1':
				$stats[$record->name]->{'semester'.$name.'_main'} = $record->total;
				$total->{'semester'.$name.'_main'} += $record->total;
				break;
			case '2':
				$stats[$record->name]->{'semester'.$name.'_second'} = $record->total;
				$total->{'semester'.$name.'_second'} += $record->total;
				break;
			case '3':
				$stats[$record->name]->{'semester'.$name.'_refused'} = $record->total;
				$total->{'semester'.$name.'_refused'} += $record->total;
				break;
		}
	}
}

$stats = array_values($stats);

$data = new stdClass();
$data->statistics = $stats;
$data->total = $total;

echo $OUTPUT->render_from_template('local_apsolu/statistics_people', $data);

