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
 * Liste des éléments d'évaluation.
 *
 * @package    local_apsolu
 * @copyright  2020 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$sql = "SELECT ags.id, ags.name, r.id AS roleid, ac.name AS calendar" .
    " FROM {apsolu_grade_items} ags" .
    " LEFT JOIN {role} r ON r.id = ags.roleid" .
    " LEFT JOIN {apsolu_calendars} ac ON ac.id = ags.calendarid" .
    " ORDER BY ac.name, r.sortorder, ags.name";

$roles = role_get_names();

$data = new stdClass();
$data->wwwroot = $CFG->wwwroot;
$data->gradeitems = [];
$data->count_gradeitems = 0;

foreach ($DB->get_records_sql($sql) as $gradeitem) {
    $gradeitem->role = '';
    if (isset($roles[$gradeitem->roleid]) === true) {
        $gradeitem->role = $roles[$gradeitem->roleid]->localname;
    }

    $data->gradeitems[] = $gradeitem;
    $data->count_gradeitems++;
}

if (isset($notificationform)) {
    $data->notification = $notificationform;
}

echo $OUTPUT->render_from_template('local_apsolu/gradeitems', $data);
