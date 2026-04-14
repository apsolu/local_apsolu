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
$notification = null;

$urlparams = ['page' => $page, 'courseid' => $course->id];
if ($sessionid !== 0) {
    $urlparams['sessionid'] = $sessionid;
}

$PAGE->set_url('/local/apsolu/attendance/index.php', $urlparams);

$streditcoursesettings = get_string('qr_codes_settings', 'local_apsolu');

$PAGE->navbar->add($streditcoursesettings);

$title = $streditcoursesettings;
$fullname = $course->fullname;

$PAGE->set_title($title);
$PAGE->set_heading($fullname);

if ($sessionid == '0') {
    // Plusieurs QR codes (toutes les sessions à venir pour ce cours).
    $session = new stdClass();
    $session->id = $sessionid;
    $session->name = 'Toutes les sessions à venir';
    $sessions = [$session->id => $session->name];
    foreach (Session::get_records(['courseid' => $courseid]) as $record) {
        if ($record->has_expired() === true) {
            continue;
        }
        $sessions[$record->id] = $record->name;
    }

    if (count($sessions) === 1) {
        throw new moodle_exception('no_course_sessions_found_please_check_the_period_settings', 'local_apsolu');
    }

    $incomingsessions = $sessions;
    unset($incomingsessions[0]);
    $qrcodedbstatus = qrcode::get_course_qrcodes_dbstatus(array_keys($incomingsessions));

    $setupglobal = true;
    $qrcode = false;
} else {
    // 1 QR code : session particulière
    $session = Session::get_record(['id' => $sessionid], '*', MUST_EXIST);
    if ($session->courseid != $courseid) {
        // Id de session incorrect par rapport à l'id du cours.
        throw new moodle_exception('no_course_sessions_found', 'local_apsolu');
    }

    // Session terminée?
    if ($session->has_expired()) {
        $notification = $OUTPUT->notification(get_string('the_qrcode_session_has_expired', 'local_apsolu'), 'warning');
    }

    $qrcode = qrcode::get_record(['sessionid' => $session->id]);
    $qrcodedbstatus = ['isindb' => $qrcode !== false, 'isprintable' => $qrcode !== false && empty($qrcode->settings->rotate)];

    $setupglobal = false;
}

if ($qrcode === false) {
    // QR code n'existe pas encore pour la session OU session id = 0 (soit paramètres des QR codes pour toutes les sessions).
    $qrcode = new qrcode();
    $qrcode->set_default_settings(); // On applique les settings par défaut cad ceux définis dans l'admin du site.
    $qrcode->sessionid = $sessionid; // Session id = 0 si pas de session id.
} else {
    $qrcode->settings = json_decode($qrcode->settings);
}

// Valeurs du formulaire : valeurs par défaut (si pas de qr code ou pas de session id) ou valeurs actuelles.
$default = new stdClass();
$default->sessionid = $session->id;

foreach ((array) $qrcode->settings as $key => $value) {
    $default->$key = $value;
}


// Types de présences.
$statuses = [];
foreach (Status::get_records() as $record) {
    $statuses[$record->id] = $record->longlabel;
}


// Build form.
$customdata = [$default, $statuses];
$customdata['setupglobal'] = $setupglobal;
$customdata['courseid'] = $course->id;
$customdata['qrcodedbstatus'] = $qrcodedbstatus;

$mform = new qrcode_form($PAGE->url->out(false), $customdata);

// Traite les données envoyées par le formulaire.
if ($data = $mform->get_data()) {
    // Construit la variable stockant les options du QR code.
    $settings = new stdClass();
    foreach (qrcode::get_json_setting_names() as $name) {
        if (isset($data->$name) === false) {
            $data->$name = '';
        }

        $settings->$name = $data->$name;
    }

    // Tous les QR codes.
    if ($setupglobal) {
        $codes = array_keys($sessions);
        unset($codes[0]);

        $qrcodesdb = [];
        $valid = true;
        foreach ($codes as $sessionid) {
            $qrcode = qrcode::get_record(['sessionid' => $sessionid]);

            // Enregistrer : le qr code doit exister en bd.
            if (isset($_POST['save'])) {
                $event = 'updated';
                if ($qrcode === false) {
                    throw new moodle_exception('no_qrcode_found', 'local_apsolu');
                    $valid = false;
                    break;
                }
                $qrcode->settings = $settings; // On modifie les paramètres, mais pas le keycode.
                $qrcodesdb[] = $qrcode;
            } else if (isset($_POST['generate'])) {
                // Générer.
                $event = 'created';
                // Le qrcode n'est pas en bdd : on créé une nouvelle entrée après avoir fait un delete sur le session id.
                if ($qrcode === false) {
                    $qrcode = new qrcode();
                    $qrcode->sessionid = $sessionid;
                }
                $qrcode->settings = $settings;
                $qrcode->keycode = qrcode::generate_keycode(); // On génère un nouveau keycode (création et mise à jour).
                $qrcodesdb[] = $qrcode;
            }
        }

        if ($valid && isset($event)) {
            $count = 0;
            foreach ($qrcodesdb as $qrcode) {
                $qrcode->save();
                $count++;
            }
            $notification = $OUTPUT->notification(get_string('X_qr_codes_' . $event, 'local_apsolu', $count), 'notifysuccess');
        }
    } else {
        // Un seul QR code.
        $qrcode = qrcode::get_record(['sessionid' => $session->id]);

        // Enregistrer : le qr code doit exister en bd.
        if (isset($_POST['save'])) {
            if ($qrcode === false) {
                throw new moodle_exception('no_qrcode_found', 'local_apsolu');
            } else {
                $qrcode->settings = $settings; // On modifie les paramètres, mais pas le keycode.
                $qrcode->save();
                $notification = $OUTPUT->notification(
                    get_string('X_qr_code_updated', 'local_apsolu', $session->name),
                    'notifysuccess'
                );
            }
        } else if (isset($_POST['generate'])) {
            // Générer.

            // Le qrcode n'est pas en bdd : on créé une nouvelle entrée après avoir fait un delete sur le session id.
            if ($qrcode === false) {
                $qrcode = new qrcode();
                $qrcode->sessionid = $session->id;
            }
            $qrcode->settings = $settings;
            $qrcode->keycode = qrcode::generate_keycode(); // On génère un nouveau keycode (création et mise à jour).
            $qrcode->save();

            $notification = $OUTPUT->notification(
                get_string('X_qr_code_created', 'local_apsolu', $session->name),
                'notifysuccess'
            );
        }
    }

    // Update form after DB inserts.
    if ($sessionid == "0") {
        $customdata['qrcodedbstatus'] = qrcode::get_course_qrcodes_dbstatus(array_keys($incomingsessions));
    } else {
        $customdata['qrcodedbstatus'] = qrcode::get_session_qrcode_dbstatus($sessionid);
    }

    $mform = new qrcode_form($PAGE->url->out(false), $customdata);
} else if ($setupglobal) {
    // Message lorsqu'on arrive sur le formulaire pour indiquer que les paramètres indiqués sont les paramètres par défaut
    // et non pas les paramètres actuels (certains qr codes ont peut être des paramètres différents).
    $notification = $OUTPUT->notification(get_string('beware_of_default_settings', 'local_apsolu'), 'notifymessage');
}

// Titre du formulaire : sessions concernées (toutes les sessions à venir ou une seule session).
$sessionsetuplabel = html_writer::span(get_string('generate_a_qr_code_for', 'local_apsolu'), 'me-3');

// Lien pour retourner à la vue par session, avec l'id session concernée le cas échéant.
$href = new moodle_url(
    '/local/apsolu/attendance/index.php',
    ['page' => 'edit', 'courseid' => $courseid, 'sessionid' => $session->id]
);
$sessionsetup = html_writer::link(
    $href,
    $session->name,
    ['class' => 'text-info ms-3', 'title' => get_string('qrcode_navigation_help', 'local_apsolu')]
);

$title = html_writer::div($sessionsetuplabel . $sessionsetup, 'h4 mt-5 mb-3 ps-1');

// Affichage de la page.
echo $OUTPUT->header();
echo $OUTPUT->tabtree($tabsbar, 'qrcode');
echo $OUTPUT->heading($title);
if ($notification !== null) {
    echo $notification;
}
$mform->display();
echo $OUTPUT->footer();
