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
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../../config.php');
require_once($CFG->dirroot.'/local/apsolu/grades/grades_courses_form.php');

$courseid = optional_param('courseid', null, PARAM_INT);

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/local/apsolu/grades/index.php');
$PAGE->set_title(get_string('mygradedstudents', 'local_apsolu'));

// Navigation.
$PAGE->navbar->add(get_string('mygradedstudents', 'local_apsolu'));

$PAGE->requires->js_call_amd('local_apsolu/grades', 'initialise');

require_login();

// Teachers.
$sql = "SELECT DISTINCT c.*".
    " FROM {course} c".
    " JOIN {course_categories} cc ON cc.id = c.category".
    " JOIN {enrol} e ON c.id = e.courseid".
    " JOIN {enrol_select_roles} esr ON e.id = esr.enrolid AND esr.roleid IN (9, 10)".
    " JOIN {apsolu_courses} ac ON ac.id = c.id".
    " JOIN {context} ctx ON c.id = ctx.instanceid AND ctx.contextlevel = 50".
    " JOIN {role_assignments} ra ON ctx.id = ra.contextid AND ra.roleid = 3".
    " WHERE ra.userid = ?".
    " AND e.enrol = 'select'".
    " AND e.status = 0".
    " ORDER BY cc.name, ac.numweekday, ac.starttime";
$records = $DB->get_records_sql($sql, array($USER->id));

if (count($records) === 0) {
    print_error('accessdenied', 'local_apsolu');
}

$courses = array('' => get_string('choosedots'));
foreach ($records as $record) {
    $courses[$record->id] = $record->fullname;
}

// Build form.
$customdata = array($courses);
$coursesmform = new local_apsolu_grades_form(null, $customdata);

if ($data = $coursesmform->get_data()) {
    if (isset($data->courseid, $courses[$data->courseid]) && ctype_digit($data->courseid)) {
        $courseid = $data->courseid;
    }
} else if (isset($courseid)) {
    $coursesmform->set_data(array('courseid' => $courseid));
}

$data = new stdClass();
$data->periods = array(new stdClass(), new stdClass());
$data->courseid = $courseid;
$data->count_overall_users = 0;

if ($courseid) {
    // Un cours a été sélectionné.
    $time = time();
    $current_semester_index = 1;
    if ($time > get_config('local_apsolu', 'semester1_grading_deadline')) {
        // La saisie pour le S1 est terminée.
        $data->periods[0]->timestart = get_config('local_apsolu', 'semester1_enrol_startdate');
        $data->periods[0]->timeend = get_config('local_apsolu', 'semester1_enddate');

        $data->periods[1]->timestart = get_config('local_apsolu', 'semester1_reenrol_startdate');
        $data->periods[1]->timeend = get_config('local_apsolu', 'semester2_enddate');

        if ($time > get_config('local_apsolu', 'semester2_grading_deadline')) {
            // La saisie pour le S2 est terminée.
            $current_semester_index = -1;
        } else {
            // Dans l'onglet S1, on veut les inscrits du S1.
            // Dans l'onglet S2, on peut saisir pour le S2.
            $current_semester_index = 1;
        }
    } else {
        // Dans l'onglet S1, on peut saisir pour le S1.
        $data->periods[0]->timestart = get_config('local_apsolu', 'semester1_enrol_startdate');
        $data->periods[0]->timeend = get_config('local_apsolu', 'semester1_enddate');

        // Dans l'onglet S2, on peut saisir pour le S2.
        $data->periods[1]->timestart = get_config('local_apsolu', 'semester1_enrol_startdate');
        $data->periods[1]->timeend = get_config('local_apsolu', 'semester1_enddate');

        $current_semester_index = 0;
    }

    // All users.
    $sql = "SELECT u.*, ra.roleid, r.name AS rolename, uid1.data AS ufr, uid2.data AS lmd, ag.grade1, ag.grade2, ag.grade3, ag.grade4, ue.timestart, ue.timeend".
        " FROM {user} u".
        " LEFT JOIN {user_info_data} uid1 ON u.id = uid1.userid AND uid1.fieldid = 4". // UFR.
        " LEFT JOIN {user_info_data} uid2 ON u.id = uid2.userid AND uid2.fieldid = 5". // LMD.
        " JOIN {user_enrolments} ue ON u.id = ue.userid AND ue.status = 0".
        " JOIN {enrol} e ON e.id = ue.enrolid AND e.enrol = 'select' AND e.status = 0".
        " JOIN {course} c ON c.id = e.courseid AND c.id = :courseid".
        " JOIN {apsolu_courses} ac ON ac.id = c.id".
        " JOIN {context} ctx ON c.id = ctx.instanceid AND ctx.contextlevel = 50".
        " JOIN {role_assignments} ra ON ctx.id = ra.contextid AND ra.itemid = e.id AND ra.userid = u.id AND ra.roleid IN (9, 10)".
        " JOIN {role} r ON r.id = ra.roleid".
        " LEFT JOIN {apsolu_grades} ag ON u.id = ag.userid AND c.id = ag.courseid".
        " WHERE u.deleted = 0".
        " ORDER BY u.lastname, u.firstname, u.institution, u.department";
    $conditions = array('courseid' => $courseid);

    // Old $users = $DB->get_recordset_sql($sql, $conditions);.

    // Traitement du formulaire.
    $data->count_saved_students = 0;
    if (isset($_POST['grading'])) {
        foreach ($DB->get_recordset_sql($sql, $conditions) as $user) {
            $needupdate = false;

            $currentgrades = array();
            $newgrades = array();

            for ($i = 1; $i < 5; $i++) {
                $grade = 'grade'.$i;

                if (isset($user->{$grade})) {
                    $currentgrades[$i] = $user->{$grade};
                } else {
                    $currentgrades[$i] = '';
                }

                if ($current_semester_index === 0 || $current_semester_index === 1 && $i > 2) {
                    // Au semestre 1, on peut modifier toutes les notes.
                    // Au semestre 2, on ne peut modifier que les notes du S2.

                    $newgrades[$i] = '';
                    if (isset($_POST['abi'][$user->id][$grade])) {
                        $newgrades[$i] = 'abi';
                    } else if (isset($_POST['abj'][$user->id][$grade])) {
                        $newgrades[$i] = 'abj';
                    } else if (isset($_POST['grade'][$user->id][$grade])) {
                        if (in_array($_POST['grade'][$user->id][$grade], array('', 'abi', 'abj'))) {
                            // Suppression de la note.
                            $newgrades[$i] = '';
                        } else {
                            $newgrades[$i] = str_replace(',', '.', $_POST['grade'][$user->id][$grade]);
                            $newgrades[$i] = preg_replace('/[^0-9\.]*/', '', $newgrades[$i]);
                            if (!is_numeric($newgrades[$i]) || $newgrades[$i] < 0 || $newgrades[$i] > 20) {
                                // Mauvaise note !
                                $newgrades[$i] = $currentgrades[$i];
                            }
                        }
                    }
                } else {
                    $newgrades[$i] = $currentgrades[$i];
                }
            }

            if ($currentgrades !== $newgrades) {
                $grade = $DB->get_record('apsolu_grades', array('userid' => $user->id, 'courseid' => $courseid));
                if ($grade) {
                    // La note existe.

                    $history = new stdClass();
                    $history->gradeid = $grade->id;
                    $history->teacherid = $grade->teacherid;
                    $history->grade1 = $grade->grade1;
                    $history->grade2 = $grade->grade2;
                    $history->grade3 = $grade->grade3;
                    $history->grade4 = $grade->grade4;
                    $history->timemodified = $grade->timemodified;

                    if ($DB->insert_record('apsolu_grades_history', $history)) {
                        $grade->teacherid = $USER->id;
                        $grade->grade1 = $newgrades[1];
                        $grade->grade2 = $newgrades[2];
                        $grade->grade3 = $newgrades[3];
                        $grade->grade4 = $newgrades[4];
                        $grade->timemodified = time();

                        if ($DB->update_record('apsolu_grades', $grade)) {
                            $user->grade1 = $newgrades[1];
                            $user->grade2 = $newgrades[2];
                            $user->grade3 = $newgrades[3];
                            $user->grade4 = $newgrades[4];

                            $data->count_saved_students++;
                        }
                    }
                } else {
                    // La note n'existe pas.
                    $grade = new stdClass();
                    $grade->courseid = $courseid;
                    $grade->userid = $user->id;
                    $grade->teacherid = $USER->id;
                    $grade->grade1 = $newgrades[1];
                    $grade->grade2 = $newgrades[2];
                    $grade->grade3 = $newgrades[3];
                    $grade->grade4 = $newgrades[4];
                    $grade->timecreated = $grade->timemodified = time();
                    if ($DB->insert_record('apsolu_grades', $grade)) {
                        $user->grade1 = $newgrades[1];
                        $user->grade2 = $newgrades[2];
                        $user->grade3 = $newgrades[3];
                        $user->grade4 = $newgrades[4];

                        $data->count_saved_students++;
                    }
                }
            }
        }
    }

    foreach ($data->periods as $index => $period) {
        if ($index === 0) {
            $period->label = get_string('firstsemester', 'local_apsolu');
            $period->shortname = 'semester1';
        } else {
            $period->label = get_string('secondsemester', 'local_apsolu');
            $period->shortname = 'semester2';

        }

        if ($current_semester_index === $index) {
            $period->active = 'active';
        } else {
            $period->active = '';
        }

        // Lorsqu'on ne peut plus saisir de notes, on place l'onglet par défaut sur semestre 2.
        if ($index === 1 && $current_semester_index === -1) {
           $period->active = 'active';
        }

        $period->users = array();
        $period->count_users = 0;
        $period->require_grades = 0;

        $period->action = $CFG->wwwroot.'/local/apsolu/grades/index.php';

        foreach ($DB->get_recordset_sql($sql, $conditions) as $user) {
            if (($user->timestart > 0 && $user->timestart < $period->timestart) || ($user->timeend > 0 && $user->timeend > $period->timeend)) {
                continue;
            }

            for ($i = 1; $i < 5; $i++) {
                ${'grade'.$i.'str'} = get_string('practicegrade', 'local_apsolu');
                ${'grade'.$i.'attr'} = 'readonly="1" ';
            }

            $grade2str = $grade4str = get_string('theorygrade', 'local_apsolu');

            if ($current_semester_index === 0) {
                // Semestre 1.
                if ($user->roleid == 9) {
                    // Évalué option.

                    // TODO: rendre plus flexible.
                    if ($user->lmd === 'L1' && strstr($user->email, '@') === '@etudiant.univ-rennes2.fr') {
                        // UEF bis Rennes 2.
                        $grade1attr = '';
                        $grade2str = '';
                        $grade3attr = '';
                        $grade4str = '';
                    } else {
                        $grade1attr = '';
                        $grade2attr = '';
                        $grade3attr = '';
                        $grade4attr = '';
                    }
                } else if ($user->roleid == 10) {
                    // Évalué bonification.
                    $grade1attr = '';
                    $grade2str = '';
                    $grade3attr = '';
                    $grade4str = '';
                }
            } elseif ($current_semester_index === 1) {
                // Semestre 2.
                if ($user->roleid == 9) {
                    // Évalué option.
                    $grade3attr = '';
                    $grade4attr = '';
                } else {
                    // Évalué bonification.
                    $grade3attr = '';
                    $grade4str = '';
                }
            }

            $user->grades = array();
            for ($i = (($index * 2) + 1); $i <= (($index + 1) * 2); $i++) {

                $grade = 'grade'.$i;
                $gradeattr = ${'grade'.$i.'attr'};
                $placeholder = ${'grade'.$i.'str'};

                $abi = ($user->{$grade} == 'abi') ? 'checked="1" ' : '';
                $abj = ($user->{$grade} == 'abj') ? 'checked="1" ' : '';

                if ($gradeattr !== '') {
                    $classattr = ' class="grade-disabled"';
                } else {
                    $classattr = '';

                    if ($current_semester_index === $index) {
                        if (empty($user->{$grade})) {
                            $period->require_grades++;
                        }
                    }
                }

                $user->grades[] = ''.
                    '<td'.$classattr.'><input type="checkbox" name="abi['.$user->id.']['.$grade.']" value="1" '.$abi.$gradeattr.'/></td>'.
                    '<td'.$classattr.'><input type="checkbox" name="abj['.$user->id.']['.$grade.']" value="1" '.$abj.$gradeattr.'/></td>'.
                    '<td'.$classattr.'><input type="text" pattern="[0-9.,]*|abi|abj" name="grade['.$user->id.']['.$grade.']" placeholder="'.$placeholder.'" value="'.$user->{$grade}.'" '.$gradeattr.'/></td>';
            }

            $user->htmlpicture = $OUTPUT->user_picture($user, array('courseid' => $courseid));

            $period->users[] = clone $user;
            $period->count_users++;
            $data->count_overall_users++;
        }
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('mystudents', 'local_apsolu'));
$coursesmform->display();
if ($courseid) {
    echo $OUTPUT->render_from_template('local_apsolu/grades', $data);
}
echo $OUTPUT->footer();
