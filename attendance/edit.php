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
 * Page de prise des présences.
 *
 * @package    local_apsolu
 * @copyright  2017 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use UniversiteRennes2\Apsolu\Payment;
use local_apsolu\core\attendance;
use local_apsolu\core\customfields;

require_once(__DIR__.'/../../../config.php');
require_once($CFG->dirroot.'/local/apsolu/classes/apsolu/payment.php');

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

$streditcoursesettings = get_string('attendance', 'local_apsolu');

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

echo $OUTPUT->header();
echo $OUTPUT->tabtree($tabsbar, 'sessions');
echo $OUTPUT->heading($pagedesc);

$sessions = $DB->get_records('apsolu_attendance_sessions', array('courseid' => $courseid));
$count_sessions = count($sessions);

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
        if (($session->sessiontime + 24 * 60 * 60) > time()) {
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

if (isset($invalid_enrolments) === true) {
    $lists_style = '';
    $args['invalid_enrolments'] = 1;
} else {
    $lists_style = ' style="display: none;"';
}

if (isset($inactive_enrolments) === true) {
    $args['inactive_enrolments'] = 1;
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

$customfields = customfields::getCustomFields();

// Récupérer tous les inscrits.
// TODO: jointure avec colleges
$sql = "SELECT u.*, ue.id AS ueid, ue.status, ue.timestart, ue.timeend, ue.enrolid, e.enrol, ra.id AS raid, ra.roleid, uid1.data AS apsolusesame, uid2.data AS apsolucardpaid".
    " FROM {user} u".
    " LEFT JOIN {user_info_data} uid1 ON u.id = uid1.userid AND uid1.fieldid = :apsolusesame".
    " LEFT JOIN {user_info_data} uid2 ON u.id = uid2.userid AND uid2.fieldid = :apsolucardpaid".
    " JOIN {user_enrolments} ue ON u.id = ue.userid".
    " JOIN {enrol} e ON e.id = ue.enrolid".
    " JOIN {role_assignments} ra ON u.id = ra.userid AND ((e.id = ra.itemid) OR (e.enrol = 'manual' AND ra.itemid = 0))".
    " JOIN {role} r ON r.id = ra.roleid".
    " JOIN {context} ctx ON ra.contextid = ctx.id AND ctx.instanceid = e.courseid".
    " WHERE e.status = 0". // Only active enrolments.
    " AND e.courseid = :courseid".
    " AND ctx.contextlevel = 50". // Course level.
    " AND r.archetype = 'student'";

$params = array();
$params['apsolusesame'] = $customfields['apsolusesame']->id;
$params['apsolucardpaid'] = $customfields['apsolucardpaid']->id;

if (isset($invalid_enrolments) === false) {
    // Récupération des inscriptions courantes.
    $sql .= "AND ue.status = 0".
        " AND (ue.timestart < :timestart OR ue.timestart = 0)".
        " AND (ue.timeend > :timeend OR ue.timeend = 0)";
    $params['timestart'] = $session->sessiontime;
    $params['timeend'] = $session->sessiontime;
} else {
    // Récupération de toutes les inscriptions.
    $sql .= " AND ue.status >= 0";
}

$sql .= " ORDER BY u.lastname, u.firstname";

if (isset($CFG->is_siuaps_rennes) === true && in_array($courseid, array(210, 218, 6, 330), true) === true) {
    // Hack pour les cours de football.
    if (in_array($courseid, array(210, 218), true) === true) {
        $sql = str_replace(' AND e.courseid = :courseid', ' AND e.courseid IN(210, 218)', $sql);
    } else {
        $sql = str_replace(' AND e.courseid = :courseid', ' AND e.courseid IN(6, 330)', $sql);
    }
    $students = $DB->get_records_sql($sql, $params);
} else {
    $params['courseid'] = $courseid;
    $students = $DB->get_records_sql($sql, $params);
}

// TODO: récupérer les gens inscrits ponctuellement.
$sql = "SELECT DISTINCT u.*, uid1.data AS apsolusesame, uid2.data AS apsolucardpaid".
    " FROM {user} u".
    " JOIN {apsolu_attendance_presences} aap ON u.id = aap.studentid".
    " JOIN {apsolu_attendance_sessions} aas ON aas.id = aap.sessionid".
    " LEFT JOIN {user_info_data} uid1 ON u.id = uid1.userid AND uid1.fieldid = :apsolusesame".
    " LEFT JOIN {user_info_data} uid2 ON u.id = uid2.userid AND uid2.fieldid = :apsolucardpaid".
    " WHERE aas.courseid = :courseid";

$params = array();
$params['apsolusesame'] = $customfields['apsolusesame']->id;
$params['apsolucardpaid'] = $customfields['apsolucardpaid']->id;
$params['courseid'] = $courseid;

foreach ($DB->get_records_sql($sql, $params) as $student) {
    if (isset($students[$student->id]) === false) {
        $student->status = null;
        $student->timestart = time() + 60;
        $student->timeend = time() + 60;
        $student->enrolid = null;
        $student->raid = null;
        $student->roleid = null;

        $students[$student->id] = $student;
    }
}

// Tri les étudiants alphabétiquement.
uasort($students, function($a, $b) {
    if ($a->lastname > $b->lastname) {
        return 1;
    } else if ($a->lastname < $b->lastname) {
        return -1;
    } else {
        if ($a->firstname > $b->firstname) {
            return 1;
        } else if ($a->firstname < $b->firstname) {
            return -1;
        }
    }

    return 0;
});

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

$course_presences = attendance::getCoursePresences($courseid);
$activity_presences = attendance::getActivityPresences($course->category);

$presences = $DB->get_records('apsolu_attendance_presences', array('sessionid' => $sessionid), $sort = '', $fields = 'studentid, statusid, description, id');

$roles = role_fix_names($DB->get_records('role'));

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
    '<caption class="text-left">'.get_string('attendance_table_caption', 'local_apsolu', (object) ['count_students' => count($students)]).'</caption>'.
    '<thead>'.
        '<tr>';
if (isset($inactive_enrolments) === true) {
    echo '<th>'.get_string('attendance_enrolment_state', 'local_apsolu').'</th>';
}
echo '<th>'.get_string('pictureofuser').'</th>'.
            '<th>'.get_string('lastname').'</th>'.
            '<th>'.get_string('firstname').'</th>'.
            '<th>'.get_string('attendance_presence', 'local_apsolu').'</th>'.
            '<th>'.get_string('attendance_comment', 'local_apsolu').'</th>'.
            '<th>'.get_string('attendance_course_presences_count', 'local_apsolu').'</th>'.
            '<th>'.get_string('attendance_activity_presences_count', 'local_apsolu').'</th>'.
            '<th>'.get_string('attendance_enrolment_type', 'local_apsolu').'</th>'.
            '<th'.$lists_style.'>'.get_string('attendance_enrolment_list', 'local_apsolu').'</th>'.
            '<th>'.get_string('attendance_complement', 'local_apsolu').'</th>'.
            '<th>'.get_string('attendance_enrolments_management', 'local_apsolu').'</th>'.
        '</tr>'.
    '</thead>'.
    '<tbody>';

$statuses = $DB->get_records('apsolu_attendance_statuses');

$authorizedUsers = enrol_select_plugin::get_authorized_registred_users($courseid, $session->sessiontime, $session->sessiontime);

$payments = Payment::get_users_cards_status_per_course($courseid);
$paymentsimages = Payment::get_statuses_images();

foreach ($students as $student) {
    $activestart = ($student->timestart == 0 || $student->timestart < time());
    $activeend = ($student->timeend == 0 || $student->timeend > time());
    $enrolment_status = intval($activestart && $activeend);

    if (isset($inactive_enrolments) === false && $enrolment_status === 0) {
        // Inactive enrolement !
        continue;
    }

    if ($enrolment_status === 1) {
        $enrolment_status = get_string('active');
        $enrolment_status_style = 'text-center';
    } else {
        $enrolment_status = get_string('inactive');
        $enrolment_status_style = 'text-center table-warning';
    }

    $picture = new user_picture($student);
    $picture->size = 50;

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
        // $status_style = ' class="table-warning"';
    }

    if (isset($presences[$student->id]->description) === false) {
        $presences[$student->id] = new stdClass();
        $presences[$student->id]->description = '';
    }

    if (isset($course_presences[$student->id]) === false) {
        $course_presences[$student->id] = array('Présences: 0');
    }

    if (isset($activity_presences[$student->id]) === false) {
        $activity_presences[$student->id] = array('Présences: 0');
    }

    // Information.
    $informations = array();

    $informations_style = 'none';
    if (isset($student->apsolusesame) === false || $student->apsolusesame !== '1') {
        $informations[] = get_string('attendance_invalid_account', 'local_apsolu');
        $informations_style = 'table-danger';
    }

    if (isset($payments[$student->id]) === true) {
        foreach ($payments[$student->id] as $card) {
            $informations[] = $paymentsimages[$card->status]->image.' '.$card->fullname;
            if ($card->status === Payment::DUE) {
                $informations_style = 'table-danger';
            }
        }
    }

    if (isset($authorizedUsers[$student->id]) === false) {
        $informations[] = get_string('attendance_forbidden_enrolment', 'local_apsolu');
        $informations_style = 'table-danger';
    }

    if (isset($roles[$student->roleid]) === true) {
        $rolename = $roles[$student->roleid]->name;
    } else {
        $rolename = '-';
    }

    if (isset($student->enrolid) === true) {
        $enrolment_link = '<a class="btn btn-default apsolu-attendance-edit-enrolments" data-userid="'.$student->id.'" data-courseid="'.$courseid.'" data-enrolid="'.$student->enrolid.'" data-statusid="'.$student->status.'" data-roleid="'.$student->roleid.'" href="'.$CFG->wwwroot.'/enrol/'.$student->enrol.'/manage.php?enrolid='.$student->enrolid.'">'.get_string('attendance_edit_enrolment', 'local_apsolu').'</a>';
    } else {
        $enrolment_link = get_string('attendance_ontime_enrolment', 'local_apsolu');
    }

    echo '<tr>';
    if (isset($inactive_enrolments) === true) {
        echo '<td class="'.$enrolment_status_style.'">'.$enrolment_status.'</td>';
    }
    echo '<td>'.$OUTPUT->render($picture).'</td>'.
        '<td>'.$student->lastname.'</td>'.
        '<td>'.$student->firstname.'</td>'.
        '<td'.$status_style.'>'.$radios.'</td>'.
        '<td><textarea name="comment['.$student->id.']">'.htmlentities($presences[$student->id]->description, ENT_COMPAT, 'UTF-8').'</textarea></td>'.
        '<td><ul><li>'.implode('</li><li>', $course_presences[$student->id]).'</li></ul></td>'.
        '<td><ul><li>'.implode('</li><li>', $activity_presences[$student->id]).'</li></ul></td>'.
        '<td class="apsolu-attendance-role" data-userid="'.$student->id.'">'.$rolename.'</td>';

    if ($student->status === null) {
        echo '<td'.$lists_style.'>-</td>';
    } else {
        echo '<td class="apsolu-attendance-status" data-userid="'.$student->id.'"'.$lists_style.'>'.enrol_select_plugin::get_enrolment_list_name($student->status, 'short').'</td>';
    }

    echo '<td class="'.$informations_style.'">'.implode('<br />', $informations).'</td>'.
            '<td>'.$enrolment_link.'</td>'.
        '</tr>';
}
echo '</tbody>'.
    '</table>';

echo '<p class="text-right">'.
    '<input class="btn btn-primary" type="submit" name="apsolu" value="'.get_string('savechanges').'" />';

if (isset($invalid_enrolments) === true) {
    echo '<input type="hidden" name="invalid_enrolments" value="1" />';
}

if (isset($inactive_enrolments) === true) {
    echo '<input type="hidden" name="inactive_enrolments" value="1" />';
}

echo '</p>';
echo '</form>';

echo $OUTPUT->footer();
