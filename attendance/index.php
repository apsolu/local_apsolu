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
 * Contrôleur pour les pages de cours pour module de prises de présences.
 *
 * @package    local_apsolu
 * @copyright  2025 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\federation\activity;
use local_apsolu\core\federation\adhesion;
use local_apsolu\core\federation\course as FederationCourse;
use local_apsolu\event\federation_adhesion_viewed;

require_once(__DIR__ . '/../../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$page = required_param('page', PARAM_ALPHA);

$pages = ['edit', 'export', 'overview', 'sessions'];

$qrcodeenabled = get_config('local_apsolu', 'qrcode_enabled');
if (empty($qrcodeenabled) === false) {
    $pages[] = 'qrcode';
}

if (in_array($page, $pages, $strict = true) === false) {
    $page = 'edit';
}

// Login to the course.
$course = get_course($courseid);
require_login($course);

// Vérifier qu'il s'agit d'une activité APSOLU.
$activity = $DB->get_record('apsolu_courses', ['id' => $course->id]);
if ($activity === false) {
    throw new moodle_exception('taking_attendance_is_only_possible_on_a_course', 'local_apsolu');
}

// Basic access control checks.
$coursecontext = context_course::instance($course->id);
require_capability('moodle/course:update', $coursecontext);

$PAGE->set_pagelayout('admin');
$PAGE->set_url('/local/apsolu/attendance/index.php', ['page' => $page, 'courseid' => $course->id]);
$PAGE->set_context($coursecontext);

// Onglets.
$tabsbar = [];

$url = new moodle_url('/local/apsolu/attendance/index.php', ['page' => 'edit', 'courseid' => $courseid]);
$tabsbar[] = new tabobject('edit', $url, get_string('attendance_sessionsview', 'local_apsolu'));

$url = new moodle_url('/local/apsolu/attendance/index.php', ['page' => 'overview', 'courseid' => $courseid]);
$tabsbar[] = new tabobject('overview', $url, get_string('attendance_overview', 'local_apsolu'));

$url = new moodle_url('/local/apsolu/attendance/index.php', ['page' => 'sessions', 'courseid' => $courseid]);
$tabsbar[] = new tabobject('sessions', $url, get_string('attendance_sessions_edit', 'local_apsolu'));

$url = new moodle_url('/local/apsolu/attendance/index.php', ['page' => 'export', 'courseid' => $courseid]);
$tabsbar[] = new tabobject('export', $url, get_string('export', 'local_apsolu'));

if (empty($qrcodeenabled) === false) {
    $url = new moodle_url('/local/apsolu/attendance/index.php', ['page' => 'qrcode', 'courseid' => $courseid]);
    $tabsbar[] = new tabobject('qrcode', $url, get_string('qr_code', 'local_apsolu'));
}

// Charge la page.
require_once($CFG->dirroot . '/local/apsolu/attendance/' . $page . '/index.php');
