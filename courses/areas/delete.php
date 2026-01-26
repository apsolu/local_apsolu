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
 * Gère la page de suppression d'une zone géographique.
 *
 * @package    local_apsolu
 * @copyright  2020 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

use local_apsolu\core\location;
use local_apsolu\core\area;

$areaid = required_param('areaid', PARAM_INT);
$delete = optional_param('delete', '', PARAM_ALPHANUM); // Confirmation hash.

$area = new Area();
$area->load($areaid, $required = true);

$deletehash = md5($area->id);
$returnurl = new moodle_url('/local/apsolu/courses/index.php', ['tab' => 'areas']);

if ($delete === $deletehash) {
    // Effectue les actions de suppression.
    require_sesskey();

    $area->delete();

    $message = get_string('area_has_been_deleted', 'local_apsolu');
    redirect($returnurl, $message, $delay = null, \core\output\notification::NOTIFY_SUCCESS);
}

// Vérifie si cette zone n'est pas associée à un lieu.
$locations = Location::get_records(['areaid' => $area->id], 'name');
if (count($locations) !== 0) {
    $datatemplate = [];
    $datatemplate['message'] = get_string('area_cannot_be_deleted', 'local_apsolu', $area->name);
    $datatemplate['dependences'] = array_values($locations);
    $message = $OUTPUT->render_from_template('local_apsolu/courses_form_undeletable_message', $datatemplate);

    redirect($returnurl, $message, $delay = null, \core\output\notification::NOTIFY_WARNING);
}

// Affiche un message de confirmation.
$datatemplate = [];
$datatemplate['message'] = get_string('do_you_want_to_delete_locations_area', 'local_apsolu', $area->name);
$message = $OUTPUT->render_from_template('local_apsolu/courses_form_delete_message', $datatemplate);

// Bouton de validation.
$urlarguments = ['tab' => 'areas', 'action' => 'delete', 'areaid' => $area->id, 'delete' => $deletehash];
$confirmurl = new moodle_url('/local/apsolu/courses/index.php', $urlarguments);
$confirmbutton = new single_button($confirmurl, get_string('delete'), 'post');

// Bouton d'annulation.
$cancelurl = new moodle_url('/local/apsolu/courses/index.php', ['tab' => 'areas']);

echo $OUTPUT->confirm($message, $confirmbutton, $cancelurl);
