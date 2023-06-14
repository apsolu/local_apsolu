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
 * Page pour lister les rôles.
 *
 * @package    local_apsolu
 * @copyright  2023 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('roles', 'local_apsolu'));

$sql = "SELECT r.id, r.name, r.shortname, r.description, r.sortorder, r.archetype, ar.color, ar.fontawesomeid".
    " FROM {role} r".
    " LEFT JOIN {apsolu_roles} ar ON r.id = ar.id".
    " WHERE r.archetype = 'student'".
    " ORDER BY sortorder";
$roles = role_fix_names($DB->get_records_sql($sql));
unset($roles[5]);

$data = new stdClass();
$data->wwwroot = $CFG->wwwroot;
$data->roles = array_values($roles);
$data->count_roles = count($roles);

if (isset($notificationform)) {
    $data->notification = $notificationform;
}

echo $OUTPUT->render_from_template('local_apsolu/configuration_roles', $data);

echo $OUTPUT->footer();
