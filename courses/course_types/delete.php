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
 * Gère la page de suppression d'un type de format.
 *
 * @package    local_apsolu
 * @copyright  2026 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_course\customfield\course_handler;
use local_apsolu\core\course;
use local_apsolu\core\coursetype;

defined('MOODLE_INTERNAL') || die;

$coursetypeid = optional_param('coursetypeid', 0, PARAM_INT);
$delete = optional_param('delete', '', PARAM_ALPHANUM); // Confirmation hash.

$coursetype = new CourseType();
$coursetype->load($coursetypeid, $required = true);

$deletehash = md5($coursetype->id);
$returnurl = new moodle_url('/local/apsolu/courses/index.php', ['tab' => 'course_types']);

// Vérifie si ce type n'est pas le dernier existant.
if ($DB->count_records('apsolu_courses_types') < 2) {
    $datatemplate = [];
    $datatemplate['message'] = get_string('course_type_cannot_be_deleted_because_this_is_the_last_one', 'local_apsolu');
    $message = $OUTPUT->render_from_template('local_apsolu/courses_form_undeletable_message', $datatemplate);

    redirect($returnurl, $message, $delay = null, \core\output\notification::NOTIFY_WARNING);
}

// Vérifie si ce type n'est pas associé à un cours.
$courses = course::get_records(['t.id' => $coursetype->id]);
if (count($courses) !== 0) {
    $datatemplate = [];
    $datatemplate['message'] = get_string('course_type_cannot_be_deleted', 'local_apsolu', $coursetype->name);
    $datatemplate['dependences'] = [];
    foreach ($courses as $course) {
        $datatemplate['dependences'][] = $course->fullname;
    }
    $message = $OUTPUT->render_from_template('local_apsolu/courses_form_undeletable_message', $datatemplate);

    redirect($returnurl, $message, $delay = null, \core\output\notification::NOTIFY_WARNING);
}

// Effectue les actions de suppression.
if ($delete === $deletehash) {
    require_sesskey();

    $coursetype->delete();

    $message = get_string('course_type_has_been_deleted', 'local_apsolu');
    redirect($returnurl, $message, $delay = null, \core\output\notification::NOTIFY_SUCCESS);
}

// Affiche un message de confirmation.
$datatemplate = [];
$datatemplate['message'] = get_string('do_you_want_to_delete_course_type', 'local_apsolu', $coursetype->name);
$message = $OUTPUT->render_from_template('local_apsolu/courses_form_delete_message', $datatemplate);

// Bouton de validation.
$urlarguments = ['tab' => 'course_types', 'action' => 'delete', 'coursetypeid' => $coursetype->id, 'delete' => $deletehash];
$confirmurl = new moodle_url('/local/apsolu/courses/index.php', $urlarguments);
$confirmbutton = new single_button($confirmurl, get_string('delete'), 'post');

// Bouton d'annulation.
$cancelurl = new moodle_url('/local/apsolu/courses/index.php', ['tab' => 'course_types']);

echo $OUTPUT->confirm($message, $confirmbutton, $cancelurl);
