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

require_once($CFG->dirroot.'/user/selector/lib.php');

function get_activity_presences_count($courseid, $userid = null) {
    global $DB, $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }

    $sql = "SELECT COUNT(aap.id)".
        " FROM {apsolu_attendance_presences} aap".
        " JOIN {apsolu_attendance_sessions} aas ON aas.id = aap.sessionid".
        " JOIN {apsolu_attendance_statuses} status ON status.id = aap.statusid".
        " WHERE aas.courseid = :courseid".
        " AND aap.studentid = :userid".
        " AND status.id IN (1, 2)";
    return $DB->count_records_sql($sql, array('courseid' => $courseid, 'userid' => $userid));
}

function get_complement_presences_count($courseid, $userid = null) {
    global $DB, $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }

    $conditions = array('component' => 'local_apsolu_presence', 'courseid' => $courseid, 'relateduserid' => $userid);
    return $DB->count_records('logstore_standard_log', $conditions);
}

function get_course_price($courseid, $roleid, $userid = null) {
    global $DB, $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }

    $complement = $DB->get_record('apsolu_complements', array('id' => $courseid));
    if ($complement) {
        return $complement->price;
    }

    $activity = $DB->get_record('apsolu_courses', array('id' => $courseid));
    if ($activity) {
        // TODO: se baser sur l'enrolid plutot que le courseid ?
        $sql = "SELECT DISTINCT ac.*".
            " FROM {apsolu_colleges} ac".
            " JOIN {apsolu_colleges_members} acm ON ac.id = acm.collegeid".
            " JOIN {cohort_members} cm ON cm.cohortid = acm.cohortid AND cm.userid = :userid".
            " WHERE ac.roleid = :roleid";
        $college = $DB->get_record_sql($sql, array('userid' => $userid, 'roleid' => $roleid));
        if ($college) {
            return $college->userprice;
        }
    }

    return false;
}

class local_apsolu_payment_user_selector extends \user_selector_base {
    /**
     * Candidate users
     * @param string $search
     * @return array
     */
    public function find_users($search) {
        global $DB;
        // By default wherecondition retrieves all users except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'u');

        $fields      = 'SELECT ' . $this->required_fields_sql('u');
        $countfields = 'SELECT COUNT(1)';

        $sql = " FROM {user} u
                WHERE $wherecondition";
        list($sort, $sortparams) = users_order_by_sql('u', $search, $this->accesscontext);
        $order = ' ORDER BY ' . $sort;

        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params);
            if ($potentialmemberscount > $this->maxusersperpage) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }

        $availableusers = $DB->get_records_sql($fields . $sql . $order, array_merge($params, $sortparams));

        if (empty($availableusers)) {
            return array();
        }

        if ($search) {
            $groupname = get_string('enrolcandidatesmatching', 'enrol', $search);
        } else {
            $groupname = get_string('enrolcandidates', 'enrol');
        }

        return array($groupname => $availableusers);
    }

    /**
     *
     * Note: this function must be implemented if you use the search ajax field
     *       (e.g. set $options['file'] = '/admin/filecontainingyourclass.php';)
     * @return array the options needed to recreate this user_selector.
     */
    protected function get_options() {
        return array(
            'class' => get_class($this),
            'file' => '/local/apsolu/locallib.php',
            'name' => $this->name,
            'exclude' => $this->exclude,
            'extrafields' => $this->extrafields,
            'multiselect' => $this->multiselect,
            'accesscontext' => $this->accesscontext,
        );
    }
}

/**
 * Renvoie toutes les activités dans lesquelles un utilisateur est inscrit.
 * @param int userid (si null, on prend l'id de l'utilisateur courant)
 * @return array
 */
function get_user_activity_any_enrolments($paymentcenterid, $userid = null) {
    global $DB, $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }

    $sql = "SELECT DISTINCT c.*, cc.name AS sport, FORMAT(acol.userprice, 2) AS price, ac.paymentcenterid,".
        " e.id AS enrolid, ue.status, ra.roleid".
        " FROM {course} c".
        " JOIN {course_categories} cc ON cc.id = c.category".
        " JOIN {apsolu_courses} ac ON c.id=ac.id".
        // Check cohorts.
        " JOIN {enrol} e ON c.id = e.courseid".
        " JOIN {enrol_select_cohorts} ewc ON e.id = ewc.enrolid".
        " JOIN {cohort_members} cm ON cm.cohortid = ewc.cohortid".
        " JOIN {user_enrolments} ue ON e.id = ue.enrolid AND ue.userid = cm.userid".
        " JOIN {role_assignments} ra ON ra.userid = ue.userid AND ra.userid = cm.userid AND ra.itemid = e.id".
        " JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50 AND ctx.instanceid = c.id".
        " JOIN {apsolu_colleges} acol ON acol.roleid = ra.roleid".
        " JOIN {apsolu_colleges_members} acm ON acol.id = acm.collegeid AND acm.cohortid = cm.cohortid".
        " WHERE e.enrol = 'select'".
        " AND e.status = 0". // Active.
        " AND ac.paymentcenterid = :paymentcenterid".
        " AND cm.userid = :userid".
        " AND c.visible = 1".
        " ORDER BY c.fullname";
    return $DB->get_records_sql($sql, array('paymentcenterid' => $paymentcenterid, 'userid' => $userid));
}

/**
 * Renvoie toutes les activités complémentaires dans lesquelles un utilisateur est inscrit et validé.
 * @param int userid (si null, on prend l'id de l'utilisateur courant)
 * @return array
 */
function get_user_complement_any_enrolments($paymentcenterid, $userid = null) {
    global $DB, $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }

    $sql = "SELECT DISTINCT c.*, FORMAT(ac.price, 2) AS price, ac.federation, ac.paymentcenterid, e.id AS enrolid, ue.status, ra.roleid".
        " FROM {course} c".
        " JOIN {apsolu_complements} ac ON c.id=ac.id".
        " JOIN {enrol} e ON c.id = e.courseid".
        " JOIN {user_enrolments} ue ON e.id = ue.enrolid".
        " JOIN {cohort_members} cm ON cm.userid = ue.userid".
        " JOIN {role_assignments} ra ON ra.userid = ue.userid AND ra.userid = cm.userid AND ra.itemid = e.id".
        " JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50 AND ctx.instanceid=c.id".
        " WHERE e.enrol = 'select'".
        " AND e.status = 0". // Active.
        " AND ac.paymentcenterid = :paymentcenterid".
        " AND cm.userid = :userid".
        " AND c.visible = 1".
        " ORDER BY c.fullname";

    return $DB->get_records_sql($sql, array('paymentcenterid' => $paymentcenterid, 'userid' => $userid));
}

function get_user_payments_sum($paymentcenterid, $userid = null) {
    global $DB, $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }

    $sql = "SELECT SUM(amount) AS price".
        " FROM {apsolu_payments}".
        " WHERE paymentcenterid = :paymentcenterid".
        " AND userid = :userid".
        " AND status = 1";
    $sum = $DB->get_record_sql($sql, array('paymentcenterid' => $paymentcenterid, 'userid' => $userid));

    if ($sum === false) {
        return 0;
    }

    return $sum->price;
}


function get_data_user_payment_centers($userid = null) {
    global $CFG, $DB, $USER;

    require_once($CFG->dirroot.'/user/profile/lib.php');

    if ($userid === null) {
        $user = $USER;
    } else {
        $user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
    }

    // Initialise les champs custom.
    $customfields = profile_user_record($user->id);
    $cardpaid = (isset($customfields->cardpaid) === true && $customfields->cardpaid === '1');
    $muscupaid = (isset($customfields->muscupaid) === true && $customfields->muscupaid === '1');
    $federationpaid = (isset($customfields->federationpaid) === true && $customfields->federationpaid === '1');

    $paymentcenters = $DB->get_records('apsolu_payments_centers');
    foreach ($paymentcenters as $centerid => $center) {
        $center->activities = array();
        $center->activities_price = 0;
        $center->activities_paid = false;
        $center->count_activities = 0;

        $center->complements = array();
        $center->complements_price = 0;
        $center->complements_paid = false;
        $center->count_complements = 0;

        // Parcours les cours du SIUAPS.
        foreach (get_user_activity_any_enrolments($center->id) as $activity) {
            if ($activity->status !== \enrol_select_plugin::ACCEPTED) {
                continue;
            }

            // HACK pour calculer la présence.
            $presences = get_activity_presences_count($activity->id, $user->id);
            if ($presences < 2) {
                // continue;
            }

            if ($center->activities_price < $activity->price) {
                $center->activities_price = $activity->price;
            }

            $center->activities[] = $activity;
            $center->count_activities++;
        }

        // Parcours les activités complémentaires du SIUAPS.
        foreach (get_user_complement_any_enrolments($center->id) as $complement) {
            if ($complement->status !== \enrol_select_plugin::ACCEPTED) {
                continue;
            }

            // HACK pour la musculation.
            if (stripos('musculation', $complement->fullname) !== false) {
                $presences = get_complement_presences_count($complement->id, $user->id);
                if ($presences < 1) {
                    continue;
                }
            }

            $center->complements_price += $complement->price;

            $center->complements[] = $complement;
            $center->count_complements++;
        }

        // Build total.
        $center->paid_amount = get_user_payments_sum($center->id);
        $center->due_amount = 0;

        // TODO: à revoir pour être plus souple...
        switch ($centerid) {
            case '1': // SIUAPS de Rennes.
                if ($cardpaid === false) {
                    $center->due_amount += $center->activities_price;
                }

                if ($muscupaid === false) {
                    $center->due_amount += $center->complements_price;
                }
                break;
            case '2': // Association sportive.
                if ($federationpaid === false) {
                    $center->due_amount += $center->complements_price;
                }
                break;
        }

        $center->total_amount = $center->due_amount + $center->paid_amount;

        if ($center->count_activities === 0 && $center->count_complements === 0) {
            unset($paymentcenters[$centerid]);
            continue;
        }

        if ($center->due_amount < 0) {
            // TODO: raise an exception !
            $center->due_amount = 0;
            // unset($paymentcenters[$centerid]);
            // continue;
        }

        // Vérification des écritures en base de données.
        if ($center->due_amount === 0) {
            $payment = new \stdClass();
            $payment->id = 0;
        } else {
            // Chercher une commande active, sinon créer.
            $payment = $DB->get_record('apsolu_payments', array('userid' => $user->id, 'paymentcenterid' => $center->id, 'status' => 0));
            if ($payment === false) {
                $payment = new \stdClass();
                $payment->method = 'paybox';
                $payment->source = 'apsolu';
                $payment->amount = $center->due_amount;
                $payment->status = '0'; // Pas payé !
                $payment->timepaid = null;
                $payment->timecreated = strftime('%FT%T');
                $payment->timemodified = $payment->timecreated;
                $payment->userid = $user->id;
                $payment->paymentcenterid = $center->id;
                $paymentid = $DB->insert_record('apsolu_payments', $payment);

                if (!$paymentid) {
                    print_error('error_no_payment_found', 'local_apsolu');
                }

                $payment->id = $paymentid;
            } else if ($payment->amount != $center->due_amount) {
                $payment->amount = $center->due_amount;
                $payment->timemodified = strftime('%FT%T');
                $DB->update_record('apsolu_payments', $payment);
            }

            // $items = $DB->get_records('apsolu_payments_items', array('paymentid' => $payment->id), $sort = '', $fields = 'courseid, id');
            $items = array();

            // TODO: à revoir pour être plus souple...
            switch ($centerid) {
                case '1': // SIUAPS de Rennes.
                    if ($cardpaid === false && $center->activities_price > 0) {
                        if (isset($items[0]) === false) {
                            // Insert.
                            $item = (object) ['paymentid' => $payment->id, 'courseid' => 0, 'roleid' => 0];
                            $DB->insert_record('apsolu_payments_items', $item);
                        } else {
                            unset($items[0]);
                        }
                    }

                    if ($muscupaid === false && $center->complements_price > 0) {
                        if (isset($items[250]) === false) {
                            // Insert.
                            $item = (object) ['paymentid' => $payment->id, 'courseid' => 250, 'roleid' => 0];
                            $DB->insert_record('apsolu_payments_items', $item);
                        } else {
                            unset($items[250]);
                        }
                    }
                    break;
                case '2': // Association sportive.
                    if ($federationpaid === false && $center->complements_price > 0) {
                        if (isset($items[249]) === false) {
                            // Insert.
                            $item = (object) ['paymentid' => $payment->id, 'courseid' => 249, 'roleid' => 0];
                            $DB->insert_record('apsolu_payments_items', $item);
                        } else {
                            unset($items[249]);
                        }
                    }
                    break;
            }

            foreach ($items as $item) {
                // Supprime les items obsolètes.
                $DB->delete_records('apsolu_payments_items', array('id' => $item->id));
            }

            /*
            $items = $DB->get_records('apsolu_payments_items', array('paymentid' => $payment->id), $sort = '', $fields = 'courseid, id');
            if (count($items) === 0) {
                // TODO: raise an exception !
                unset($paymentcenters[$centerid]);
                continue;
            }
             */
        }

        // Mise en forme.
        foreach (array('activities_price', 'complements_price', 'total_amount', 'paid_amount', 'due_amount') as $pricetype) {
            $str = $pricetype.'_format';
            $center->{$str} = number_format($center->{$pricetype}, 2, ',', ' ');
        }

        // Variables paybox.
        $center->paybox = new \stdClass();

        // Numéro de site (fourni par Paybox).
        $center->paybox->PBX_SITE = $center->sitenumber;

        // Numéro de rang (fourni par Paybox).
        $center->paybox->PBX_RANG = $center->rank;

        // Identifiant interne (fourni par Paybox).
        $center->paybox->PBX_IDENTIFIANT = $center->idnumber;

        // Montant total de la transaction.
        $center->paybox->PBX_TOTAL = $center->due_amount * 100;

        // Devise de la transaction.
        $center->paybox->PBX_DEVISE = 978;

        // Référence commande côté commerçant.
        $center->paybox->PBX_CMD = $payment->id;

        // Adresse Email de l’acheteur.
        $center->paybox->PBX_PORTEUR = $user->email;

        // Liste des variables à retourner par Paybox.
        $center->paybox->PBX_RETOUR = 'Mt:M;Ref:R;Auto:A;Erreur:E';

        // Type d’algorithme de hachage pour le calcul de l’empreinte.
        $center->paybox->PBX_HASH = 'sha512';

        // Horodatage de la transaction.
        $center->paybox->PBX_TIME = date('c');

        // URL de retour en cas de succès ou d'erreur.
        $center->paybox->PBX_EFFECTUE = $CFG->wwwroot.'/local/apsolu/payment/index.php?status=accepted';
        $center->paybox->PBX_REFUSE = $CFG->wwwroot.'/local/apsolu/payment/index.php?status=refused';
        $center->paybox->PBX_ANNULE = $CFG->wwwroot.'/local/apsolu/payment/index.php?status=cancel';
        $center->paybox->PBX_ATTENTE = $CFG->wwwroot.'/local/apsolu/payment/index.php?status=wait';
        $center->paybox->PBX_REPONDRE_A = $CFG->wwwroot.'/local/apsolu/payment/paybox.php';

        // Type de paiement.
        $center->paybox->PBX_TYPEPAIEMENT = 'CARTE';
        $center->paybox->PBX_TYPECARTE = 'CB';

        // Signature calculée avec la clé secrète.
        $message = '';
        foreach ((array) $center->paybox as $key => $value) {
            $message .= '&'.$key.'='.$value;
        }
        $message = substr($message, 1);

        $binkey = pack('H*', $center->hmac);
        $center->paybox->PBX_HMAC = strtoupper(hash_hmac($center->paybox->PBX_HASH, $message, $binkey));
    }

    return $paymentcenters;
}

function get_teachers($courseid) {
    global $DB;

    $sql = "SELECT u.*".
        " FROM {user} u".
        " JOIN {role_assignments} ra ON u.id = ra.userid".
        " JOIN {context} c ON c.id = ra.contextid".
        " WHERE c.instanceid = :courseid".
        " AND c.contextlevel = 50".
        " AND ra.roleid = 3";
    return $DB->get_records_sql($sql, array('courseid' => $courseid));
}

class local_apsolu_courses_federation_user_selector extends \user_selector_base {
    /**
     * Candidate users
     * @param string $search
     * @return array
     */
    public function find_users($search) {
        global $DB;
        // By default wherecondition retrieves all users except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'u');

        $fields      = 'SELECT ' . $this->required_fields_sql('u');
        $countfields = 'SELECT COUNT(1)';

        $sql = " FROM {user} u".
                " JOIN {role_assignments} ra ON u.id = ra.userid".
                " JOIN {context} ctx ON ctx.id = ra.contextid".
                " JOIN {course} c ON c.id = ctx.instanceid AND ctx.contextlevel = 50".
                " JOIN {apsolu_complements} ac ON c.id = ac.id AND ac.federation = 1".
                " WHERE ".$wherecondition;
        list($sort, $sortparams) = users_order_by_sql('u', $search, $this->accesscontext);
        $order = ' ORDER BY ' . $sort;

        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params);
            if ($potentialmemberscount > $this->maxusersperpage) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }

        $availableusers = $DB->get_records_sql($fields . $sql . $order, array_merge($params, $sortparams));

        if (empty($availableusers)) {
            return array();
        }

        if ($search) {
            $groupname = get_string('enrolcandidatesmatching', 'enrol', $search);
        } else {
            $groupname = get_string('enrolcandidates', 'enrol');
        }

        return array($groupname => $availableusers);
    }

    /**
     *
     * Note: this function must be implemented if you use the search ajax field
     *       (e.g. set $options['file'] = '/admin/filecontainingyourclass.php';)
     * @return array the options needed to recreate this user_selector.
     */
    protected function get_options() {
        return array(
            'class' => get_class($this),
            'file' => '/local/apsolu/courses/locallib.php',
            'name' => $this->name,
            'exclude' => $this->exclude,
            'extrafields' => $this->extrafields,
            'multiselect' => $this->multiselect,
            'accesscontext' => $this->accesscontext,
        );
    }
}
