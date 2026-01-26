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
 * Page pour gérer l'édition d'un rôle.
 *
 * @package    local_apsolu
 * @copyright  2023 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\role;

defined('MOODLE_INTERNAL') || die;

require(__DIR__ . '/roles_edit_form.php');

// Get role id.
$roleid = optional_param('roleid', 0, PARAM_INT);

if ($roleid === 5) {
    $roleid = 0;
}

$record = $DB->get_record('role', ['id' => $roleid, 'archetype' => 'student'], $fields = '*', MUST_EXIST);
$record = current(role_fix_names([$record]));

$role = new Role();
$role->load($roleid);
$role->id = $record->id;

// Build form.
$customdata = [$role, $record->name];
$mform = new local_apsolu_roles_edit_form(null, $customdata);

if ($data = $mform->get_data()) {
    // Message à afficher à la fin de l'enregistrement.
    $message = get_string('role_updated', 'local_apsolu');

    // Save data.
    $role->save($data);

    // Redirige vers la page générale.
    $returnurl = new moodle_url('/local/apsolu/configuration/index.php', ['page' => 'roles']);
    redirect($returnurl, $message, $delay = null, \core\output\notification::NOTIFY_SUCCESS);
}

// Display form.
$PAGE->requires->js_call_amd('local_apsolu/colorpicker', 'initialise');

$heading = get_string('edit_role', 'local_apsolu');

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);
$mform->display();
echo $OUTPUT->footer();
