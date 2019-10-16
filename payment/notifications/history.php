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

$data = new stdClass();
$data->wwwroot = $CFG->wwwroot;
$data->dunnings = array();
$data->count_dunnings = 0;

$sql = "SELECT ad.*, u.firstname, u.lastname, COUNT(adp.id) AS count_posts".
    " FROM {apsolu_dunnings} ad".
    " JOIN {user} u ON u.id = ad.userid".
    " LEFT JOIN {apsolu_dunnings_posts} adp ON ad.id = adp.dunningid".
    " GROUP BY ad.id".
    " ORDER BY ad.timecreated DESC";
$dunnings = $DB->get_records_sql($sql);

$sql = "SELECT ".$DB->sql_concat('dc.dunningid', '" "', 'c.id').", c.*, dc.dunningid".
    " FROM {apsolu_payments_cards} c".
    " JOIN {apsolu_dunnings_cards} dc ON c.id = dc.cardid".
    " ORDER BY c.name";
$cards = $DB->get_records_sql($sql);

foreach ($dunnings as $dunning) {
    $dunning->message = nl2br($dunning->message);
    $dunning->timecreated = userdate($dunning->timecreated, get_string('strftimedatetime', 'local_apsolu'));

    if (empty($dunning->timestarted) === true) {
        $dunning->status = get_string('waiting', 'local_apsolu');
        $dunning->status_style = 'warning';
    } else if (empty($dunning->timeended) === true) {
        $dunning->status = get_string('running', 'local_apsolu');
        $dunning->status_style = 'success';
    } else {
        $dunning->status = get_string('finished', 'local_apsolu');
        $dunning->status_style = 'info';
    }

    $dunning->cards = array();
    $dunning->count_cards = 0;
    foreach ($cards as $recordid => $card) {
        if ($dunning->id === $card->dunningid) {
            $dunning->cards[] = $card;
            $dunning->count_cards++;
            unset($cards[$recordid]);
        }
    }

    $data->dunnings[] = $dunning;
    $data->count_dunnings++;
}

echo $OUTPUT->render_from_template('local_apsolu/payment_notifications_history', $data);
