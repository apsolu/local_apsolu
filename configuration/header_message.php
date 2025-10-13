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
 * Page permettant de modifier le message d'entête.
 *
 * @package    local_apsolu
 * @copyright  2017 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/apsolu/configuration/header_message_form.php');

// Build form.
$defaults = new stdClass();
$defaults->apsoluheaderactive = get_config('local_apsolu', 'apsoluheaderactive');
$defaults->apsoluheaderstyle = get_config('local_apsolu', 'apsoluheaderstyle');
$defaults->apsoluheadercontent = ['text' => get_config('local_apsolu', 'apsoluheadercontent'), 'format' => 1];

$customdata = [$defaults];
$mform = new local_apsolu_header_form(null, $customdata);

$notification = '';
if ($data = $mform->get_data()) {
    if (isset($data->apsoluheaderactive) === false) {
        $data->apsoluheaderactive = 0;
    }

    if ($data->apsoluheaderactive != $defaults->apsoluheaderactive) {
        add_to_config_log('apsoluheaderactive', $defaults->apsoluheaderactive, $data->apsoluheaderactive, 'local_apsolu');
        set_config('apsoluheaderactive', $data->apsoluheaderactive, 'local_apsolu');
    }

    if ($data->apsoluheaderstyle != $defaults->apsoluheaderstyle) {
        add_to_config_log('apsoluheaderstyle', $defaults->apsoluheaderstyle, $data->apsoluheaderstyle, 'local_apsolu');
        set_config('apsoluheaderstyle', $data->apsoluheaderstyle, 'local_apsolu');
    }

    if ($data->apsoluheadercontent['text'] != $defaults->apsoluheadercontent['text']) {
        $oldvalue = $defaults->apsoluheadercontent['text'];
        $newvalue = $data->apsoluheadercontent['text'];
        add_to_config_log('apsoluheadercontent', $oldvalue, $newvalue, 'local_apsolu');
        set_config('apsoluheadercontent', $data->apsoluheadercontent['text'], 'local_apsolu');
    }

    $oldvalue = get_config('core', 'additionalhtmltopofbody');
    if (empty($data->apsoluheaderactive) === true) {
        $newvalue = '';
    } else {
        $style = "";

        if ($data->apsoluheaderstyle && $data->apsoluheaderstyle != 'none') {
            $style = "mb-0 alert alert-" . $data->apsoluheaderstyle;
        }

        // Encapsule le HTML dans une div afin de pouvoir masquer le contenu sur la page d'accueil du site.
        $newvalue = sprintf(
            '<div id="apsolu-topofbody"><div class="%s">%s</div></div>',
            $style,
            $data->apsoluheadercontent['text']
        );
    }

    if ($oldvalue !== $newvalue) {
        add_to_config_log('additionalhtmltopofbody', $oldvalue, $newvalue, 'core');
        set_config('additionalhtmltopofbody', $newvalue);
    }

    $notification = $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('header_message', 'local_apsolu'));
echo $notification;
$mform->display();
echo $OUTPUT->footer();
