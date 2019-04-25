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

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot.'/local/apsolu/grades/export_form.php');
require_once($CFG->libdir.'/excellib.class.php');

// Load courses.
$courses = array('*' => get_string('all'));

$sql = "SELECT c.id, c.fullname".
    " FROM {course} c".
    " JOIN {apsolu_courses} ac ON ac.id = c.id".
    " ORDER BY c.fullname";
$records = $DB->get_records_sql($sql);

if (count($records) === 0) {
    print_error('usernotavailable');
}

foreach ($records as $record) {
    $courses[$record->id] = $record->fullname;
}

// Load sites.
$cities = array();
foreach ($DB->get_records('apsolu_cities', null, $sort = 'name') as $city) {
    $cities[$city->id] = $city->name;
}

// Load institutions.
$sql = "SELECT DISTINCT institution FROM {user} WHERE id > 2 AND deleted = 0 AND auth = 'shibboleth' ORDER BY institution";
$institutions = array();
foreach ($DB->get_records_sql($sql) as $record) {
    if (!empty($record->institution)) {
        $institutions[$record->institution] = $record->institution;
    }
}

// Departments list.
$departmentslist = array();
foreach ($DB->get_records_sql('SELECT DISTINCT department FROM {user} ORDER BY department') as $record) {
    if (empty($record->department) === true) {
        continue;
    }
    $departmentslist[] = $record->department;
}

// Load roles.
$roles = array();
foreach ($DB->get_records('role', array('archetype' => 'student')) as $role) {
    if (!empty($role->name) && $role->name !== 'Libre' && $role->name !== 'Découverte') {
        $roles[$role->id] = $role->name;
    }
}

// Load semesters.
$semesters = array(
    '1' => get_string('semester1', 'local_apsolu'),
    '2' => get_string('semester2', 'local_apsolu'),
);

if (date('m') > 8) {
    $year = date('y');
} else {
    $year = date('y')-1;
}

$timestart_semester1 = mktime(0, 0, 0, 8, 1, $year);
$timeend_semester1 = mktime(0, 0, 0, 1, 1, $year+1);
$timestart_semester2 = mktime(0, 0, 0, 1, 1, $year+1);
$timeend_semester2 = mktime(0, 0, 0, 7, 1, $year+1);

// Build form.
$defaults = (object) ['courses' => '*', 'cities' => '', 'departments' => '', 'roles' => '*', 'semesters' => '*'];
$customdata = array($defaults, $courses, $cities, $institutions, $roles, $semesters);
$mform = new local_apsolu_courses_grades_export_form(null, $customdata);

if ($data = $mform->get_data()) {
    // Save data.
    $conditions = array();

    $sql = "SELECT u.*, r.name AS rolename, ue.status AS listid, c.id AS courseid, c.shortname AS course, ag.grade1, ag.grade2, ag.grade3, ag.grade4, info.data AS ufr".
        " FROM {user} u".
        " LEFT JOIN {user_info_data} info ON u.id = info.userid AND info.fieldid = 4". // UFR = 4.
        " JOIN {user_enrolments} ue ON u.id = ue.userid".
        " JOIN {enrol} e ON e.id = ue.enrolid AND e.enrol = 'select' AND e.status = 0".
        " JOIN {course} c ON c.id = e.courseid".
        " JOIN {apsolu_courses} ac ON ac.id = c.id".
        " JOIN {apsolu_locations} al ON al.id = ac.locationid".
        " JOIN {apsolu_areas} aa ON aa.id = al.areaid".
        " LEFT JOIN {apsolu_grades} ag ON ac.id = ag.courseid AND u.id = ag.userid".
        " JOIN {context} ctx ON c.id = ctx.instanceid AND ctx.contextlevel = 50".
        " JOIN {role_assignments} ra1 ON ctx.id = ra1.contextid AND ra1.userid = u.id AND ra1.itemid = e.id".
        " JOIN {role} r ON r.id = ra1.roleid";
    $where = array('u.deleted = 0');

    // Lastnames filter.
    if (isset($data->lastnames)) {
        $lastnames = array();
        foreach (explode(',', $data->lastnames) as $i => $lastname) {
            if (empty($lastname)) {
                continue;
            }
            $lastnames[] = 'u.lastname LIKE :lastname'.$i;
            $conditions['lastname'.$i] = '%'.trim($lastname).'%';
        }

        if (isset($lastnames[0])) {
            $where[] = '( '.implode(' OR ', $lastnames).' )';
        }
    }

    // Courses filter.
    if (isset($data->courses[0]) && $data->courses[0] !== '*') {
        $courses = array();
        foreach ($data->courses as $course) {
            if (ctype_digit($course)) {
                $courses[] = $course;
            }
        }

        if (isset($courses[0])) {
            $where[] = "c.id IN (".implode(', ', $courses).")";
        }
    }

    // Sites filter.
    if (isset($data->cities) === true) {
        $cities = array();
        foreach ($data->cities as $city) {
            if (ctype_digit($city) === true) {
                $cities[] = $city;
            }
        }

        if (isset($cities[0]) === true) {
            $where[] = "aa.cityid IN (".implode(', ', $cities).")";
        }
    }

    // Institutions filter.
    if (isset($data->institutions)) {
        $institutions = array();
        foreach ($data->institutions as $i => $institution) {
            $institutions[] = ':institution'.$i;
            $conditions['institution'.$i] = $institution;
        }
        $where[] = "u.institution IN (".implode(', ', $institutions).")";
    }

    // UFR filter.
    if (isset($data->ufrs)) {
        $ufrs = array();
        foreach (explode(',', $data->ufrs) as $i => $ufr) {
            if (empty($ufr)) {
                continue;
            }
            $ufrs[] = 'info.data LIKE :ufr'.$i;
            $conditions['ufr'.$i] = '%'.trim($ufr).'%';
        }

        if (isset($ufrs[0])) {
            $where[] = '( '.implode(' OR ', $ufrs).' )';
        }
    }

    // Departments filter.
    if (isset($data->departments)) {
        $departments = array();
        foreach (explode(',', $data->departments) as $i => $department) {
            if (empty($department)) {
                continue;
            }
            $departments[] = 'u.department LIKE :department'.$i;
            $conditions['department'.$i] = '%'.trim($department).'%';
        }

        if (isset($departments[0])) {
            $where[] = '( '.implode(' OR ', $departments).' )';
        }
    }

    // Roles filter.
    if (isset($data->roles)) {
        $where[] = "ra1.roleid = :roleid";
        $conditions['roleid'] = $data->roles;
    }

    // Semesters filter.
    if (isset($data->semesters)) {
        switch($data->semesters) {
            case '2':
                // TODO: ajouter une option pour afficher/ne pas afficher les notes attendues.
                // $where[] = '((ag.grade3 IS NOT NULL AND ag.grade3 != "") OR (ag.grade4 IS NOT NULL AND ag.grade4 != ""))';
                $where[] = '((ue.timestart = 0 OR ue.timestart >= :timestart) AND (ue.timeend = 0 OR ue.timeend <= :timeend))';
                $conditions['timestart'] = $timestart_semester2;
                $conditions['timeend'] = $timeend_semester2;
                break;
            case '1':
            default:
                // TODO: ajouter une option pour afficher/ne pas afficher les notes attendues.
                // $where[] = '((ag.grade1 IS NOT NULL AND ag.grade1 != "") OR (ag.grade2 IS NOT NULL AND ag.grade2 != ""))';
                $where[] = '((ue.timestart = 0 OR ue.timestart >= :timestart) AND (ue.timeend = 0 OR ue.timeend <= :timeend))';
                $conditions['timestart'] = $timestart_semester1;
                $conditions['timeend'] = $timeend_semester1;
        }
    }

    // Lists filter (seulement les acceptés).
    $where[] = "ue.status = 0";

    // Build final query.
    if (isset($where[0])) {
        $sql .= " WHERE ".implode(' AND ', $where);
    }

    $sql .= " ORDER BY u.lastname, u.firstname, u.institution";

    if ($data->submitbutton === get_string('display', 'local_apsolu')) {
        // TODO: display
        $datatemplate = new stdClass();
        $datatemplate->users = array();
        $datatemplate->count_users = 0;

        if (isset($data->semesters) && $data->semesters === '2') {
            $datatemplate->semester = get_string('secondsemester', 'local_apsolu');
        } else {
            $datatemplate->semester = get_string('firstsemester', 'local_apsolu');
        }

        $recordset = $DB->get_recordset_sql($sql, $conditions);
        foreach ($recordset as $user) {
            $user->customfields = profile_user_record($user->id);
            $user->htmlpicture = $OUTPUT->user_picture($user, array('courseid' => $user->courseid));

            if ($data->semesters == 2) {
                $user->grade1 = $user->grade3;
                $user->grade2 = $user->grade4;
            }

            $datatemplate->users[] = $user;

            $datatemplate->count_users++;
        }

        $datatemplate->found_users = get_string('found_students', 'local_apsolu', $datatemplate->count_users);

        $PAGE->requires->js_call_amd('local_apsolu/extractions', 'initialise');

        $mform->display();
        echo $OUTPUT->render_from_template('local_apsolu/grades_export', $datatemplate);
        echo $OUTPUT->render_from_template('local_apsolu/grades_departments', (object) ['departments' => $departmentslist]);

    } else {
        // TODO: export csv

        // Creating a workbook.
        $workbook = new MoodleExcelWorkbook("-");

        // Sending HTTP headers.
        $partname = array();

        if (isset($data->courses[0]) && $data->courses[0] !== '*' && !isset($data->courses[1])) {
            $partname[] = preg_replace('/[^a-z0-9\-]/', '_', strtolower($customdata[1][$data->courses[0]]));
        }

        if (isset($data->courses[0]) && $data->courses[0] === '*') {
            $partname[] = 'tout_';
        }

        if (isset($data->institutions[0])) {
            $partname[] = preg_replace('/[^a-z0-9\-]/', '_', strtolower(implode('_', $data->institutions)));
        }

        if (isset($roles[$data->roles])) {
            $partname[] = preg_replace('/[^a-zéÉ0-9\-]/', '_', strtolower($roles[$data->roles]));
        }

        if (isset($data->semesters) && $data->semesters === '2') {
            $partname[] = str_replace(' ', '_', get_string('secondsemester', 'local_apsolu'));
        } else {
            $partname[] = str_replace(' ', '_', get_string('firstsemester', 'local_apsolu'));
        }

        $filename = preg_replace('/\_+/', '_', 'liste_etudiants_'.implode('_', $partname).'_'.time().'.xls');

        $workbook->send($filename);

        // Adding the worksheet.
        $myxls = $workbook->add_worksheet();

        $excelformat = new MoodleExcelFormat(array('border' => PHPExcel_Style_Border::BORDER_THIN));

        // Set headers.
        $headers = array();
        $headers[] = get_string('lastname');
        $headers[] = get_string('firstname');
        $headers[] = get_string('idnumber');
        $headers[] = get_string('sex', 'local_apsolu');
        $headers[] = get_string('institution');
        $headers[] = get_string('department');
        $headers[] = get_string('ufr', 'local_apsolu');
        $headers[] = get_string('cycle', 'local_apsolu');
        $headers[] = get_string('practicegrade', 'local_apsolu');
        $headers[] = get_string('theorygrade', 'local_apsolu');
        $headers[] = get_string('course');

        foreach ($headers as $position => $value) {
            $myxls->write_string(0, $position, $value, $excelformat);
        }

        // Set data.
        $line = 1;
        $users = array();
        $recordset = $DB->get_recordset_sql($sql, $conditions);
        foreach ($recordset as $user) {
            if (!isset($users[$user->id])) {
                $user->customfields = profile_user_record($user->id);

                $users[$user->id] = $user;
            }

            if ($data->semesters == 2) {
                $grade1 = $user->grade3;
                $grade2 = $user->grade4;
            } else {
                $grade1 = $user->grade1;
                $grade2 = $user->grade2;
            }

            $myxls->write_string($line, 0, $users[$user->id]->lastname, $excelformat);
            $myxls->write_string($line, 1, $users[$user->id]->firstname, $excelformat);
            $myxls->write_string($line, 2, $users[$user->id]->idnumber, $excelformat);
            $myxls->write_string($line, 3, $users[$user->id]->customfields->apsolusex, $excelformat);
            $myxls->write_string($line, 4, $users[$user->id]->institution, $excelformat);
            $myxls->write_string($line, 5, $users[$user->id]->department, $excelformat);
            $myxls->write_string($line, 6, $users[$user->id]->customfields->apsoluufr, $excelformat);
            $myxls->write_string($line, 7, $users[$user->id]->customfields->apsolucycle, $excelformat);
            $myxls->write_string($line, 8, $grade1, $excelformat);
            $myxls->write_string($line, 9, $grade2, $excelformat);
            $myxls->write_string($line, 10, $user->course, $excelformat);

            $line++;
        }

        $workbook->close();
        exit(0);
    }
} else {
    $mform->display();
    echo $OUTPUT->render_from_template('local_apsolu/grades_departments', (object) ['departments' => $departmentslist]);
}
