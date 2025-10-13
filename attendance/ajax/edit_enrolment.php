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
 * Script ajax gérant l'inscription à une session de cours.
 *
 * @package    local_apsolu
 * @copyright  2017 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../../config.php');
require($CFG->dirroot . '/enrol/select/lib.php');
require(__DIR__ . '/edit_enrolment_form.php');

$userid = required_param('userid', PARAM_INT); // User id.
$courseid = required_param('courseid', PARAM_INT); // Course id.
$enrolid = required_param('enrolid', PARAM_INT); // Enrol id.
$statusid = required_param('statusid', PARAM_INT); // Status id (user enrolment status).
$roleid = required_param('roleid', PARAM_INT); // Role id.

// Set permissions.
$course = get_course($courseid);
require_login($course);

$student = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

$context = context_course::instance($courseid);
require_capability('moodle/course:update', $context);

$PAGE->set_context($context);

$data = (object) ['userid' => $userid, 'courseid' => $courseid,
    'enrolid' => $enrolid, 'statusid' => $statusid, 'roleid' => $roleid];
$statuses = [];
foreach (enrol_select_plugin::$states as $stateid => $state) {
    $statuses[$stateid] = enrol_select_plugin::get_enrolment_list_name($stateid);
}

$roles = [];
foreach (enrol_select_get_custom_student_roles() as $role) {
    $roles[$role->id] = $role->name;
}

$json = new stdClass();

// Initialize form.
$url = new moodle_url('/local/apsolu/attendance/ajax/edit_enrolment.php');
$params = [$data, $statuses, $roles];

$mform = new edit_enrolment_form($url, $params);

// Handle data form.
if ($data = $mform->get_data()) {
    try {
        $transaction = $DB->start_delegated_transaction();

        $instance = $DB->get_record('enrol', ['id' => $enrolid, 'enrol' => 'select'], '*', MUST_EXIST);

        $enrolselectplugin = new enrol_select_plugin();
        $enrolselectplugin->enrol_user(
            $instance,
            $student->id,
            $data->roleid,
            $timestart = 0,
            $timeend = 0,
            $status = $data->statusid,
            $recovergrades = null
        );

        $json->status = $statuses[$data->statusid];
        $json->statusid = $data->statusid;
        $json->role = $roles[$data->roleid];
        $json->roleid = $data->roleid;
        $json->userid = $student->id;

        $notification = get_string('changessaved');

        $transaction->allow_commit();
    } catch (Exception $exception) {
        $transaction->rollback($exception);

        $notification = get_string('error', 'error');
    }
}

$picture = new user_picture($student);
$picture->size = 50;

$json->form = '<div id="apsolu-attendance-ajax-edit-enrolment">';

$json->form .= '<p>' . $OUTPUT->render($picture) . ' ' . $student->firstname . ' ' . $student->lastname . '</p>';

if (isset($notification) === true) {
    if ($notification === get_string('changessaved')) {
        $json->form .= '<p class="alert alert-success">' . get_string('changessaved') . '</p>';
    } else {
        $json->form .= '<p class="alert alert-danger">' . get_string('error', 'error') . '</p>';
    }
}

$json->form .= $mform->render();

$json->form .= '</div>';

echo json_encode($json);
