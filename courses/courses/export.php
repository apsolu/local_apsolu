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

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/csvlib.class.php');

// Liste des cours.
$sql = "SELECT c.id, ccc.name AS grouping, cc.name AS category, ask.name AS skill,".
    " ac.weekday, ac.starttime, ac.endtime, ac.license, al.name AS location, ap.name AS period, '-' AS teachers".
    " FROM {course} c".
    " JOIN {course_categories} cc ON cc.id = c.category".
    " JOIN {course_categories} ccc ON ccc.id = cc.parent".
    " JOIN {apsolu_courses} ac ON c.id = ac.id".
    " JOIN {apsolu_courses_categories} acc ON acc.id = c.category".
    " JOIN {apsolu_skills} ask ON ask.id = ac.skillid".
    " JOIN {apsolu_locations} al ON al.id = ac.locationid".
    " JOIN {apsolu_periods} ap ON ap.id = ac.periodid".
    " ORDER BY category, ac.numweekday, ac.starttime, location, skill";
$courses = $DB->get_records_sql($sql);

// Liste des enseignants.
$sql = "SELECT ctx.instanceid, u.firstname, u.lastname, u.email".
    " FROM {user} u".
    " JOIN {role_assignments} ra ON u.id = ra.userid".
    " JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50".
    " WHERE ra.roleid = 3".
    " ORDER BY u.lastname, u.firstname";
foreach ($DB->get_recordset_sql($sql) as $teacher) {
    if (!isset($courses[$teacher->instanceid])) {
        continue;
    }

    $user = $teacher->firstname.' '.$teacher->lastname.' ('.$teacher->email.')';
    if ($courses[$teacher->instanceid]->teachers === '-') {
        $courses[$teacher->instanceid]->teachers = $user;
    } else {
        $courses[$teacher->instanceid]->teachers .= ', '.$user;
    }
}

// Génération du fichier csv.
$filename = str_replace(' ', '_', strtolower(get_string('courses', 'local_apsolu')));

$headers = array(
    get_string('groupings', 'local_apsolu'),
    get_string('categories', 'local_apsolu'),
    get_string('skills', 'local_apsolu'),
    get_string('weekdays', 'local_apsolu'),
    get_string('starttime', 'local_apsolu'),
    get_string('endtime', 'local_apsolu'),
    get_string('federation', 'local_apsolu'),
    get_string('locations', 'local_apsolu'),
    get_string('periods', 'local_apsolu'),
    get_string('teachers'),
);

$csvexport = new \csv_export_writer();
$csvexport->set_filename($filename);
$csvexport->add_data($headers);

foreach ($courses as $course) {
    unset($course->id);
    $course->weekday = get_string($course->weekday, 'calendar');
    $csvexport->add_data((array) $course);
}

$csvexport->download_file();

exit;
