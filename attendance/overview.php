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
 * Page récapitulative des présences.
 *
 * @package    local_apsolu
 * @copyright  2017 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../../config.php');

$courseid = optional_param('courseid', 0, PARAM_INT); // Course id.
$sessionid = optional_param('sessionid', 0, PARAM_INT); // Session id.
$invalid_enrolments = optional_param('invalid_enrolments', null, PARAM_INT);
$inactive_enrolments = optional_param('inactive_enrolments', null, PARAM_INT);

$PAGE->set_pagelayout('base'); // Désactive l'affichage des blocs.
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

$url = new moodle_url('/local/apsolu/attendance/sessions/index.php', array('courseid' => $courseid));
$tabsbar[] = new tabobject('sessions_edit', $url, get_string('attendance_sessions_edit', 'local_apsolu'));

$url = new moodle_url('/local/apsolu/attendance/export/export.php', array('courseid' => $courseid));
$tabsbar[] = new tabobject('export', $url, get_string('export', 'local_apsolu'));

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

$sessions = $DB->get_records('apsolu_attendance_sessions', array('courseid' => $courseid), $sort = 'sessiontime');
$statuses = $DB->get_records('apsolu_attendance_statuses');

echo '<div class="table-responsive">'.
'<table class="table table-striped">'.
    '<thead class="thead-light">'.
        '<tr>'.
            '<th rowspan="2">'.get_string('pictureofuser').'</th>'.
            '<th rowspan="2">'.get_string('lastname').'</th>'.
            '<th rowspan="2">'.get_string('firstname').'</th>';
foreach ($sessions as $session) {
    echo '<th rowspan="2" class="text-center">'.userdate($session->sessiontime, get_string('strftimeabbrday', 'local_apsolu')).'</th>';
}
echo '<th colspan="'.count($statuses).'" class="text-center">'.get_string('attendance_presences_summary', 'local_apsolu').'</th></tr>';

$total_presence = new stdClass();
echo '<tr>';
foreach ($statuses as $status) {
    echo '<th>'.get_string($status->code, 'local_apsolu').'</th>';
    $total_presence->{$status->code} = 0;
}
echo '</tr></thead><tbody>';

$session_presences = array();

$statuses = $DB->get_records('apsolu_attendance_statuses');
foreach ($users as $user) {
    // Initialise le compteur de présence pour l'utilisateur.
    $user_presences = clone $total_presence;

    $picture = new user_picture($user);
    $picture->size = 50;

    echo '<tr>';
    echo '<td>'.$OUTPUT->render($picture).'</td>';
    echo '<td>'.$user->lastname.'</td>';
    echo '<td>'.$user->firstname.'</td>';

    $presences = $DB->get_records('apsolu_attendance_presences', array('studentid' => $user->id), $sort = '', $field = 'sessionid, statusid, description');
    foreach ($sessions as $session) {
        if (isset($session_presences[$session->id]) === false) {
            // Initialise le compteur de présence de cette session.
            $session_presences[$session->id] = clone $total_presence;
        }

        if (isset($presences[$session->id]) === true) {
            $presence = $presences[$session->id];
            $code = $statuses[$presence->statusid]->code;

            // Incrémente le compteur de présences pour la session.
            $session_presences[$session->id]->{$code}++;
            $user_presences->{$code}++;

            if (empty($presence->description) === true) {
                $comment = '';
            } else {
                $comment = '<details class="apsolu-comments-details">'.
                    '<summary class="apsolu-comments-summary"><img alt="'.get_string('comments').'" class="iconsmall" src="'.$OUTPUT->image_url('t/message').'" /></summary>'.
                    '<div class="apsolu-comments-div">'.$presence->description.'</div>'.
                    '</details>';
            }

            $abbr = get_string($code.'_short', 'local_apsolu');
            $label = get_string($code, 'local_apsolu');
            $style = get_string($code.'_style', 'local_apsolu');

            echo '<td class="table-'.$style.' text-center"><abbr title="'.$label.'">'.$abbr.'</abbr>'.$comment.'</td>';
        } else {
            echo '<td class="text-center">-</td>';
        }
    }

    foreach ($user_presences as $presence) {
        echo '<th class="text-center">'.$presence.'</th>';
    }

    echo '</tr>';
}

echo '</tbody>';
echo '</tfoot>';
foreach ((array) $total_presence as $code => $value) {
    echo '<tr>';
    echo '<th colspan="3">'.get_string($code.'_total', 'local_apsolu').'</th>';
    foreach ($session_presences as $presence) {
        echo '<th class="text-center">'.$presence->{$code}.'</th>';
    }
    echo '</tr>';
}
echo '</tfoot>';
echo '</table></div>';

echo $OUTPUT->footer();
