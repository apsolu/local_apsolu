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

defined('MOODLE_INTERNAL') || die;

$sql = "SELECT acc.id, cc.name, acc.federation, ccc.name AS grouping".
    " FROM {apsolu_courses_categories} acc".
    " JOIN {course_categories} cc ON cc.id = acc.id".
    " JOIN {course_categories} ccc ON ccc.id = cc.parent".
    " ORDER BY cc.name, cc.sortorder";
$categories = $DB->get_records_sql($sql);

$data = new stdClass();
$data->wwwroot = $CFG->wwwroot;
$data->categories = array_values($categories);
$data->count_categories = count($categories);
$data->is_siuaps_rennes = isset($CFG->is_siuaps_rennes);

if (isset($notificationform)) {
    $data->notification = $notificationform;
}

echo $OUTPUT->render_from_template('local_apsolu/courses_categories', $data);
