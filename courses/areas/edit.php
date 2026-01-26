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
 * Page pour gérer l'édition d'une zone géographique.
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\area;

defined('MOODLE_INTERNAL') || die;

require(__DIR__ . '/edit_form.php');

// Get area id.
$areaid = optional_param('areaid', 0, PARAM_INT);

// Generate object.
$area = new Area();
if ($areaid !== 0) {
    $area->load($areaid);
}

// Cities.
$cities = [];
foreach ($DB->get_records('apsolu_cities') as $city) {
    $cities[$city->id] = $city->name;
}

// Build form.
$customdata = [$area, $cities];
$mform = new local_apsolu_courses_areas_edit_form(null, $customdata);

if ($data = $mform->get_data()) {
    // Message à afficher à la fin de l'enregistrement.
    $message = get_string('area_updated', 'local_apsolu');
    if (empty($area->id) === true) {
        $message = get_string('area_saved', 'local_apsolu');
    }

    // Save data.
    $area->save($data);

    // Redirige vers la page générale.
    $returnurl = new moodle_url('/local/apsolu/courses/index.php', ['tab' => 'areas']);
    redirect($returnurl, $message, $delay = null, \core\output\notification::NOTIFY_SUCCESS);
}

// Display form.
$heading = get_string('edit_area', 'local_apsolu');
if (empty($area->id) === true) {
    $heading = get_string('add_area', 'local_apsolu');
}

echo $OUTPUT->heading($heading);
$mform->display();
