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
 * Page listant les activités FFSU.
 *
 * @package    local_apsolu
 * @copyright  2022 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$sql = "SELECT afa.id, afa.name, cc.name AS apsoluname" .
    " FROM {apsolu_federation_activities} afa" .
    " LEFT JOIN {course_categories} cc ON cc.id = afa.categoryid" .
    " ORDER BY afa.name, cc.name";
$activities = $DB->get_records_sql($sql);

echo $OUTPUT->header();
echo $OUTPUT->heading_with_help(get_string('activity_list', 'local_apsolu'), 'activity_list', 'local_apsolu');
echo $OUTPUT->tabtree($tabtree, $page);

$data = new stdClass();
$data->activities = array_values($activities);
$data->wwwroot = $CFG->wwwroot;
echo $OUTPUT->render_from_template('local_apsolu/federation_activities', $data);

echo $OUTPUT->footer();
