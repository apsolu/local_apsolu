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
 * Liste les types de format.
 *
 * @package   local_apsolu
 * @copyright 2026 Université Rennes 2
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\coursetype;

defined('MOODLE_INTERNAL') || die;

$coursetypes = $DB->get_records('apsolu_courses_types', $conditions = null, $sort = 'sortorder');
$countcoursetypes = count($coursetypes);
$sortorder = 1;
foreach ($coursetypes as $id => $coursetype) {
    $coursetypes[$id]->first = ($sortorder === 1);
    $coursetypes[$id]->last = ($sortorder === $countcoursetypes);
    $sortorder++;
}

$data = new stdClass();
$data->wwwroot = $CFG->wwwroot;
$data->course_types = array_values($coursetypes);
$data->count_course_types = $countcoursetypes;

if (isset($notificationform)) {
    $data->notification = $notificationform;
}

echo $OUTPUT->render_from_template('local_apsolu/courses_course_types', $data);
