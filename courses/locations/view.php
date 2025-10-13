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
 * Liste les lieux de pratique.
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$sql = "SELECT al.id, al.name, al.address, al.email, al.phone, al.longitude, al.latitude, am.name AS manager, aa.name AS area" .
    " FROM {apsolu_locations} al" .
    " JOIN {apsolu_managers} am ON am.id = al.managerid" .
    " JOIN {apsolu_areas} aa ON aa.id = al.areaid" .
    " ORDER BY al.name";
$locations = $DB->get_records_sql($sql);

$data = new stdClass();
$data->wwwroot = $CFG->wwwroot;
$data->locations = array_values($locations);
$data->count_locations = count($locations);

if (isset($notificationform)) {
    $data->notification = $notificationform;
}

echo $OUTPUT->render_from_template('local_apsolu/courses_locations', $data);
