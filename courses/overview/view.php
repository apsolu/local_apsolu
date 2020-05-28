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
 * Affiche la vue d'ensemble.
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/enrol/select/lib.php');

$categoryid = optional_param('categoryid', null, PARAM_INT);

if ($categoryid !== null ) {
    $sql = "SELECT c.id, c.fullname".
        " FROM {course} c".
        " JOIN {apsolu_courses} ac ON ac.id = c.id".
        " JOIN {course_categories} cc ON cc.id = c.category".
        " WHERE c.category = :categoryid".
        " ORDER BY category, ac.numweekday, ac.starttime";
    $courses = $DB->get_records_sql($sql, array('categoryid' => $categoryid));

    foreach ($courses as $course) {
        $conditions = array('enrol' => 'select', 'status' => 0, 'courseid' => $course->id);
        $course->enrols = array_values($DB->get_records('enrol', $conditions));
        $course->count_enrols = count($course->enrols);
        $course->multiple_enrols = $course->count_enrols > 1;

        foreach ($course->enrols as $id => $enrol) {
            foreach (enrol_select_plugin::$states as $statusid => $statusname) {
                $variable = 'count_'.$statusname.'_list';

                $enrolments = $DB->get_records('user_enrolments', array('enrolid' => $enrol->id, 'status' => $statusid));
                $course->enrols[$id]->{$variable} = count($enrolments);
            }
        }
    }

    $data = new stdClass();
    $data->wwwroot = $CFG->wwwroot;
    $data->courses = array_values($courses);
    $data->count_courses = count($courses);

    echo $OUTPUT->render_from_template('local_apsolu/courses_overview_courses', $data);
} else {
    $sql = "SELECT cc.id, cc.name".
        " FROM {course_categories} cc".
        " JOIN {course} c ON c.category = cc.id".
        " JOIN {apsolu_courses} ac ON ac.id = c.id".
        " GROUP BY cc.id".
        " ORDER BY cc.name, cc.sortorder";
    $categories = $DB->get_records_sql($sql);

    $data = new stdClass();
    $data->wwwroot = $CFG->wwwroot;
    $data->categories = array_values($categories);
    $data->count_categories = count($categories);

    echo $OUTPUT->render_from_template('local_apsolu/courses_overview_categories', $data);
}
