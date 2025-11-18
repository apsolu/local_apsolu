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
 * Page pour afficher le QR code et prendre les présences.
 *
 * @package    local_apsolu
 * @copyright  2025 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\attendance\qrcode;
use local_apsolu\core\attendancesession as Session;
use core_qrcode;

require_once(__DIR__ . '/../../../config.php');

$id = optional_param('id', 0, PARAM_INT);
$keycode = optional_param('keycode', null, PARAM_TEXT);

$qrcodeenabled = get_config('local_apsolu', 'qrcode_enabled');
if (empty($qrcodeenabled) === true) {
    // TODO.
    throw new moodle_exception('');
}

if ($keycode !== null) {
    unset($id);
    $qrcode = qrcode::get_record(['keycode' => $keycode], '*', MUST_EXIST);
} else {
    unset($keycode);
    $qrcode = qrcode::get_record(['id' => $id], '*', MUST_EXIST);
}

$qrcode->settings = json_decode($qrcode->settings, $associative = false, flags: JSON_THROW_ON_ERROR);

$session = Session::get_record(['id' => $qrcode->sessionid], '*', MUST_EXIST);

// Login to the course.
$course = $DB->get_record('course', ['id' => $session->courseid], '*', MUST_EXIST);
require_login($course);

// Vérifier qu'il s'agit d'une activité APSOLU.
$activity = $DB->get_record('apsolu_courses', ['id' => $course->id]);
if ($activity === false) {
    throw new moodle_exception('taking_attendance_is_only_possible_on_a_course', 'local_apsolu');
}

// Basic access control checks.
$coursecontext = context_course::instance($course->id, MUST_EXIST);
if (isset($id) === true) {
    require_capability('moodle/course:update', $coursecontext);

    $PAGE->set_pagelayout('print');
    $PAGE->set_url('/local/apsolu/attendance/qrcode.php', ['id' => $id]);

    // $PAGE->add_body_class('limitedwidth');

    if (empty($qrcode->settings->rotate) === false) {
        // TODO: call JS.
    }

    // Construit le fil d'ariane.
    $courseurl = new moodle_url('/course/view.php', ['id' => $course->id]);
    $sessionurl = new moodle_url('/local/apsolu/attendance/index.php', ['page' => 'edit', 'courseid' => $course->id]);

    // Construit l'image du QR code.
    $qrcodeurl = new moodle_url('/local/apsolu/attendance/qrcode.php', ['keycode' => $qrcode->keycode]);
    $image = new core_qrcode($qrcodeurl->out(false));

    $lines = explode(PHP_EOL, $image->getBarcodeSVGcode(15, 15, $black));
    unset($lines[0], $lines[1]);
    $lines[2] = preg_replace(
        '/<svg width="([0-9]+)" height="([0-9]+)"/',
        '<svg width="100%" height="100%" viewBox="0 0 \1 \2"',
        $lines[2]
    );

    // Affichage du QR code.
    $data = new stdClass();
    $data->course = html_writer::link($courseurl, $course->fullname);
    $data->session = html_writer::link($sessionurl, $session->name);
    $data->image = implode(PHP_EOL, $lines);
    if (empty($CFG->debugdisplay) === false) {
        $url = new moodle_url('/local/apsolu/attendance/qrcode.php', ['keycode' => $qrcode->keycode]);
        $data->debugurl = $url->out(false);
    }

    echo $OUTPUT->render_from_template('local_apsolu/attendance_qrcode', $data);

    exit(0);
}

// TODO.

if (
    empty($qrcode->settings->allowguests) === true &&
    is_enrolled($coursecontext, $user = null, $withcapability = '', $onlyactive = true) === false
) {
    throw new moodle_exception('not_enrolled', 'local_apsolu');
}

$PAGE->set_pagelayout('print');
$PAGE->set_url('/local/apsolu/attendance/qrcode.php', ['id' => $id]);

$PAGE->set_pagelayout('admin');
echo 'fin';die();
