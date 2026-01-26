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
 * Page pour gérer la suppression d'un type de présence.
 *
 * @package    local_apsolu
 * @copyright  2023 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

use local_apsolu\core\attendance\status;

$statusid = required_param('statusid', PARAM_INT);
$delete = optional_param('delete', '', PARAM_ALPHANUM); // Confirmation hash.

$status = new Status();
$status->load($statusid, $required = true);

$deletehash = md5($status->id);
$returnurl = new moodle_url('/local/apsolu/configuration/index.php', ['page' => 'attendancestatuses']);

if ($delete === $deletehash) {
    // Effectue les actions de suppression.
    require_sesskey();

    $status->delete();

    $message = get_string('attendance_status_has_been_deleted', 'local_apsolu');
    redirect($returnurl, $message, $delay = null, \core\output\notification::NOTIFY_SUCCESS);
}

// Affiche un message de confirmation.
$datatemplate = [];
$datatemplate['message'] = get_string('do_you_want_to_delete_attendance_status', 'local_apsolu', (string) $status);
$datatemplate['additional'] = get_string('all_attendances_taken_with_this_status_will_be_deleted', 'local_apsolu');
$message = $OUTPUT->render_from_template('local_apsolu/courses_form_delete_message', $datatemplate);

// Bouton de validation.
$urlarguments = ['page' => 'attendancestatuses', 'action' => 'delete', 'statusid' => $status->id, 'delete' => $deletehash];
$confirmurl = new moodle_url('/local/apsolu/configuration/index.php', $urlarguments);
$confirmbutton = new single_button($confirmurl, get_string('delete'), 'post');

// Bouton d'annulation.
$cancelurl = new moodle_url('/local/apsolu/configuration/index.php', ['page' => 'attendancestatuses']);

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);
echo $OUTPUT->confirm($message, $confirmbutton, $cancelurl);
echo $OUTPUT->footer();
