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
 * Liste les activités sportives.
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\federation\activity as Activity;

defined('MOODLE_INTERNAL') || die;

// Génère la liste des activités FFSU.
$federation_activities = array();
foreach (Activity::get_records() as $activity) {
    $federation_activities[$activity->categoryid] = $activity->categoryid;
}

// Génère la liste des activités APSOLU.
$sql = "SELECT acc.id, cc.name, ccc.name AS grouping".
    " FROM {apsolu_courses_categories} acc".
    " JOIN {course_categories} cc ON cc.id = acc.id".
    " JOIN {course_categories} ccc ON ccc.id = cc.parent".
    " ORDER BY cc.name, cc.sortorder";
$categories = array();
foreach ($DB->get_records_sql($sql) as $categoryid => $category) {
    // Positionne le témoin FFSU.
    $category->federation = isset($federation_activities[$categoryid]);

    $categories[$categoryid] = $category;
}

$data = new stdClass();
$data->wwwroot = $CFG->wwwroot;
$data->categories = array_values($categories);
$data->count_categories = count($categories);
$data->federation_course = local_apsolu\core\course::get_federation_courseid();

if (isset($notificationform)) {
    $data->notification = $notificationform;
}

echo $OUTPUT->render_from_template('local_apsolu/courses_categories', $data);
