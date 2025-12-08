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

// phpcs:disable moodle.Commenting.TodoComment.MissingInfoInline

use local_apsolu\attendance\qrcode;
use local_apsolu\core\attendancesession as Session;
use core_qrcode;

require_once(__DIR__ . '/../../../config.php');

$id = optional_param('id', 0, PARAM_INT);
$sessionid = optional_param('sessionid', null, PARAM_INT);
$keycode = optional_param('keycode', null, PARAM_TEXT);
$print = optional_param('print', null, PARAM_INT);

$qrcodeenabled = get_config('local_apsolu', 'qrcode_enabled');
if (empty($qrcodeenabled) === true) {
    throw new moodle_exception('qr_code_function_is_not_enabled', 'local_apsolu');
}

if ($keycode !== null) {
    // Gère le scan du QR code par les étudiants.
    unset($id, $sessionid);
    $qrcode = qrcode::get_record(['keycode' => $keycode]);
    if ($qrcode === false) {
        // TODO: améliorer l'affichage.
        $PAGE->set_context(context_system::instance());
        $PAGE->set_pagelayout('course');
        $PAGE->set_url('/local/apsolu/attendance/qrcode.php', ['keycode' => $keycode]);

        echo $OUTPUT->header();
        echo $OUTPUT->notification(get_string('the_qr_code_does_not_exist_or_has_expired', 'local_apsolu'), 'notifyproblem');
        echo $OUTPUT->footer();
        exit(0);
    }
} else if ($sessionid !== null) {
    // Gére la création du QR code par l'enseignant.
    unset($id, $keycode);

    $session = Session::get_record(['id' => $sessionid], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $session->courseid], '*', MUST_EXIST);

    // Vérifier qu'il s'agit d'une activité APSOLU.
    $activity = $DB->get_record('apsolu_courses', ['id' => $course->id]);
    if ($activity === false) {
        throw new moodle_exception('taking_attendance_is_only_possible_on_a_course', 'local_apsolu');
    }

    // Basic access control checks.
    $coursecontext = context_course::instance($course->id, MUST_EXIST);

    // Login to the course.
    require_login($course, $autologinguest = false);

    require_capability('moodle/course:update', $coursecontext);

    $qrcode = new qrcode();
    $qrcode->keycode = qrcode::generate_keycode();
    $qrcode->set_default_settings();
    $qrcode->sessionid = $sessionid;
    $qrcode->save();

    // Réaffiche la page en passant l'id du QR code en paramètre.
    redirect(new moodle_url('/local/apsolu/attendance/qrcode.php', ['id' => $qrcode->id]));
} else {
    // Gére l'affichage du QR code par l'enseignant.
    unset($keycode, $sessionid);
    $qrcode = qrcode::get_record(['id' => $id], '*', MUST_EXIST);
}

$qrcode->settings = json_decode($qrcode->settings, $associative = false, flags: JSON_THROW_ON_ERROR);

$session = Session::get_record(['id' => $qrcode->sessionid], '*', MUST_EXIST);

$course = $DB->get_record('course', ['id' => $session->courseid], '*', MUST_EXIST);

// Vérifier qu'il s'agit d'une activité APSOLU.
$activity = $DB->get_record('apsolu_courses', ['id' => $course->id]);
if ($activity === false) {
    throw new moodle_exception('taking_attendance_is_only_possible_on_a_course', 'local_apsolu');
}

// Basic access control checks.
$coursecontext = context_course::instance($course->id, MUST_EXIST);
$PAGE->set_context($coursecontext);

if (isset($id) === true) {
    // Login to the course.
    require_login($course, $autologinguest = false);

    require_capability('moodle/course:update', $coursecontext);

    $PAGE->set_pagelayout('print');
    $PAGE->set_url('/local/apsolu/attendance/qrcode.php', ['id' => $id]);

    $rotate = empty($qrcode->settings->rotate) === false;
    if ($rotate === true) {
        $settings = $qrcode->settings;
        $qrcode->keycode = qrcode::generate_keycode();
        $qrcode->save();

        // Restaure les paramètres au format objet.
        $qrcode->settings = $settings;
    }

    $isloggedin = isloggedin();
    if (empty($qrcode->settings->autologout) === false && $isloggedin === true) {
        // TODO: gérer le warning "mutated the session after it was closed".
        $authsequence = get_enabled_auth_plugins();
        foreach ($authsequence as $authname) {
            $authplugin = get_auth_plugin($authname);
            $authplugin->logoutpage_hook();
        }

        require_logout();
        $isloggedin = false;
    }

    // Construit le fil d'ariane.
    $courseurl = new moodle_url('/course/view.php', ['id' => $course->id]);
    $sessionurl = new moodle_url('/local/apsolu/attendance/index.php', ['page' => 'edit', 'courseid' => $course->id]);

    // Construit l'image du QR code.
    $qrcodeurl = new moodle_url('/local/apsolu/attendance/qrcode.php', ['keycode' => $qrcode->keycode]);
    $image = new core_qrcode($qrcodeurl->out(false));

    $lines = explode(PHP_EOL, $image->getBarcodeSVGcode(15, 15, $color = 'black'));
    unset($lines[0], $lines[1]);
    $lines[2] = preg_replace(
        '/<svg width="([0-9]+)" height="([0-9]+)"/',
        '<svg width="100%" height="100%" viewBox="0 0 \1 \2"',
        $lines[2]
    );

    // Affichage du QR code.
    $data = new stdClass();
    $data->wwwroot = $CFG->wwwroot;
    $data->color = get_config('theme_apsolu', 'custom_brandcolor');
    $data->sessionname = $session->name;
    $data->sitename = format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), "escape" => false]);
    $data->course = html_writer::link($courseurl, $course->fullname);
    $data->session = html_writer::link($sessionurl, $session->name);
    $data->rotate = $rotate;
    $data->image = implode(PHP_EOL, $lines);
    $data->print = $print;
    $data->user = get_string('loggedinnot');
    if ($isloggedin === true) {
        $data->user = fullname($USER);
    }
    if (empty($CFG->debugdisplay) === false) {
        $url = new moodle_url('/local/apsolu/attendance/qrcode.php', ['keycode' => $qrcode->keycode]);
        $data->debugurl = $url->out(false);
    }

    echo $OUTPUT->render_from_template('local_apsolu/attendance_qrcode', $data);

    exit(0);
}

// Login to the site to handle guests.
require_login($courseorid = null, $autologinguest = false);

$PAGE->set_pagelayout('course');
$PAGE->set_url('/local/apsolu/attendance/qrcode.php', ['keycode' => $keycode]);
$PAGE->navbar->add(get_string('attendance', 'local_apsolu'));

$CFG->additionalhtmltopofbody = ''; // Désactive sur cette page le bandeau d'information.

$now = time();
$roles = [];

$sql = "SELECT DISTINCT r.*
          FROM {role} r
          JOIN {role_assignments} ra ON r.id = ra.roleid
          JOIN {context} ctx ON ra.contextid = ctx.id
          JOIN {course} c ON c.id = ctx.instanceid AND ctx.contextlevel = 50
          JOIN {enrol} e ON c.id = e.courseid AND ra.itemid = e.id
          JOIN {user_enrolments} ue ON e.id = ue.enrolid AND ue.userid = ra.userid
         WHERE ra.userid = :userid
           AND e.status = :status
           AND (ue.timestart = 0 OR :now1 >= ue.timestart)
           AND (ue.timeend = 0 OR :now2 <= ue.timeend)";
$params = ['userid' => $USER->id, 'status' => ENROL_INSTANCE_ENABLED, 'now1' => $now, 'now2' => $now];
foreach (role_fix_names($DB->get_records_sql($sql, $params)) as $role) {
    $roles[] = $role->name;
}

if ($roles === []) {
    $roles[] = get_string('none');
}

echo $OUTPUT->header();
echo $OUTPUT->heading($course->fullname);

echo html_writer::start_tag('dl', ['class' => 'row']);
echo html_writer::start_tag('div', ['class' => 'col-12 col-md-6']);
echo html_writer::tag('dt', get_string('course'));
echo html_writer::tag('dd', $course->fullname);
echo html_writer::tag('dt', get_string('user'));
echo html_writer::tag('dd', fullname($USER));
echo html_writer::end_tag('div');
echo html_writer::start_tag('div', ['class' => 'col-12 col-md-6']);
echo html_writer::tag('dt', get_string('session', 'local_apsolu'));
echo html_writer::tag('dd', $session->name);
echo html_writer::tag('dt', get_string('role', 'local_apsolu'));
echo html_writer::tag('dd', implode(', ', $roles));
echo html_writer::end_tag('div');
echo html_writer::end_tag('dl');

try {
    $message = $qrcode->sign($session);

    echo $OUTPUT->notification($message, 'notifysuccess');
} catch (Exception $exception) {
    echo $OUTPUT->notification($exception->getMessage(), 'notifyproblem');
}

echo $OUTPUT->footer();
