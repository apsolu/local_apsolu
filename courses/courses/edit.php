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
 * @copyright  2016 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\course;
use local_apsolu\core\customfields;

defined('MOODLE_INTERNAL') || die;

require(__DIR__ . '/edit_form.php');

// Récupère les données passées en paramètre.
$courseid = optional_param('courseid', 0, PARAM_INT);
$coursetypeid = required_param('coursetypeid', PARAM_INT);

// Récupère le créneau (ou génère un nouveau créneau).
$course = new Course();
// $course->typeid = $coursetypeid;
if ($courseid !== 0) {
    $course->load($courseid);
}

if (empty($course->id) === false) {
    $coursetypeid = $course->customfields['type']->get_value();
} else {
    // Initialise les champs personnalisés.
    $course->customfields = Course::get_customfield_records();
}

if (empty($coursetypeid) === true) {
    throw new moodle_exception('missingparam', 'error', '', 'coursetypeid');
}

// Récupère le format de créneau.
$coursetype = $DB->get_record('apsolu_courses_types', ['id' => $coursetypeid], '*', MUST_EXIST);

// Construit l'url de la page.
$paramurl = ['tab' => $tab, 'action' => 'edit', 'courseid' => $courseid, 'coursetypeid' => $coursetypeid];
$url = new moodle_url('/local/apsolu/courses/index.php', $paramurl);

// Construit le formulaire.
$customdata = [$coursetypeid, $course];
$mform = new local_apsolu_courses_courses_edit_form($url->out(false), $customdata);

if ($data = $mform->get_data()) {
    // Message à afficher à la fin de l'enregistrement.
    $message = get_string('course_updated', 'local_apsolu');
    if (empty($course->id) === true) {
        $message = get_string('course_saved', 'local_apsolu');
    }

    $course->save($data);

    // Redirige vers la page générale.
    $returnurl = new moodle_url('/local/apsolu/courses/index.php', ['tab' => 'courses', 'coursetypeid' => $coursetypeid]);
    redirect($returnurl, $message, $delay = null, \core\output\notification::NOTIFY_SUCCESS);
}

// Display form.
$heading = get_string('edit_course_of_type_X', 'local_apsolu', $coursetype->name);
if (empty($course->id) === true) {
    $heading = get_string('add_course_of_type_X', 'local_apsolu', $coursetype->name);
}
echo $OUTPUT->heading($heading);
$mform->display();
