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
 * Gère la page de suppression d'un numéro d'association.
 *
 * @package    local_apsolu
 * @copyright  2022 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

use local_apsolu\core\federation\number as Number;

$numberid = required_param('numberid', PARAM_INT);
$delete = optional_param('delete', '', PARAM_ALPHANUM); // Confirmation hash.

$number = new Number();
$number->load($numberid, $required = true);

$deletehash = md5($number->id);
$returnurl = new moodle_url('/local/apsolu/federation/index.php', array('page' => 'numbers'));

if ($delete === $deletehash) {
    // Effectue les actions de suppression.
    require_sesskey();

    $number->delete();

    $message = get_string('association_number_has_been_deleted', 'local_apsolu');
    redirect($returnurl, $message, $delay = null, \core\output\notification::NOTIFY_SUCCESS);
}

// Affiche un message de confirmation.
$datatemplate = array();
$datatemplate['message'] = get_string('do_you_want_to_delete_association_number', 'local_apsolu', $number->number);
$message = $OUTPUT->render_from_template('local_apsolu/courses_form_delete_message', $datatemplate);

// Bouton de validation.
$urlarguments = array('page' => 'numbers', 'action' => 'delete', 'numberid' => $number->id, 'delete' => $deletehash);
$confirmurl = new moodle_url('/local/apsolu/federation/index.php', $urlarguments);
$confirmbutton = new single_button($confirmurl, get_string('delete'), 'post');

// Bouton d'annulation.
$cancelurl = new moodle_url('/local/apsolu/federation/index.php', array('page' => 'numbers'));

// Display form.
echo $OUTPUT->header();

echo $OUTPUT->confirm($message, $confirmbutton, $cancelurl);

echo $OUTPUT->footer();

