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
require_once($CFG->dirroot.'/local/apsolu/reports/notify_form.php');

if (!isset($_POST['users'])) {
    $_POST['users'] = array();
}

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/local/apsolu/reports/notify.php');
$PAGE->set_title(get_string('reports_mystudents', 'local_apsolu'));

// Navigation.
$PAGE->navbar->add(get_string('reports_mystudents', 'local_apsolu'));

require_login();

// Load courses.
$courses = array('*' => get_string('all'));
$is_manager = $DB->get_record('role_assignments', array('contextid' => 1, 'roleid' => 1, 'userid' => $USER->id));

if (!$is_manager) {
    $is_manager = is_siteadmin();
}

if (!$is_manager) {
    // Check if is teacher.
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

$users = array();
foreach ($_POST['users'] as $userid) {
    $user = $DB->get_record('user', array('id' => $userid, 'deleted' => 0));

    if ($user) {
        $users[$user->id] = $user;
    }
}

if ($users === array()) {
    print_error('usernotavailable');
}

if (!isset($users[$USER->id])) {
    $users[$USER->id] = $USER;
}

$mform = new local_apsolu_notify_form(null, array($users));

if ($mform->is_cancelled()) {
    redirect($return);
} else if ($data = $mform->get_data()) {
    if (!empty($data->message)) {
        if (empty($data->subject)) {
            $data->subject = get_string('defaultnotifysubject', 'local_apsolu');
        }

        foreach ($users as $user) {
            // TODO: eventdata as \stdClass is deprecated. Please use \core\message\message instead.
            $eventdata = (object) array(
                'name' => 'select_notification',
                'component' => 'enrol_select',
                'userfrom' => $USER,
                'userto' => $user,
                'subject' => $data->subject,
                'fullmessage' => $data->message,
                'fullmessageformat' => FORMAT_PLAIN,
                'fullmessagehtml' => null,
                'smallmessage' => '',
                // TODO: revoir ces nouveaux paramètres !
                'context' => context_system::instance(),
                'courseid' => 1,
            );

            message_send($eventdata);
        }

        $url = $CFG->wwwroot.'/blocks/apsolu_teachers/extractions.php';
        redirect($url, 'Les utilisateurs ont été notifiés.', 5, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        $url = $CFG->wwwroot.'/blocks/apsolu_teachers/extractions.php';
        redirect($url, 'Le message ne peut pas être vide.', 5, \core\output\notification::NOTIFY_ERROR);
    }
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
