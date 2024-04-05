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

use SimpleXMLElement;
use stdClass;

/**
 * Fonctions pour le module apsolu.
 *
 * @package    local_apsolu
 * @copyright  2018 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class Payment {
    /**
     * Code d'un paiement dû.
     */
    const DUE = 0;

    /**
     * Code d'un paiement payé.
     */
    const PAID = 1;

    /**
     * Code d'un paiement gratuit.
     */
    const FREE = 2;

    /**
     * Code d'un paiement offert.
     */
    const GIFT = 3;

    /**
     * Retourne la liste des attestations de natations validées pour Brest.
     *
     * @param int|string $courseid Identifiant numérique du cours.
     *
     * @return array|null Le tableau est au format [userid => note].
     */
    public static function get_appn_brest($courseid) {
        global $CFG, $DB;

        if ($CFG->wwwroot !== 'https://espace-suaps.univ-brest.fr' && empty($CFG->debugdisplay) === true) {
            return null;
        }

        // Détermine si il s'agit d'un cours appartenant au groupement d'activités APPN.
        $sql = "SELECT c.id
                  FROM {course} c
                  JOIN {apsolu_courses} ac ON c.id = ac.id
                  JOIN {course_categories} cc1 ON cc1.id = c.category
                  JOIN {course_categories} cc2 ON cc2.id = cc1.parent
                  JOIN {apsolu_courses_groupings} acg ON cc2.id = acg.id
                 WHERE cc2.name LIKE 'APPN%'
                   AND c.id = :courseid";
        if ($DB->get_record_sql($sql, ['courseid' => $courseid]) !== false) {
            // Détermine si un dépôt de devoirs existe.
            $sql = "SELECT cm.instance
                      FROM {course_modules} cm
                      JOIN {modules} m ON m.id = cm.module
                     WHERE m.name = 'assign'
                       AND cm.course = :courseid";
            $cm = $DB->get_record_sql($sql, ['courseid' => $courseid]);
            if ($cm !== false) {
                $sql = "SELECT userid, grade
                          FROM {assign_grades}
                         WHERE assignment = :assignment
                           AND grade > 0";
                return $DB->get_records_sql($sql, ['assignment' => $cm->instance]);
            }
        }

        return null;
    }

    /**
     * Retourne pour un utilisateur donné, les cartes qui le concerne potentiellement.
     * Attention ! Ce ne sont pas les cartes dûes.
     *
     * @param int|null $userid Si null, le $userid sera calculé en fonction de l'utilisateur courant.
     *
     * @return array
     */
    public static function get_user_cards($userid = null) {
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
            // Check colleges.
            // " JOIN {apsolu_colleges} acol ON acol.roleid = ra.roleid".
            // " JOIN {apsolu_colleges_members} acm ON acol.id = acm.collegeid AND acm.cohortid = cm.cohortid".
            // Check cards w/ cohorts/roles.
            " JOIN {apsolu_payments_cards_cohort} apcc ON apc.id = apcc.cardid AND apcc.cohortid = cm.cohortid".
            " JOIN {apsolu_payments_cards_roles} apcr ON apc.id = apcr.cardid AND apcr.roleid = ra.roleid".
            " WHERE e.enrol = 'select'".
            " AND c.visible = 1". // Cours visible.
            " AND e.status = 0". // Méthode d'inscription active.
            " AND ue.status = 0". // Inscription validée.
            " AND cm.userid = :userid".
            " ORDER BY apc.fullname";
        return $DB->get_records_sql($sql, ['userid' => $userid]);
    }

    /**
     * Retourne tous les utilisateurs concernés par une carte donnée.
     *
     * @param int $cardid
     *
     * @return array
     */
    public static function get_card_users($cardid) {
        global $DB;

        $sql = "SELECT DISTINCT u.*".
            " FROM {user} u".
            " JOIN {user_info_data} uid ON u.id = uid.userid".
            " JOIN {user_info_field} uif ON uif.id = uid.fieldid".
            " JOIN {user_enrolments} ue ON u.id = ue.userid".
            " JOIN {enrol} e ON e.id = ue.enrolid".
            " JOIN {enrol_select_cards} esc ON e.id = esc.enrolid".
            " JOIN {course} c ON c.id = e.courseid".
            // Check cohorts.
            " JOIN {enrol_select_cohorts} ewc ON e.id = ewc.enrolid".
            " JOIN {cohort_members} cm ON cm.cohortid = ewc.cohortid AND u.id = cm.userid".
            " JOIN {role_assignments} ra ON ra.userid = ue.userid AND ra.userid = cm.userid AND ra.itemid = e.id".
            " JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50 AND ctx.instanceid = c.id".
            // Check colleges.
            // " JOIN {apsolu_colleges} acol ON acol.roleid = ra.roleid".
            // " JOIN {apsolu_colleges_members} acm ON acol.id = acm.collegeid AND acm.cohortid = cm.cohortid".
            // Check cards w/ cohorts/roles.
            " JOIN {apsolu_payments_cards_cohort} apcc ON esc.cardid = apcc.cardid AND apcc.cohortid = cm.cohortid".
            " JOIN {apsolu_payments_cards_roles} apcr ON esc.cardid = apcr.cardid AND apcr.roleid = ra.roleid".
            " WHERE e.enrol = 'select'".
            " AND u.deleted = 0". // Utilisateur non supprimé.
            " AND c.visible = 1". // Cours visible.
            " AND e.status = 0". // Méthode d'inscription active.
            " AND ue.status = 0". // Inscription validée.
            " AND uif.shortname = 'apsolusesame'". // Compte Sésame validé.
            " AND esc.cardid = :cardid";
        return $DB->get_records_sql($sql, ['cardid' => $cardid]);
    }

    /**
     * Retourne une instance d'inscription en fonction d'une carte et d'un utilisateur.
     *
     * @param int      $cardid
     * @param int|null $userid Si null, le $userid sera calculé en fonction de l'utilisateur courant.
     *
     * @return array
     */
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
            " JOIN {apsolu_payments_cards_cohort} apcc ON esc.cardid = apcc.cardid AND apcc.cohortid = cm.cohortid".
            " JOIN {apsolu_payments_cards_roles} apcr ON apcr.roleid = ra.roleid AND apcr.cardid = esc.cardid".
            " JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50 AND ctx.instanceid = c.id".
            // Check colleges.
            // " JOIN {apsolu_colleges} acol ON acol.roleid = ra.roleid".
            // " JOIN {apsolu_colleges_members} acm ON acol.id = acm.collegeid AND acm.cohortid = cm.cohortid".
            " WHERE e.enrol = 'select'".
            " AND c.visible = 1". // Cours visible.
            " AND e.status = 0". // Méthode d'inscription active.
            " AND ue.status = 0". // Inscription validée.
            " AND cm.userid = :userid".
            " AND esc.cardid = :cardid";
        return $DB->get_records_sql($sql, ['cardid' => $cardid, 'userid' => $userid]);
    }

    /**
     * Calcul pour un utilisateur donné si la carte est dûe.
     * Attention ! Ne vérifie pas si l'utilisateur est éligible/concerné par cette carte.
     *
     * @param stdclass $card
     * @param int|null $userid Si null, le $userid sera calculé en fonction de l'utilisateur courant.
     *
     * @return int Statut de paiement défini au niveau de la classe Payment.
     */
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
            " AND ap.userid = :userid".
            " AND ap.timepaid IS NOT NULL";
        $payment = $DB->get_record_sql($sql, ['cardid' => $card->id, 'userid' => $userid]);
        if ($payment !== false) {
            debugging('Carte '.$card->fullname.' payée !', $level = DEBUG_DEVELOPER);
            return $payment->status; // self::PAID or self::GIFT.
        }

        $enrols = self::get_user_enrols_by_card($card->id, $userid);

        // Vérifie les séances d'essais.
        if ($card->trial > 0) {
            foreach ($enrols as $enrol) {
                // TODO: mauvais component.
                // TODO: n'utilise pas un champ indexé ! ÇA RAME !
                $conditions = ['component' => 'local_apsolu_presence', 'courseid' => $enrol->courseid, 'relateduserid' => $userid];
                if ($DB->count_records('logstore_standard_log', $conditions) >= $card->trial) {
                    debugging('Carte '.$card->fullname.' due (fin des séances d\'essais).', $level = DEBUG_DEVELOPER);
                    return self::DUE;
                }
            }

            return self::FREE;
        }

        // Vérifie les activités offertes.
        $calendars = $DB->get_records('apsolu_calendars');

        $enrolcalendars = [];
        foreach ($enrols as $enrol) {
            if (isset($calendars[$enrol->customchar1]) === false) {
                debugging('Aucun calendrier pour l\'inscription #'.$enrol->id.' (course #'.$enrol->courseid.')', $level = DEBUG_DEVELOPER);
                continue;
            }

            $calendartypeid = $calendars[$enrol->customchar1]->typeid;
            if (isset($enrolcalendars[$calendartypeid]) === false) {
                $enrolcalendars[$calendartypeid] = 0;
            }
            $enrolcalendars[$calendartypeid]++;
        }

        $calendars = $DB->get_records('apsolu_payments_cards_cals', ['cardid' => $card->id], $sort = '', $fields = 'calendartypeid, value');
        foreach ($calendars as $calendar) {
            if (isset($enrolcalendars[$calendar->calendartypeid]) === false) {
                continue;
            }

            if ($enrolcalendars[$calendar->calendartypeid] > $calendar->value) {
                debugging('Carte '.$card->fullname.' due (nombre d\'inscriptions offertes dépasées).', $level = DEBUG_DEVELOPER);
                return self::DUE;
            }
        }

        return self::FREE;
    }

    /**
     * Retourne un objet contenant toutes les informations nécessaires pour le formulaire HTML Paybox.
     *
     * @param stdclass $payment
     *
     * @return stdclass
     */
    public static function get_paybox_settings($payment) {
        global $CFG, $DB, $USER;

        $center = $DB->get_record('apsolu_payments_centers', ['id' => $payment->paymentcenterid], '*', MUST_EXIST);

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
        $paybox->PBX_CMD = $payment->prefix.$payment->id;

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

        // Nombre d'éléments dans le panier.
        $paybox->PBX_SHOPPINGCART = '<?xml version="1.0" encoding="utf-8"?><shoppingcart><total><totalQuantity>'.$payment->quantity.'</totalQuantity></total></shoppingcart>';

        // Adresse postale.
        $address = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><Billing><Address></Address></Billing>');
        $address->Address[0]->addChild('FirstName', $payment->address->firstname);
        $address->Address[0]->addChild('LastName', $payment->address->lastname);
        $address->Address[0]->addChild('Address1', $payment->address->address1);
        $address->Address[0]->addChild('Address2', $payment->address->address2);
        $address->Address[0]->addChild('ZipCode', $payment->address->zipcode);
        $address->Address[0]->addChild('City', $payment->address->city);
        $address->Address[0]->addChild('CountryCode', $payment->address->countrycode);

        // Convertit l'objet XML en chaîne, et supprime les retours à la ligne.
        $paybox->PBX_BILLING = str_replace(PHP_EOL, '', $address->asXML());

        // Signature calculée avec la clé secrète.
        $message = '';
        foreach ((array) $paybox as $key => $value) {
            $message .= '&'.$key.'='.$value;
        }
        $message = substr($message, 1);

        $binkey = pack('H*', $center->hmac);
        $paybox->PBX_HMAC = strtoupper(hash_hmac($paybox->PBX_HASH, $message, $binkey));

        // Réencode les caractères.
        $paybox->PBX_BILLING = htmlentities($paybox->PBX_BILLING);

        return $paybox;
    }

    /**
     * Retourne les cartes requises pour un cours donné.
     *
     * @param int $courseid
     *
     * @return array
     */
    public static function get_course_cards($courseid) {
        global $DB;

        $sql = "SELECT DISTINCT apc.id, apc.name, apc.fullname, apc.trial, apc.price, apc.centerid".
            " FROM {apsolu_payments_cards} apc".
            " JOIN {enrol_select_cards} esc ON esc.cardid = apc.id".
            " JOIN {enrol} e ON e.id = esc.enrolid".
            " JOIN {course} c ON c.id = e.courseid".
            " WHERE e.enrol = 'select'".
            " AND c.id = :courseid".
            " AND e.status = 0". // Méthode d'inscription active.
            " ORDER BY apc.fullname";
        return $DB->get_records_sql($sql, ['courseid' => $courseid]);
    }

    /**
     * Retourne pour un cours donné le statut du paiement d'un utilisateur donné.
     *
     * @param int $courseid
     * @param int $userid
     *
     * @return array of object
     */
    public static function get_user_cards_status_per_course($courseid, $userid = null) {
        global $USER;

        if ($userid === null) {
            $userid = $USER->id;
        }

        $users = self::get_users_cards_status_per_course($courseid, $userid);

        if (empty($users) === true) {
            return [];
        }

        return current($users);
    }

    /**
     * Retourne pour un cours donné le statut du paiement des utilisateurs inscrits.
     *
     * @param int $courseid
     * @param int $userid
     *
     * @return array
     */
    public static function get_users_cards_status_per_course($courseid, $userid = null) {
        global $DB;

        $users = [];

        // Sélectionner les cartes dûes pour chaque utilisateur dans un cours.
        $sql = "SELECT apc.*, ue.userid".
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
            // Check colleges.
            // " JOIN {apsolu_colleges} acol ON acol.roleid = ra.roleid".
            // " JOIN {apsolu_colleges_members} acm ON acol.id = acm.collegeid AND acm.cohortid = cm.cohortid".
            // Check cards w/ cohorts/roles.
            " JOIN {apsolu_payments_cards_cohort} apcc ON apc.id = apcc.cardid AND apcc.cohortid = cm.cohortid".
            " JOIN {apsolu_payments_cards_roles} apcr ON apc.id = apcr.cardid AND apcr.roleid = ra.roleid".
            " WHERE e.enrol = 'select'".
            " AND c.id = :courseid".
            " AND e.status = 0". // Méthode d'inscription active.
            " AND ue.status = 0"; // Inscription validée.
        $params = ['courseid' => $courseid];

        if (empty($userid) === false) {
            $sql .= " AND ue.userid = :userid";
            $params['userid'] = $userid;
        }

        $recordset = $DB->get_recordset_sql($sql, $params);
        foreach ($recordset as $record) {
            if (isset($users[$record->userid]) === false) {
                $users[$record->userid] = [];
            }

            $userid = $record->userid;
            unset($record->userid);

            $record->status = self::get_user_card_status($record, $userid);

            $users[$userid][$record->id] = $record;
        }
        $recordset->close();

        return $users;
    }

    /**
     * Retourne un tableau des clés de traduction des statuts de paiement, indéxé par le code des statuts de paiement.
     *
     * @return array
     */
    public static function get_statuses_labels() {
        $labels = [];
        $labels[self::DUE] = 'due';
        $labels[self::PAID] = 'paid';
        $labels[self::FREE] = 'free';
        $labels[self::GIFT] = 'gift';

        return $labels;
    }

    /**
     * Retourne un tableau d'objets contenant la représentation HTML et le libellé d'un statuts de paiement.
     *
     * @return array
     */
    public static function get_statuses_images() {
        global $OUTPUT;

        $images = [];
        foreach (self::get_statuses_labels() as $statusid => $statusname) {
            $alt = 'alt_'.$statusname;
            $definition = 'definition_'.$statusname;

            $images[$statusid] = new stdClass();
            $images[$statusid]->image = $OUTPUT->pix_icon('t/'.$statusname, get_string($alt, 'local_apsolu'), 'local_apsolu', ['title' => get_string($alt, 'local_apsolu'), 'width' => '12px', 'height' => '12px']);
            $images[$statusid]->definition = get_string($definition, 'local_apsolu');
        }

        return $images;
    }

    /**
     * Retourne true lorsque les paiements sont ouverts.
     *
     * @return boolean
     */
    public static function is_open() {
        $time = time();

        $payments_startdate = get_config('local_apsolu', 'payments_startdate');
        if ($time < $payments_startdate) {
            // Les paiements n'ont pas démarré.
            return false;
        }

        $payments_enddate = get_config('local_apsolu', 'payments_enddate');
        if ($time > $payments_enddate) {
            // Les paiements sont terminés.
            return false;
        }

        return true;
    }
}
