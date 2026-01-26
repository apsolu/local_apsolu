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
 * @copyright  2023 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\role;

defined('MOODLE_INTERNAL') || die;

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('roles', 'local_apsolu'));

$roles = role::get_records();

$data = new stdClass();
$data->wwwroot = $CFG->wwwroot;
$data->roles = array_values($roles);
$data->count_roles = count($roles);

if (isset($notificationform)) {
    $data->notification = $notificationform;
}

echo $OUTPUT->render_from_template('local_apsolu/configuration_roles', $data);

echo $OUTPUT->footer();
