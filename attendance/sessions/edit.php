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
 * @copyright  2017 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require(__DIR__ . '/edit_form.php');

$template = 'local_apsolu/attendance_sessions_form';

// Get session id.
$sessionid = optional_param('sessionid', 0, PARAM_INT);

// Generate object.
$session = false;
if ($sessionid != 0) {
    $session = $DB->get_record('apsolu_attendance_sessions', ['id' => $sessionid]);
}

if ($session === false) {
    $apsolu_course = $DB->get_record('apsolu_courses', ['id' => $courseid]);

    $sessions = $DB->get_records('apsolu_attendance_sessions', ['courseid' => $courseid]);
    $name = 'Cours n°'.(count($sessions) + 1);

    $instance = new stdClass();
    $instance->sessionid = 0;
    $instance->name = $name;
    $instance->sessiontime = 0;
    $instance->courseid = $course->id;
    $instance->activityid = $course->category;
    $instance->locationid = $apsolu_course->locationid;
} else {
    $instance = clone $session;
    $instance->sessionid = $session->id;
}

$instance->notify = 1; // Default: notify students.

// Load locations.
$locations = [];
foreach ($DB->get_records('apsolu_locations', $conditions = null, $sort = 'name') as $location) {
    $locations[$location->id] = $location->name;
}

// Build form.
$customdata = ['session' => $instance, 'locations' => $locations];
$mform = new local_apsolu_attendance_sessions_edit_form(null, $customdata);

if ($mdata = $mform->get_data()) {
    // Save data.
    $session = new stdClass();
    $session->id = $mdata->sessionid;
    $session->name = $mdata->name;
    $session->sessiontime = $mdata->sessiontime;
    $session->locationid = $mdata->locationid;
    $session->courseid = $course->id;
    $session->activityid = $course->category;

    if ($session->id === 0) {
        $session->id = $DB->insert_record('apsolu_attendance_sessions', $session);
    } else {
        $DB->update_record('apsolu_attendance_sessions', $session);
    }

    // Display notification and display elements list.
    $notifications[] = $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');

    // Détermine si la session a changé de date et/ou de lieu.
    $changed = ($instance->sessiontime != $session->sessiontime || $instance->locationid != $session->locationid);

    // Prépare les variables pour les notifications.
    $variables = (object) ['datetime' => userdate($session->sessiontime, get_string('strftimedatetime', 'local_apsolu')), 'location' => $locations[$session->locationid]];
    if ($instance->sessionid === 0) {
        $subject = get_string('attendance_forum_create_session_subject', 'local_apsolu', userdate($session->sessiontime, get_string('strftimedate', 'local_apsolu')));
        $message = get_string('attendance_forum_create_session_message', 'local_apsolu', $variables);
    } else {
        $subject = get_string('attendance_forum_edit_session_subject', 'local_apsolu', userdate($session->sessiontime, get_string('strftimedate', 'local_apsolu')));
        $message = get_string('attendance_forum_edit_session_message', 'local_apsolu', $variables);
    }

    // Notifie le forum.
    if (empty($mdata->notify) === false) {
        if ($changed === false) {
            $notifications[] = $OUTPUT->notification(get_string('attendance_error_no_modification', 'local_apsolu'), 'notifymessage');
        }

        $forum = $DB->get_record('forum', ['type' => 'news', 'course' => $course->id]);
        if ($forum === false) {
            $notifications[] = $OUTPUT->notification(get_string('attendance_error_no_news_forum', 'local_apsolu'), 'notifyproblem');
        }

        if ($changed === true && $forum !== false) {
            require_once($CFG->dirroot . '/mod/forum/lib.php');

            list($course, $cm) = get_course_and_cm_from_instance($forum, 'forum');
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
                $notifications[] = $OUTPUT->notification(get_string('attendance_success_message_forum', 'local_apsolu'), 'notifysuccess');
            } else {
                $notifications[] = $OUTPUT->notification(get_string('attendance_error_message_forum', 'local_apsolu'), 'notifyproblem');
            }
        }
    }

    // Notifie le secrétariat.
    if ($changed === true) {
        $functional_contact_mail = get_config('local_apsolu', 'functional_contact');
        if (filter_var($functional_contact_mail, FILTER_VALIDATE_EMAIL) !== false) {
            if (isset($CFG->divertallemailsto) === true && filter_var($CFG->divertallemailsto, FILTER_VALIDATE_EMAIL) !== false) {
                $functional_contact_mail = $CFG->divertallemailsto;
            }

            require_once($CFG->libdir . '/phpmailer/moodle_phpmailer.php');

            $mailer = new moodle_phpmailer();
            $mailer->AddAddress($functional_contact_mail);
            $mailer->Subject = $subject.' ('.$course->fullname.')';
            $mailer->Body = $message;
            if (isset($cm->id) === true) {
                $mailer->Body .= '<p><a href="'.$CFG->wwwroot.'/mod/forum/view.php?id='.$cm->id.'">'.get_string('postincontext', 'forum').'</a></p>';
            } else {
                $mailer->Body .= '<p><strong>'.get_string('no_messages_sent_to_forum', 'local_apsolu').'</strong></p>';
            }
            $mailer->From = $CFG->noreplyaddress;
            $mailer->FromName = '';
            $mailer->CharSet = 'UTF-8';
            $mailer->isHTML();
            $mailer->Send();
        }
    }

    require(__DIR__ . '/view.php');
} else {
    // Display form.
    $data->form = $mform->render();
}
