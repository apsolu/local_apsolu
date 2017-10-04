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
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../../config.php');

$courseid = optional_param('courseid', 0, PARAM_INT); // Course id.
$sessionid = optional_param('sessionid', 0, PARAM_INT); // Session id.
$invalid_enrolments = optional_param('invalid_enrolments', null, PARAM_INT);
$inactive_enrolments = optional_param('inactive_enrolments', null, PARAM_INT);

$PAGE->set_pagelayout('admin');
$PAGE->set_url('/local/apsolu/attendance/edit.php', array('courseid' => $courseid));

// Basic access control checks.
// Login to the course and retrieve also all fields defined by course format.
$course = get_course($courseid);
require_login($course);
$course = course_get_format($course)->get_course();

$category = $DB->get_record('course_categories', array('id' => $course->category), '*', MUST_EXIST);
$coursecontext = context_course::instance($course->id);
require_capability('moodle/course:update', $coursecontext);

// Vérifier qu'il s'agit d'une activité APSOLU.
$activity = $DB->get_record('apsolu_courses', array('id' => $course->id));
if ($activity === false) {
    // TODO: créer un message.
    print_error('needcoursecategroyid');
}

$streditcoursesettings = get_string('attendance_overview', 'local_apsolu');

$PAGE->navbar->add($streditcoursesettings);

$pagedesc = $streditcoursesettings;
$title = $streditcoursesettings;
$fullname = $course->fullname;

$PAGE->set_title($title);
$PAGE->set_heading($fullname);

// Call javascript.
$PAGE->requires->js_call_amd('local_apsolu/attendance', 'initialise');

// Build tabtree.
$tabsbar = array();

$url = new moodle_url('/local/apsolu/attendance/edit.php', array('courseid' => $courseid));
$tabsbar[] = new tabobject('sessions', $url, get_string('attendance_sessionsview', 'local_apsolu'));

$url = new moodle_url('/local/apsolu/attendance/overview.php', array('courseid' => $courseid));
$tabsbar[] = new tabobject('overview', $url, get_string('attendance_overview', 'local_apsolu'));


echo $OUTPUT->header();
echo $OUTPUT->tabtree($tabsbar, 'overview');
echo $OUTPUT->heading($pagedesc);

$sql = "SELECT DISTINCT u.*".
    " FROM {user} u".
    " JOIN {apsolu_attendance_presences} aap ON u.id = aap.studentid".
    " JOIN {apsolu_attendance_sessions} aas ON aas.id = aap.sessionid".
    " WHERE aas.courseid = :courseid".
    " ORDER BY u.lastname, u.firstname, u.institution";
$users = $DB->get_records_sql($sql, array('courseid' => $courseid));

$sessions = $DB->get_records('apsolu_attendance_sessions', array('courseid' => $courseid));

echo '<table class="table table-bordered">'.
    '<thead>'.
        '<tr>'.
            '<th>'.get_string('pictureofuser').'</th>'.
            '<th>'.get_string('lastname').'</th>'.
            '<th>'.get_string('firstname').'</th>';
foreach ($sessions as $session) {
    echo '<th class="text-center">'.userdate($session->sessiontime, get_string('strftimeabbrday', 'local_apsolu')).'</th>';
}
echo '</tr></thead><tbody>';

$statuses = $DB->get_records('apsolu_attendance_statuses');
foreach ($users as $user) {
    $picture = new user_picture($user);
    $picture->size = 50;

    echo '<tr>';
    echo '<td>'.$OUTPUT->render($picture).'</td>';
    echo '<td>'.$user->lastname.'</td>';
    echo '<td>'.$user->firstname.'</td>';

    $presences = $DB->get_records('apsolu_attendance_presences', array('studentid' => $user->id), $sort = '', $field = 'sessionid, statusid, description');
    foreach ($sessions as $session) {
        if (isset($presences[$session->id]) === true) {
            $presence = $presences[$session->id];
            $code = $statuses[$presence->statusid]->code;

            if (empty($presence->description) === true) {
                $comment = '';
            } else {
                $comment = '<details class="apsolu-comments-details">'.
                    '<summary class="apsolu-comments-summary"><img alt="'.get_string('comments').'" class="iconsmall" src="'.$OUTPUT->pix_url('t/message').'" /></summary>'.
                    '<div class="apsolu-comments-div">'.$presence->description.'</div>'.
                    '</details>';
            }

            $abbr = get_string($code.'_short', 'local_apsolu');
            $label = get_string($code, 'local_apsolu');
            $style = get_string($code.'_style', 'local_apsolu');

            echo '<td class="'.$style.' text-center"><abbr title="'.$label.'">'.$abbr.'</abbr>'.$comment.'</td>';
        } else {
            echo '<td class="text-center">-</td>';
        }
    }

    echo '</tr>';
}

echo '</tbody></table>';

echo $OUTPUT->footer();
