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
 * @copyright  2017 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

// Get session id.
$sessionid = required_param('sessionid', PARAM_INT);
$delete = optional_param('delete', 0, PARAM_INT); // Confirmation hash.

$url = new moodle_url('/local/apsolu/attendance/sessions/index.php', array('action' => 'delete', 'courseid' => $courseid, 'sessionid' => $sessionid));

$session = $DB->get_record('apsolu_attendance_sessions', array('id' => $sessionid, 'courseid' => $courseid), '*', MUST_EXIST);
$presences = $DB->get_records('apsolu_attendance_presences', array('sessionid' => $session->id));

$template = 'local_apsolu/attendance_sessions_form';

if (count($presences) > 0) {
    $notifications[] = $OUTPUT->notification(get_string('attendance_undeletable_session', 'local_apsolu', $session->name), 'notifyproblem');

    require(__DIR__.'/view.php');
} else if ($delete === 1) {
    // We do - time to delete the course.
    require_sesskey();

    try {
        $transaction = $DB->start_delegated_transaction();

        $DB->delete_records('apsolu_attendance_presences', array('sessionid' => $session->id));
        $DB->delete_records('apsolu_attendance_sessions', array('id' => $session->id));

        $notifications[] = $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');

        // Notification.
        require_once($CFG->dirroot.'/mod/forum/lib.php');

        $forum = $DB->get_record('forum', array('type' => 'news', 'course' => $course->id));
        if ($forum === false) {
            $notifications[] = $OUTPUT->notification(get_string('attendance_error_no_news_forum', 'local_apsolu'), 'notifyproblem');
        } else {
            // Load locations.
            $locations = array();
            foreach ($DB->get_records('apsolu_locations', $conditions = null, $sort = 'name') as $location) {
                $locations[$location->id] = $location->name;
            }

            list($course, $cm) = get_course_and_cm_from_instance($forum, 'forum');
            $context = context_module::instance($cm->id);

            $sessiontime = userdate($session->sessiontime, get_string('strftimedate', 'local_apsolu'));
            $variables = (object) ['datetime' => userdate($session->sessiontime, get_string('strftimedatetime', 'local_apsolu')), 'location' => $locations[$session->locationid]];

            $subject = get_string('attendance_forum_delete_session_subject', 'local_apsolu', $sessiontime);
            $message = get_string('attendance_forum_delete_session_message', 'local_apsolu', $variables);

            // Create the discussion.
            $discussion = new stdClass();
            $discussion->course = $course->id;
            $discussion->forum = $forum->id;
            $discussion->message = $message;
            $discussion->messageformat = FORMAT_HTML;   // Force formatting for now.
            $discussion->messagetrust = trusttext_trusted($context);
            $discussion->itemid = array();
            $discussion->groupid = 0;
            $discussion->mailnow = 0;
            $discussion->subject = $subject;
            $discussion->name = $subject;
            $discussion->timestart = 0;
            $discussion->timeend = 0;
            $discussion->attachments = array();
            $discussion->pinned = FORUM_DISCUSSION_UNPINNED;
            $fakemform = array();
            if ($discussionid = forum_add_discussion($discussion, $fakemform)) {
                $notifications[] = $OUTPUT->notification(get_string('attendance_success_message_forum', 'local_apsolu'), 'notifysuccess');
            } else {
                $notifications[] = $OUTPUT->notification(get_string('attendance_error_message_forum', 'local_apsolu'), 'notifyproblem');
            }

            // Notifie le secrétariat.
            $functional_contact_mail = get_config('local_apsolu', 'functional_contact');
            if (filter_var($functional_contact_mail, FILTER_VALIDATE_EMAIL) !== false) {
                if (isset($CFG->divertallemailsto) === true) {
                    $functional_contact_mail = $CFG->divertallemailsto;
                }

                require_once $CFG->libdir.'/phpmailer/moodle_phpmailer.php';

                $mailer = new moodle_phpmailer();
                $mailer->AddAddress($functional_contact_mail);
                $mailer->Subject = $subject.' ('.$course->fullname.')';
                $mailer->Body = $message.'<p><a href="'.$CFG->wwwroot.'/mod/forum/view.php?id='.$cm->id.'">Voir le message</a></p>';
                $mailer->From = $CFG->noreplyaddress;
                $mailer->FromName = '';
                $mailer->CharSet = 'UTF-8';
                $mailer->isHTML();
                $mailer->Send();
            }
        }

        $transaction->allow_commit();
    } catch (Exception $exception) {
        $notifications[] = $OUTPUT->notification(get_string('unknownerror'), 'notifyproblem');

        $transaction->rollback($exception);
    }

    require(__DIR__.'/view.php');
} else {
    // Affichage du formulaire de confirmation.
    $message = $OUTPUT->notification(get_string('attendance_delete_session', 'local_apsolu', $session->name), 'notifyproblem');

    $urlarguments = array('action' => 'delete', 'courseid' => $course->id, 'sessionid' => $sessionid, 'delete' => 1);
    $confirmurl = new moodle_url('/local/apsolu/attendance/sessions/index.php', $urlarguments);
    $confirmbutton = new single_button($confirmurl, get_string('delete'), 'post');

    $cancelurl = new moodle_url('/local/apsolu/attendance/sessions/index.php', array('courseid' => $courseid));

    $data->form = $OUTPUT->confirm($message, $confirmbutton, $cancelurl);
}
