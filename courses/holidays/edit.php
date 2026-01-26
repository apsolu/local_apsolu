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
 * Page pour gérer l'édition d'un jour férié.
 *
 * @package   local_apsolu
 * @copyright 2020 Université Rennes 2
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\holiday;

defined('MOODLE_INTERNAL') || die;

require(__DIR__ . '/edit_form.php');

// Get holiday id.
$holidayid = optional_param('holidayid', 0, PARAM_INT);

// Generate object.
$holiday = new Holiday();
if ($holidayid !== 0) {
    $holiday->load($holidayid);
}

// Build form.
$customdata = ['holiday' => $holiday];
$mform = new local_apsolu_courses_holidays_edit_form(null, $customdata);

if ($data = $mform->get_data()) {
    // Message à afficher à la fin de l'enregistrement.
    $message = get_string('holiday_updated', 'local_apsolu');
    if (empty($holiday->id) === true) {
        $message = get_string('holiday_saved', 'local_apsolu');
    }

    // Save data.
    $record = $DB->get_record(Holiday::TABLENAME, ['day' => $data->day]);
    if ($record !== false) {
        $holiday->load($record->id);
    }
    $holiday->save($data);

    // Régénère les sessions.
    if (isset($data->regensessions) === true) {
        $holiday->regenerate_sessions();
    }

    // Redirige vers la page générale.
    $returnurl = new moodle_url('/local/apsolu/courses/index.php', ['tab' => 'holidays']);
    redirect($returnurl, $message, $delay = null, \core\output\notification::NOTIFY_SUCCESS);
}

// Display form.
$heading = get_string('edit_holiday', 'local_apsolu');
if (empty($holiday->id) === true) {
    $heading = get_string('add_holiday', 'local_apsolu');
}

echo $OUTPUT->heading($heading);
$mform->display();
