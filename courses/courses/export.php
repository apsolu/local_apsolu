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
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:disable moodle.NamingConventions.ValidVariableName.VariableNameUnderscore

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/csvlib.class.php');

// Liste des cours.
$sql = "SELECT c.id, c.idnumber, ccc.name AS grouping, cc.name AS category, ask.name AS skill,
               ac.weekday, ac.starttime, ac.endtime, ac.license, al.name AS location, ap.name AS period, '-' AS teachers
          FROM {course} c
          JOIN {course_categories} cc ON cc.id = c.category
          JOIN {course_categories} ccc ON ccc.id = cc.parent
          JOIN {apsolu_courses} ac ON c.id = ac.id
          JOIN {apsolu_courses_categories} acc ON acc.id = c.category
          JOIN {apsolu_skills} ask ON ask.id = ac.skillid
          JOIN {apsolu_locations} al ON al.id = ac.locationid
          JOIN {apsolu_periods} ap ON ap.id = ac.periodid
      ORDER BY category, ac.numweekday, ac.starttime, location, skill";
$courses = $DB->get_records_sql($sql);

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

    $user = $teacher->firstname.' '.$teacher->lastname.' ('.$teacher->email.')';
    if ($courses[$teacher->instanceid]->teachers === '-') {
        $courses[$teacher->instanceid]->teachers = $user;
    } else {
        $courses[$teacher->instanceid]->teachers .= ', '.$user;
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

    $index = 'enrol'.$enrol->id;
    $courses[$enrol->courseid]->{$index} = sprintf('Déb. ins.: %s, fin ins.: %s, LP: %s, LC: %s, rôles: %s, cartes: %s',
        $enrolstartdate, $enrolenddate, $main_quota, $wait_quota, $roles, $cards);
}

// Génération du fichier csv.
$filename = str_replace(' ', '_', strtolower(get_string('courses', 'local_apsolu')));

$headers = [
    get_string('course_number', 'local_apsolu'),
    get_string('idnumbercourse'),
    get_string('groupings', 'local_apsolu'),
    get_string('categories', 'local_apsolu'),
    get_string('skills', 'local_apsolu'),
    get_string('weekdays', 'local_apsolu'),
    get_string('starttime', 'local_apsolu'),
    get_string('endtime', 'local_apsolu'),
    get_string('federation', 'local_apsolu'),
    get_string('locations', 'local_apsolu'),
    get_string('periods', 'local_apsolu'),
    get_string('teachers'),
];

$csvexport = new \csv_export_writer();
$csvexport->set_filename($filename);
$csvexport->add_data($headers);

foreach ($courses as $course) {
    $course->weekday = get_string($course->weekday, 'local_apsolu');
    $csvexport->add_data((array) $course);
}

$csvexport->download_file();

exit;
