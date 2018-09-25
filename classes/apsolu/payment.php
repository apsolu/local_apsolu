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
 * Fonctions pour le module apsolu.
 *
 * @package    local_apsolu
 * @copyright  2018 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace UniversiteRennes2\Apsolu;

class Payment {
    const DUE = 0;
    const PAID = 1;
    const FREE = 2;
    const GIFT = 3;

    public static function get_user_cards($paid = null, $userid = null) {
        global $DB, $USER;

        if ($userid === null) {
            $userid = $USER->id;
        }

        $sql = "SELECT DISTINCT apc.id, apc.name, apc.fullname, apc.trial, apc.price, apc.centerid".
            " FROM {apsolu_payments_cards} apc".
            " JOIN {enrol_select_cards} esc ON esc.cardid = apc.id".
            " JOIN {enrol} e ON e.id = esc.enrolid".
            " JOIN {course} c ON c.id = e.courseid".
            // Check cohorts.
            " JOIN {enrol_select_cohorts} ewc ON e.id = ewc.enrolid".
            " JOIN {cohort_members} cm ON cm.cohortid = ewc.cohortid".
            " JOIN {user_enrolments} ue ON e.id = ue.enrolid AND ue.userid = cm.userid".
            " JOIN {role_assignments} ra ON ra.userid = ue.userid AND ra.userid = cm.userid AND ra.itemid = e.id".
            " JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50 AND ctx.instanceid = c.id".
            " JOIN {apsolu_colleges} acol ON acol.roleid = ra.roleid".
            " JOIN {apsolu_colleges_members} acm ON acol.id = acm.collegeid AND acm.cohortid = cm.cohortid".
            //
            " JOIN {apsolu_payments_cards_cohort} apcc ON apc.id = apcc.cardid AND apcc.cohortid = cm.cohortid".
            " JOIN {apsolu_payments_cards_roles} apcr ON apc.id = apcr.cardid AND apcr.roleid = ra.roleid".
            " WHERE e.enrol = 'select'".
            " AND c.visible = 1". // Cours visible.
            " AND e.status = 0". // Méthode d'inscription active.
            " AND ue.status = 0". // Inscription validée.
            " AND cm.userid = :userid".
            " ORDER BY apc.fullname";
        return $DB->get_records_sql($sql, array('userid' => $userid));
    }

    public static function get_user_enrols_by_card($cardid, $userid = null) {
        global $DB, $USER;

        if ($userid === null) {
            $userid = $USER->id;
        }

        $sql = "SELECT DISTINCT e.*".
            " FROM {enrol} e".
            " JOIN {enrol_select_cards} esc ON e.id = esc.enrolid".
            " JOIN {course} c ON c.id = e.courseid".
            // Check cohorts.
            " JOIN {enrol_select_cohorts} ewc ON e.id = ewc.enrolid".
            " JOIN {cohort_members} cm ON cm.cohortid = ewc.cohortid".
            " JOIN {user_enrolments} ue ON e.id = ue.enrolid AND ue.userid = cm.userid".
            " JOIN {role_assignments} ra ON ra.userid = ue.userid AND ra.userid = cm.userid AND ra.itemid = e.id".
            " JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50 AND ctx.instanceid = c.id".
            " JOIN {apsolu_colleges} acol ON acol.roleid = ra.roleid".
            " JOIN {apsolu_colleges_members} acm ON acol.id = acm.collegeid AND acm.cohortid = cm.cohortid".
            " WHERE e.enrol = 'select'".
            " AND c.visible = 1". // Cours visible.
            " AND e.status = 0". // Méthode d'inscription active.
            " AND ue.status = 0". // Inscription validée.
            " AND cm.userid = :userid".
            " AND esc.cardid = :cardid";
        return $DB->get_records_sql($sql, array('cardid' => $cardid, 'userid' => $userid));
    }

    public static function get_user_card_status($card, $userid = null) {
        global $DB, $USER;

        if ($userid === null) {
            $userid = $USER->id;
        }

        // Vérifie si la carte a déjà été payée.
        $sql = "SELECT ap.*".
            " FROM {apsolu_payments} ap".
            " JOIN {apsolu_payments_items} api ON ap.id = api.paymentid".
            " WHERE api.cardid = :cardid".
            " AND ap.timepaid IS NOT NULL";
        $payment = $DB->get_record_sql($sql, array('cardid' => $card->id));
        if ($payment !== false) {
            debugging('Carte '.$card->fullname.' payée !');
            return $payment->status; // self::PAID or self::GIFT.
        }

        $enrols = Payment::get_user_enrols_by_card($card->id);

        // Vérifie les séances d'essais.
        if ($card->trial > 0) {
            foreach ($enrols as $enrol) {
                // TODO: mauvais component.
                // TODO: n'utilise pas un champ indexé ! ÇA RAME !
                $conditions = array('component' => 'local_apsolu_presence', 'courseid' => $enrol->courseid, 'relateduserid' => $userid);
                if ($DB->count_records('logstore_standard_log', $conditions) >= $card->trial) {
                    debugging('Carte '.$card->fullname.' due (fin des séances d\'essais).');
                    return self::DUE;
                }
            }

            return self::FREE;
        }

        // Vérifie les activités offertes.
        $calendars = $DB->get_records('apsolu_calendars');

        $enrolcalendars = array();
        foreach ($enrols as $enrol) {
            if (isset($calendars[$enrol->customchar1]) === false) {
                continue;
            }

            $calendartypeid = $calendars[$enrol->customchar1]->typeid;
            if (isset($enrolcalendars[$calendartypeid]) === false) {
                $enrolcalendars[$calendartypeid] = 0;
            }
            $enrolcalendars[$calendartypeid]++;
        }

        $calendars = $DB->get_records('apsolu_payments_cards_cals', array('cardid' => $card->id), $sort = '', $fields = 'calendartypeid, value');
        foreach ($calendars as $calendar) {
            if (isset($enrolcalendars[$calendar->calendartypeid]) === false) {
                continue;
            }

            if ($enrolcalendars[$calendar->calendartypeid] > $calendar->value) {
                debugging('Carte '.$card->fullname.' due (nombre d\'inscriptions offertes dépasées).');
                return self::DUE;
            }
        }

        return self::FREE;
    }

    public static function get_paybox_settings($payment) {
        global $CFG, $DB, $USER;

        $center = $DB->get_record('apsolu_payments_centers', array('id' => $payment->paymentcenterid), '*', MUST_EXIST);

        // Variables paybox.
        $paybox = new \stdClass();

        // Numéro de site (fourni par Paybox).
        $paybox->PBX_SITE = $center->sitenumber;

        // Numéro de rang (fourni par Paybox).
        $paybox->PBX_RANG = $center->rank;

        // Identifiant interne (fourni par Paybox).
        $paybox->PBX_IDENTIFIANT = $center->idnumber;

        // Montant total de la transaction.
        $paybox->PBX_TOTAL = $payment->amount * 100;

        // Devise de la transaction.
        $paybox->PBX_DEVISE = 978;

        // Référence commande côté commerçant.
        $paybox->PBX_CMD = $payment->id;

        // Adresse Email de l’acheteur.
        $paybox->PBX_PORTEUR = $USER->email;

        // Liste des variables à retourner par Paybox.
        $paybox->PBX_RETOUR = 'Mt:M;Ref:R;Auto:A;Erreur:E';

        // Type d’algorithme de hachage pour le calcul de l’empreinte.
        $paybox->PBX_HASH = 'sha512';

        // Horodatage de la transaction.
        $paybox->PBX_TIME = date('c');

        // URL de retour en cas de succès ou d'erreur.
        $paybox->PBX_EFFECTUE = $CFG->wwwroot.'/local/apsolu/payment/index.php?status=accepted';
        $paybox->PBX_REFUSE = $CFG->wwwroot.'/local/apsolu/payment/index.php?status=refused';
        $paybox->PBX_ANNULE = $CFG->wwwroot.'/local/apsolu/payment/index.php?status=cancel';
        $paybox->PBX_ATTENTE = $CFG->wwwroot.'/local/apsolu/payment/index.php?status=wait';
        $paybox->PBX_REPONDRE_A = $CFG->wwwroot.'/local/apsolu/payment/paybox.php';

        // Type de paiement.
        $paybox->PBX_TYPEPAIEMENT = 'CARTE';
        $paybox->PBX_TYPECARTE = 'CB';

        // Signature calculée avec la clé secrète.
        $message = '';
        foreach ((array) $paybox as $key => $value) {
            $message .= '&'.$key.'='.$value;
        }
        $message = substr($message, 1);

        $binkey = pack('H*', $center->hmac);
        $paybox->PBX_HMAC = strtoupper(hash_hmac($paybox->PBX_HASH, $message, $binkey));

        return $paybox;
    }
}
