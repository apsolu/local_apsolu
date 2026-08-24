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
 * Génère un fichier CSV exportant tous les cours, avec une colonne pour les enseignants et une colonne par méthodes d'inscription.
 *
 * La colonne des méthodes d'inscription contient les dates d'inscription, les quotas, les rôles acceptés et les tarifs requis.
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:disable moodle.NamingConventions.ValidVariableName.VariableNameUnderscore

use local_apsolu\core\course;
use local_apsolu\output\courses as CoursesRenderer;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/csvlib.class.php');

$coursetypeid = optional_param('coursetypeid', null, PARAM_INT);

// Liste des cours.
$courses = Course::get_records_by_course_type($coursetypeid);

// Liste des enseignants.
$sql = "SELECT ctx.instanceid, u.firstname, u.lastname, u.email
          FROM {user} u
          JOIN {role_assignments} ra ON u.id = ra.userid
          JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
         WHERE ra.roleid = 3
      ORDER BY u.lastname, u.firstname";
$recordset = $DB->get_recordset_sql($sql);
foreach ($recordset as $teacher) {
    if (!isset($courses[$teacher->instanceid])) {
        continue;
    }

    $user = $teacher->firstname . ' ' . $teacher->lastname . ' (' . $teacher->email . ')';
    if ($courses[$teacher->instanceid]->teachers === '-') {
        $courses[$teacher->instanceid]->teachers = $user;
    } else {
        $courses[$teacher->instanceid]->teachers .= ', ' . $user;
    }
}
$recordset->close();

// Liste des rôles.
$roles = role_fix_names($DB->get_records('role'));

// Liste des rôles par méthodes d'inscription.
$enrol_roles = [];
$recordset = $DB->get_recordset('enrol_select_roles');
foreach ($recordset as $enrol) {
    if (isset($enrol_roles[$enrol->enrolid]) === false) {
        $enrol_roles[$enrol->enrolid] = [];
    }

    $enrol_roles[$enrol->enrolid][] = $roles[$enrol->roleid]->name;
}
$recordset->close();

// Liste des tarifs.
$cards = $DB->get_records('apsolu_payments_cards');

// Liste des tarifs par méthodes d'inscription.
$enrol_cards = [];
$recordset = $DB->get_recordset('enrol_select_cards');
foreach ($recordset as $enrol) {
    if (isset($enrol_cards[$enrol->enrolid]) === false) {
        $enrol_cards[$enrol->enrolid] = [];
    }

    $enrol_cards[$enrol->enrolid][] = $cards[$enrol->cardid]->fullname;
}
$recordset->close();

// Liste des méthodes d'inscription.
foreach ($DB->get_records('enrol', ['enrol' => 'select'], $sort = 'enrolstartdate') as $enrol) {
    if (isset($courses[$enrol->courseid]) === false) {
        continue;
    }

    $enrolstartdate = userdate($enrol->enrolstartdate, get_string('strftimedatetimeshort'));
    $enrolenddate = userdate($enrol->enrolenddate, get_string('strftimedatetimeshort'));

    $main_quota = $enrol->customint1;
    $wait_quota = $enrol->customint2;

    $roles = '-';
    if (isset($enrol_roles[$enrol->id]) === true) {
        sort($enrol_roles[$enrol->id]);
        $roles = implode(', ', $enrol_roles[$enrol->id]);
    }

    $cards = '-';
    if (isset($enrol_cards[$enrol->id]) === true) {
        sort($enrol_cards[$enrol->id]);
        $cards = implode(', ', $enrol_cards[$enrol->id]);
    }

    $index = 'enrol' . $enrol->id;
    $courses[$enrol->courseid]->{$index} = sprintf(
        'Déb. ins.: %s, fin ins.: %s, LP: %s, LC: %s, rôles: %s, cartes: %s',
        $enrolstartdate,
        $enrolenddate,
        $main_quota,
        $wait_quota,
        $roles,
        $cards
    );
}

// Génération du fichier csv.
$filename = str_replace(' ', '_', strtolower(get_string('courses', 'local_apsolu')));

$headers = CoursesRenderer::get_headers($coursetypeid, CoursesRenderer::VISIBLE_ONLY_ADMINISTRATION);
$courses = CoursesRenderer::get_data(course::get_records_by_course_type($coursetypeid), $headers);
$courses = Course::sort($courses);

$defaultheaders = [
    'id' => get_string('course_number', 'local_apsolu'),
    'name' => get_string('name'),
    'idnumber' => get_string('idnumbercourse'),
    'grouping' => get_string('grouping', 'local_apsolu'),
];
$headers = array_merge($defaultheaders, $headers, ['teachers' => get_string('teachers')]);

$csvexport = new \csv_export_writer();
$csvexport->set_filename($filename);
$csvexport->add_data($headers);

foreach ($courses as $course) {
    $data = [];
    $data[] = $course->id;
    $data[] = $course->fullname;
    $data[] = $course->idnumber;
    $data[] = $course->grouping;
    foreach ($course->fields as $value) {
        $data[] = $value;
    }
    $data[] = implode(', ', $course->teachers);

    $csvexport->add_data($data);
}

$csvexport->download_file();

exit;
