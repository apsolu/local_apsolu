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

require_once($CFG->dirroot.'/local/apsolu/locallib.php');
require_once($CFG->dirroot.'/local/apsolu/payment/notifications/compose_form.php');

// Generate object.
$compose = new stdClass();
$compose->id = 0;
$compose->simulation = 1;
$compose->subject = '';
$compose->message = '';
$compose->cards = array();

$cards = $DB->get_records('apsolu_payments_cards');

// Build form.
$customdata = array($compose, $cards);
$mform = new local_apsolu_payment_notifications_compose_form(null, $customdata);

if ($data = $mform->get_data()) {
    // Save data.
    $compose = new stdClass();
    $compose->id = 0;
    $compose->subject = trim($data->subject);
    $compose->message = trim($data->message['text']);
    $compose->timecreated = time();
    $compose->timestarted = null;
    $compose->timeended = null;
    $compose->userid = $USER->id;

    if (empty($data->simulation) === false) {
        // TODO: créer un champ dédié à la simulation dans la table.
        $compose->subject = '[x] '.$compose->subject;
    }

    $compose->cards = array();

    foreach ($cards as $card) {
        $field = 'card'.$card->id;
        if (isset($data->{$field}) === true) {
            $compose->cards[] = $card->id;
        }
    }

    // TODO: transaction.
    if ($compose->id === 0) {
        $compose->id = $DB->insert_record('apsolu_dunnings', $compose);

        foreach ($compose->cards as $cardid) {
            $card = new stdClass();
            $card->dunningid = $compose->id;
            $card->cardid = $cardid;

            $DB->insert_record('apsolu_dunnings_cards', $card);
        }
    }

    // Display notification and display elements list.
    $notificationform = $OUTPUT->notification(get_string('dunningsaved', 'local_apsolu'), 'notifysuccess');
}

$data = new stdClass();
$data->wwwroot = $CFG->wwwroot;
$data->form = $mform->render();
$data->notificationform = '';
if (isset($notificationform) === true) {
    $data->notificationform = $notificationform;
}

echo $OUTPUT->render_from_template('local_apsolu/payment_notifications_compose', $data);
