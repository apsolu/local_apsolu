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
 * Gère la page de suppression d'un gestionnaire de lieux.
 *
 * @package    local_apsolu
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

use local_apsolu\core\location;
use local_apsolu\core\manager;

$managerid = required_param('managerid', PARAM_INT);
$delete = optional_param('delete', '', PARAM_ALPHANUM); // Confirmation hash.

$manager = new Manager();
$manager->load($managerid, $required = true);

$deletehash = md5($manager->id);
$returnurl = new moodle_url('/local/apsolu/courses/index.php', ['tab' => 'managers']);

if ($delete === $deletehash) {
    // Effectue les actions de suppression.
    require_sesskey();

    $manager->delete();

    $message = get_string('locations_manager_has_been_deleted', 'local_apsolu');
    redirect($returnurl, $message, $delay = null, \core\output\notification::NOTIFY_SUCCESS);
}

// Vérifie si ce gestionnaire n'est pas associé à un lieu.
$locations = Location::get_records(['managerid' => $manager->id], 'name');
if (count($locations) !== 0) {
    $datatemplate = [];
    $datatemplate['message'] = get_string('locations_manager_cannot_be_deleted', 'local_apsolu', $manager->name);
    $datatemplate['dependences'] = array_values($locations);
    $message = $OUTPUT->render_from_template('local_apsolu/courses_form_undeletable_message', $datatemplate);

    redirect($returnurl, $message, $delay = null, \core\output\notification::NOTIFY_WARNING);
}

// Affiche un message de confirmation.
$datatemplate = [];
$datatemplate['message'] = get_string('do_you_want_to_delete_locations_manager', 'local_apsolu', $manager->name);
$message = $OUTPUT->render_from_template('local_apsolu/courses_form_delete_message', $datatemplate);

// Bouton de validation.
$urlarguments = ['tab' => 'managers', 'action' => 'delete', 'managerid' => $manager->id, 'delete' => $deletehash];
$confirmurl = new moodle_url('/local/apsolu/courses/index.php', $urlarguments);
$confirmbutton = new single_button($confirmurl, get_string('delete'), 'post');

// Bouton d'annulation.
$cancelurl = new moodle_url('/local/apsolu/courses/index.php', ['tab' => 'managers']);

echo $OUTPUT->confirm($message, $confirmbutton, $cancelurl);
