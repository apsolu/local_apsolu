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
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use UniversiteRennes2\Apsolu as apsolu;

define('AJAX_SCRIPT', true);

require_once(__DIR__.'/../../../../config.php');
require($CFG->dirroot.'/enrol/select/lib.php');
require(__DIR__.'/edit_enrolment_form.php');

$userid = required_param('userid', PARAM_INT); // Course id.
$courseid = required_param('courseid', PARAM_INT); // Course id.
$listid = required_param('listid', PARAM_INT); // List id (user enrolment status).
$ueid = required_param('ueid', PARAM_INT); // User enrolement id.
$roleid = required_param('roleid', PARAM_INT); // Role id.
$raid = required_param('raid', PARAM_INT); // Role assignment id.

// Set permissions.
require_login();

$student = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);

$context = context_course::instance($courseid);

$PAGE->set_context($context);

$data = (object) ['userid' => $userid, 'courseid' => $courseid, 'listid' => $listid, 'ueid' => $ueid, 'roleid' => $roleid, 'raid' => $raid];
$lists = array();
foreach (enrol_select_plugin::$states as $stateid => $state) {
    $lists[$stateid] = enrol_select_plugin::get_enrolment_list_name($stateid);
}

$roles = array();
foreach (apsolu\get_custom_student_roles() as $role) {
    $roles[$role->id] = $role->name;
}

$json = new stdClass();

// Initialize form.
$url = new moodle_url('/local/apsolu/attendance/ajax/edit_enrolment.php');
$params = array($data, $lists, $roles);

$mform = new edit_enrolment_form($url, $params);

// Handle data form.
if ($data = $mform->get_data()) {
    try {
        $transaction = $DB->start_delegated_transaction();

        $sql = "UPDATE {user_enrolments} SET status = :status WHERE id = :id AND userid = :userid";
        $DB->execute($sql, array('status' => $data->listid, 'id' => $data->ueid, 'userid' => $userid));

        $sql = "UPDATE {role_assignments} SET roleid = :roleid WHERE id = :id AND userid = :userid";
        $DB->execute($sql, array('roleid' => $data->roleid, 'id' => $data->raid, 'userid' => $userid));

        $json->list = $lists[$data->listid];
        $json->listid = $data->listid;
        $json->ueid = $data->ueid;
        $json->role = $roles[$data->roleid];
        $json->roleid = $data->roleid;
        $json->raid = $data->raid;

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

$json->form .= '<p>'.$OUTPUT->render($picture).' '.$student->firstname.' '.$student->lastname.'</p>';

if (isset($notification) === true) {
    if ($notification === get_string('changessaved')) {
        $json->form .= '<p class="alert alert-success">'.get_string('changessaved').'</p>';
    } else {
        $json->form .= '<p class="alert alert-danger">'.get_string('error', 'error').'</p>';
    }
}

$json->form .= $mform->render();

$json->form .= '</div>';

echo json_encode($json);
