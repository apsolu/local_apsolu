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
$sessionid = optional_param('sessionid', 0, PARAM_INT); // Course id.
$unactive_enrolements = optional_param('unactive_enrolements', null, PARAM_INT); // Course id.
// $returnto = optional_param('returnto', 0, PARAM_ALPHANUM); // Generic navigation return page switch.

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

$streditcoursesettings = get_string('attendance', 'local_apsolu');

$PAGE->navbar->add($streditcoursesettings);

$pagedesc = $streditcoursesettings;
$title = $streditcoursesettings;
$fullname = $course->fullname;

$PAGE->set_title($title);
$PAGE->set_heading($fullname);

// Call javascript.
$PAGE->requires->js_call_amd('local_apsolu/attendance', 'initialise');

echo $OUTPUT->header();
echo $OUTPUT->heading($pagedesc);

$sessions = $DB->get_records('apsolu_attendance_sessions', array('courseid' => $courseid));
$count_sessions = count($sessions);
if ($count_sessions === 0) {
    // Create sessions.
    $period = $DB->get_record('apsolu_periods', array('id' => $activity->periodid));
    if ($period === false) {
        // TODO: créer un message.
        print_error('needcoursecategroyid');
    }

    $sessions = array();
    foreach (explode(',', $period->weeks) as $week) {
        $date = new DateTime($week.'T00:00:00');
        if ($activity->numweekday !== '1') {
            $date->add(new DateInterval('P'.($activity->numweekday - 1).'D'));
        }

        list($year, $month, $day) = explode(':',  $date->format('Y:m:d'));
        list($hour, $minute) = explode(':', $activity->starttime);

        $sessiontime = make_timestamp(
            $year,
            $month,
            $day,
            $hour,
            $minute,
            0,
            $tz = 99,
            true
        );

        $count_sessions++;

        $session = new stdClass();
        $session->name = 'Cours n°'.$count_sessions.' - '.strftime('%A %e %B %Y à %Hh%M', $sessiontime); // Cours n°1 - mercredi 12 septembre à 18h.
        $session->sessiontime = $sessiontime;
        $session->courseid = $courseid;
        $session->activityid = $course->category;
        $session->timecreated = time();
        $session->timemodified = time();
        $session->id = $DB->insert_record('apsolu_attendance_sessions', $session);

        $sessions[$session->id] = $session;
    }
}

if ($count_sessions === 0) {
    // TODO: créer un message.
    print_error('needcoursecategroyid');
}

// Faire choisir une session.
require_once($CFG->dirroot.'/local/apsolu/attendance/edit_select_form.php');

$sessions_select = array();
foreach ($sessions as $session) {
    $sessions_select[$session->id] = $session->name;

    if ($sessionid === 0) {
        if ($session->sessiontime > time()) {
            $sessionid = $session->id;
        }
    }
}

if ($sessionid === 0) {
    // On met le dernier créneau de l'année.
    $sessionid = $session->id;
}

// First create select form.
$args = array(
    'courseid' => $course->id,
    'sessionid' => $sessionid,
    'sessions' => $sessions,
);
if (isset($unactive_enrolements) === true) {
    $args['unactive_enrolements'] = 1;
}

// TODO: à revoir...
$url = new moodle_url('/local/apsolu/attendance/edit.php', array('courseid' => $courseid));
$selectform = new edit_select_form($url, $args);
$selectform->display();

$session = $DB->get_record('apsolu_attendance_sessions', array('id' => $sessionid, 'courseid' => $courseid));

if ($session === false) {
    // TODO: créer un message.
    print_error('needcoursecategroyid');
}

// Récupérer tous les inscrits.
// TODO: jointure avec colleges
$sql = "SELECT u.*, ue.timestart, ue.timeend, ue.enrolid, e.enrol".
    " FROM {user} u".
    " JOIN {user_enrolments} ue ON u.id = ue.userid".
    " JOIN {enrol} e ON e.id = ue.enrolid".
    " JOIN {role_assignments} ra ON u.id = ra.userid AND ((e.id = ra.itemid) OR (e.enrol = 'manual' AND ra.itemid = 0))".
    " JOIN {role} r ON r.id = ra.roleid".
    " JOIN {context} ctx ON ra.contextid = ctx.id AND ctx.instanceid = e.courseid".
    " WHERE ue.status = 0". // Only active.
    " AND e.status = 0". // Only active.
    " AND e.courseid = :courseid".
    " AND ctx.contextlevel = 50". // Course level.
    " AND r.archetype = 'student'".
    " ORDER BY u.lastname, u.firstname";
$students = $DB->get_records_sql($sql, array('courseid' => $courseid));

// TODO: récupérer les gens inscrits ponctuellement.
$sql = "SELECT DISTINCT u.*".
    " FROM {user} u".
    " JOIN {apsolu_attendance_presences} aap ON u.id = aap.studentid".
    " JOIN {apsolu_attendance_sessions} aas ON aas.id = aap.sessionid".
    " WHERE aas.courseid = :courseid";
foreach ($DB->get_records_sql($sql, array('courseid' => $courseid)) as $student) {
    if (isset($students[$student->id]) === false) {
        $student->timestart = time() + 60;
        $student->timeend = time() + 60;
        $student->enrolid = null;

        $students[$student->id] = $student;
    }
}

// Attendance form.
$args = array(
    'courseid' => $course->id,
    'sessionid' => $sessionid,
    'students' => $students,
);

// TODO: à revoir...
$url = new moodle_url('/local/apsolu/attendance/edit.php', array('courseid' => $courseid));
/*
$mform = new edit_form($url, $args);
$mform->display();
*/

$sql = "SELECT aap.studentid, COUNT(*) AS total".
    " FROM {apsolu_attendance_presences} aap".
    " JOIN {apsolu_attendance_sessions} aas ON aas.id = aap.sessionid".
    " WHERE aas.courseid = :courseid".
    " GROUP BY studentid";
$course_presences = $DB->get_records_sql($sql, array('courseid' => $courseid));

$sql = "SELECT aap.studentid, COUNT(*) AS total".
    " FROM {apsolu_attendance_presences} aap".
    " JOIN {apsolu_attendance_sessions} aas ON aas.id = aap.sessionid".
    " WHERE aas.activityid = :categoryid".
    " GROUP BY aap.studentid";
$activity_presences = $DB->get_records_sql($sql, array('categoryid' => $course->category));

$presences = $DB->get_records('apsolu_attendance_presences', array('sessionid' => $sessionid), $sort='', $fields='studentid, statusid, description, id');

$notification = false;
if (isset($_POST['apsolu']) === true) {
    foreach ($_POST['presences'] as $userid => $status) {
        if (isset($presences[$userid]) === false) {
            $presence = new stdClass();
            $presence->studentid = $userid;
            $presence->teacherid = $USER->id;
            $presence->statusid = $status;
            $presence->description = '';
            $presence->timecreated = time();
            $presence->timemodified = time();
            $presence->sessionid = $sessionid;

            if (isset($_POST['comment'][$userid]) === true) {
                $presence->description = $_POST['comment'][$userid];
            }

            $presence->id = $DB->insert_record('apsolu_attendance_presences', $presence);
            $presences[$userid] = $presence;
            $notification = true;
        } else {
            $comment = '';
            if (isset($_POST['comment'][$userid]) === true) {
                $comment = $_POST['comment'][$userid];
            }

            $presence = $presences[$userid];
            if ($presence->statusid !== $status || $presence->description !== $comment) {
                $presence->teacherid = $USER->id;
                $presence->statusid = $status;
                $presence->description = $comment;
                $presence->timemodified = time();

                $DB->update_record('apsolu_attendance_presences', $presence);
                $presences[$userid] = $presence;
                $notification = true;
            }
        }
    }
}

echo '<h3>'.$title.' : '.$session->name.'</h3>';
if ($notification === true) {
    echo $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');
}
echo '<form method="post" action="'.$CFG->wwwroot.'/local/apsolu/attendance/edit.php?courseid='.$courseid.'&amp;sessionid='.$sessionid.'" />';
echo '<table class="table table-striped" id="apsolu-attendance-table">'.
    '<thead>'.
        '<tr>'.
            '<th>'.get_string('attendance_active_enrolment', 'local_apsolu').'</th>'.
            '<th>'.get_string('pictureofuser').'</th>'.
            '<th>'.get_string('firstname').'</th>'.
            '<th>'.get_string('lastname').'</th>'.
            '<th>'.get_string('attendance_presence', 'local_apsolu').'</th>'.
            '<th>'.get_string('attendance_comment', 'local_apsolu').'</th>'.
            '<th>'.get_string('attendance_course_presences_count', 'local_apsolu').'</th>'.
            '<th>'.get_string('attendance_activity_presences_count', 'local_apsolu').'</th>'.
            '<th>'.get_string('attendance_valid_account', 'local_apsolu').'</th>'.
            '<th>'.get_string('attendance_sport_card', 'local_apsolu').'</th>'.
            '<th>'.get_string('attendance_allowed_enrolment', 'local_apsolu').'</th>'.
            '<th>'.get_string('attendance_enrolments_management', 'local_apsolu').'</th>'.
        '</tr>'.
    '</thead>'.
    '<tbody>';

foreach ($students as $student) {
    $activestart = ($student->timestart == 0 || $student->timestart < time());
    $activeend = ($student->timeend == 0 || $student->timeend > time());
    $enrolment_status = intval($activestart && $activeend);

    if (isset($unactive_enrolements) === false && $enrolment_status === 0) {
        // Unactive enrolement !
        continue;
    }

    if ($enrolment_status === 1) {
        $enrolment_status = '<span class="text-success">'.get_string('active').'</span>';
    } else {
        $enrolment_status = '<span class="text-danger">'.get_string('inactive').'</span>';
    }

    $picture = new user_picture($student);
    $picture->size = 50;

    $statuses = $DB->get_records('apsolu_attendance_statuses');

    $radios = '';
    $found = false;
    foreach ($statuses as $status) {
        $checked = '';
        if (isset($presences[$student->id]) && $presences[$student->id]->statusid === $status->id) {
            $found = true;
            $checked = 'checked="checked" ';
        }
        $radios .= '<label><input type="radio" name="presences['.$student->id.']" value="'.$status->id.'" '.$checked.'/> '.get_string($status->code, 'local_apsolu').'</label>';
    }

    $status_style = '';
    if ($found === false) {
        // $status_style = ' class="warning"';
    }

    if (isset($presences[$student->id]->description) === false) {
        $presences[$student->id] = new stdClass();
        $presences[$student->id]->description = '';
    }

    if (isset($course_presences[$student->id]->total) === false) {
        $course_presences[$student->id] = new stdClass();
        $course_presences[$student->id]->total = 0;
    }

    if (isset($activity_presences[$student->id]->total) === false) {
        $activity_presences[$student->id] = new stdClass();
        $activity_presences[$student->id]->total = 0;
    }

    $validsesame = get_string('no');
    if (isset($student->validsesame) && $student->validsesame === '1') {
        $validsesame = get_string('yes');
    }

    $cardpaid = get_string('no');
    if (isset($student->cardpaid) && $student->cardpaid === '1') {
        $cardpaid = get_string('yes');
    }

    // TODO: est-ce qu'il est autorisé ?

    if (isset($student->enrolid) === true) {
        $enrolment_link = '<a href="'.$CFG->wwwroot.'/enrol/'.$student->enrol.'/manage.php?enrolid='.$student->enrolid.'">'.get_string('attendance_edit_enrolment', 'local_apsolu').'</a>';
    } else {
        $enrolment_link = get_string('attendance_ontime_enrolment', 'local_apsolu');
    }

    echo '<tr>'.
        '<td>'.$enrolment_status.'</td>'.
        '<td>'.$OUTPUT->render($picture).'</td>'.
        '<td>'.$student->firstname.'</td>'.
        '<td>'.$student->lastname.'</td>'.
        '<td'.$status_style.'>'.$radios.'</td>'.
        '<td><textarea name="comment['.$student->id.']">'.htmlentities($presences[$student->id]->description, ENT_COMPAT, 'UTF-8').'</textarea></td>'.
        '<td>'.$course_presences[$student->id]->total.'</td>'.
        '<td>'.$activity_presences[$student->id]->total.'</td>'.
        '<td>'.$validsesame.'</td>'.
        '<td>'.$cardpaid.'</td>'.
        '<td>-</td>'.
        '<td>'.$enrolment_link.'</td>'.
        '</tr>';
}
echo '</tbody>'.
    '</table>';
echo '<p class="text-right">'.
    '<input class="btn btn-primary" type="submit" name="apsolu" value="'.get_string('savechanges').'" />';
if (isset($unactive_enrolements) === true) {
    echo '<input type="hidden" name="unactive_enrolements" value="1" />';
}
echo '</p>';
echo '</form>';

echo $OUTPUT->footer();
