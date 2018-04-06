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

// TODO: rendre plus flexible.
define('FFSUID', 249);

require_once(__DIR__.'/../../../config.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot.'/local/apsolu/reports/locallib.php');
require_once($CFG->dirroot.'/local/apsolu/reports/ffsu_form.php');
require_once($CFG->libdir.'/excellib.class.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/local/apsolu/reports/ffsu.php');
$PAGE->set_title(get_string('reports_mystudents', 'local_apsolu'));

// Navigation.
$PAGE->navbar->add(get_string('reports_mystudents', 'local_apsolu'));

require_login();

// Load courses.
$is_manager = $DB->get_record('role_assignments', array('contextid' => 1, 'roleid' => 1, 'userid' => $USER->id));

if (!$is_manager) {
    $is_manager = is_siteadmin();
}

if (!$is_manager) {
    // Teachers.
    $sql = "SELECT DISTINCT c.*".
        " FROM {enrol} e".
        " JOIN {course} c ON c.id = e.courseid".
        " JOIN {apsolu_courses} ac ON ac.id = c.id".
        " JOIN {context} ctx ON c.id = ctx.instanceid AND ctx.contextlevel = 50".
        " JOIN {role_assignments} ra ON ctx.id = ra.contextid AND ra.roleid = 3".
        " WHERE ra.userid = ?".
        " AND e.enrol = 'select'".
        " AND e.status = 0";
    $records = $DB->get_records_sql($sql, array($USER->id));

    if (count($records) === 0) {
        print_error('usernotavailable');
    }
}

// Load institutions.
$sql = "SELECT DISTINCT institution FROM {user} WHERE id > 2 AND deleted = 0 AND auth = 'shibboleth' ORDER BY institution";
$institutions = array('*' => get_string('all'));
foreach ($DB->get_records_sql($sql) as $record) {
    if (!empty($record->institution)) {
        $institutions[$record->institution] = $record->institution;
    }
}

// Load groups.
$groups = array('*' => get_string('all'));
foreach ($DB->get_records('groups', array('courseid' => FFSUID), 'name') as $group) {
    $groups[$group->id] = $group->name;
}

// Load medicals.
$medicals = array(
    '*' => get_string('all'),
    '1' => get_string('yes'),
    '0' => get_string('no'),
);

// Load paids.
$paids = array(
    '*' => get_string('all'),
    '1' => get_string('yes'),
    '0' => get_string('no'),
);

// Load sexes.
$sexes = array(
    '*' => get_string('all'),
    'M' => get_string('male', 'local_apsolu'),
    'F' => get_string('female', 'local_apsolu'),
);


// Build form.
$defaults = (object) ['institutions' => '*', 'groups' => '*', 'medicals' => '*', 'paids' => '*', 'sexes' => '*'];
$customdata = array($defaults, $institutions, $groups, $medicals, $paids, $sexes);
$mform = new local_apsolu_reports_federation_export_form(null, $customdata);

if ($data = $mform->get_data()) {
    // Save data.
    $conditions = array();

    $sql = "SELECT u.*, g.name AS sport".
        " FROM {user} u".
        " JOIN {user_enrolments} ue ON u.id = ue.userid".
        " JOIN {enrol} e ON e.id = ue.enrolid AND e.enrol = 'select' AND e.status = 0".
        " JOIN {course} c ON c.id = e.courseid AND c.id = ".FFSUID.
        " JOIN {groups} g ON c.id = g.courseid".
        " JOIN {groups_members} gm ON g.id = gm.groupid AND u.id = gm.userid";

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

    // Groups filter.
    if (isset($data->groups[0]) && $data->groups[0] !== '*') {
        $groups = array();
        foreach ($data->groups as $group) {
            if (ctype_digit($group)) {
                $groups[] = $group;
            }
        }

        if (isset($groups[0])) {
            $where[] = "g.id IN (".implode(', ', $groups).")";
        }
    }

    // Medicals filter.
    if (isset($data->medicals)) {
        if ($data->medicals === '0') {
            $sql .= " LEFT JOIN {user_info_data} ui1 ON u.id = ui1.userid AND ui1.fieldid = 13";
            $where[] = "(ui1.data = 0 OR ui1.data IS NULL)";
        } elseif ($data->medicals === '1') {
            $sql .= " JOIN {user_info_data} ui1 ON u.id = ui1.userid AND ui1.fieldid = 13 AND ui1.data = 1";
        }
    }

    // Paids filter.
    if (isset($data->paids)) {
        if ($data->paids === '0') {
            $sql .= " LEFT JOIN {user_info_data} ui2 ON u.id = ui2.userid AND ui2.fieldid = 9";
            $where[] = "(ui2.data = 0 OR ui2.data IS NULL)";
        } elseif ($data->paids === '1') {
            $sql .= " JOIN {user_info_data} ui2 ON u.id = ui2.userid AND ui2.fieldid = 9 AND ui2.data = 1";
        }
    }

    // Sexes filter.
    if (isset($data->sexes)) {
        if ($data->sexes === 'M') {
            $sql .= " JOIN {user_info_data} ui3 ON u.id = ui3.userid AND ui3.fieldid = 2";
            $where[] = "ui3.data = 'M'";
        } elseif ($data->sexes === 'F') {
            $sql .= " JOIN {user_info_data} ui3 ON u.id = ui3.userid AND ui3.fieldid = 2";
            $where[] = "ui3.data = 'F'";
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
            $user->customfields = profile_user_record($user->id);
            $user->htmlpicture = $OUTPUT->user_picture($user, array('courseid' => FFSUID));
            $data->users[] = $user;
            $data->count_users++;
        }

        $data->found_users = get_string('reports_found_students', 'local_apsolu', $data->count_users);

        $PAGE->requires->js_call_amd('local_apsolu/extractions', 'initialise');

        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('reports_mystudents', 'local_apsolu'));
        $mform->display();
        echo $OUTPUT->render_from_template('local_apsolu/reports_ffsu', $data);
        echo $OUTPUT->footer();

    } else {
        // TODO: export csv

        // Creating a workbook.
        $workbook = new MoodleExcelWorkbook("-");
        // Sending HTTP headers.
        $workbook->send('liste_etudiants_ffsu_'.time().'.xls');
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
        $headers[] = get_string('group');
        $headers[] = get_string('federation_number', 'local_apsolu_courses');
        $headers[] = get_string('medical_certificate', 'local_apsolu_courses');
        $headers[] = get_string('federation_paid', 'local_apsolu_courses');
        foreach ($headers as $position => $value) {
            $myxls->write_string(0, $position, $value, $excelformat);
        }

        // Set data.
        $line = 1;
        $recordset = $DB->get_recordset_sql($sql, $conditions);
        foreach ($recordset as $user) {
            $user->customfields = profile_user_record($user->id);

            if (isset($user->customfields->apsolufederationpaid) && $user->customfields->apsolufederationpaid === '1') {
                $user->customfields->apsolufederationpaid = get_string('yes');
            } else {
                $user->customfields->apsolufederationpaid = get_string('no');
            }

            if (isset($user->customfields->apsolumedicalcertificate) && $user->customfields->apsolumedicalcertificate === '1') {
                $user->customfields->apsolumedicalcertificate = get_string('yes');
            } else {
                $user->customfields->apsolumedicalcertificate = get_string('no');
            }

            $myxls->write_string($line, 0, $user->lastname, $excelformat);
            $myxls->write_string($line, 1, $user->firstname, $excelformat);
            $myxls->write_string($line, 2, $user->idnumber, $excelformat);
            $myxls->write_string($line, 3, $user->customfields->apsolusex, $excelformat);
            $myxls->write_string($line, 4, $user->institution, $excelformat);
            $myxls->write_string($line, 5, $user->department, $excelformat);
            $myxls->write_string($line, 6, $user->customfields->apsoluufr, $excelformat);
            $myxls->write_string($line, 7, $user->customfields->apsolulmd, $excelformat);
            $myxls->write_string($line, 8, $user->sport, $excelformat);
            $myxls->write_string($line, 9, $user->customfields->apsolufederationnumber, $excelformat);
            $myxls->write_string($line, 10, $user->customfields->apsolumedicalcertificate, $excelformat);
            $myxls->write_string($line, 11, $user->customfields->apsolufederationpaid, $excelformat);

            $line++;
        }

        $workbook->close();
    }
} else {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('reports_mystudents', 'local_apsolu'));
    $mform->display();
    echo $OUTPUT->footer();
}
