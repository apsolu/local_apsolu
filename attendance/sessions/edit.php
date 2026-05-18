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
 * Page d'édition des sessions de cours.
 *
 * @package    local_apsolu
 * @copyright  2017 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\attendancesession;
use local_apsolu\core\course;
use local_apsolu\core\messaging;

defined('MOODLE_INTERNAL') || die;

require(__DIR__ . '/edit_form.php');

$template = 'local_apsolu/attendance_sessions_form';

// Get session id.
$sessionid = optional_param('sessionid', 0, PARAM_INT);
$PAGE->set_url('/local/apsolu/attendance/index.php', [
    'page' => 'sessions',
    'action' => 'edit',
    'courseid' => $courseid,
    'sessionid' => $sessionid,
]);

// Generate object.
$session = new AttendanceSession();
if ($sessionid !== 0) {
    $session->load($sessionid);
}

if (empty($session->id) === true) {
    $apsolucourse = $DB->get_record('apsolu_courses', ['id' => $courseid]);

    $sessions = $DB->get_records('apsolu_attendance_sessions', ['courseid' => $courseid]);
    $name = 'Cours n°' . (count($sessions) + 1);

    $session->name = $name;
    $session->sessiontime = 0;
    $session->duration = course::getDuration($apsolucourse->starttime, $apsolucourse->endtime);
    $session->courseid = $course->id;
    $session->locationid = $apsolucourse->locationid;
}

// Load locations.
$locations = [];
foreach ($DB->get_records('apsolu_locations', $conditions = null, $sort = 'name') as $location) {
    $locations[$location->id] = $location->name;
}

// Build form.
$customdata = ['session' => $session, 'locations' => $locations];
$mform = new local_apsolu_attendance_sessions_edit_form($PAGE->url->out(false), $customdata);

if ($mdata = $mform->get_data()) {
    // Save data.
    $instance = clone $session;
    $session->save($mdata);

    // Display notification and display elements list.
    $notifications[] = $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');

    // Détermine si la session a changé de date et/ou de lieu.
    $changed = (
        $instance->sessiontime != $session->sessiontime ||
        $instance->duration != $session->duration ||
        $instance->locationid != $session->locationid
    );

    // Prépare les variables pour les notifications.
    $variables = (object) [
        'datetime' => userdate($session->sessiontime, get_string('strftimedatetime', 'local_apsolu')),
        'duration' => get_string('X_minutes', 'local_apsolu', $session->duration / 60),
        'location' => $locations[$session->locationid],
    ];
    if ($instance->id === 0) {
        $subject = get_string(
            'attendance_forum_create_session_subject',
            'local_apsolu',
            userdate($session->sessiontime, get_string('strftimedate', 'local_apsolu'))
        );
        $message = get_string('attendance_forum_create_session_message', 'local_apsolu', $variables);
    } else {
        $subject = get_string(
            'attendance_forum_edit_session_subject',
            'local_apsolu',
            userdate($session->sessiontime, get_string('strftimedate', 'local_apsolu'))
        );
        $message = get_string('attendance_forum_edit_session_message', 'local_apsolu', $variables);
    }

    // Notifie le forum.
    if (empty($mdata->notify) === false) {
        if ($changed === false) {
            $notifications[] = $OUTPUT->notification(
                get_string('attendance_error_no_modification', 'local_apsolu'),
                'notifymessage'
            );
        }

        $forum = $DB->get_record('forum', ['type' => 'news', 'course' => $course->id]);
        if ($forum === false) {
            $notifications[] = $OUTPUT->notification(get_string('attendance_error_no_news_forum', 'local_apsolu'), 'notifyproblem');
        }

        if ($changed === true && $forum !== false) {
            require_once($CFG->dirroot . '/mod/forum/lib.php');

            [$course, $cm] = get_course_and_cm_from_instance($forum, 'forum');
            $context = context_module::instance($cm->id);

            // Create the discussion.
            $discussion = new stdClass();
            $discussion->course = $course->id;
            $discussion->forum = $forum->id;
            $discussion->message = $message;
            $discussion->messageformat = FORMAT_HTML;   // Force formatting for now.
            $discussion->messagetrust = trusttext_trusted($context);
            $discussion->itemid = [];
            $discussion->groupid = 0;
            $discussion->mailnow = 0;
            $discussion->subject = $subject;
            $discussion->name = $subject;
            $discussion->timestart = 0;
            $discussion->timeend = 0;
            $discussion->attachments = [];
            $discussion->pinned = FORUM_DISCUSSION_UNPINNED;
            $fakemform = [];
            if ($discussionid = forum_add_discussion($discussion, $fakemform)) {
                $notifications[] = $OUTPUT->notification(
                    get_string('attendance_success_message_forum', 'local_apsolu'),
                    'notifysuccess'
                );
            } else {
                $notifications[] = $OUTPUT->notification(
                    get_string('attendance_error_message_forum', 'local_apsolu'),
                    'notifyproblem'
                );
            }
        }
    }

    // Notifie le secrétariat.
    if ($changed === true) {
        if (isset($cm->id) === true) {
            $body = $message . '<p><a href="' . $CFG->wwwroot . '/mod/forum/view.php?id=' . $cm->id . '">' .
                get_string('postincontext', 'forum') . '</a></p>';
        } else {
            $body = $message . '<p><strong>' . get_string('no_messages_sent_to_forum', 'local_apsolu') . '</strong></p>';
        }

        messaging::notify_functional_address($subject . ' (' . $course->fullname . ')', $body);
    }

    require(__DIR__ . '/view.php');
} else {
    // Display form.
    $data->form = $mform->render();
}
