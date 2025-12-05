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
 * @copyright  2025 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\attendance\qrcode;
use local_apsolu\core\attendance\status;
use local_apsolu\core\attendancesession as Session;
use local_apsolu\form\attendance\qrcode as qrcode_form;

defined('MOODLE_INTERNAL') || die;

$sessionid = optional_param('sessionid', 0, PARAM_INT);

if ($sessionid !== 0) {
    $PAGE->set_url('/local/apsolu/attendance/index.php', ['page' => $page, 'courseid' => $course->id, 'sessionid' => $sessionid]);
}

$streditcoursesettings = get_string('qr_code', 'local_apsolu');

$PAGE->navbar->add($streditcoursesettings);

$title = $streditcoursesettings;
$fullname = $course->fullname;

$PAGE->set_title($title);
$PAGE->set_heading($fullname);

$sessions = [0 => 'Toutes les sessions à venir'];
foreach (Session::get_records(['courseid' => $courseid]) as $record) {
    if ($record->has_expired() === true) {
        continue;
    }
    $sessions[$record->id] = $record->name;
}

if (count($sessions) === 1) {
    throw new moodle_exception('no_course_sessions_found_please_check_the_period_settings', 'local_apsolu');
}

$statuses = [];
foreach (Status::get_records() as $record) {
    $statuses[$record->id] = $record->longlabel;
}

$qrcode = false;
if ($sessionid !== 0 && isset($sessions[$sessionid]) === true) {
    $qrcode = qrcode::get_record(['sessionid' => $sessionid]);
}

if ($qrcode === false) {
    $qrcode = new qrcode();
    $qrcode->set_default_settings();
    $qrcode->sessionid = $sessionid;
} else {
    $qrcode->settings = json_decode($qrcode->settings);
}

$default = new stdClass();
$default->sessionid = $qrcode->sessionid;

foreach ((array) $qrcode->settings as $key => $value) {
    $default->$key = $value;
}

// Build form.
$customdata = [$default, $sessions, $statuses];
$mform = new qrcode_form($PAGE->url->out(false), $customdata);

// Traite les données envoyées par le formulaire.
$notification = null;
if ($data = $mform->get_data()) {
    // Calcule la liste des sessions à générer.
    $codes = [];
    if (empty($data->sessionid) === true) {
        $codes = array_keys($sessions);
        unset($codes[0]);
    } else if (isset($sessions[$data->sessionid]) === true) {
        $codes = [$data->sessionid];
    }

    // Construit la variable stockant les options du QR code.
    $settings = new stdClass();
    foreach (qrcode::get_json_setting_names() as $name) {
        if (isset($data->$name) === false) {
            $data->$name = '';
        }

        $settings->$name = $data->$name;
    }

    $count = 0;
    foreach ($codes as $sessionid) {
        $qrcodedata = new stdClass();
        $qrcodedata->keycode = qrcode::generate_keycode();
        $qrcodedata->settings = $settings;
        $qrcodedata->sessionid = $sessionid;

        $qrcode = new qrcode();
        $qrcode->save($qrcodedata);

        $count++;
    }

    if ($count > 1) {
        $notification = $OUTPUT->notification(get_string('X_qr_codes_created', 'local_apsolu', $count), 'notifysuccess');
    } else {
        $notification = $OUTPUT->notification(get_string('X_qr_code_created', 'local_apsolu', $count), 'notifysuccess');
    }
}

// Affichage de la page.
echo $OUTPUT->header();
echo $OUTPUT->tabtree($tabsbar, 'qrcode');
if ($notification !== null) {
    echo $notification;
}
$mform->display();
echo $OUTPUT->footer();
