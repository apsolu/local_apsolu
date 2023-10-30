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
 * Permet d'extraire la liste des étudiants inscrits à la FFSU.
 *
 * @package    local_apsolu
 * @copyright  2023 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_user\fields;
use local_apsolu\core\customfields;
use local_apsolu\core\federation\activity;
use local_apsolu\core\federation\adhesion;
use local_apsolu\core\federation\course as FederationCourse;

require_once(__DIR__.'/../../../../config.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->libdir.'/excellib.class.php');
require_once(__DIR__.'/export_form.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/local/apsolu/federation/listing/index.php');
$PAGE->set_title(get_string('list_of_my_students', 'local_apsolu'));

require_login();

$federationcourse = new FederationCourse();
$courseid = $federationcourse->get_courseid();
if (empty($courseid) === true) {
    // La cours FFSU n'est pas configuré.
    print_error('federation_module_is_not_configured', 'local_apsolu');
}

$coursecontext = context_course::instance($courseid, MUST_EXIST);

// Contrôle les permissions d'accès.
require_capability('moodle/course:update', $coursecontext);

// Navigation.
$PAGE->navbar->add(get_string('list_of_my_students', 'local_apsolu'));

// Récupère les activités.
$activities = [];
foreach (activity::get_activity_data() as $activity) {
    $activities[$activity['id']] = $activity['name'];
}

// Récupère les sexes.
$sexes = adhesion::get_sexes();

// Récupère les institutions, ufr et départements.
$customfields = customfields::getCustomFields();
$sql = "SELECT DISTINCT u.institution, u.department, uid.data AS ufr
          FROM {user} u
     LEFT JOIN {user_info_data} uid ON u.id = uid.userid AND uid.fieldid = :fieldid
         WHERE u.deleted = 0";
$institutions = [];
$ufrs = [];
$departments = [];

$recordset = $DB->get_recordset_sql($sql, ['fieldid' => $customfields['apsoluufr']->id]);
foreach ($recordset as $record) {
    $institution = trim($record->institution);
    if (empty($institution) === false) {
        $institutions[$institution] = $institution;
    }

    $ufr = trim($record->ufr);
    if (empty($ufr) === false) {
        $ufrs[$ufr] = $ufr;
    }

    $department = trim($record->department);
    if (empty($department) === false) {
        $departments[$department] = $department;
    }
}
$recordset->close();

ksort($institutions);
ksort($ufrs);
ksort($departments);

// Build form.
$customdata = [$activities, $sexes, $institutions, $ufrs, $departments];
$mform = new local_apsolu_federation_export_form(null, $customdata);

if ($data = $mform->get_data()) {
    $fields = fields::for_userpic()->get_sql('u');

    $sql = "SELECT ".substr($fields->selects, 1).", u.idnumber, u.institution, u.department, uid1.data AS ufr, uid2.data AS cycle,
                   afac.name AS mainsport, afa.sex, afa.federationnumber
              FROM {user} u
              JOIN {apsolu_federation_adhesions} afa ON u.id = afa.userid
              JOIN {apsolu_federation_activities} afac ON afac.id = afa.mainsport
         LEFT JOIN {user_info_data} uid1 ON u.id = uid1.userid AND uid1.fieldid = :fieldid1
         LEFT JOIN {user_info_data} uid2 ON u.id = uid2.userid AND uid2.fieldid = :fieldid2
             WHERE u.deleted = 0
               AND afa.federationnumber IS NOT NULL";
    $params = [];
    $params['fieldid1'] = $customfields['apsoluufr']->id;
    $params['fieldid2'] = $customfields['apsolucycle']->id;

    // Champ activités.
    if (isset($data->activities[0])) {
        list($sqlin, $sqlparams) = $DB->get_in_or_equal($data->activities, SQL_PARAMS_NAMED, 'activities_');

        $sql .= sprintf(' AND afa.mainsport %s', $sqlin);
        $params = array_merge($params, $sqlparams);
    }

    // Champ sexes.
    if (isset($data->sexes[0]) === true && isset($data->sexes[1]) === false) {
        $sql .= ' AND afa.sex = :sex';
        $params['sex'] = $data->sexes[0];
    }

    // Champ institutions.
    if (isset($data->institutions[0])) {
        list($sqlin, $sqlparams) = $DB->get_in_or_equal($data->institutions, SQL_PARAMS_NAMED, 'institution_');

        $sql .= sprintf(' AND u.institution %s', $sqlin);
        $params = array_merge($params, $sqlparams);
    }

    // Champ UFR.
    if (isset($data->ufrs[0]) === true) {
        list($sqlin, $sqlparams) = $DB->get_in_or_equal($data->ufrs, SQL_PARAMS_NAMED, 'ufr_');

        $sql .= sprintf(' AND uid.data %s', $sqlin);
        $params = array_merge($params, $sqlparams);
    }

    // Champ départements.
    if (isset($data->departments[0]) === true) {
        list($sqlin, $sqlparams) = $DB->get_in_or_equal($data->departments, SQL_PARAMS_NAMED, 'department_');

        $sql .= sprintf(' AND u.department %s', $sqlin);
        $params = array_merge($params, $sqlparams);
    }

    // Champ nom de famille.
    if (isset($data->lastnames) === true) {
        $lastnames = [];
        foreach (explode(',', $data->lastnames) as $i => $lastname) {
            $lastname = trim($lastname);
            if (empty($lastname)) {
                continue;
            }

            $lastnames[] = 'u.lastname LIKE :lastname'.$i;
            $params['lastname'.$i] = '%'.$lastname.'%';
        }

        if (isset($lastnames[0])) {
            $sql .= ' AND ( '.implode(' OR ', $lastnames).' )';
        }
    }

    $sql .= " ORDER BY u.lastname, u.firstname, u.institution, ufr, u.department";


    if ($data->submitbutton === get_string('show')) {
        // Récupère les données.
        $data = new stdClass();
        $data->users = [];
        $data->count_users = 0;
        $data->action = $CFG->wwwroot.'/blocks/apsolu_dashboard/notify.php';

        $recordset = $DB->get_recordset_sql($sql, $params);
        foreach ($recordset as $user) {
            $user->htmlpicture = $OUTPUT->user_picture($user, ['courseid' => $courseid]);
            $data->users[] = $user;
            $data->count_users++;
        }
        $recordset->close();
    } else {
        // Export au format excel.
        $workbook = new MoodleExcelWorkbook("-");
        $workbook->send('liste_etudiants_ffsu_'.time().'.xls');
        $myxls = $workbook->add_worksheet();

        if (class_exists('PHPExcel_Style_Border') === true) {
            // Jusqu'à Moodle 3.7.x.
            $properties = ['border' => PHPExcel_Style_Border::BORDER_THIN];
        } else {
            // Depuis Moodle 3.8.x.
            $properties = ['border' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN];
        }

        $excelformat = new MoodleExcelFormat($properties);

        // Set headers.
        $headers = [];
        $headers[] = get_string('lastname');
        $headers[] = get_string('firstname');
        $headers[] = get_string('federation_number', 'local_apsolu');
        $headers[] = get_string('main_sport', 'local_apsolu');
        $headers[] = get_string('idnumber');
        $headers[] = get_string('sex', 'local_apsolu');
        $headers[] = get_string('institution');
        $headers[] = get_string('department');
        $headers[] = get_string('ufr', 'local_apsolu');
        $headers[] = get_string('cycle', 'local_apsolu');
        foreach ($headers as $position => $value) {
            $myxls->write_string(0, $position, $value, $excelformat);
        }

        // Set data.
        $line = 1;
        $recordset = $DB->get_recordset_sql($sql, $params);
        foreach ($recordset as $user) {
            $myxls->write_string($line, 0, $user->lastname, $excelformat);
            $myxls->write_string($line, 1, $user->firstname, $excelformat);
            $myxls->write_string($line, 2, $user->federationnumber, $excelformat);
            $myxls->write_string($line, 3, $user->mainsport, $excelformat);
            $myxls->write_string($line, 4, $user->idnumber, $excelformat);
            $myxls->write_string($line, 5, $user->sex, $excelformat);
            $myxls->write_string($line, 6, $user->institution, $excelformat);
            $myxls->write_string($line, 7, $user->department, $excelformat);
            $myxls->write_string($line, 8, $user->ufr, $excelformat);
            $myxls->write_string($line, 9, $user->cycle, $excelformat);

            $line++;
        }
        $recordset->close();

        $workbook->close();
        exit(0);
    }
}

// Javascript.
$PAGE->requires->js_call_amd('block_apsolu_dashboard/select_all_checkboxes', 'initialise');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('list_of_my_students', 'local_apsolu'));
$mform->display();
if (isset($data) === true) {
    echo $OUTPUT->render_from_template('local_apsolu/federation_listing', $data);
}
echo $OUTPUT->footer();
