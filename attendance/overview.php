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
$calendarid = optional_param('calendarid', null, PARAM_INT); // Calendar id.
$sessionid = optional_param('sessionid', 0, PARAM_INT); // Session id.

$PAGE->set_pagelayout('base'); // Désactive l'affichage des blocs.
$PAGE->set_url('/local/apsolu/attendance/edit.php', ['courseid' => $courseid]);

// Basic access control checks.
// Login to the course and retrieve also all fields defined by course format.
$course = get_course($courseid);
require_login($course);
$course = course_get_format($course)->get_course();

$category = $DB->get_record('course_categories', ['id' => $course->category], '*', MUST_EXIST);
$coursecontext = context_course::instance($course->id);
require_capability('moodle/course:update', $coursecontext);

// Vérifier qu'il s'agit d'une activité APSOLU.
$activity = $DB->get_record('apsolu_courses', ['id' => $course->id]);
if ($activity === false) {
    // TODO: créer un message.
    throw new moodle_exception('needcoursecategroyid');
}

$streditcoursesettings = get_string('attendance_overview', 'local_apsolu');

$PAGE->navbar->add($streditcoursesettings);

$title = $streditcoursesettings;
$fullname = $course->fullname;

$PAGE->set_title($title);
$PAGE->set_heading($fullname);

// Call javascript.
$options = [];
$options['widthFixed'] = true;
$options['widgets'] = ['stickyHeaders'];
$options['widgetOptions'] = ['stickyHeaders_filteredToTop' => true, 'stickyHeaders_offset' => '50px'];
$PAGE->requires->js_call_amd('local_apsolu/sort', 'initialise', [$options]);

$PAGE->requires->js_call_amd('local_apsolu/attendance', 'initialise');

// Build tabtree.
$tabsbar = [];

$url = new moodle_url('/local/apsolu/attendance/edit.php', ['courseid' => $courseid]);
$tabsbar[] = new tabobject('sessions', $url, get_string('attendance_sessionsview', 'local_apsolu'));

$url = new moodle_url('/local/apsolu/attendance/overview.php', ['courseid' => $courseid]);
$tabsbar[] = new tabobject('overview', $url, get_string('attendance_overview', 'local_apsolu'));

$url = new moodle_url('/local/apsolu/attendance/sessions/index.php', ['courseid' => $courseid]);
$tabsbar[] = new tabobject('sessions_edit', $url, get_string('attendance_sessions_edit', 'local_apsolu'));

$url = new moodle_url('/local/apsolu/attendance/export/export.php', ['courseid' => $courseid]);
$tabsbar[] = new tabobject('export', $url, get_string('export', 'local_apsolu'));

// Affiche des onglets pour choisir son semestre.
$sql = "SELECT DISTINCT ac.id, ac.coursestartdate, ac.courseenddate, ac.name
          FROM {apsolu_calendars} ac
          JOIN {enrol} e ON e.customchar1 = ac.id
         WHERE e.enrol = 'select'
           AND e.courseid = :courseid
           AND e.status = 0";
$calendars = [];
foreach ($DB->get_records_sql($sql, ['courseid' => $courseid]) as $calendar) {
    if (isset($calendars[0]) === false) {
        // Ajoute le lien pour afficher la vue complète.
        $url = new moodle_url('/local/apsolu/attendance/overview.php', ['courseid' => $courseid]);
        $calendars[0] = (object) ['active' => false, 'name' => get_string('fullview', 'local_apsolu'), 'url' => $url];
    }

    $params = ['courseid' => $courseid, 'calendarid' => $calendar->id];
    $url = new moodle_url('/local/apsolu/attendance/overview.php', $params);

    $calendar->url = $url;
    $calendar->active = ((int) $calendar->id === $calendarid);

    if ($calendarid === null && $calendar->coursestartdate > time() && $calendar->courseenddate < time()) {
        $calendarid = $calendar->id;
        $calendar->active = true;
    }

    $calendars[$calendar->id] = $calendar;
}

if (isset($calendars[$calendarid]) === true) {
    // Récupère toutes les sessions disponibles correspondant au semestre sélectionné.
    $starttime = $calendars[$calendarid]->coursestartdate;
    $endtime = $calendars[$calendarid]->courseenddate;

    $sql = "SELECT aas.*
              FROM {apsolu_attendance_sessions} aas
             WHERE aas.courseid = :courseid
               AND aas.sessiontime BETWEEN :starttime AND :endtime
             ORDER BY aas.sessiontime";
    $params = ['courseid' => $courseid, 'starttime' => $starttime, 'endtime' => $endtime];
    $sessions = $DB->get_records_sql($sql, $params);
} else {
    // Rend l'onglet de vue d'ensemble de toutes les sessions actif.
    $calendars[0]->active = true;

    // Récupère toutes les sessions disponibles.
    $sessions = $DB->get_records('apsolu_attendance_sessions', ['courseid' => $courseid], $sort = 'sessiontime');
}
$statuses = $DB->get_records('apsolu_attendance_statuses', $conditions = null, $sort = 'sortorder');

// Initialise la variable globale pour la template.
$data = new stdClass();
$data->calendars = array_values($calendars);
$data->count_calendars = count($data->calendars);

// Récupère les sessions disponibles/sélectionnées.
$data->sessions = [];
$data->count_sessions = 0;
foreach ($sessions as $session) {
    $data->sessions[] = userdate($session->sessiontime, get_string('strftimeabbrday', 'local_apsolu'));
    $data->count_sessions++;
}

// Récupère les différents statuts de présence.
$data->statuses = [];
$data->count_statuses = count($statuses);
$totalpresences = [];
foreach ($statuses as $status) {
    $data->statuses[] = $status->longlabel;
    $totalpresences[$status->id] = 0;
}

$totalpresencespersessions = [];

// Construit la liste des utilisateurs.
$users = [];

// Récupère la liste des utilisateurs inscrits aux cours.
$sql = "SELECT DISTINCT u.*, '0' AS guest
          FROM {user} u
          JOIN {user_enrolments} ue ON u.id = ue.userid
          JOIN {enrol} e ON e.id = ue.enrolid
         WHERE ue.status = 0
           AND e.status = 0
           AND e.courseid = :courseid";
$params = ['courseid' => $courseid];

if (isset($calendars[$calendarid]) === true) {
    $sql .= " AND e.customchar1 = :calendarid";
    $params['calendarid'] = $calendarid;
}

foreach ($DB->get_records_sql($sql, $params) as $user) {
    $id = $user->lastname.' '.$user->firstname.' '.$user->institution.' '.$user->id;
    $users[$id] = $user;
}

// Récupère la liste des utilisateurs ayant une présence dans ce cours.
$sql = "SELECT DISTINCT u.*, '1' AS guest
          FROM {user} u
          JOIN {apsolu_attendance_presences} aap ON u.id = aap.studentid
          JOIN {apsolu_attendance_sessions} aas ON aas.id = aap.sessionid
         WHERE aas.courseid = :courseid";
$params = ['courseid' => $courseid];

if (isset($calendars[$calendarid]) === true) {
    $sql .= " AND aas.sessiontime BETWEEN :starttime AND :endtime";
    $params['starttime'] = $calendars[$calendarid]->coursestartdate;
    $params['endtime'] = $calendars[$calendarid]->courseenddate;
}

foreach ($DB->get_records_sql($sql, $params) as $user) {
    $id = $user->lastname.' '.$user->firstname.' '.$user->institution.' '.$user->id;

    if (isset($users[$id])) {
        continue;
    }

    $users[$id] = $user;
}

ksort($users);

$data->users = [];
foreach ($users as $user) {
    $picture = new user_picture($user);
    $picture->size = 50;

    $student = new stdClass();
    $student->picture = $OUTPUT->render($picture);
    $student->lastname = $user->lastname;
    $student->firstname = $user->firstname;
    $student->guest = $user->guest;
    $student->presences_per_sessions = [];
    $student->total_presences_per_statuses = $totalpresences;

    // TODO: optimiser en sortant cette requête dans la boucle.
    $fields = 'sessionid, statusid, description';
    $presences = $DB->get_records('apsolu_attendance_presences', ['studentid' => $user->id], $sort = '', $fields);
    foreach ($sessions as $session) {
        if (isset($totalpresencespersessions[$session->id]) === false) {
            // Initialise le compteur de présence de cette session.
            $totalpresencespersessions[$session->id] = $totalpresences;
        }

        $presence = new stdClass();
        if (isset($presences[$session->id]) === true) {
            $id = $presences[$session->id]->statusid;

            // Incrémente le compteur de présences pour la session.
            $totalpresencespersessions[$session->id][$id]++;
            $student->total_presences_per_statuses[$id]++;

            // Récupère les informations à afficher sur le template.
            $presence->description = $presences[$session->id]->description;
            $presence->abbr = $statuses[$id]->shortlabel;
            $presence->label = $statuses[$id]->longlabel;
            $presence->style = $statuses[$id]->color;
        }
        $student->presences_per_sessions[] = $presence;
    }

    $student->total_presences_per_statuses = array_values($student->total_presences_per_statuses);
    $data->users[] = $student;
}

$data->total_per_statuses = [];
foreach ($totalpresences as $id => $value) {
    $status = new stdClass();
    $status->label = $statuses[$id]->sumlabel;
    $status->sessions = [];
    foreach ($totalpresencespersessions as $presence) {
        $status->sessions[] = $presence[$id];
    }

    $data->total_per_statuses[] = $status;
}

// Affichage de la page.
echo $OUTPUT->header();
echo $OUTPUT->tabtree($tabsbar, 'overview');
echo $OUTPUT->render_from_template('local_apsolu/attendance_sessions_overview', $data);
echo $OUTPUT->footer();
