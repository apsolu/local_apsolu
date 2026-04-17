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

use local_apsolu\core\federation\course as FederationCourse;
use local_apsolu\core\course;
use local_apsolu\core\customfields;
use local_apsolu\core\grouping;

defined('MOODLE_INTERNAL') || die;

require_once(__DIR__ . '/../../locallib.php');

$coursetypeid = optional_param('coursetypeid', null, PARAM_INT);

$coursestypes = $DB->get_records('apsolu_courses_types', $conditions = null, $sort = 'sortorder');
if (isset($coursestypes[$coursetypeid]) === false) {
    $coursetype = current($coursestypes);
    $coursetypeid = $coursetype->id;
}
$coursestypes[$coursetypeid]->selected = true;

$categories = $DB->get_records('course_categories');

$customfields = $DB->get_records(
    'apsolu_courses_fields',
    ['coursetypeid' => $coursetypeid],
    $sort = null,
    $fields = 'customfieldid, showinadministration, showonpublicpages'
);

$currentactivity = null;
$currentaltclass = 'odd';

$courses = [];
$cities = [];

$i = 0;
$locationindex = null;
$headers = [];
// Récupère la liste des champs personnalisés pour ce type de format de cours.
foreach (customfields::get_course_custom_fields($coursetypeid) as $name => $customfield) {
    if (empty($customfields[$customfield->id]->showinadministration) === true) {
        // Ignore les champs non affichés dans l'administration.
        continue;
    }

    if (in_array($name, ['category', 'event', 'type'], $strict = true) === true) {
        // Le champ "Activité" (category) est toujours affiché.
        // Les champs "Libellé complémentaire" et "Type" ne sont jamais affichés.
        continue;
    }

    if (in_array($customfield->type, ['textarea'], $strict = true) === true) {
        // Les champs de type "zone de texte" ne sont jamais affichés.
        continue;
    }

    if ($customfield->shortname === 'location') {
        $locationindex = $i;
    }

    $i++;
    $headers[$customfield->shortname] = get_string($customfield->shortname, 'local_apsolu');
}

foreach (course::get_records_by_course_type($coursetypeid) as $course) {
// foreach (course::get_records(['t.id' => $coursetypeid]) as $course) {
    /*
    if ($currentactivity !== $course->categoryid) {
        $currentactivity = $course->categoryid;

        if ($currentaltclass === 'odd') {
            $currentaltclass = 'even';
        } else {
            $currentaltclass = 'odd';
        }
    }*/
    $altclass = $currentaltclass;

    $teachers = UniversiteRennes2\Apsolu\get_teachers($course->id);
    sort($teachers);

    $fields = [];
    foreach ($headers as $key => $string) {
        $value = '';
        if (isset($course->customfields[$key]) === true) {
            $value = $course->customfields[$key]->export_value();
        }
        $fields[] = $value;
    }

    $fullname = $course->customfields['category']->export_value();
    if (isset($course->customfields['event']) === true && empty($course->customfields['event']->export_value()) === false) {
        $fullname .= ' (' . $course->customfields['event']->export_value() . ')';
    }

    $parent = $categories[$course->category]->parent;

    $data = new stdClass();
    $data->id = $course->id;
    $data->fullname = $fullname;
    $data->grouping = $categories[$parent]->name;
    $data->fields = $fields;
    $data->alt_class = $altclass;
    $data->visible = $course->visible;
    $data->teachers = $teachers;
    $data->customfields = $course->customfields;
    // $data->city = $course->city;
    // var_dump($course);die();
    if (isset($course->dbfields['cityid']) === true) {
        $cities[$course->dbfields['cityid']] = 1;
    }
    $courses[] = $data;
}

// TODO: trier $courses[] par ordre alphabétique avec la fonction Course::sort($courses).
$courses = Course::sort($courses);

if (count($cities) > 1 && $locationindex !== null) {
    foreach ($courses as $course) {
        $course->fields[$locationindex] = sprintf('[%s] %s', $course->dbfields['cityname'], $course->fields[$locationindex]);
    }
}

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
