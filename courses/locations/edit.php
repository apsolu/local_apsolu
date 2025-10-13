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
 * Page pour gérer l'édition d'un lieu de pratique.
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\location;

defined('MOODLE_INTERNAL') || die;

require(__DIR__ . '/edit_form.php');

// Get location id.
$locationid = optional_param('locationid', 0, PARAM_INT);

// Generate object.
$location = new Location();
if ($locationid !== 0) {
    $location->load($locationid);
}

// Load areas.
$areas = [];
foreach ($DB->get_records('apsolu_areas', $conditions = null, $sort = 'name') as $area) {
    $areas[$area->id] = $area->name;
}

if ($areas === []) {
    throw new moodle_exception('error_no_area', 'local_apsolu', $CFG->wwwroot . '/local/apsolu/courses/index.php?tab=areas');
}

// Load managers.
$managers = [];
foreach ($DB->get_records('apsolu_managers', $conditions = null, $sort = 'name') as $manager) {
    $managers[$manager->id] = $manager->name;
}

if ($managers === []) {
    throw new moodle_exception('error_no_manager', 'local_apsolu', $CFG->wwwroot . '/local/apsolu/courses/index.php?tab=managers');
}

// Build form.
$customdata = [$location, $areas, $managers];
$mform = new local_apsolu_courses_locations_edit_form(null, $customdata);

if ($data = $mform->get_data()) {
    // Message à afficher à la fin de l'enregistrement.
    $message = get_string('location_updated', 'local_apsolu');
    if (empty($location->id) === true) {
        $message = get_string('location_saved', 'local_apsolu');
    }

    // Save data.
    $location->save($data);

    // Redirige vers la page générale.
    $returnurl = new moodle_url('/local/apsolu/courses/index.php', ['tab' => 'locations']);
    redirect($returnurl, $message, $delay = null, \core\output\notification::NOTIFY_SUCCESS);
}

// Display form.
$heading = get_string('edit_location', 'local_apsolu');
if (empty($location->id) === true) {
    $heading = get_string('add_location', 'local_apsolu');
}

echo $OUTPUT->heading($heading);
$mform->display();
