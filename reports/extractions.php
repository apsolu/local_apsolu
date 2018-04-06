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
 * @copyright  2018 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../../config.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot.'/local/apsolu/reports/locallib.php');
require_once($CFG->dirroot.'/local/apsolu/reports/extractions_form.php');
require_once($CFG->libdir.'/excellib.class.php');

$force_manager = optional_param('manager', null, PARAM_INT);

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/local/apsolu/reports/students.php');
$PAGE->set_title(get_string('reports_mystudents', 'local_apsolu'));

// Navigation.
$PAGE->navbar->add(get_string('reports_mystudents', 'local_apsolu'));

require_login();

// Load courses.
$courses = array('*' => get_string('all'));

if ($force_manager) {
    $is_manager = $DB->get_record('role_assignments', array('contextid' => 1, 'roleid' => 1, 'userid' => $USER->id));

    if (!$is_manager) {
        $is_manager = is_siteadmin();
    }
} else {
    $is_manager = false;
}

if ($is_manager) {
    // Managers.
    $sql = "SELECT c.id, c.fullname".
        " FROM {course} c".
        " JOIN {apsolu_courses} ac ON ac.id = c.id".
        " ORDER BY c.fullname";
    $records = $DB->get_records_sql($sql);
} else {
    // Teachers.
    $sql = "SELECT DISTINCT c.*".
        " FROM {enrol} e".
        " JOIN {course} c ON c.id = e.courseid".
        " JOIN {apsolu_courses} ac ON ac.id = c.id".
        " JOIN {context} ctx ON c.id = ctx.instanceid AND ctx.contextlevel = 50".
        " JOIN {role_assignments} ra ON ctx.id = ra.contextid AND ra.roleid = 3".
        " WHERE ra.userid = ?".
        " AND e.enrol = 'select'".
        " AND e.status = 0".
        " ORDER BY c.fullname";
    $records = $DB->get_records_sql($sql, array($USER->id));
}

if (count($records) === 0) {
    print_error('usernotavailable');
}

foreach ($records as $record) {
    $courses[$record->id] = $record->fullname;
}

// Load institutions.
$sql = "SELECT DISTINCT institution FROM {user} WHERE id > 2 AND deleted = 0 AND auth = 'shibboleth' ORDER BY institution";
$institutions = array('*' => get_string('all'));
foreach ($DB->get_records_sql($sql) as $record) {
    if (!empty($record->institution)) {
        $institutions[$record->institution] = $record->institution;
    }
}

// Load departments.
$sql = "SELECT DISTINCT department FROM {user} WHERE id > 2 AND deleted = 0 AND auth = 'shibboleth' ORDER BY department";
$departments = array();
foreach ($DB->get_records_sql($sql) as $record) {
    if (empty($record->department) === false) {
        $departments[] = $record->department;
    }
}

// Load roles.
$roles = array('*' => get_string('all'));
foreach ($DB->get_records('role', array('archetype' => 'student')) as $role) {
    if (!empty($role->name) !== false) {
        $roles[$role->id] = $role->name;
    }
}

// Load semesters.
$semesters = array(
    '*' => get_string('all'),
    1 => get_string('semester1', 'local_apsolu_courses'),
    2 => get_string('semester2', 'local_apsolu_courses'),
);

if (date('m') > 8) {
    $year = date('y');
    $default_semester = 1;
} else {
    $year = date('y')-1;
    $default_semester = 2;
}

$timestart_semester1 = mktime(0, 0, 0, 8, 1, $year);
$timeend_semester1 = mktime(0, 0, 0, 1, 1, $year+1);
$timestart_semester2 = mktime(0, 0, 0, 1, 1, $year+1);
$timeend_semester2 = mktime(0, 0, 0, 7, 1, $year+1);

// Load lists.
$lists = array(
    '*' => get_string('all'),
    '0' => get_string('accepted_list', 'enrol_select'),
    '2' => get_string('main_list', 'enrol_select'),
    '3' => get_string('wait_list', 'enrol_select'),
    '4' => get_string('deleted_list', 'enrol_select'),
);

// Load paids.
$paids = array(
    '*' => get_string('all'),
    '1' => get_string('yes'),
    '0' => get_string('no'),
);

// Build form.
$defaults = (object) ['institutions' => '*', 'roles' => '*', 'semesters' => $default_semester, 'lists' => '0', 'paids' => '*'];
$customdata = array($defaults, $courses, $institutions, $roles, $semesters, $lists, $paids, $force_manager);
$mform = new local_apsolu_reports_export_course_users_form(null, $customdata);

if ($data = $mform->get_data()) {
    // Save data.
    $conditions = array();

    $sql = "SELECT u.*, r.name AS rolename, ue.status AS listid, c.id AS courseid, c.fullname AS course, e.name AS enrol".
        " FROM {user} u".
        " JOIN {user_enrolments} ue ON u.id = ue.userid".
        " JOIN {enrol} e ON e.id = ue.enrolid AND e.enrol = 'select' AND e.status = 0".
        " JOIN {course} c ON c.id = e.courseid".
        " JOIN {apsolu_courses} ac ON ac.id = c.id".
        " JOIN {context} ctx ON c.id = ctx.instanceid AND ctx.contextlevel = 50".
        " JOIN {role_assignments} ra1 ON ctx.id = ra1.contextid AND ra1.userid = u.id AND ra1.itemid = e.id".
        " JOIN {role} r ON r.id = ra1.roleid";

    if (!$is_manager) {
        // Teachers.
        $sql .= " JOIN {role_assignments} ra2 ON ctx.id = ra2.contextid AND ra2.roleid = 3 AND ra2.userid = :owner";
        $conditions['owner'] = $USER->id;
    }

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

    // Institutions filter.
    if (isset($data->institutions[0]) && $data->institutions[0] !== '*') {
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
            $ufrs[] = 'ui4.data LIKE :ufr'.$i;
            $conditions['ufr'.$i] = '%'.trim($ufr).'%';
        }

        if (isset($ufrs[0])) {
            $sql .= " LEFT JOIN {user_info_data} ui4 ON u.id = ui4.userid AND ui4.fieldid = 4";
            $where[] = '( '.implode(' OR ', $ufrs).' )';
        }
    }

    // Departments filter.
    if (isset($data->departments)) {
        $departments_filter = array();
        foreach (explode(',', $data->departments) as $i => $department) {
            if (empty($department)) {
                continue;
            }
            $departments_filter[] = 'u.department LIKE :department'.$i;
            $conditions['department'.$i] = '%'.trim($department).'%';
        }

        if (isset($departments_filter[0])) {
            $where[] = '( '.implode(' OR ', $departments_filter).' )';
        }
    }

    // Roles filter.
    if (isset($data->roles[0]) && $data->roles[0] !== '*') {
        $roles = array();
        foreach ($data->roles as $role) {
            if (ctype_digit($role)) {
                $roles[] = $role;
            }
        }

        if (isset($roles[0])) {
            $where[] = "ra1.roleid IN (".implode(', ', $roles).")";
        }
    }

    // Semesters filter.
    if (isset($data->semesters) && $data->semesters !== '*') {
        switch ($data->semesters) {
            case '2':
                $where[] = '((ue.timestart = 0 OR ue.timestart >= :timestart) AND (ue.timeend = 0 OR ue.timeend <= :timeend))';
                $conditions['timestart'] = $timestart_semester2;
                $conditions['timeend'] = $timeend_semester2;
                break;
            case '1':
            default:
                $where[] = '((ue.timestart = 0 OR ue.timestart >= :timestart) AND (ue.timeend = 0 OR ue.timeend <= :timeend))';
                $conditions['timestart'] = $timestart_semester1;
                $conditions['timeend'] = $timeend_semester1;
        }
    }

    // Lists filter.
    if (isset($data->lists[0]) && $data->lists[0] !== '*') {
        $lists = array();
        foreach ($data->lists as $list) {
            if (ctype_digit($list)) {
                $lists[] = $list;
            }
        }

        if (isset($lists[0])) {
            $where[] = "ue.status IN (".implode(', ', $lists).")";
        }
    }

    // Paids filter.
    if (isset($data->paids)) {
        if ($data->paids === '0') {
            $sql .= " LEFT JOIN {user_info_data} ui ON u.id = ui.userid AND ui.fieldid = 12";
            $where[] = "(ui.data = 0 OR ui.data IS NULL)";
        } elseif ($data->paids === '1') {
            $sql .= " JOIN {user_info_data} ui ON u.id = ui.userid AND ui.fieldid = 12 AND ui.data = 1";
        }
    }

    // Build final query.
    if (isset($where[0])) {
        $sql .= " WHERE ".implode(' AND ', $where);
    }

    $sql .= " ORDER BY u.lastname, u.firstname, u.institution";

    if ($data->submitbutton === get_string('display', 'local_apsolu')) {
        // TODO: display
        $data = new stdClass();
        $data->users = array();
        $data->count_users = 0;
        $data->action = $CFG->wwwroot.'/blocks/apsolu_teachers/notify.php';

        $recordset = $DB->get_recordset_sql($sql, $conditions);
        foreach ($recordset as $user) {
            $user->list = $customdata[6][$user->listid];
            $user->customfields = profile_user_record($user->id);
            $user->htmlpicture = $OUTPUT->user_picture($user, array('courseid' => $user->courseid));
            $data->users[] = $user;
            $data->count_users++;
        }

        $data->found_users = get_string('reports_found_students', 'local_apsolu', $data->count_users);

        $PAGE->requires->js_call_amd('local_apsolu/reports_extractions', 'initialise');

        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('reports_mystudents', 'local_apsolu'));
        $mform->display();
        echo $OUTPUT->render_from_template('local_apsolu/reports_extractions', $data);
        echo $OUTPUT->render_from_template('local_apsolu/reports_departments', (object) ['departments' => $departments]);
        echo $OUTPUT->footer();
    } else {
        // TODO: export csv

        // Creating a workbook.
        $workbook = new MoodleExcelWorkbook("-");
        // Sending HTTP headers.
        if (isset($data->courses[0]) && $data->courses[0] !== '*' && !isset($data->courses[1])) {
            $filename = preg_replace('/[^a-z0-9\-]/', '_', strtolower($customdata[1][$data->courses[0]])).'_';
        } else if (isset($data->courses[0]) && $data->courses[0] === '*') {
            $filename = 'tout_';
        } else {
            $filename = '';
        }

        $workbook->send('liste_etudiants_'.$filename.time().'.xls');
        // Adding the worksheet.
        $myxls = $workbook->add_worksheet();

        $excelformat = new MoodleExcelFormat(array('border' => PHPExcel_Style_Border::BORDER_THIN));

        // Set headers.
        $headers = array();
        $headers[] = get_string('lastname');
        $headers[] = get_string('firstname');
        $headers[] = get_string('idnumber');
        $headers[] = get_string('sex', 'local_apsolu_auth');
        $headers[] = get_string('institution');
        $headers[] = get_string('department');
        $headers[] = get_string('ufr', 'local_apsolu_auth');
        $headers[] = get_string('lmd', 'local_apsolu_auth');
        $headers[] = get_string('role', 'local_apsolu_courses');
        $headers[] = get_string('enrolments', 'enrol');
        $headers[] = get_string('cardsport', 'block_apsolu_payment');
        $headers[] = get_string('list');
        if (!(isset($data->courses[0]) && $data->courses[0] !== '*' && !isset($data->courses[1]))) {
            $headers[] = get_string('course');
        }

        foreach ($headers as $position => $value) {
            $myxls->write_string(0, $position, $value, $excelformat);
        }

        // Set data.
        $line = 1;
        $recordset = $DB->get_recordset_sql($sql, $conditions);
        foreach ($recordset as $user) {
            $user->customfields = profile_user_record($user->id);

            if (isset($user->customfields->apsolucardpaid) && $user->customfields->apsolucardpaid === '1') {
                $user->customfields->apsolucardpaid = get_string('yes');
            } else {
                $user->customfields->apsolucardpaid = get_string('no');
            }

            $myxls->write_string($line, 0, $user->lastname, $excelformat);
            $myxls->write_string($line, 1, $user->firstname, $excelformat);
            $myxls->write_string($line, 2, $user->idnumber, $excelformat);
            $myxls->write_string($line, 3, $user->customfields->apsolusex, $excelformat);
            $myxls->write_string($line, 4, $user->institution, $excelformat);
            $myxls->write_string($line, 5, $user->department, $excelformat);
            $myxls->write_string($line, 6, $user->customfields->apsoluufr, $excelformat);
            $myxls->write_string($line, 7, $user->customfields->apsolulmd, $excelformat);
            $myxls->write_string($line, 8, $user->rolename, $excelformat);
            $myxls->write_string($line, 9, $user->enrol, $excelformat);
            $myxls->write_string($line, 10, $user->customfields->apsolucardpaid, $excelformat);
            $myxls->write_string($line, 11, $customdata[6][$user->listid], $excelformat);
            if (!(isset($data->courses[0]) && $data->courses[0] !== '*' && !isset($data->courses[1]))) {
                $myxls->write_string($line, 12, $user->course, $excelformat);
            }

            $line++;
        }

        $workbook->close();
    }
} else {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('reports_mystudents', 'local_apsolu'));
    if ($is_manager) {
        // TODO: rendre plus flexible.
        echo '<p class="text-right"><a class="btn btn-primary" href="'.$CFG->wwwroot.'/blocks/apsolu_teachers/stats.php">Télécharger le gros fichier</a></p>';
    }
    $mform->display();
    echo $OUTPUT->render_from_template('local_apsolu/reports_departments', (object) ['departments' => $departments]);
    echo $OUTPUT->footer();
}
