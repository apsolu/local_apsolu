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
 * Gère la page de suppression d'un site.
 *
 * @package    local_apsolu
 * @copyright  2020 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

use local_apsolu\core\area;
use local_apsolu\core\city;

$cityid = required_param('cityid', PARAM_INT);
$delete = optional_param('delete', '', PARAM_ALPHANUM); // Confirmation hash.

$city = new City();
$city->load($cityid, $required = true);

$deletehash = md5($city->id);
$returnurl = new moodle_url('/local/apsolu/courses/index.php', ['tab' => 'cities']);

if ($delete === $deletehash) {
    // Effectue les actions de suppression.
    require_sesskey();

    $city->delete();

    $message = get_string('city_has_been_deleted', 'local_apsolu');
    redirect($returnurl, $message, $delay = null, \core\output\notification::NOTIFY_SUCCESS);
}

// Vérifie si ce site n'est pas associé à une zone.
$areas = Area::get_records(['cityid' => $city->id], 'name');
if (count($areas) !== 0) {
    $datatemplate = [];
    $datatemplate['message'] = get_string('city_cannot_be_deleted', 'local_apsolu', $city->name);
    $datatemplate['dependences'] = array_values($areas);
    $message = $OUTPUT->render_from_template('local_apsolu/courses_form_undeletable_message', $datatemplate);

    redirect($returnurl, $message, $delay = null, \core\output\notification::NOTIFY_WARNING);
}

// Affiche un message de confirmation.
$datatemplate = [];
$datatemplate['message'] = get_string('do_you_want_to_delete_areas_city', 'local_apsolu', $city->name);
$message = $OUTPUT->render_from_template('local_apsolu/courses_form_delete_message', $datatemplate);

// Bouton de validation.
$urlarguments = ['tab' => 'cities', 'action' => 'delete', 'cityid' => $city->id, 'delete' => $deletehash];
$confirmurl = new moodle_url('/local/apsolu/courses/index.php', $urlarguments);
$confirmbutton = new single_button($confirmurl, get_string('delete'), 'post');

// Bouton d'annulation.
$cancelurl = new moodle_url('/local/apsolu/courses/index.php', ['tab' => 'cities']);

echo $OUTPUT->confirm($message, $confirmbutton, $cancelurl);
