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

namespace local_apsolu\form\federation;

use context_system;
use core_date;
use local_apsolu\core\federation\adhesion;
use local_apsolu\core\federation\course as FederationCourse;
use local_apsolu\external\email;
use local_apsolu\form\send_email_form;
use stdClass;
use UniversiteRennes2\Apsolu\Payment;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/apsolu/classes/apsolu/payment.php');

/**
 * Modal form to send email.
 *
 * @package   local_apsolu
 * @copyright 2025 Université Rennes 2
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class validate_pass_sport extends send_email_form {
    /**
     * Process the form submission, used if form was submitted via AJAX.
     *
     * @return array
     */
    public function process_dynamic_submission() {
        global $DB;

        $data = $this->get_data();

        // Note: le tableau ne contient qu'un seul utilisateur.
        $user = false;
        $receivers = explode(',', $data->users);

        // Validation du certificat.
        $validation = json_decode($data->jsondata);
        if ($validation === null) {
            return [];
        }

        // Récupère l'id du cours FFSU.
        $federationcourse = new FederationCourse();
        $federationcourseid = $federationcourse->get_courseid();
        if ($federationcourseid === false) {
            return [];
        }

        foreach ($receivers as $userid) {
            $user = $DB->get_record('user', ['id' => $userid]);
            if ($user === false) {
                continue;
            }

            $centers = [];
            foreach (Payment::get_user_cards_status_per_course($federationcourseid, $user->id) as $card) {
                if ($card->status != Payment::DUE) {
                    continue;
                }

                if (isset($centers[$card->centerid]) === false) {
                    $centers[$card->centerid] = new stdClass();
                    $centers[$card->centerid]->amount = 0;
                    $centers[$card->centerid]->items = [];
                }

                $centers[$card->centerid]->amount += $card->price;
                $centers[$card->centerid]->items[$card->id] = $card->id;
            }

            foreach ($centers as $centerid => $center) {
                // Insère un nouveau paiement.
                $payment = new stdClass();
                $payment->method = 'pass';
                $payment->source = 'apsolu';
                $payment->amount = $center->amount;
                $payment->status = Payment::DUE;
                $payment->timepaid = null;
                $payment->timecreated = core_date::strftime('%FT%T');
                $payment->timemodified = $payment->timecreated;
                $payment->userid = $user->id;
                $payment->paymentcenterid = $centerid;

                if ($validation === Payment::PAID) {
                    // Le paiement a été validé.
                    $payment->status = Payment::PAID;
                    $payment->timepaid = $payment->timecreated;
                }

                $payment->id = $DB->insert_record('apsolu_payments', $payment);

                foreach ($center->items as $cardid) {
                    $item = new stdClass();
                    $item->paymentid = $payment->id;
                    $item->cardid = $cardid;

                    $DB->insert_record('apsolu_payments_items', $item);
                }

                // Enregistre un évènement.
                $event = \local_apsolu\event\update_user_payment::create([
                    'relateduserid' => $userid,
                    'context' => context_system::instance(),
                    'other' => ['paymentid' => $payment->id, 'items' => $center->items],
                ]);
                $event->trigger();
            }

            // Met à jour l'adhésion.
            $adhesion = $DB->get_record(Adhesion::TABLENAME, ['userid' => $user->id]);
            if ($adhesion !== false) {
                if ($validation == Adhesion::PASS_SPORT_STATUS_PENDING) {
                    $adhesion->passsportstatus = null;
                    $adhesion->federationnumberrequestdate = null;
                } else if ($validation == Adhesion::PASS_SPORT_STATUS_VALIDATED) {
                    $adhesion->passsportstatus = $validation;
                }
                $DB->update_record(Adhesion::TABLENAME, $adhesion);
            }
        }

        // Envoi de la notification.
        $message = [];
        $message['subject'] = $data->subject;
        $message['carboncopy'] = isset($data->carboncopy);
        $message['carboncopysubject'] = '';
        if ($message['carboncopy'] === true) {
            if ($user !== false) {
                $message['carboncopysubject'] = '[' . $user->firstname . ' ' . $user->lastname . '] ' . $message['subject'];
            }
        }
        $message['body'] = $data->message['text'];
        $message['receivers'] = $receivers;

        $messages = [$message];

        return email::send_instant_emails($messages);
    }
}
