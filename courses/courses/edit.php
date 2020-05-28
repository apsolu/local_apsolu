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
 * Page pour gérer l'édition d'un créneau.
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\course as Course;

defined('MOODLE_INTERNAL') || die;

require(__DIR__.'/edit_form.php');

// Get course id.
$courseid = optional_param('courseid', 0, PARAM_INT);

// Generate object.
$course = new Course();
if ($courseid !== 0) {
    $course->load($courseid);
}

$url = new moodle_url('local/apsolu/courses/courses/edit.php', array('tab' => $tab, 'action' => 'edit', 'courseid' => $courseid));

// Load categories.
$sql = "SELECT cc.id, cc.name".
    " FROM {course_categories} cc".
    " JOIN {apsolu_courses_categories} acc ON cc.id = acc.id".
    " ORDER BY cc.name";
$categories = array();
foreach ($DB->get_records_sql($sql) as $category) {
    $categories[$category->id] = $category->name;
}

if ($categories === array()) {
    print_error('error_no_category', 'local_apsolu', $CFG->wwwroot.'/local/apsolu/courses/index.php?tab=categories');
}

// Load skills.
$skills = array();
foreach ($DB->get_records('apsolu_skills', $conditions = null, $sort = 'name') as $skill) {
    $skills[$skill->id] = $skill->name;
}

if ($skills === array()) {
    print_error('error_no_skill', 'local_apsolu', $CFG->wwwroot.'/local/apsolu/courses/index.php?tab=skills');
}

// Load locations.
$locations = array();
foreach ($DB->get_records('apsolu_locations', $conditions = null, $sort = 'name') as $location) {
    $locations[$location->id] = $location->name;
}

if ($locations === array()) {
    print_error('error_no_location', 'local_apsolu', $CFG->wwwroot.'/local/apsolu/courses/index.php?tab=locations');
}

// Load periods.
$periods = array();
foreach ($DB->get_records('apsolu_periods', $conditions = null, $sort = 'name') as $period) {
    $periods[$period->id] = $period->name;
}

if ($periods === array()) {
    print_error('error_no_period', 'local_apsolu', $CFG->wwwroot.'/local/apsolu/courses/index.php?tab=periods');
}

// Load weekdays.
$weekdays = Course::get_weekdays();

// Build form.
$customdata = array($course, $categories, $skills, $locations, $periods, $weekdays);
$mform = new local_apsolu_courses_courses_edit_form(null, $customdata);

if ($data = $mform->get_data()) {
    // Message à afficher à la fin de l'enregistrement.
    $message = get_string('course_updated', 'local_apsolu');
    if (empty($course->id) === true) {
        $message = get_string('course_saved', 'local_apsolu');
    }

    // Save data.
    $data->str_category = $categories[$data->category];
    $data->str_skill = $skills[$data->skillid];
    $course->save($data);

    // Redirige vers la page générale.
    $returnurl = new moodle_url('/local/apsolu/courses/index.php', array('tab' => 'courses'));
    redirect($returnurl, $message, $delay = null, \core\output\notification::NOTIFY_SUCCESS);
}

// Display form.
$heading = get_string('edit_course', 'local_apsolu');
if (empty($course->id) === true) {
    $heading = get_string('add_course', 'local_apsolu');
}

echo $OUTPUT->heading($heading);
if (empty($course->id) === false) {
    echo '<ul class="list-inline text-right">'.
        '<li class="list-inline-item"><a class="btn btn-primary" href="'.$CFG->wwwroot.'/user/index.php?id='.$course->id.'">Inscrire un utilisateur</a></li>'.
        '<li class="list-inline-item"><a class="btn btn-primary" href="'.$CFG->wwwroot.'/enrol/instances.php?id='.$course->id.'">Méthode d\'inscription</a></li>'.
        '</ul>';
}
$mform->display();
