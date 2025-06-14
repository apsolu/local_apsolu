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
 * Page pour lister les calendriers.
 *
 * @package    local_apsolu
 * @copyright  2017 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('calendars', 'local_apsolu'));

$calendars = $DB->get_records('apsolu_calendars', $conditions = [], $sort = 'name');
$calendarstypes = $DB->get_records('apsolu_calendars_types', $conditions = [], $sort = 'name');

$fields = [];
$fields[] = 'enrolstartdate';
$fields[] = 'enrolenddate';
$fields[] = 'coursestartdate';
$fields[] = 'courseenddate';
$fields[] = 'reenrolstartdate';
$fields[] = 'reenrolenddate';
$fields[] = 'gradestartdate';
$fields[] = 'gradeenddate';

$now = time();

$data = new stdClass();
$data->wwwroot = $CFG->wwwroot;
$data->calendars = [];
$data->count_calendars = 0;

foreach ($calendars as $calendar) {
    foreach ($fields as $field) {
        if (empty($calendar->{$field}) === true) {
            continue;
        }

        if ($calendar->{$field} > $now) {
            continue;
        }

        $attribute = 'style_'.$field;
        $calendar->{$attribute} = 'class="text-danger"';
    }

    $calendar->type = $calendarstypes[$calendar->typeid]->name;

    $data->calendars[] = $calendar;
    $data->count_calendars++;
}

if (isset($notificationform)) {
    $data->notification = $notificationform;
}

$options = [];
$options['sortLocaleCompare'] = true;
$options['widgets'] = ['stickyHeaders'];
$options['widgetOptions'] = ['stickyHeaders_filteredToTop' => true, 'stickyHeaders_offset' => '50px'];

$PAGE->requires->js_call_amd('local_apsolu/sort', 'initialise', [$options]);

echo $OUTPUT->render_from_template('local_apsolu/configuration_calendars', $data);

echo $OUTPUT->footer();
