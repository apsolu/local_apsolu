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
 * @copyright  2016 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\course;
use local_apsolu\core\customfields;
use local_apsolu\core\federation\course as FederationCourse;
use local_apsolu\core\grouping;
use local_apsolu\output\courses as CoursesRenderer;

defined('MOODLE_INTERNAL') || die;

require_once(__DIR__ . '/../../locallib.php');

$coursetypeid = optional_param('coursetypeid', null, PARAM_INT);

$coursestypes = $DB->get_records('apsolu_courses_types', $conditions = null, $sort = 'sortorder');
if (isset($coursestypes[$coursetypeid]) === false) {
    $coursetype = current($coursestypes);
    $coursetypeid = $coursetype->id;
}
$coursestypes[$coursetypeid]->selected = true;


$currentactivity = null;
$currentaltclass = 'odd';

$courses = [];
$cities = [];

$headers = CoursesRenderer::get_headers($coursetypeid, CoursesRenderer::VISIBLE_ONLY_ADMINISTRATION);
$courses = CoursesRenderer::get_data(course::get_records_by_course_type($coursetypeid), $headers);
$courses = Course::sort($courses, $sortorder = ['visible', 'category', 'weekday', 'daterange', 'timerange', 'location', 'skill']);

$data = new stdClass();
$data->wwwroot = $CFG->wwwroot;
$data->courses = array_values($courses);
$data->count_courses = count($courses);
$data->notification = '';
$data->coursestypes = array_values($coursestypes);
$data->coursetypeid = $coursetypeid;
$data->headers = array_values($headers);

if (isset($notificationform)) {
    $data->notification = $notificationform;
}

// Ajoute des avertissements aux gestionnaires pour indiquer que des paramètres n'ont pas été renseignés.
$attributes = ['federation_contact', 'functional_contact', 'technical_contact'];
foreach ($attributes as $attribute) {
    $email = get_config('local_apsolu', $attribute);
    if (empty($email) === false) {
        continue;
    }

    $parameters = new stdClass();
    $parameters->url = $CFG->wwwroot . '/local/apsolu/configuration/index.php?page=messaging';
    $parameters->page = get_string('messaging', 'local_apsolu');
    $data->notification = html_writer::div(
        get_string('the_fields_of_X_page_have_to_be_completed', 'local_apsolu', $parameters),
        'alert alert-danger'
    );
    break;
}

$PAGE->requires->js_call_amd('local_apsolu/table-row-counter', 'initialise');
echo $OUTPUT->render_from_template('local_apsolu/courses_courses', $data);
