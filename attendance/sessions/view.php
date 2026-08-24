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
 * @copyright  2017 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$time = time();
$data->sessions = [];
$data->count_sessions = 0;

$sql = "SELECT aas.*, COUNT(aap.id) AS count, al.name AS location
          FROM {apsolu_attendance_sessions} aas
          JOIN {apsolu_locations} al ON al.id = aas.locationid
     LEFT JOIN {apsolu_attendance_presences} aap ON aas.id = aap.sessionid
         WHERE courseid = :courseid
      GROUP BY aas.id
      ORDER BY aas.sessiontime";

foreach ($DB->get_records_sql($sql, ['courseid' => $courseid]) as $session) {
    $session->sessiontimestr = userdate($session->sessiontime, get_string('strftimedatetimewithyear', 'local_apsolu'));
    $session->durationstr = get_string('X_minutes', 'local_apsolu', $session->duration / 60);
    $session->expired = $time > $session->sessiontime;

    $data->sessions[] = $session;
    $data->count_sessions++;
}

$template = 'local_apsolu/attendance_sessions_view';
