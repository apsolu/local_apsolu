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

namespace local_apsolu\task;

use core_user;
use DateTime;
use exception;
use local_apsolu\core\federation\adhesion as Adhesion;
use local_apsolu\core\federation\course as FederationCourse;
use UniversiteRennes2\Apsolu\Payment;

/**
 * Classe représentant la tâche pour notifier les nouvelles inscriptions à la FFSU.
 *
 * Cette tâche est utile pour notifier les référents fonctionnels après le paiement de l'étudiant.
 *
 * @package    local_apsolu
 * @copyright  2025 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notify_new_federation_adhesions extends \core\task\scheduled_task {
    /**
     * Retourne le nom de la tâche.
     *
     * @return string
     */
    public function get_name() {
        // Shown in admin screens.
        return get_string('task_for_notify_new_federation_adhesions', 'local_apsolu');
    }

    /**
     * Execute la tâche.
     *
     * @return void
     */
    public function execute() {
        global $CFG, $DB;

        require_once($CFG->dirroot.'/local/apsolu/classes/apsolu/payment.php');

        $federationcourse = new FederationCourse();
        $federationcourseid = $federationcourse->get_course();
        if ($federationcourseid === false) {
            return;
        }

        // Traite le nouveau système, où les utilisateurs demande implicitement une licence au moment du paiement.
        $namefields = 'u.' . implode(', u.', core_user\fields::get_name_fields());
        $sql = "SELECT u.id, {$namefields}, u.email, afa.id AS adhesionid
                  FROM {user} u
                  JOIN {apsolu_federation_adhesions} afa ON u.id = afa.userid
                 WHERE u.deleted = 0
                   AND u.suspended = 0
                   AND afa.federationnumberrequestdate IS NOT NULL
                   AND afa.federationnumber IS NULL
              ORDER BY u.lastname, u.firstname";
        $users = $DB->get_records_sql($sql);

        foreach ($users as $user) {
            $adhesion = new Adhesion();
            $adhesion->load($user->adhesionid);

            if (empty($adhesion->id) === true) {
                // Ne devrait jamais arriver...
                continue;
            }

            $due = false;
            $cards = [];
            foreach (Payment::get_user_cards_status_per_course($federationcourse->id, $user->id) as $card) {
                $cards[] = $card->id;

                if ($card->status !== Payment::DUE) {
                    continue;
                }

                $due = true;
            }

            if ($due === true) {
                // Si le paiement est dû, inutile de notifier le référent fonctionnel.
                continue;
            }

            if (isset($cards[0]) === false) {
                // Si aucune carte n'est dûe, inutile de notifier le référent fonctionnel. La notification a déjà été envoyée via
                // l'onglet paiement du formulaire d'inscription à la FFSU.
                continue;
            }

            // On récupère la date des derniers paiements.
            list($insql, $params) = $DB->get_in_or_equal($cards, SQL_PARAMS_NAMED, 'cardid_');
            $sql = "SELECT MAX(ap.timepaid) AS timepaid
                      FROM {apsolu_payments} ap
                      JOIN {apsolu_payments_items} api ON ap.id = api.paymentid
                     WHERE ap.timepaid IS NOT NULL
                       AND ap.userid = :userid
                       AND api.cardid ".$insql;
            $params['userid'] = $user->id;
            $payment = $DB->get_record_sql($sql, $params);

            if ($payment === false) {
                // Si il n'y a pas eu de paiement, il n'est pas nécessaire de notifier le référent fonctionnel.
                continue;
            }

            try {
                $timepaid = new DateTime($payment->timepaid);
            } catch (Exception $exception) {
                mtrace($exception->getMessage());
                continue;
            }

            if ($timepaid->getTimestamp() <= $adhesion->federationnumberrequestdate) {
                // Si la date de demande de licence est plus récente que la date du paiement, il n'est pas nécessaire de notifier
                // le référent fonctionnel. Une notification a déjà été envoyée par le précédent cron.
                continue;
            }

            // On modifie la valeur de "federationnumberrequestdate" afin de ne pas notifier en boucle les contacts fonctionnels.
            $adhesion->federationnumberrequestdate = time();
            $adhesion->save(null, null, $check = false);

            // Envoie une notification au référent fonctionnel.
            $adhesion->notify_functional_contact();

            mtrace(sprintf('notifie de la demande de licence pour #%s %s %s (%s)',
                $user->id, $user->firstname, $user->lastname, $user->email));
        }
    }
}
