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

// phpcs:disable moodle.NamingConventions.ValidVariableName.VariableNameUnderscore

use UniversiteRennes2\Apsolu\Payment;
use local_apsolu\core\attendance;
use local_apsolu\core\customfields;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/enrol/select/lib.php');
require_once($CFG->dirroot . '/local/apsolu/classes/apsolu/payment.php');

$sessionid = optional_param('sessionid', 0, PARAM_INT); // Session id.
$invalid_enrolments = optional_param('invalid_enrolments', 0, PARAM_INT);
$inactive_enrolments = optional_param('inactive_enrolments', 0, PARAM_INT);

$sessions = $DB->get_records('apsolu_attendance_sessions', ['courseid' => $courseid], $sort = 'sessiontime');

if (count($sessions) === 0) {
    throw new moodle_exception('no_course_sessions_found_please_check_the_period_settings', 'local_apsolu');
}

// Faire choisir une session.
require_once($CFG->dirroot . '/local/apsolu/attendance/edit/edit_select_form.php');

// Faire choisir une session.
foreach ($sessions as $session) {
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

$session = $DB->get_record('apsolu_attendance_sessions', ['id' => $sessionid, 'courseid' => $courseid], '*', MUST_EXIST);

$customfields = customfields::getCustomFields();

// Récupérer tous les inscrits.
// TODO: jointure avec colleges.
// TODO: retrouver pourquoi on affiche les utilisateurs inscrits manuellement.
$sql = "SELECT u.*, ue.id AS ueid, ue.status, ue.timestart, ue.timeend, ue.enrolid,
               e.enrol, ra.id AS raid, ra.roleid, uid1.data AS apsolusesame" .
    " FROM {user} u" .
    " LEFT JOIN {user_info_data} uid1 ON u.id = uid1.userid AND uid1.fieldid = :apsolusesame" .
    " JOIN {user_enrolments} ue ON u.id = ue.userid" .
    " JOIN {enrol} e ON e.id = ue.enrolid" .
    " JOIN {role_assignments} ra ON u.id = ra.userid AND ((e.id = ra.itemid) OR (e.enrol = 'manual' AND ra.itemid = 0))" .
    " JOIN {role} r ON r.id = ra.roleid" .
    " JOIN {context} ctx ON ra.contextid = ctx.id AND ctx.instanceid = e.courseid" .
    " WHERE e.status = 0" . // Only active enrolments.
    " AND e.courseid = :courseid" .
    " AND ctx.contextlevel = 50" . // Course level.
    " AND r.archetype = 'student'";

$params = [];
$params['apsolusesame'] = $customfields['apsolusesame']->id;

if ($invalid_enrolments) {
    // Récupération de toutes les inscriptions.
    $sql .= " AND ue.status >= 0";
} else {
    // Récupération des inscriptions courantes.
    $sql .= "AND ue.status = 0" .
        " AND (ue.timestart <= :timestart OR ue.timestart = 0)" .
        " AND (ue.timeend >= :timeend OR ue.timeend = 0)";
    $params['timestart'] = $session->sessiontime;
    $params['timeend'] = $session->sessiontime;
}

$sql .= " ORDER BY u.lastname, u.firstname";

$params['courseid'] = $courseid;
$students = $DB->get_records_sql($sql, $params);

// TODO: récupérer les gens inscrits ponctuellement.
$sql = "SELECT DISTINCT u.*, uid1.data AS apsolusesame" .
    " FROM {user} u" .
    " JOIN {apsolu_attendance_presences} aap ON u.id = aap.studentid" .
    " JOIN {apsolu_attendance_sessions} aas ON aas.id = aap.sessionid" .
    " LEFT JOIN {user_info_data} uid1 ON u.id = uid1.userid AND uid1.fieldid = :apsolusesame" .
    " WHERE aas.courseid = :courseid";

$params = [];
$params['apsolusesame'] = $customfields['apsolusesame']->id;
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
uasort($students, function ($a, $b) {
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

$presences = $DB->get_records(
    'apsolu_attendance_presences',
    ['sessionid' => $sessionid],
    $sort = '',
    $fields = 'studentid, statusid, description, id'
);

$roles = role_fix_names($DB->get_records('role'));

$notification = false;
if (isset($_POST['apsolu']) === true) {
    if (isset($_POST['presences']) === false) {
        $_POST['presences'] = [];
    }

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

        $modified[$userid] = $userid;
    }

    // Traite les suppressions de présences.
    foreach ($presences as $userid => $presence) {
        if (isset($modified[$userid]) === true) {
            continue;
        }

        $DB->delete_records('apsolu_attendance_presences', ['id' => $presence->id]);
        unset($presences[$userid]);
    }
}

$global_presences = attendance::getAllCalendarPresences($sessionid);

// Traitement des étudiants à afficher dans le tableau.

$statuses = $DB->get_records('apsolu_attendance_statuses', $conditions = null, $sort = 'sortorder');
$authorizedusers = enrol_select_plugin::get_authorized_registred_users($courseid, $session->sessiontime, $session->sessiontime);
$payments = Payment::get_users_cards_status_per_course($courseid);
$paymentsimages = Payment::get_statuses_images();

$processed_sudents = [];

foreach ($students as $student) {
    $activestart = ($student->timestart == 0 || $student->timestart < time());
    $activeend = ($student->timeend == 0 || $student->timeend > time());
    $enrolment_status = intval($activestart && $activeend);

    if (!$inactive_enrolments && $enrolment_status === 0) {
        // Inactive enrolement ! (inactive enrolments are hidden).
        continue;
    }

    $processed_student = new stdClass();
    $processed_student->id = $student->id;
    $processed_student->enrolid = $student->enrolid;
    $processed_student->status = $student->status;
    $processed_student->roleid = $student->roleid;
    $processed_student->enrol = $student->enrol;
    $processed_student->enrolment_status = $enrolment_status;
    $processed_student->picture = $OUTPUT->user_picture($student, ['size' => 50]);
    $processed_student->firstname = $student->firstname;
    $processed_student->lastname = $student->lastname;
    $processed_student->idnumber = $student->idnumber;
    $processed_student->email = $student->email;

    // Contruction des infos des radio parce que mustache veut ZERO logique.
    $presences_radios = [];
    foreach ($statuses as $status) {
        $radio = new stdClass();
        $radio->label = $status->longlabel;
        $radio->statusid = $status->id;
        $radio->checked = 0;
        if (isset($presences[$student->id]) && $presences[$student->id]->statusid == $status->id) {
            $radio->checked = 1;
        }
        $presences_radios[] = $radio;
    }
    $processed_student->presences_radios = $presences_radios;

    if (isset($presences[$student->id]->description) === false) {
        $presences[$student->id] = new stdClass();
        $presences[$student->id]->description = '';
    }
    $processed_student->comment = $presences[$student->id]->description;

    // Calcul le nombre de présences au cours.
    $processed_student->presence_course = 0;
    $processed_student->presence_activity = 0;
    if (isset($global_presences[$student->id])) {
        $processed_student->presence_course = $global_presences[$student->id]->total_course;
        // On n'affiche le total par activité uniquement si il difféère du total par cours.
        if ($global_presences[$student->id]->total_course != $global_presences[$student->id]->total_activity) {
            $processed_student->presence_activity = $global_presences[$student->id]->total_activity;
        }
    }

    // Type d'inscription.
    if (isset($roles[$student->roleid]) === true) {
        $rolename = $roles[$student->roleid]->name;
    } else {
        $rolename = '-';
    }
    $processed_student->enrolment_type = $rolename;

    // Liste d'inscription.
    $processed_student->enrolment_list = "";
    if ($student->status !== null) {
        $processed_student->enrolment_list = enrol_select_plugin::get_enrolment_list_name($student->status, 'short');
    }

    // Informations.
    $informations = [];
    $processed_student->informations_alert = 0;
    if (isset($student->apsolusesame) === false || $student->apsolusesame !== '1') {
        $informations[] = get_string('attendance_invalid_account', 'local_apsolu');
        $processed_student->informations_alert = 1;
    }

    if (isset($payments[$student->id]) === true) {
        foreach ($payments[$student->id] as $card) {
            $informations[] = $paymentsimages[$card->status]->image . ' ' . $card->fullname;
            if ($card->status === Payment::DUE) {
                $processed_student->informations_alert = 1;
            }
        }
    }

    if (isset($authorizedusers[$student->id]) === false) {
        $informations[] = get_string('attendance_forbidden_enrolment', 'local_apsolu');
        $processed_student->informations_alert = 1;
    }

    $processed_student->informations = $informations;


    $processed_sudents[] = $processed_student;
}

// Construction des paramètres de la page.
$data = new stdClass();
$data->wwwroot = $CFG->wwwroot;
$data->courseid = $courseid;
$data->sessionid = $sessionid;
$data->notification = $notification;
$data->invalid_enrolments = $invalid_enrolments;
$data->inactive_enrolments = $inactive_enrolments;
$data->students = $processed_sudents;
$data->student_count = count($processed_sudents);
$data->calendar = attendance::getCalendarFromSession($sessionid);

if (isset($_POST['exportcsv']) === true || isset($_POST['exportexcel']) === true) {
    $filename = 'extraction de la session ' . $session->name;

    // Définit les entêtes.
    $headers = [];
    $headers[] = get_string('name');
    $headers[] = get_string('date');
    if (empty($data->inactive_enrolments) === false) {
        $headers[] = get_string('attendance_enrolment_state', 'local_apsolu');
    }
    $headers[] = get_string('firstname');
    $headers[] = get_string('lastname');
    $headers[] = get_string('idnumber');
    $headers[] = get_string('email');
    $headers[] = get_string('attendance_presence', 'local_apsolu');
    $headers[] = get_string('attendance_comment', 'local_apsolu');
    $headers[] = sprintf('%s (%s)', strip_tags(get_string('attendance_presences_count', 'local_apsolu')), get_string(
        'course',
        'local_apsolu'
    ));
    $headers[] = sprintf('%s (%s)', strip_tags(get_string('attendance_presences_count', 'local_apsolu')), get_string(
        'activity',
        'local_apsolu'
    ));
    $headers[] = get_string('attendance_enrolment_type', 'local_apsolu');
    if (empty($data->invalid_enrolments) === false) {
        $headers[] = get_string('attendance_enrolment_list', 'local_apsolu');
    }

    // Définit le contenu principal.
    $rows = [];
    foreach ($data->students as $student) {
        $row = [];
        $row[] = $session->name;
        $row[] = userdate($session->sessiontime, get_string('strftimedatetime'));
        if (empty($data->inactive_enrolments) === false) {
            if (empty($student->enrolment_status) === true) {
                $row[] = get_string('inactive');
            } else {
                $row[] = get_string('active');
            }
        }

        $row[] = $student->firstname;
        $row[] = $student->lastname;
        $row[] = $student->idnumber;
        $row[] = $student->email;

        $status = '';
        foreach ($student->presences_radios as $radio) {
            if (empty($radio->checked) === true) {
                continue;
            }
            $status = $radio->label;
            break;
        }
        $row[] = $status;

        $row[] = $student->comment;
        $row[] = $student->presence_course;
        $row[] = $student->presence_activity;
        $row[] = $student->enrolment_type;
        if (empty($data->invalid_enrolments) === false) {
            $row[] = $student->enrolment_list;
        }

        $rows[] = $row;
    }

    if (isset($_POST['exportcsv']) === true) {
        // Exporte les données au format CSV.
        require_once($CFG->libdir . '/csvlib.class.php');

        $csvexport = new csv_export_writer();
        $csvexport->set_filename($filename);

        $csvexport->add_data($headers);

        foreach ($rows as $row) {
            $csvexport->add_data($row);
        }

        $csvexport->download_file();
        exit();
    }

    // Export au format excel.
    require_once($CFG->libdir . '/excellib.class.php');

    $workbook = new MoodleExcelWorkbook("-");
    $workbook->send($filename);
    $myxls = $workbook->add_worksheet();

    if (class_exists('PHPExcel_Style_Border') === true) {
        // Jusqu'à Moodle 3.7.x.
        $properties = ['border' => PHPExcel_Style_Border::BORDER_THIN];
    } else {
        // Depuis Moodle 3.8.x.
        $properties = ['border' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN];
    }

    $excelformat = new MoodleExcelFormat($properties);

    foreach ($headers as $position => $value) {
        $myxls->write_string(0, $position, $value, $excelformat);
    }

    // Définit le contenu principal.
    $line = 1;
    foreach ($rows as $row) {
        foreach ($row as $i => $col) {
            $myxls->write_string($line, $i, $col, $excelformat);
        }

        $line++;
    }

    // MDL-83543: positionne un cookie pour qu'un script js déverrouille le bouton submit après le téléchargement.
    setcookie('moodledownload_' . sesskey(), time());

    // Transmet le fichier au navigateur.
    $workbook->close();
    exit(0);
}

/*** Construction de la page ***/

$title = get_string('attendance', 'local_apsolu');

$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);

// Fil d'ariane.
$PAGE->navbar->add($course->shortname, new moodle_url('/course/view.php', ['id' => $course->id]));
$PAGE->navbar->add($title);

// Dépendances Javascript.
$options = [];
$options['headers'] = [];
for ($i = 0; $i <= 11; $i++) {
    $options['headers'][$i] = ['sorter' => false];
}

$options['widgets'] = ['stickyHeaders'];
$options['widgetOptions'] = ['stickyHeaders_filteredToTop' => true, 'stickyHeaders_offset' => '50px'];

$PAGE->requires->js_call_amd('local_apsolu/sort', 'initialise', [$options]);
$PAGE->requires->js_call_amd('local_apsolu/attendance', 'initialise');

// Onglets.
$tabsbar = [];

$url = new moodle_url('/local/apsolu/attendance/edit.php', ['courseid' => $courseid]);
$tabsbar[] = new tabobject('sessions', $url, get_string('attendance_sessionsview', 'local_apsolu'));

$url = new moodle_url('/local/apsolu/attendance/overview.php', ['courseid' => $courseid]);
$tabsbar[] = new tabobject('overview', $url, get_string('attendance_overview', 'local_apsolu'));

$url = new moodle_url('/local/apsolu/attendance/sessions/index.php', ['courseid' => $courseid]);
$tabsbar[] = new tabobject('sessions_edit', $url, get_string('attendance_sessions_edit', 'local_apsolu'));

$url = new moodle_url('/local/apsolu/attendance/export/export.php', ['courseid' => $courseid]);
$tabsbar[] = new tabobject('export', $url, get_string('export', 'local_apsolu'));

// Select form pour la session et les options.
$select_args = [
    'courseid' => $course->id,
    'sessionid' => $sessionid,
    'sessions' => $sessions,
    'invalid_enrolments' => $invalid_enrolments,
    'inactive_enrolments' => $inactive_enrolments,
];

$url = new moodle_url('/local/apsolu/attendance/index.php', ['page' => 'edit', 'courseid' => $courseid]);
$selectform = new edit_select_form($url, $select_args);

// Ecriture finale.
echo $OUTPUT->header();
echo $OUTPUT->tabtree($tabsbar, 'sessions');
echo $OUTPUT->heading($title);

$selectform->display();

echo $OUTPUT->render_from_template('local_apsolu/attendance_edit', $data);

echo $OUTPUT->footer();
