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
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\city as City;

defined('MOODLE_INTERNAL') || die;

require(__DIR__.'/edit_form.php');

// Get city id.
$cityid = optional_param('cityid', 0, PARAM_INT);

// Generate object.
$city = new City();
if ($cityid !== 0) {
    $city->load($cityid);
}

// Build form.
$customdata = array('city' => $city);
$mform = new local_apsolu_courses_cities_edit_form(null, $customdata);

if ($data = $mform->get_data()) {
    // Message à afficher à la fin de l'enregistrement.
    $message = get_string('city_updated', 'local_apsolu');
    if (empty($city->id) === true) {
        $message = get_string('city_saved', 'local_apsolu');
    }

    // Save data.
    $city->save($data);

    // Redirige vers la page générale.
    $returnurl = new moodle_url('/local/apsolu/courses/index.php', array('tab' => 'cities'));
    redirect($returnurl, $message, $delay = null, \core\output\notification::NOTIFY_SUCCESS);
}

// Display form.
$heading = get_string('edit_city', 'local_apsolu');
if (empty($manager->id) === true) {
    $heading = get_string('add_city', 'local_apsolu');
}

echo $OUTPUT->heading($heading);
$mform->display();
