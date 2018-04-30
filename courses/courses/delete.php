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

$courseid = required_param('courseid', PARAM_INT);
$delete = optional_param('delete', '', PARAM_ALPHANUM); // Confirmation hash.

$url = new moodle_url('local/apsolu/courses/courses/edit.php', array('tab' => $tab, 'action' => 'edit', 'courseid' => $courseid));

$sql = "SELECT c.*".
    " FROM {course} c".
    " JOIN {apsolu_courses} ac ON ac.id = c.id".
    " WHERE c.id = ?";
$course = $DB->get_record_sql($sql, array($courseid), MUST_EXIST);

$coursecontext = context_course::instance($course->id);
$coursefullname = format_string($course->fullname, true, array('context' => $coursecontext));
$cancelurl = new moodle_url('/local/apsolu/courses/index.php', array('tab' => 'courses'));

$deletehash = md5($course->timemodified);

if ($delete === $deletehash) {
    // We do - time to delete the course.
    require_sesskey();

    // This might take a while. Raise the execution time limit.
    core_php_time_limit::raise();

    // We do this here because it spits out feedback as it goes.
    delete_course($course);
    $DB->delete_records('apsolu_courses', array('id' => $course->id));

    // Update course count in categories.
    fix_course_sortorder();

    echo $OUTPUT->continue_button($cancelurl);
} else {
    $strdeletecoursecheck = get_string('deletecoursecheck');
    $message = '<div class="alert alert-danger">'.$strdeletecoursecheck.'<br /><br />'.$coursefullname.'</div>';

    $urlarguments = array('tab' => 'courses', 'action' => 'delete', 'courseid' => $course->id, 'delete' => $deletehash);
    $confirmurl = new moodle_url('/local/apsolu/courses/index.php', $urlarguments);
    $confirmbutton = new single_button($confirmurl, get_string('delete'), 'post');

    echo $OUTPUT->confirm($message, $confirmbutton, $cancelurl);
}
