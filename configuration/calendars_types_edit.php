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
 * Page pour gérer l'édition d'un type de calendrier.
 *
 * @package    local_apsolu
 * @copyright  2017 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/apsolu/configuration/calendars_types_form.php');

$typeid = optional_param('typeid', 0, PARAM_INT);

// Définis l'instance.
$instance = false;
if ($typeid !== 0) {
    $instance = $DB->get_record('apsolu_calendars_types', ['id' => $typeid]);
}

if ($instance === false) {
    $instance = new stdClass();
    $instance->id = 0;
    $instance->name = '';
    $instance->shortname = '';
}

// Build form.
$customdata = [$instance];
$mform = new local_apsolu_calendars_types_edit_form(null, $customdata);

if ($data = $mform->get_data()) {
    // Save data.
    $instance = new stdClass();
    $instance->id = $data->typeid;
    $instance->name = trim($data->name);
    $instance->shortname = trim($data->shortname);

    if ($instance->id === 0) {
        $DB->insert_record('apsolu_calendars_types', $instance);
    } else {
        $DB->update_record('apsolu_calendars_types', $instance);
    }

    // Display notification and display elements list.
    $notificationform = $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');

    require(__DIR__ . '/calendars_types_view.php');
} else {
    // Display form.
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('calendars_types', 'local_apsolu'));

    $mform->display();
    echo $OUTPUT->footer();
}
