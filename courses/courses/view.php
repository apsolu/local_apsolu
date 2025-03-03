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
 * Liste les créneaux.
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\federation\course as FederationCourse;

defined('MOODLE_INTERNAL') || die;

require_once(__DIR__.'/../../locallib.php');

$currentactivity = null;
$currentaltclass = 'odd';

$sql = "SELECT c.id, cc.name AS category, ccc.name AS grouping, ac.event, ac.weekday, ac.starttime, ac.endtime,".
    " ask.name AS skill, al.name AS location, city.name AS city, ap.name AS period, ac.license, c.visible, c.idnumber".
    " FROM {course} c".
    " JOIN {course_categories} cc ON cc.id = c.category".
    " JOIN {course_categories} ccc ON ccc.id = cc.parent".
    " JOIN {apsolu_courses} ac ON c.id = ac.id".
    " JOIN {apsolu_courses_categories} acc ON acc.id = c.category".
    " JOIN {apsolu_skills} ask ON ask.id = ac.skillid".
    " JOIN {apsolu_locations} al ON al.id = ac.locationid".
    " JOIN {apsolu_areas} aa ON aa.id = al.areaid".
    " JOIN {apsolu_cities} city ON city.id = aa.cityid".
    " JOIN {apsolu_periods} ap ON ap.id = ac.periodid".
    " ORDER BY category, ac.numweekday, ac.starttime, city, location, skill";
$cities = [];
$courses = [];
foreach ($DB->get_records_sql($sql) as $course) {
    if ($currentactivity !== $course->category) {
        $currentactivity = $course->category;

        if ($currentaltclass === 'odd') {
            $currentaltclass = 'even';
        } else {
            $currentaltclass = 'odd';
        }
    }

    if (empty($course->event)) {
        $course->fullname = $course->category;
    } else {
        $course->fullname = $course->category.' ('.$course->event.')';
    }

    $course->alt_class = $currentaltclass;
    $course->weekday = get_string($course->weekday, 'calendar');
    $course->schedule = $course->starttime.'-'.$course->endtime;


    $teachers = UniversiteRennes2\Apsolu\get_teachers($course->id);
    sort($teachers);

    $course->teachers = $teachers;

    $cities[$course->city] = 1;
    $courses[] = $course;
}

$federationcourse = new FederationCourse();

$data = new stdClass();
$data->wwwroot = $CFG->wwwroot;
$data->courses = array_values($courses);
$data->count_courses = count($courses);
$data->unique_city = (count($cities) === 1);
$data->federation_course = $federationcourse->get_courseid();
$data->notification = '';

if (isset($notificationform)) {
    $data->notification = $notificationform;
}

// Ajoute des avertissements aux gestionnaires pour indiquer que des paramètres n'ont pas été renseignés.
$attributes = ['functional_contact', 'technical_contact'];
foreach ($attributes as $attribute) {
    $email = get_config('local_apsolu', $attribute);
    if (empty($email) === false && filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
        continue;
    }

    $parameters = new stdClass();
    $parameters->url = $CFG->wwwroot.'/local/apsolu/configuration/index.php?page=messaging';
    $parameters->page = get_string('messaging', 'local_apsolu');
    $data->notification = html_writer::div(get_string('the_fields_of_X_page_have_to_be_completed', 'local_apsolu', $parameters),
        'alert alert-danger');
    break;
}

$PAGE->requires->js_call_amd('local_apsolu/table-row-counter', 'initialise');
echo $OUTPUT->render_from_template('local_apsolu/courses_courses', $data);
