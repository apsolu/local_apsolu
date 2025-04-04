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
 * Classe représentant la tâche pour notifier les utilisateurs d'un paiement dû.
 *
 * @package    local_apsolu
 * @copyright  2017 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_apsolu\task;

use stdClass;
use UniversiteRennes2\Apsolu\Payment as Payment;

/**
 * Classe représentant la tâche pour notifier les utilisateurs d'un paiement dû.
 *
 * @package    local_apsolu
 * @copyright  2017 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_dunnings extends \core\task\scheduled_task {
    /**
     * Retourne le nom de la tâche.
     *
     * @return string
     */
    public function get_name() {
        // Shown in admin screens.
        return get_string('task_send_dunnings', 'local_apsolu');
    }

    /**
     * Execute la tâche.
     *
     * @return void
     */
    public function execute() {
        global $CFG, $DB;

        require_once($CFG->dirroot.'/local/apsolu/classes/apsolu/payment.php');

        $dunnings = $DB->get_records('apsolu_dunnings', ['timestarted' => null]);

        if (count($dunnings) === 0) {
            return;
        }

        foreach ($dunnings as $dunning) {
            mtrace('relance du '.
                userdate($dunning->timecreated, get_string('strftimedatetime', 'local_apsolu')).' intitulée '.$dunning->subject);

            $dunning->timestarted = time();
            $DB->update_record('apsolu_dunnings', $dunning);

            $dunning->messagetext = strip_tags(str_replace('</p>', PHP_EOL, $dunning->message));

            $simulation = (substr($dunning->subject, 0, 4) === '[x] ');

            $sender = $DB->get_record('user', ['id' => $dunning->userid]);
            $receivers = [];

            $sql = "SELECT apc.*".
                " FROM {apsolu_payments_cards} apc".
                " JOIN {apsolu_dunnings_cards} adc ON apc.id = adc.cardid".
                " WHERE adc.dunningid = :dunningid";
            $cards = $DB->get_records_sql($sql, ['dunningid' => $dunning->id]);
            foreach ($cards as $card) {
                mtrace(' - carte '.$card->fullname);

                $users = Payment::get_card_users($card->id);
                foreach ($users as $user) {
                    $status = Payment::get_user_card_status($card, $user->id);
                    if ($status === Payment::DUE) {
                        if (isset($receivers[$user->id]) === false) {
                            $receivers[$user->id] = 1;

                            if ($simulation === false) {
                                email_to_user($user, $sender, $dunning->subject, $dunning->messagetext, $dunning->message);

                                mtrace('   - relance envoyée à '.$user->email.
                                    ' (#'.$user->id.' '.$user->firstname.' '.$user->lastname.')');
                            }

                            $post = new stdClass();
                            $post->timecreated = time();
                            $post->dunningid = $dunning->id;
                            $post->userid = $user->id;
                            $DB->insert_record('apsolu_dunnings_posts', $post);
                        }
                    }
                }
            }

            $dunning->timeended = time();
            $DB->update_record('apsolu_dunnings', $dunning);

            if ($simulation === true) {
                mtrace('=> '.count($receivers).' envois simulés.');
            } else {
                mtrace('=> '.count($receivers).' relances envoyées.');
            }
        }
    }
}
