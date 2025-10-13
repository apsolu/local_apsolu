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
 * Page pour gérer l'édition d'un type de présence.
 *
 * @package    local_apsolu
 * @copyright  2023 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\attendance\status;

defined('MOODLE_INTERNAL') || die;

require(__DIR__ . '/attendance_statuses_edit_form.php');

// Get status id.
$statusid = optional_param('statusid', 0, PARAM_INT);

// Generate object.
$status = new Status();
if ($statusid !== 0) {
    $status->load($statusid);
}

// Build form.
$customdata = [$status];
$mform = new local_apsolu_attendance_statuses_edit_form(null, $customdata);

if ($data = $mform->get_data()) {
    // Message à afficher à la fin de l'enregistrement.
    $message = get_string('attendance_status_updated', 'local_apsolu');
    if (empty($status->id) === true) {
        $message = get_string('attendance_status_saved', 'local_apsolu');
    }

    // Save data.
    $status->save($data);

    // Redirige vers la page générale.
    $returnurl = new moodle_url('/local/apsolu/configuration/index.php', ['page' => 'attendancestatuses']);
    redirect($returnurl, $message, $delay = null, \core\output\notification::NOTIFY_SUCCESS);
}

// Display form.
$heading = get_string('edit_attendance_status', 'local_apsolu');
if (empty($status->id) === true) {
    $heading = get_string('add_attendance_status', 'local_apsolu');
}

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);
$mform->display();
echo $OUTPUT->footer();
