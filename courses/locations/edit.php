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

defined('MOODLE_INTERNAL') || die;

require(__DIR__.'/edit_form.php');

// Get location id.
$locationid = optional_param('locationid', 0, PARAM_INT);

// Generate object.
$location = false;
if ($locationid != 0) {
    $location = $DB->get_record('apsolu_locations', array('id' => $locationid));
}

if ($location === false) {
    $location = new stdClass();
    $location->id = 0;
    $location->name = '';
    $location->area = '';
    $location->address = '';
    $location->email = '';
    $location->phone = '';
    $location->longitude = '';
    $location->latitude = '';
    $location->wifi_access = 1;
    $location->indoor = '';
    $location->restricted_access = '';
    $location->manager = '';
} else {
    $location->area = $location->areaid;
    $location->manager = $location->managerid;
}

// Load areas.
$areas = array();
foreach ($DB->get_records('apsolu_areas', $conditions = null, $sort = 'name') as $area) {
    $areas[$area->id] = $area->name;
}

if ($areas === array()) {
    print_error('error_no_area', 'local_apsolu', $CFG->wwwroot.'/local/apsolu/courses/index.php?tab=areas');
}

// Load managers.
$managers = array();
foreach ($DB->get_records('apsolu_managers', $conditions = null, $sort = 'name') as $manager) {
    $managers[$manager->id] = $manager->name;
}

if ($managers === array()) {
    print_error('error_no_manager', 'local_apsolu', $CFG->wwwroot.'/local/apsolu/courses/index.php?tab=managers');
}

// Build form.
$customdata = array($location, $areas, $managers);
$mform = new local_apsolu_courses_locations_edit_form(null, $customdata);

if ($data = $mform->get_data()) {
    // Save data.
    $location = new stdClass();
    $location->id = $data->locationid;
    $location->name = $data->name;
    $location->areaid = $data->area;
    $location->address = $data->address;
    $location->email = $data->email;
    $location->phone = $data->phone;
    $location->longitude = $data->longitude;
    $location->latitude = $data->latitude;
    $location->wifi_access = $data->wifi_access;
    $location->indoor = $data->indoor;
    $location->restricted_access = $data->restricted_access;
    $location->managerid = $data->manager;

    if ($location->id == 0) {
        $location->id = $DB->insert_record('apsolu_locations', $location);
    } else {
        $DB->update_record('apsolu_locations', $location);
    }

    // Display notification and display elements list.
    $notificationform = $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');

    require(__DIR__.'/view.php');
} else {
    // Display form.
    echo '<h1>'.get_string('location_add', 'local_apsolu').'</h1>';

    $mform->display();
}
