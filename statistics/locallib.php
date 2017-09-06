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
 * @copyright  2017 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function get_rosters_statistics($roleid, $institution) {
    global $DB;

    $semester1 = array(get_config('local_apsolu', 'semester1_startdate'), get_config('local_apsolu', 'semester1_enddate'));
    $semester2 = array(get_config('local_apsolu', 'semester2_startdate'), get_config('local_apsolu', 'semester2_enddate'));

    $stats = array();

    foreach (array(1 => $semester1, 2 => $semester2) as $name => $semester) {
        $parameters = array(
            'starttime' => $semester[0],
            'endtime' => $semester[1],
            'roleid' => $roleid,
        );

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
            " AND e.customint8 <= :endtime".
            " AND r.id = :roleid";

        if (empty($institution) === false) {
            $sql .= " AND u.institution = :institution";
            $parameters['institution'] = $institution;
        }

        $sql .= " GROUP BY cc.name, ue.status";

        $records = $DB->get_recordset_sql($sql, $parameters);

        foreach ($records as $record) {
            if (isset($stats[$record->name]) === false) {
                $stats[$record->name] = new stdClass();
                $stats[$record->name]->name = $record->name;
                $stats[$record->name]->semester1_accepted = 0;
                $stats[$record->name]->semester1_main = 0;
                $stats[$record->name]->semester1_wait = 0;
                $stats[$record->name]->semester1_refused = 0;
                $stats[$record->name]->semester2_accepted = 0;
                $stats[$record->name]->semester2_main = 0;
                $stats[$record->name]->semester2_wait = 0;
                $stats[$record->name]->semester2_refused = 0;
            }

            switch($record->status) {
                case '0':
                    $stats[$record->name]->{'semester'.$name.'_accepted'} = $record->total;
                    break;
                case '2':
                    $stats[$record->name]->{'semester'.$name.'_main'} = $record->total;
                    break;
                case '3':
                    $stats[$record->name]->{'semester'.$name.'_wait'} = $record->total;
                    break;
                case '4':
                    $stats[$record->name]->{'semester'.$name.'_refused'} = $record->total;
                    break;
            }
        }
    }

    return array_values($stats);
}
