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

require_once($CFG->dirroot.'/local/apsolu/configuration/header_message_form.php');

// Build form.
$defaults = new stdClass();
$defaults->apsoluheaderactive = get_config('local_apsolu', 'apsoluheaderactive');
$defaults->apsoluheadercontent = array('text' => get_config('local_apsolu', 'apsoluheadercontent'), 'format' => 1);

$customdata = array($defaults);
$mform = new local_apsolu_header_form(null, $customdata);

$notification = '';
if ($data = $mform->get_data()) {
    if (isset($data->apsoluheaderactive) === false) {
        $data->apsoluheaderactive = 0;
    }

    set_config('apsoluheaderactive', $data->apsoluheaderactive, 'local_apsolu');
    set_config('apsoluheadercontent', $data->apsoluheadercontent['text'], 'local_apsolu');

    if (empty($data->apsoluheaderactive) === true) {
        set_config('additionalhtmltopofbody', '');
    } else {
        // Encapsule le HTML dans une div afin de pouvoir masquer le contenu sur la page d'accueil du site.
        $additionalhtmltopofbody = sprintf('<div id="apsolu-topofbody">%s</div>', $data->apsoluheadercontent['text']);
        set_config('additionalhtmltopofbody', $additionalhtmltopofbody);
    }

    $notification = $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('header_message', 'local_apsolu'));
echo $notification;
$mform->display();
echo $OUTPUT->footer();
