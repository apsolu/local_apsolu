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
 * Exporter les présences.
 *
 * @package    local_apsolu
 * @copyright  2021 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../../../config.php');
require_once(__DIR__.'/export_form.php');
require_once($CFG->libdir . '/csvlib.class.php');

$courseid = required_param('courseid', PARAM_INT); // Course id.

$PAGE->set_pagelayout('base'); // Désactive l'affichage des blocs.
$PAGE->set_url('/local/apsolu/attendance/export/export.php', array('courseid' => $courseid));

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

$strexport = get_string('export', 'local_apsolu');

$PAGE->navbar->add($strexport);

$pagedesc = $strexport;
$title = $strexport;
$fullname = $course->fullname;

$PAGE->set_title($title);
$PAGE->set_heading($fullname);

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

$mform = new local_apsolu_attendance_export_form(null, array('courseid' => $courseid));

if ($data = $mform->get_data()) {
    // Récupère toutes les sessions du cours.
    $sql = "SELECT session.id, session.name".
        " FROM {apsolu_attendance_sessions} session".
        " WHERE courseid = :courseid";
    $params = array('courseid' => $courseid);

    if (empty($data->startdate) === false) {
        $sql .= " AND session.sessiontime >= :startdate";
        $params['startdate'] = $data->startdate;
    }

    if (empty($data->enddate) === false) {
        $sql .= " AND session.sessiontime <= :enddate";
        $params['enddate'] = $data->enddate;
    }

    $headers = array();
    $headers[] = get_string('firstname');
    $headers[] = get_string('lastname');
    $headers[] = get_string('idnumber');
    $headers[] = get_string('email');
    $headers[] = get_string('roles');

    $sessions = array();
    $recordset = $DB->get_recordset_sql($sql, $params);
    foreach ($recordset as $session) {
        $headers[] = $session->name;
        $headers[] = get_string('attendance_comment', 'local_apsolu');
        $sessions[$session->id] = array('status' => '', 'description' => '');
    }
    $recordset->close();

    // Récupère tous les inscrits dans le cours.
    $sql = "SELECT DISTINCT u.id, u.firstname, u.lastname, u.idnumber, u.email, ra.roleid, r.name AS role".
        " FROM {user} u".
        " JOIN {user_enrolments} ue ON u.id = ue.userid".
        " JOIN {enrol} e ON e.id = ue.enrolid".
        " JOIN {role_assignments} ra ON ra.userid = ue.userid AND ra.itemid = e.id".
        " JOIN {role} r ON r.id = ra.roleid".
        " JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50 AND ctx.instanceid = e.courseid".
        " WHERE e.courseid = :courseid".
        " AND e.enrol = 'select'".
        " AND e.status = 0". // Méthode d'inscription activée.
        " AND ue.status = 0". // Inscription acceptée.
        " ORDER BY u.lastname, u.firstname";
    $recordset = $DB->get_recordset_sql($sql, array('courseid' => $courseid));

    $users = array();
    foreach ($recordset as $user) {
        if (isset($users[$user->id]) === false) {
            $users[$user->id] = $user;
            $users[$user->id]->roles = array();
            $users[$user->id]->presences = $sessions;
        }

        $users[$user->id]->roles[$user->roleid] = $user->role;
    }
    $recordset->close();

    // Récupère toutes les présences.
    $sql = "SELECT aap.studentid, aap.statusid, aap.description, aas.name As status, aap.sessionid".
        " FROM {apsolu_attendance_presences} aap".
        " JOIN {apsolu_attendance_statuses} aas ON aas.id = aap.statusid".
        " JOIN {apsolu_attendance_sessions} session ON session.id = aap.sessionid".
        " WHERE session.courseid = :courseid";
    $recordset = $DB->get_recordset_sql($sql, array('courseid' => $courseid));
    foreach ($recordset as $presence) {
        if (isset($users[$presence->studentid]) === false) {
            $users[$presence->studentid] = $DB->get_record('user', array('id' => $presence->studentid), $fields = '*', MUST_EXIST);
            $users[$presence->studentid]->roles = array();
        }

        if (isset($users[$presence->studentid]->presences) === false) {
            $users[$presence->studentid]->presences = $sessions;
        }

        if (isset($users[$presence->studentid]->presences[$presence->sessionid]) === false) {
            continue;
        }

        $users[$presence->studentid]->presences[$presence->sessionid]['status'] = $presence->status;
        $users[$presence->studentid]->presences[$presence->sessionid]['description'] = $presence->description;
    }
    $recordset->close();

    $filename = 'presences_de_cours';

    $csvexport = new csv_export_writer();
    $csvexport->set_filename($filename);

    $csvexport->add_data($headers);

    foreach ($users as $user) {
        $data = array();
        $data[] = $user->firstname;
        $data[] = $user->lastname;
        $data[] = $user->idnumber;
        $data[] = $user->email;
        $data[] = implode(', ', $user->roles);

        foreach ($user->presences as $presence) {
            $data[] = $presence['status'];
            $data[] = $presence['description'];
        }

        $csvexport->add_data($data);
    }

    $csvexport->download_file();
    exit();
}

// Affiche le formulaire.
echo $OUTPUT->header();
echo $OUTPUT->tabtree($tabsbar, 'export');
echo $OUTPUT->heading($pagedesc);
echo $mform->display();
echo $OUTPUT->footer();
