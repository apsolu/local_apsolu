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
 * Display a custom homepage.
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use UniversiteRennes2\Apsolu as apsolu;

defined('MOODLE_INTERNAL') || die();

// Cache stuff.
$cachedir = $CFG->dataroot.'/apsolu/local_apsolu/cache/homepage';
$rolescachefile = $cachedir.'/roles.json';
$activitiescachefile = $cachedir.'/activities.json';

$now = new DateTime();

if (is_file($activitiescachefile)) {
    $cache = new DateTime(date('F d Y H:i:s.', filectime($activitiescachefile)));
} else {
    $cache = $now;
}

if ($cache <= $now->sub(new DateInterval('PT5M'))) {
    // Rebuild cache.
    require_once($CFG->dirroot.'/enrol/select/locallib.php');

    // Get activities.
    $sql = "SELECT DISTINCT cc.id, cc.name, cc.description".
        " FROM {course_categories} cc".
        " JOIN {course} c ON cc.id = c.category".
        " JOIN {apsolu_courses} ac ON ac.id = c.id".
        " WHERE c.visible = 1".
        " AND ac.on_homepage = 1".
        " ORDER BY cc.name";
    $activities = $DB->get_records_sql($sql);

    // Get courses.
    $sql = "SELECT DISTINCT ac.id, ac.event, ask.name AS skill, al.name AS location,".
        " ac.weekday, ac.numweekday, ac.starttime, ac.endtime, ap.generic_name AS period, cc.id AS activity,".
        " 0 AS count_teachers, 0 AS count_roles".
        " FROM {apsolu_courses} ac".
        " JOIN {course} c ON c.id = ac.id".
        " JOIN {course_categories} cc ON cc.id = c.category".
        " JOIN {apsolu_skills} ask ON ask.id = ac.skillid".
        " JOIN {apsolu_locations} al ON al.id = ac.locationid".
        " JOIN {apsolu_periods} ap ON ap.id = ac.periodid".
        " WHERE c.visible = 1".
        " AND ac.on_homepage = 1".
        " ORDER BY cc.id, ac.numweekday, ac.starttime, ask.name, al.name";
    $slots = $DB->get_records_sql($sql);

    // Liste des enseignants.
    $sql = "SELECT ctx.instanceid, u.firstname, u.lastname, u.email".
        " FROM {user} u".
        " JOIN {role_assignments} ra ON u.id = ra.userid".
        " JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50".
        " WHERE ra.roleid = 3".
        " ORDER BY u.lastname, u.firstname";
    foreach ($DB->get_recordset_sql($sql) as $teacher) {
        if (!isset($slots[$teacher->instanceid])) {
            continue;
        }

        if (!isset($slots[$teacher->instanceid]->teachers)) {
            $slots[$teacher->instanceid]->teachers = array();
        }

        $fullname = htmlentities($teacher->firstname.' '.$teacher->lastname);
        $slots[$teacher->instanceid]->teachers[] = str_replace(' ', '&nbsp;', $fullname);
        $slots[$teacher->instanceid]->count_teachers++;
    }

    // Liste des types d'inscription.
    $roles = apsolu\get_custom_student_roles();

    $sql = "SELECT DISTINCT e.courseid, r.id".
        " FROM {enrol} e".
        " JOIN {enrol_select_roles} esr ON e.id = esr.enrolid".
        " JOIN {role} r ON r.id = esr.roleid".
        " WHERE e.enrol = 'select'".
        " AND e.status = 0". // Active.
        " ORDER BY r.id";
    foreach ($DB->get_recordset_sql($sql) as $role) {
        if (!isset($slots[$role->courseid])) {
            continue;
        }

        if (!isset($slots[$role->courseid]->roles)) {
            $slots[$role->courseid]->roles = array();
        }

        $slots[$role->courseid]->roles[] = $roles[$role->id];
        $slots[$role->courseid]->count_roles++;
    }

    // Set slots.
    foreach ($slots as $slot) {
        if (isset($activities[$slot->activity])) {
            if (!isset($activities[$slot->activity]->slots)) {
                $activities[$slot->activity]->slots = array();
            }

            $slot->weekday = get_string($slot->weekday, 'calendar');
            $activities[$slot->activity]->slots[] = $slot;
        }
    }

    // Mise en cache.
    $roles = array_values($roles);
    $activities = array_values($activities);

    if (is_dir($cachedir)) {
        file_put_contents($rolescachefile, json_encode($roles));
        file_put_contents($activitiescachefile, json_encode($activities));
    }
} else {
    // Use cache.
    $roles = json_decode(file_get_contents($rolescachefile));
    $activities = json_decode(file_get_contents($activitiescachefile));
}

// Build variable for template.
$data = new stdClass();
$data->roles = $roles;
$data->activities = $activities;
$data->wwwroot = $CFG->wwwroot;

// Set last menu link.
if (isloggedin() && !isguestuser()) {
    $data->dashboard_link = $CFG->wwwroot.'/my/';
} else {
    $data->login_link = $CFG->wwwroot.'/#authentification';
}

// Call javascript.
$PAGE->requires->js_call_amd('local_apsolu/homepage', 'initialise');

// Call template.
echo $OUTPUT->render_from_template('local_apsolu/homepage_index', $data);
