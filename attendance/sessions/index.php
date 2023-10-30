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
 * Contrôleur pour l'administration des sessions de cours.
 *
 * @package    local_apsolu
 * @copyright  2017 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../../../config.php');

$courseid = required_param('courseid', PARAM_INT); // Course id.
$sessionid = optional_param('sessionid', 0, PARAM_INT); // Session id.
$action = optional_param('action', 'view', PARAM_ALPHANUM);

$PAGE->set_pagelayout('admin');
$PAGE->set_url('/local/apsolu/attendance/sessions/index.php', ['courseid' => $courseid]);

// Basic access control checks.
// Login to the course and retrieve also all fields defined by course format.
$course = get_course($courseid);
require_login($course);
$course = course_get_format($course)->get_course();

$coursecontext = context_course::instance($course->id);
require_capability('moodle/course:update', $coursecontext);

// Vérifier qu'il s'agit d'une activité APSOLU.
$activity = $DB->get_record('apsolu_courses', ['id' => $course->id]);
if ($activity === false) {
    // TODO: créer un message.
    print_error('needcoursecategroyid');
}

$notifications = [];
$streditcoursesettings = get_string('attendance_sessions_edit', 'local_apsolu');

$data = new stdClass();
$data->url = $CFG->wwwroot;
$data->courseid = $courseid;

switch($action) {
    case 'delete':
    case 'edit':
    case 'view':
        require(__DIR__.'/'.$action.'.php');
        break;
    default:
        require(__DIR__.'/view.php');
}

// Titre et navigation.
$PAGE->navbar->add($streditcoursesettings);

$pagedesc = $streditcoursesettings;
$title = $streditcoursesettings;
$fullname = $course->fullname;

$PAGE->set_title($title);
$PAGE->set_heading($fullname);

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

echo $OUTPUT->header();
echo $OUTPUT->tabtree($tabsbar, 'sessions_edit');
echo $OUTPUT->heading($pagedesc);
foreach ($notifications as $notification) {
    echo $notification;
}
echo $OUTPUT->render_from_template($template, $data);
echo $OUTPUT->footer();
