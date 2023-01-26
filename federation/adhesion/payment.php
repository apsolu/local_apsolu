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
 * Page listant les activités FFSU.
 *
 * @package    local_apsolu
 * @copyright  2022 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use UniversiteRennes2\Apsolu\Payment;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/apsolu/classes/apsolu/payment.php');

$data = new stdClass();
$data->wwwroot = $CFG->wwwroot;
$data->due = false;
$data->count_cards = 0;
$data->cards = array();
$data->payment_url = (string) new moodle_url('/local/apsolu/payment/index.php');

$images = Payment::get_statuses_images();
foreach (Payment::get_user_cards_status_per_course($courseid, $USER->id) as $card) {
    $card->image = $images[$card->status]->image;

    $data->cards[] = $card;
    $data->count_cards++;

    if ($card->status !== Payment::DUE) {
        continue;
    }

    $data->due = true;
}

echo $OUTPUT->render_from_template('local_apsolu/federation_adhesion_payment', $data);
