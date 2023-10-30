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
 * Page listant les sessions de cours.
 *
 * @package    local_apsolu
 * @copyright  2017 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$sql = "SELECT ap.*, ac.numweekday, ac.starttime, al.name AS location".
    " FROM {apsolu_periods} ap".
    " JOIN {apsolu_courses} ac ON ap.id = ac.periodid".
    " JOIN {apsolu_locations} al ON al.id = ac.locationid".
    " WHERE ac.id = :courseid";
$data->period = $DB->get_record_sql($sql, ['courseid' => $courseid]);

if ($data->period !== false) {
    $weeks = explode(',', $data->period->weeks);
    $data->sessions = [];
    $data->count_sessions = 0;

    $sql = "SELECT aas.*, COUNT(aap.id) AS count, al.name AS location".
        " FROM {apsolu_attendance_sessions} aas".
        " JOIN {apsolu_locations} al ON al.id = aas.locationid".
        " LEFT JOIN {apsolu_attendance_presences} aap ON aas.id = aap.sessionid".
        " WHERE courseid = :courseid".
        " GROUP BY aas.id".
        " ORDER BY aas.sessiontime";

    foreach ($DB->get_records_sql($sql, ['courseid' => $courseid]) as $session) {
        // On calcule le premier jour de la semaine correspondant à la session.
        $week = strftime('%Y-%m-%d', $session->sessiontime - ($data->period->numweekday - 1) * 24 * 60 * 60);

        $index = array_search($week, $weeks);
        if ($index !== false) {
            unset($weeks[$index]);
        }

        $session->sessiontimestr = userdate($session->sessiontime, get_string('strftimedatetime', 'local_apsolu'));

        $data->sessions[] = $session;
        $data->count_sessions++;
    }
}

$template = 'local_apsolu/attendance_sessions_view';
