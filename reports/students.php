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
 * Sum up enrolment methods in each user's courses.
 *
 * @package    block_apsolu_teachers
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');
require_once($CFG->dirroot.'/blocks/apsolu_teachers/locallib.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/blocks/apsolu_teachers/students.php');
$PAGE->set_title(get_string('mystudents', 'block_apsolu_teachers'));

// Navigation.
$PAGE->navbar->add(get_string('mystudents', 'block_apsolu_teachers'));

require_login();

$userid = optional_param('userid', null, PARAM_INT);

if (isset($userid)) {
    // Vérifie que l'utilisateur enseigne dans au moins 1 cours.
    $sql = "SELECT DISTINCT c.*".
        " FROM {enrol} e".
        " JOIN {course} c ON c.id = e.courseid".
        " JOIN {apsolu_courses} ac ON ac.id = c.id".
        " JOIN {context} ctx ON c.id = ctx.instanceid AND ctx.contextlevel = 50".
        " JOIN {role_assignments} ra ON ctx.id = ra.contextid AND ra.roleid = 3".
        " JOIN {user_enrolments} ue ON ue.enrolid = e.id".
        " WHERE ra.userid = ?".
        " AND e.enrol = 'select'".
        " AND e.status = 0".
        " AND ue.status = 0".
        " AND ue.userid = ?";
    $enrols = $DB->get_records_sql($sql, array($USER->id, $userid));
    if (!$enrols) {
        print_error('usernotavailable');
    } else {
        $course = current($enrols);
        redirect($CFG->wwwroot.'/user/view.php?id='.$userid.'&course='.$course->id);
    }
} else {
    // Create the user selector objects.
    $options = array('multiselect' => false, 'extrafields' => array('idnumber', 'email'));
    $userselector = new \UniversiteRennes2\Apsolu\blocks_apsolu_teachers_students_selector('userid', $options);
    ob_start();
    $userselector->display();
    $userselector = ob_get_contents();
    ob_end_clean();

    $data = new stdClass();
    $data->action = $CFG->wwwroot.'/blocks/apsolu_teachers/students.php';
    $data->user_selector = $userselector;
}

if (isset($notification)) {
    echo $notification;
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('mystudents', 'block_apsolu_teachers'));
echo $OUTPUT->render_from_template('block_apsolu_teachers/students', $data);
echo $OUTPUT->footer();
