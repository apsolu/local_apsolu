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
 * Page pour éditer les numéros d'association.
 *
 * @package    local_apsolu
 * @copyright  2022 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\federation\number as Number;

defined('MOODLE_INTERNAL') || die;

require(__DIR__.'/edit_form.php');

// Get activity id.
$numberid = optional_param('numberid', 0, PARAM_INT);

// Generate object.
$number = new Number();
if ($numberid !== 0) {
    $number->load($numberid, $required = true);
}

// Build form.
$customdata = array('number' => $number, 'fields' => Number::get_default_fields());
$mform = new local_apsolu_federation_activities_edit_form(null, $customdata);

if ($data = $mform->get_data()) {
    // Message à afficher à la fin de l'enregistrement.
    $message = get_string('association_number_updated', 'local_apsolu');
    if (empty($number->id) === true) {
        $message = get_string('association_number_saved', 'local_apsolu');
    }

    // Positionne l'ordre de traitement.
    if (empty($number->id) === true) {
        $number->sortorder = $DB->count_records('apsolu_federation_numbers');
    }

    // Save data.
    $number->save($data, $mform);

    // Redirige vers la page générale.
    $returnurl = new moodle_url('/local/apsolu/federation/index.php', array('page' => 'numbers'));
    redirect($returnurl, $message, $delay = null, \core\output\notification::NOTIFY_SUCCESS);
}

// Display form.
$heading = get_string('edit_association_number', 'local_apsolu');
if (empty($number->id) === true) {
    $heading = get_string('add_association_number', 'local_apsolu');
}

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);
echo $OUTPUT->tabtree($tabtree, $page);

$mform->display();

echo $OUTPUT->footer();
