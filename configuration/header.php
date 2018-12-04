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
 * @package    local_apsolu
 * @copyright  2017 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/local/apsolu/configuration/header_form.php');

// Build form.
$defaults = new stdClass();
$defaults->apsoluheaderactive = get_config('local_apsolu', 'apsoluheaderactive');
$defaults->apsoluheadercontent = get_config('local_apsolu', 'apsoluheadercontent');

$customdata = array($defaults);
$mform = new local_apsolu_header_form(null, $customdata);

$notification = '';
if ($data = $mform->get_data()) {
    if (isset($data->apsoluheaderactive) === false) {
        $data->apsoluheaderactive = 0;
    }

    set_config('apsoluheaderactive', $data->apsoluheaderactive, 'local_apsolu');
    set_config('apsoluheadercontent', $data->apsoluheadercontent, 'local_apsolu');

    if (empty($data->apsoluheaderactive) === true) {
        set_config('additionalhtmltopofbody', '');
    } else {
        set_config('additionalhtmltopofbody', $data->apsoluheadercontent);
    }

    $notification = $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('settings_configuration_header', 'local_apsolu'));
echo $notification;
$mform->display();
echo $OUTPUT->footer();
