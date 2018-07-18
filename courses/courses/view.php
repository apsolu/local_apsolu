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

namespace UniversiteRennes2\Apsolu;

defined('MOODLE_INTERNAL') || die;

require_once(__DIR__.'/../../locallib.php');

$PAGE->requires->js_call_amd('local_apsolu/sort_courses', 'initialise');

$currentactivity = null;
$currentaltclass = 'odd';

$sql = "SELECT c.id, cc.name AS category, ccc.name AS grouping, ac.event, ac.weekday, ac.starttime, ac.endtime,".
    " ask.name AS skill, al.name AS location, ap.name AS period, ac.license, c.visible".
    " FROM {course} c".
    " JOIN {course_categories} cc ON cc.id = c.category".
    " JOIN {course_categories} ccc ON ccc.id = cc.parent".
    " JOIN {apsolu_courses} ac ON c.id = ac.id".
    " JOIN {apsolu_courses_categories} acc ON acc.id = c.category".
    " JOIN {apsolu_skills} ask ON ask.id = ac.skillid".
    " JOIN {apsolu_locations} al ON al.id = ac.locationid".
    " JOIN {apsolu_periods} ap ON ap.id = ac.periodid".
    " ORDER BY c.visible DESC, category, ac.numweekday, ac.starttime, location, skill";
$courses = array();
foreach ($DB->get_records_sql($sql) as $course) {
    if ($currentactivity !== $course->category) {
        $currentactivity = $course->category;

        if ($currentaltclass === 'odd') {
            $currentaltclass = 'even';
        } else {
            $currentaltclass = 'odd';
        }
    }

    if (empty($course->event)) {
        $course->fullname = $course->category;
    } else {
        $course->fullname = $course->category.' ('.$course->event.')';
    }

    $course->alt_class = $currentaltclass;
    $course->weekday = get_string($course->weekday, 'calendar');
    $course->schedule = $course->starttime.'-'.$course->endtime;


    $teachers = get_teachers($course->id);
    sort($teachers);

    $course->teachers = $teachers;

    $courses[] = $course;
}

$data = new \stdClass();
$data->wwwroot = $CFG->wwwroot;
$data->courses = array_values($courses);
$data->count_courses = count($courses);

if (isset($notificationform)) {
    $data->notification = $notificationform;
}

echo $OUTPUT->render_from_template('local_apsolu/courses_courses', $data);

