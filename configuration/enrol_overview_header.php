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
 * Page permettant de modifier le bandeau d'informations au-dessus de la page d'inscription.
 *
 * @package    local_apsolu
 * @copyright  2026 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/apsolu/configuration/header_message_form.php');

// Build form.
$defaults = new stdClass();
$defaults->apsoluheaderactive = get_config('local_apsolu', 'apsoluoverviewheaderactive');
$defaults->apsoluheaderstyle = get_config('local_apsolu', 'apsoluoverviewheaderstyle');
$defaults->apsoluheadercontent = ['text' => get_config('local_apsolu', 'apsoluoverviewheadercontent'), 'format' => 1];
$defaults->apsoluheaderdismiss = get_config('local_apsolu', 'apsoluoverviewheaderdismiss');

$customdata = [$defaults, 'overviewheader'];
$mform = new local_apsolu_header_form(null, $customdata);

$notification = '';
if ($data = $mform->get_data()) {
    if (isset($data->apsoluheaderactive) === false) {
        $data->apsoluheaderactive = 0;
    }

    if ($data->apsoluheaderactive != $defaults->apsoluheaderactive) {
        add_to_config_log('apsoluoverviewheaderactive', $defaults->apsoluheaderactive, $data->apsoluheaderactive, 'local_apsolu');
        set_config('apsoluoverviewheaderactive', $data->apsoluheaderactive, 'local_apsolu');
    }

    if ($data->apsoluheaderstyle != $defaults->apsoluheaderstyle) {
        add_to_config_log('apsoluoverviewheaderstyle', $defaults->apsoluheaderstyle, $data->apsoluheaderstyle, 'local_apsolu');
        set_config('apsoluoverviewheaderstyle', $data->apsoluheaderstyle, 'local_apsolu');
    }

    if ($data->apsoluheaderdismiss != $defaults->apsoluheaderdismiss) {
        add_to_config_log(
            'apsoluoverviewheaderdismiss',
            $defaults->apsoluheaderdismiss,
            $data->apsoluheaderdismiss,
            'local_apsolu'
        );
        set_config('apsoluoverviewheaderdismiss', $data->apsoluheaderdismiss, 'local_apsolu');
    }

    if ($data->apsoluheadercontent['text'] != $defaults->apsoluheadercontent['text']) {
        $oldvalue = $defaults->apsoluheadercontent['text'];
        $newvalue = $data->apsoluheadercontent['text'];
        add_to_config_log('apsoluoverviewheadercontent', $oldvalue, $newvalue, 'local_apsolu');
        set_config('apsoluoverviewheadercontent', $data->apsoluheadercontent['text'], 'local_apsolu');
    }

    $notification = $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('enrol_overview_message', 'local_apsolu'));
echo $notification;
$mform->display();
echo $OUTPUT->footer();
