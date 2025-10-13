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
 * External email API
 *
 * @package   local_apsolu
 * @copyright 2023 Université Rennes 2 {@link https://www.univ-rennes2.fr}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_apsolu\external;

use context_system;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/externallib.php');

/**
 * Email external functions
 *
 * @package   local_apsolu
 * @copyright 2023 Université Rennes 2 {@link https://www.univ-rennes2.fr}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class email extends external_api {
    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function send_instant_emails_parameters() {
        return new external_function_parameters(
            [
                'messages' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'subject' => new external_value(PARAM_TEXT, 'the subject of the email'),
                            'carboncopy' => new external_value(
                                PARAM_BOOL,
                                'Send a copy of the email to sender',
                                VALUE_DEFAULT,
                                true
                            ),
                            'carboncopysubject' => new external_value(PARAM_TEXT, 'the subject of the email for carbon copy ; if
                                    empty, the main subject will be used.'),
                            'body' => new external_value(PARAM_RAW, 'the text of the email'),
                            'receivers' => new external_multiple_structure(
                                new external_value(PARAM_INT, 'id of the user to send the private email')
                            ),
                        ]
                    )
                ),
            ]
        );
    }

    /**
     * Send private emails from the current user to other users.
     *
     * @param array $messages An array of message to send.
     * @return array
     */
    public static function send_instant_emails($messages = []) {
        global $CFG, $USER, $DB;

        // Ensure the current user is allowed to run this function.
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:sendmessage', $context);

        $params = self::validate_parameters(self::send_instant_emails_parameters(), ['messages' => $messages]);

        $resultmessages = [];
        foreach ($params['messages'] as $message) {
            // Set email data.
            $subject = $message['subject'];
            $body = $message['body'];

            // Retrieve all tousers of the messages.
            $receivers = $message['receivers'];

            // Handle carbon copy and prevent to send email twice if current user is already in receivers list.
            if ($message['carboncopy'] && !in_array($USER->id, $receivers)) {
                $receivers[] = $USER->id;
            }

            [$sqluserids, $sqlparams] = $DB->get_in_or_equal($receivers, SQL_PARAMS_NAMED, 'userid_');
            $tousers = $DB->get_records_select("user", "id " . $sqluserids . " AND deleted = 0", $sqlparams);

            foreach ($receivers as $receiver) {
                $resultmsg = []; // The info about the success of the operation.

                // We are going to do some checking.
                // Code should match /messages/index.php checks.
                $success = true;

                // Check the user exists.
                if (empty($tousers[$receiver])) {
                    $success = false;
                    $errormessage = get_string('touserdoesntexist', 'message', $receiver);
                }

                // Now we can send the message (at least try).
                if ($success) {
                    if ($message['carboncopy'] && $receiver === $USER->id && empty($message['carboncopysubject']) === false) {
                        // Message avec un sujet spécial pour la copie carbone.
                        $success = email_to_user($tousers[$receiver], $USER, $message['carboncopysubject'], $body);
                    } else {
                        $success = email_to_user($tousers[$receiver], $USER, $subject, $body);
                    }
                }

                // Build the resultmsg.
                if ($success) {
                    $resultmsg['msgid'] = $success;
                } else {
                    // WARNINGS: for backward compatibility we return this errormessage.
                    // We should have thrown exceptions as these errors prevent results to be returned.
                    // See http://docs.moodle.org/dev/Errors_handling_in_web_services#When_to_send_a_warning_on_the_server_side .
                    $resultmsg['msgid'] = -1;
                    $resultmsg['errormessage'] = $errormessage;
                }

                $resultmessages[] = $resultmsg;
            }
        }

        return $resultmessages;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.8
     */
    public static function send_instant_emails_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                [
                    'msgid' => new external_value(
                        PARAM_INT,
                        'test this to know if it succeeds:  id of the created message if it succeeded, -1 when failed'
                    ),
                    'errormessage' => new external_value(PARAM_TEXT, 'error message - if it failed', VALUE_OPTIONAL),
                ]
            )
        );
    }
}
