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
 * Page pour lister les types de calendriers.
 *
 * @package    local_apsolu
 * @copyright  2017 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('calendars_types', 'local_apsolu'));

$calendars_types = $DB->get_records('apsolu_calendars_types', $conditions = array(), $sort = 'name');

$data = new stdClass();
$data->wwwroot = $CFG->wwwroot;
$data->calendars_types = array();
$data->count_calendars_types = 0;

foreach ($calendars_types as $calendar) {
    $data->calendars_types[] = $calendar;
    $data->count_calendars_types++;
}

if (isset($notificationform)) {
    $data->notification = $notificationform;
}

echo $OUTPUT->render_from_template('local_apsolu/configuration_calendars_types', $data);

echo $OUTPUT->footer();
