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

use UniversiteRennes2\Apsolu\Payment;
use local_apsolu\core\attendance;
use local_apsolu\core\customfields;

require_once(__DIR__.'/../../../config.php');
require_once($CFG->dirroot.'/local/apsolu/classes/apsolu/payment.php');

$courseid = optional_param('courseid', 0, PARAM_INT); // Course id.
$sessionid = optional_param('sessionid', 0, PARAM_INT); // Session id.
$invalid_enrolments = optional_param('invalid_enrolments', null, PARAM_INT);
$inactive_enrolments = optional_param('inactive_enrolments', null, PARAM_INT);

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
    throw new moodle_exception('taking_attendance_is_only_possible_on_a_course', 'local_apsolu');
}

$streditcoursesettings = get_string('attendance', 'local_apsolu');

$PAGE->navbar->add($course->shortname, new moodle_url('/course/view.php', ['id' => $course->id]));
$PAGE->navbar->add($streditcoursesettings);

$pagedesc = $streditcoursesettings;
$title = $streditcoursesettings;
$fullname = $course->fullname;

$PAGE->set_title($title);
$PAGE->set_heading($fullname);

// Call javascript.
$options = [];
$options['headers'] = [];
for ($i = 0; $i <= 11; $i++) {
    $options['headers'][$i] = ['sorter' => false];
}
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

echo $OUTPUT->header();
echo $OUTPUT->tabtree($tabsbar, 'sessions');
echo $OUTPUT->heading($pagedesc);

$sessions = $DB->get_records('apsolu_attendance_sessions', ['courseid' => $courseid], $sort = 'sessiontime');
$count_sessions = count($sessions);

if ($count_sessions === 0) {
    throw new moodle_exception('no_course_sessions_found_please_check_the_period_settings', 'local_apsolu');
}

// Faire choisir une session.
require_once($CFG->dirroot.'/local/apsolu/attendance/edit_select_form.php');

$sessions_select = [];
foreach ($sessions as $session) {
    $sessions_select[$session->id] = $session->name;

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

// First create select form.
$args = [
    'courseid' => $course->id,
    'sessionid' => $sessionid,
    'sessions' => $sessions,
];

if (isset($invalid_enrolments) === true) {
    $lists_style = '';
    $args['invalid_enrolments'] = 1;
} else {
    $lists_style = 'display: none;';
}

if (isset($inactive_enrolments) === true) {
    $args['inactive_enrolments'] = 1;
}

// TODO: à revoir...
$url = new moodle_url('/local/apsolu/attendance/edit.php', ['courseid' => $courseid]);
$selectform = new edit_select_form($url, $args);
$selectform->display();

$session = $DB->get_record('apsolu_attendance_sessions', ['id' => $sessionid, 'courseid' => $courseid]);

if ($session === false) {
    // TODO: créer un message.
    throw new moodle_exception('needcoursecategroyid');
}

$customfields = customfields::getCustomFields();

// Récupérer tous les inscrits.
// TODO: jointure avec colleges
$sql = "SELECT u.*, ue.id AS ueid, ue.status, ue.timestart, ue.timeend, ue.enrolid, e.enrol, ra.id AS raid, ra.roleid, uid1.data AS apsolusesame".
    " FROM {user} u".
    " LEFT JOIN {user_info_data} uid1 ON u.id = uid1.userid AND uid1.fieldid = :apsolusesame".
    " JOIN {user_enrolments} ue ON u.id = ue.userid".
    " JOIN {enrol} e ON e.id = ue.enrolid".
    " JOIN {role_assignments} ra ON u.id = ra.userid AND ((e.id = ra.itemid) OR (e.enrol = 'manual' AND ra.itemid = 0))".
    " JOIN {role} r ON r.id = ra.roleid".
    " JOIN {context} ctx ON ra.contextid = ctx.id AND ctx.instanceid = e.courseid".
    " WHERE e.status = 0". // Only active enrolments.
    " AND e.courseid = :courseid".
    " AND ctx.contextlevel = 50". // Course level.
    " AND r.archetype = 'student'";

$params = [];
$params['apsolusesame'] = $customfields['apsolusesame']->id;

if (isset($invalid_enrolments) === false) {
    // Récupération des inscriptions courantes.
    $sql .= "AND ue.status = 0".
        " AND (ue.timestart <= :timestart OR ue.timestart = 0)".
        " AND (ue.timeend >= :timeend OR ue.timeend = 0)";
    $params['timestart'] = $session->sessiontime;
    $params['timeend'] = $session->sessiontime;
} else {
    // Récupération de toutes les inscriptions.
    $sql .= " AND ue.status >= 0";
}

$sql .= " ORDER BY u.lastname, u.firstname";

$params['courseid'] = $courseid;
$students = $DB->get_records_sql($sql, $params);

// TODO: récupérer les gens inscrits ponctuellement.
$sql = "SELECT DISTINCT u.*, uid1.data AS apsolusesame".
    " FROM {user} u".
    " JOIN {apsolu_attendance_presences} aap ON u.id = aap.studentid".
    " JOIN {apsolu_attendance_sessions} aas ON aas.id = aap.sessionid".
    " LEFT JOIN {user_info_data} uid1 ON u.id = uid1.userid AND uid1.fieldid = :apsolusesame".
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
uasort($students, function($a, $b) {
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

// TODO: rendre moins spécifique à Brest.
if ($CFG->wwwroot === 'https://espace-suaps.univ-brest.fr' || empty($CFG->debugdisplay) === false) {
    // Détermine si il s'agit d'un cours appartenant au groupement d'activités APPN.
    $sql = "SELECT c.id
              FROM {course} c
              JOIN {apsolu_courses} ac ON c.id = ac.id
              JOIN {course_categories} cc1 ON cc1.id = c.category
              JOIN {course_categories} cc2 ON cc2.id = cc1.parent
              JOIN {apsolu_courses_groupings} acg ON cc2.id = acg.id
             WHERE cc2.name LIKE 'APPN%'
               AND c.id = :courseid";
    if ($DB->get_record_sql($sql, ['courseid' => $courseid]) !== false) {
        // Détermine si un dépôt de devoirs existe.
        $sql = "SELECT cm.instance
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module
                 WHERE m.name = 'assign'
                   AND cm.course = :courseid";
        $cm = $DB->get_record_sql($sql, ['courseid' => $courseid]);
        if ($cm !== false) {
            $sql = "SELECT userid, grade
                      FROM {assign_grades}
                     WHERE assignment = :assignment
                       AND grade > 0";
            $appnvalidations = $DB->get_records_sql($sql, ['assignment' => $cm->instance]);
        }
    }
}

// Attendance form.
$args = [
    'courseid' => $course->id,
    'sessionid' => $sessionid,
    'students' => $students,
];

// TODO: à revoir...
$url = new moodle_url('/local/apsolu/attendance/edit.php', ['courseid' => $courseid]);
/*
$mform = new edit_form($url, $args);
$mform->display();
*/

$course_presences = attendance::getCoursePresences($courseid);
$activity_presences = attendance::getActivityPresences($course->category);

$presences = $DB->get_records('apsolu_attendance_presences', ['sessionid' => $sessionid], $sort = '', $fields = 'studentid, statusid, description, id');

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

echo '<h3>'.$title.' : '.$session->name.'</h3>';

if ($notification === true) {
    echo $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');
}

// Construit le tableau HTML.
$table = new html_table();
$table->id = 'apsolu-attendance-table';
$table->attributes = ['class' => 'table table-sortable table-striped'];

// Définit les entêtes du tableau.
$table->head = [];
if (isset($inactive_enrolments) === true) {
    $table->head[] = get_string('attendance_enrolment_state', 'local_apsolu');
}
$table->head[] = get_string('pictureofuser');
$table->head[] = get_string('lastname');
$table->head[] = get_string('firstname');
$table->head[] = get_string('attendance_presence', 'local_apsolu');
$table->head[] = get_string('attendance_comment', 'local_apsolu');
$table->head[] = get_string('attendance_course_presences_count', 'local_apsolu');
$table->head[] = get_string('attendance_activity_presences_count', 'local_apsolu');
$table->head[] = get_string('attendance_enrolment_type', 'local_apsolu');
$cell = new html_table_cell();
$cell->text = get_string('attendance_enrolment_list', 'local_apsolu');
$cell->style = $lists_style;
$table->head[] = $cell;
$table->head[] = get_string('attendance_complement', 'local_apsolu');
$table->head[] = get_string('attendance_enrolments_management', 'local_apsolu');

// Initialise les données du tableau.
$table->data = [];

$statuses = $DB->get_records('apsolu_attendance_statuses', $conditions = null, $sort = 'sortorder');

$authorizedUsers = enrol_select_plugin::get_authorized_registred_users($courseid, $session->sessiontime, $session->sessiontime);

$payments = Payment::get_users_cards_status_per_course($courseid);
$paymentsimages = Payment::get_statuses_images();

foreach ($students as $student) {
    $activestart = ($student->timestart == 0 || $student->timestart < time());
    $activeend = ($student->timeend == 0 || $student->timeend > time());
    $enrolment_status = intval($activestart && $activeend);

    if (isset($inactive_enrolments) === false && $enrolment_status === 0) {
        // Inactive enrolement !
        continue;
    }

    if ($enrolment_status === 1) {
        $enrolment_status = get_string('active');
        $enrolment_status_style = 'text-center';
    } else {
        $enrolment_status = get_string('inactive');
        $enrolment_status_style = 'text-center table-warning';
    }

    $picture = new user_picture($student);
    $picture->size = 50;

    $radios = '';
    $found = false;
    foreach ($statuses as $status) {
        $checked = '';
        if (isset($presences[$student->id]) && $presences[$student->id]->statusid === $status->id) {
            $found = true;
            $checked = 'checked="checked" ';
        }
        $radios .= '<label><input type="radio" name="presences['.$student->id.']" value="'.$status->id.'" '.$checked.'/> '.$status->longlabel.'</label>';
    }

    $status_style = '';
    if ($found === false) {
        // $status_style = ' class="table-warning"';
    }

    if (isset($presences[$student->id]->description) === false) {
        $presences[$student->id] = new stdClass();
        $presences[$student->id]->description = '';
    }

    // Calcul le nombre de présences au cours.
    if (isset($course_presences[$student->id]) === false) {
        $presence = new stdClass();
        $presence->name = get_string('attendances', 'local_apsolu');
        $presence->total = 0;

        $course_presences[$student->id] = [$presence];
    }

    $coursepresences[$student->id] = [];
    foreach ($course_presences[$student->id] as $presence) {
        $coursepresences[$student->id][] = get_string('attendances_total', 'local_apsolu', $presence);
    }

    // Calcul le nombre de présences à l'activité.
    if (isset($activity_presences[$student->id]) === false) {
        $presence = new stdClass();
        $presence->name = get_string('attendances', 'local_apsolu');
        $presence->total = 0;

        $activity_presences[$student->id] = [$presence];
    }

    $activitypresences[$student->id] = [];
    foreach ($activity_presences[$student->id] as $presence) {
        $activitypresences[$student->id][] = get_string('attendances_total', 'local_apsolu', $presence);
    }

    // Information.
    $informations = [];

    $informations_style = 'none';
    if (isset($student->apsolusesame) === false || $student->apsolusesame !== '1') {
        $informations[] = get_string('attendance_invalid_account', 'local_apsolu');
        $informations_style = 'table-danger';
    }

    if (isset($payments[$student->id]) === true) {
        foreach ($payments[$student->id] as $card) {
            $informations[] = $paymentsimages[$card->status]->image.' '.$card->fullname;
            if ($card->status === Payment::DUE) {
                $informations_style = 'table-danger';
            }
        }
    }

    if (isset($authorizedUsers[$student->id]) === false) {
        $informations[] = get_string('attendance_forbidden_enrolment', 'local_apsolu');
        $informations_style = 'table-danger';
    }

    // TODO: à supprimer.
    if (isset($appnvalidations) === true) {
        // Affiche l'état de la validation du certificat pour les APPN de Brest.
        if (isset($appnvalidations[$student->id]) === true) {
            $informations[] = $paymentsimages[Payment::PAID]->image.' Attestation savoir nager';
        } else {
            $informations[] = $paymentsimages[Payment::DUE]->image.' Attestation savoir nager';
            $informations_style = 'table-danger';
        }
    }

    if (isset($roles[$student->roleid]) === true) {
        $rolename = $roles[$student->roleid]->name;
    } else {
        $rolename = '-';
    }

    if (isset($student->enrolid) === true) {
        $enrolment_link = '<a class="btn btn-default btn-secondary apsolu-attendance-edit-enrolments" data-userid="'.$student->id.'" data-courseid="'.$courseid.'" data-enrolid="'.$student->enrolid.'" data-statusid="'.$student->status.'" data-roleid="'.$student->roleid.'" href="'.$CFG->wwwroot.'/enrol/'.$student->enrol.'/manage.php?enrolid='.$student->enrolid.'">'.get_string('attendance_edit_enrolment', 'local_apsolu').'</a>';
    } else {
        $enrolment_link = get_string('attendance_ontime_enrolment', 'local_apsolu');
    }

    $cols = [];
    if (isset($inactive_enrolments) === true) {
        $cell = new html_table_cell();
        $cell->text = $enrolment_status;
        $cell->attributes = ['class' => $enrolment_status_style];
        $cols[] = $cell;
    }
    $cols[] = $OUTPUT->render($picture);
    $cols[] = $student->lastname;
    $cols[] = $student->firstname;
    $cell = new html_table_cell();
    $cell->text = $radios;
    if (empty($status_style) === false) {
        $cell->style = 'table-warning';
    }
    $cols[] = $cell;
    $cols[] = '<textarea name="comment['.$student->id.']">'.htmlentities($presences[$student->id]->description, ENT_COMPAT, 'UTF-8').'</textarea>';
    $cols[] = '<ul><li>'.implode('</li><li>', $coursepresences[$student->id]).'</li></ul>';
    $cols[] = '<ul><li>'.implode('</li><li>', $activitypresences[$student->id]).'</li></ul>';
    $cell = new html_table_cell();
    $cell->text = $rolename;
    $cell->attributes = ['class' => 'apsolu-attendance-role', 'data-userid' => $student->id];
    $cols[] = $cell;

    if ($student->status === null) {
        $cell  = new html_table_cell();
        $cell->text = '-';
        $cell->style = $lists_style;
        $cols[] = $cell;
    } else {
        $cell  = new html_table_cell();
        $cell->text = enrol_select_plugin::get_enrolment_list_name($student->status, 'short');
        $cell->attributes = ['class' => 'apsolu-attendance-status', 'data-userid' => $student->id];
        $cell->style = $lists_style;
        $cols[] = $cell;
    }

    $cell  = new html_table_cell();
    $cell->text = implode('<br />', $informations);
    $cell->attributes = ['class' => $informations_style];
    $cols[] = $cell;
    $cols[] = $enrolment_link;

    $table->data[] = $cols;
}

$table->caption = get_string('attendance_table_caption', 'local_apsolu', (object) ['count_students' => count($table->data)]);

echo '<form method="post" action="'.$CFG->wwwroot.'/local/apsolu/attendance/edit.php?courseid='.$courseid.'&amp;sessionid='.$sessionid.'" />';
echo html_writer::table($table);
echo '<p class="text-right">'.
    '<input class="btn btn-primary" type="submit" name="apsolu" value="'.get_string('savechanges').'" />';

if (isset($invalid_enrolments) === true) {
    echo '<input type="hidden" name="invalid_enrolments" value="1" />';
}

if (isset($inactive_enrolments) === true) {
    echo '<input type="hidden" name="inactive_enrolments" value="1" />';
}

echo '</p>';
echo '</form>';

echo $OUTPUT->footer();
