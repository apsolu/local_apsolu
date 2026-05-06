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

use core\exception\moodle_exception;
use local_apsolu\attendance\qrcode;
use local_apsolu\core\attendancesession as Session;
use core_qrcode;

require_once(__DIR__ . '/../../../config.php');

$id = optional_param('id', 0, PARAM_INT);
$sessionid = optional_param('sessionid', null, PARAM_INT);
$courseid = optional_param('courseid', null, PARAM_INT);
$keycode = optional_param('keycode', null, PARAM_TEXT);

// Au moins l'un de ces paramètres doit être fourni!
if (empty($id) && empty($sessionid) && empty($courseid) && empty($keycode)) {
    throw new \moodle_exception('missingparam', '', '', "keycode OR id OR sessionid OR courseid");
}

$print = optional_param('print', null, PARAM_INT);

$qrcodeenabled = get_config('local_apsolu', 'qrcode_enabled');
if (empty($qrcodeenabled) === true) {
    throw new moodle_exception('qr_code_function_is_not_enabled', 'local_apsolu');
}

// Gére l'action "Générer et afficher"
// QR code généré avec les paramètres par défaut (admin) depuis la vue par sessions puis page rechargée avec l'id du code.
if (!empty($sessionid)) {
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

    try {
        require_capability('moodle/course:update', $coursecontext);
    } catch (required_capability_exception $exception) {
        throw new moodle_exception('not_allowed_qrcode_display', 'local_apsolu');
    }

    $qrcode = new qrcode();
    $qrcode->keycode = qrcode::generate_keycode();
    $qrcode->set_default_settings();
    $qrcode->sessionid = $sessionid;
    $qrcode->save();

    // Réaffiche la page en passant l'id du QR code en paramètre.
    redirect(new moodle_url('/local/apsolu/attendance/qrcode.php', ['id' => $qrcode->id]));
} else {
    // Gère l'action afficher (un seul QR code) / imprimer (un ou plusieurs QR codes) / scanner.

    // Tous les QR codes des sessions à venir.
    if (!empty($courseid)) {
        unset($id, $sessionid, $keycode);
        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    } else {
        // Un seul QR code : passé en paramètre soit
            // - par l'id pour affichage / impression par l'enseignant
            // - par keycode pour pointage par l'étudiant.
        if (!empty($keycode)) {
            unset($id, $sessionid, $courseid);
            $codeidentifier = ['keycode' => $keycode];
        } else if (!empty($id)) { // Endpoint pour le cas où aucun paramètre fourni dans le GET (id = 0).
            unset($keycode, $sessionid, $courseid);
            $codeidentifier = ['id' => $id];
        }

        $qrcode = qrcode::get_record($codeidentifier);

        // QR code non trouvé ou expiré.
        if ($qrcode === false) {
            $PAGE->set_context(context_system::instance());
            $PAGE->set_pagelayout('course');
            $PAGE->set_url('/local/apsolu/attendance/qrcode.php', $codeidentifier);

            echo $OUTPUT->header();
            echo $OUTPUT->notification(get_string('the_qr_code_does_not_exist_or_has_expired', 'local_apsolu'), 'notifyproblem');
            echo $OUTPUT->footer();
            exit(0);
        }


        $qrcode->settings = json_decode($qrcode->settings, $associative = false, flags: JSON_THROW_ON_ERROR);
        $session = Session::get_record(['id' => $qrcode->sessionid], '*', MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $session->courseid], '*', MUST_EXIST);
    }

    // Vérifier qu'il s'agit d'une activité APSOLU.
    $activity = $DB->get_record('apsolu_courses', ['id' => $course->id]);
    if ($activity === false) {
        throw new moodle_exception('taking_attendance_is_only_possible_on_a_course', 'local_apsolu');
    }

    // Basic access control checks.
    $coursecontext = context_course::instance($course->id, MUST_EXIST);
    $PAGE->set_context($coursecontext);

    // Affichage du QR code (affichage / impression) par l'enseignant.
    if (isset($id) === true || isset($courseid) === true) {
        // Login to the course.
        require_login($course, $autologinguest = false);
        $isloggedin = isloggedin();

        // Ne peuvent afficher ou imprimer un qr code que les personnes autorisées à modifier le cours.
        try {
            require_capability('moodle/course:update', $coursecontext);
        } catch (required_capability_exception $exception) {
            throw new moodle_exception('not_allowed_qrcode_display', 'local_apsolu');
        }

        $PAGE->set_url('/local/apsolu/attendance/qrcode.php', isset($id) ? ['id' => $id] : ['courseid' => $courseid]);

        // Affichage de plusieurs QR codes : en mode impression uniquement
        // (incompatible avec rotate, ne tient pas compte d'autologout).
        if (isset($courseid)) {
            $print = true;
            $rotate = false;

            $sessions = [];
            foreach (Session::get_records(['courseid' => $courseid]) as $record) {
                if ($record->has_expired() === true) {
                    continue;
                }
                $sessions[] = $record;
            }

            if (count($sessions) === 0) {
                throw new moodle_exception('no_course_sessions_found_please_check_the_period_settings', 'local_apsolu');
            }

            $qrcodedbstatus = qrcode::get_course_qrcodes_dbstatus(array_column($sessions, 'id'));

            if ($qrcodedbstatus['isprintable'] !== true) {
                throw new moodle_exception('print_qrcodes_not_available', 'local_apsolu');
            }

            $qrcodesdata = [];
            foreach ($sessions as $session) {
                $qrcode = qrcode::get_record(['sessionid' => $session->id]);
                // Image du QR code et informations sur la session.
                $qrcodesdata[] = qrcode::build_qrcode_image($courseid, $qrcode, $session);
            }
        } else {
            // Affichage ou impression d'un seul QR code.

            // Paramètre rotate : incompatible avec le mode impression -> on affiche le QR code avec un message d'avertissement.
            $rotate = empty($qrcode->settings->rotate) === false;
            if ($rotate === true) {
                $settings = $qrcode->settings;
                $qrcode->keycode = qrcode::generate_keycode();
                $qrcode->save();

                // Restaure les paramètres au format objet.
                $qrcode->settings = $settings;

                if ($print) {
                    $print = !$print;
                    // Message sur le premier QR code uniquement
                    // le paramètre print est retiré dès le rechargement à 30s donc le message disparait.
                    $notification = get_string('rotate_qr_code_noprint_mode', 'local_apsolu');
                }
            }

            // Mode autologout : pour l'affichage uniquement (pas en mode print)
                // effectué uniquement si l'utilisateur est encore loggué (plus le cas après la première rotation en mode rotate).
            if (!$print && empty($qrcode->settings->autologout) === false && $isloggedin === true) {
                // TODO: gérer le warning "mutated the session after it was closed".
                $authsequence = get_enabled_auth_plugins();
                foreach ($authsequence as $authname) {
                    $authplugin = get_auth_plugin($authname);
                    $authplugin->logoutpage_hook();
                }

                require_logout();
                $isloggedin = false;
            }

            // Image du QR code et informations sur la session.
            $qrcodesdata = [qrcode::build_qrcode_image($course->id, $qrcode, $session)];
        }

        // Création des variables pour le template.
        $data = new StdClass();
        $data->print = $print;
        $data->rotate = $rotate;
        $data->display_qrcode = true; // Si false : le template affiche le résultat du scan.
        $data->multiple = isset($courseid);

        // Informations génériques (nom du cours, user, navbar etc).
        $data->wwwroot = $CFG->wwwroot;
        $data->refreshurl = $PAGE->url->out($escape = false);
        $data->color = get_config('theme_apsolu', 'custom_brandcolor');
        $data->sitename = format_string(
            $SITE->shortname,
            true,
            ['context' => context_course::instance(SITEID), "escape" => false]
        );
        $data->course = html_writer::link(new moodle_url('/course/view.php', ['id' => $course->id]), $course->fullname);
        $data->user = $isloggedin === true ? fullname($USER) : get_string('loggedinnot');

        // Image du QR code et informations sur la session.
        $data->qrcodes = $qrcodesdata;
        $PAGE->set_pagelayout('print');

        if (isset($notification)) {
            echo $OUTPUT->notification($notification, 'warning');
        }
        echo $OUTPUT->header();
        echo $OUTPUT->render_from_template('local_apsolu/attendance_qrcode', $data);

        exit(0);
    } else if (isset($keycode) === true) {
        // Affichage du résultat du scan.
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

        $data = new stdClass();
        $data->display_qrcode = false;
        $data->course = $course->fullname;
        $data->username = fullname($USER);
        $data->session = $session->name;
        $data->roles = implode(', ', $roles);

        echo $OUTPUT->render_from_template('local_apsolu/attendance_qrcode', $data);

        try {
            $message = $qrcode->sign($session);

            echo $OUTPUT->notification($message, 'notifysuccess');
        } catch (Exception $exception) {
            echo $OUTPUT->notification($exception->getMessage(), 'notifyproblem');
        }

        echo $OUTPUT->footer();
    }
}
