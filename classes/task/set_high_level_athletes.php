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
 * Classe représentant la tâche permettant d'alimenter le témoin sportif de haut niveau.
 *
 * @package    local_apsolu
 * @copyright  2017 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_apsolu\task;

use context_system;
use core_date;
use stdClass;
use UniversiteRennes2\Apsolu\Payment;

/**
 * Classe représentant la tâche permettant d'alimenter le témoin sportif de haut niveau.
 *
 * @package    local_apsolu
 * @copyright  2017 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class set_high_level_athletes extends \core\task\scheduled_task {
    /**
     * Retourne le nom de la tâche.
     *
     * @return string
     */
    public function get_name() {
        // Shown in admin screens.
        return get_string('set_high_level_athletes', 'local_apsolu');
    }

    /**
     * Execute la tâche.
     *
     * @return void
     */
    public function execute() {
        global $CFG, $DB;

        if (isset($CFG->is_siuaps_rennes) === false) {
            // TODO: implémenter l'équivalent pour les autres universités.
            return true;
        }

        // TODO: faire une page pour configurer le groupe et le cours (menu déroulant + ids) à synchroniser.
        require_once($CFG->dirroot . '/local/apsolu/classes/apsolu/payment.php');

        $groupingid = 4;
        $courseid = 423;

        $workoutcourseid = 250;

        $field = $DB->get_record('user_info_field', ['shortname' => 'apsoluhighlevelathlete']);
        if ($field === false) {
            mtrace('Le champ "apsoluhighlevelathlete" n\'existe pas.');
            return false;
        }

        $grouping = $DB->get_record('groupings', ['id' => $groupingid, 'courseid' => $courseid]);
        if ($grouping === false) {
            mtrace('Le groupement "athlètes de haut niveau validés" n\'existe pas.');
            return false;
        }

        $cards = $DB->get_records('apsolu_payments_cards');

        $sql = "SELECT DISTINCT gm.userid" .
            " FROM {groups_members} gm" .
            " JOIN {groupings_groups} gg ON gg.groupid = gm.groupid" .
            " WHERE gg.groupingid = :groupingid";
        $members = $DB->get_records_sql($sql, ['groupingid' => $grouping->id]);
        foreach ($members as $member) {
            // Positionne le témoin sportifs de haut niveau dans le profil de l'utilisateur.
            $data = new \stdClass();
            $data->userid = $member->userid;
            $data->fieldid = $field->id;
            $data->data = '1';
            $data->dataformat = '0';

            $conditions = ['userid' => $data->userid, 'fieldid' => $data->fieldid];
            if ($record = $DB->get_record('user_info_data', $conditions)) {
                if ($data->data !== $record->data) {
                    $data->id = $record->id;
                    $DB->update_record('user_info_data', $data);
                    mtrace("\t update " . $field->shortname . " : userid=" . $data->userid . " data=" . $data->data);
                }
            } else {
                $DB->insert_record('user_info_data', $data);
                mtrace("\t insert " . $field->shortname . " : userid=" . $data->userid . " data=" . $data->data);
            }

            // Offre l'accès à la salle de musculation aux sportifs de haut niveau.
            $items = [];
            foreach (Payment::get_user_cards_status_per_course($workoutcourseid, $member->userid) as $card) {
                if ($card->status != Payment::DUE) {
                    continue;
                }

                if (isset($cards[$card->id]) === false) {
                    continue;
                }

                $centerid = $cards[$card->id]->centerid;
                if (isset($items[$centerid]) === false) {
                    $items[$centerid] = [];
                }

                $items[$centerid][] = $card->id;
            }

            foreach ($items as $centerid => $cardsid) {
                $payment = new stdClass();
                $payment->id = 0;
                $payment->method = 'coins';
                $payment->source = 'apsolu';
                $payment->amount = 0;
                $payment->status = Payment::GIFT;
                $payment->timepaid = '';
                $payment->timecreated = core_date::strftime('%FT%T');
                $payment->timemodified = $payment->timecreated;
                $payment->timepaid = $payment->timecreated;
                $payment->userid = $member->userid;
                $payment->paymentcenterid = $centerid;

                try {
                    $transaction = $DB->start_delegated_transaction();

                    $payment->id = $DB->insert_record('apsolu_payments', $payment);
                    mtrace("\t insert apsolu_payments: userid=" . $member->userid . " status=gift, centerid=" . $centerid);

                    foreach ($cardsid as $cardid) {
                        $item = new stdClass();
                        $item->paymentid = $payment->id;
                        $item->cardid = $cardid;

                        $DB->insert_record('apsolu_payments_items', $item);
                        mtrace("\t insert apsolu_payments_items: paymentid=" . $payment->id . ", cardid=" . $cardid);
                    }

                    $event = \local_apsolu\event\update_user_payment::create([
                        'relateduserid' => $member->userid,
                        'context' => context_system::instance(),
                        'other' => ['payment' => $payment, 'items' => $cardsid],
                    ]);
                    $event->trigger();

                    $success = true;

                    $transaction->allow_commit();
                } catch (Exception $exception) {
                    $success = false;
                    $transaction->rollback($exception);
                }
            }
        }

        // Supprime le témoin sportifs de haut niveau.
        $datas = $DB->get_records('user_info_data', ['fieldid' => $field->id]);
        foreach ($datas as $data) {
            if ($data->data !== '1') {
                continue; // Ne traiter que les comptes marqués haut niveau.
            }

            if (isset($members[$data->userid]) === false) {
                $data->data = 0;
                $DB->update_record('user_info_data', $data);
                mtrace("\t remove " . $field->shortname . " : userid=" . $data->userid . " data=" . $data->data);
            }
        }
    }
}
