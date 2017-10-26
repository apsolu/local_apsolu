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
 * UI related functions and classes.
 *
 * @package    local_apsolu
 * @copyright  2017 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace UniversiteRennes2\Apsolu;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/user/selector/lib.php');

/**
 * Cohort assignment candidates
 */
class auth_user_selector extends \user_selector_base {
    protected $auth;

    public function __construct($name, $options) {
        $this->auth = $options['auth'];
        parent::__construct($name, $options);
    }

    /**
     * Candidate users
     * @param string $search
     * @return array
     */
    public function find_users($search) {
        global $DB;

        // By default wherecondition retrieves all users except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'u');
        $params['auth'] = $this->auth;

        $fields      = 'SELECT ' . $this->required_fields_sql('u');
        $countfields = 'SELECT COUNT(1)';

        $sql = " FROM {user} u
                WHERE u.auth = :auth AND $wherecondition";

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
            $groupname = get_string('potusersmatching', 'cohort', $search);
        } else {
            $groupname = get_string('potusers', 'cohort');
        }

        return array($groupname => $availableusers);
    }

    protected function get_options() {
        $options = parent::get_options();
        $options['auth'] = $this->auth;
        $options['file'] = 'local/apsolu/users/locallib.php';
        return $options;
    }
}

function is_mergeable($shibboleth_user) {
    global $DB;

    // Vérifier qu'il est validé.
    $confirmed = $DB->get_record('user_info_data', array('fieldid' => '11', 'userid' => $shibboleth_user->id));

    if ($confirmed === false || $confirmed->data === '0') {
        return get_string('users_not_confirmed_shibboleth', 'local_apsolu');
    }

    // Vérifier qu'il n'a pas de cours.
    $assignments = $DB->get_records('user_enrolments', array('userid' => $shibboleth_user->id));
    if (count($assignments) > 0) {
        return get_string('users_enrolments_shibboleth', 'local_apsolu');
    }

    return true;
}

function merge($email_user, $shibboleth_user) {
    global $CFG, $DB;

    try {
        $transaction = $DB->start_delegated_transaction();

        // Cohorts.
        $sql = "UPDATE {cohort_members} SET userid = :newval WHERE userid = :oldval";
        $DB->execute($sql, array('newval' => $email_user->id.'0000000', 'oldval' => $email_user->id)); // Compte email.
        $DB->execute($sql, array('newval' => $email_user->id, 'oldval' => $shibboleth_user->id)); // Compte Sésame.
        $DB->execute($sql, array('newval' => $shibboleth_user->id, 'oldval' => $email_user->id.'0000000')); // Compte email.

        // Data info.
        $sql = "UPDATE {user_info_data} SET userid = :newval WHERE userid = :oldval";
        $DB->execute($sql, array('newval' => $email_user->id.'0000000', 'oldval' => $email_user->id)); // Compte email.
        $DB->execute($sql, array('newval' => $email_user->id, 'oldval' => $shibboleth_user->id)); // Compte Sésame.
        $DB->execute($sql, array('newval' => $shibboleth_user->id, 'oldval' => $email_user->id.'0000000')); // Compte email.

        // User.
        $sql = "UPDATE {user} SET id = :newval, deleted = :deleted WHERE id = :oldval";
        $DB->execute($sql, array('newval' => $email_user->id.'0000000', 'deleted' => 1, 'oldval' => $email_user->id)); // Compte email.
        $DB->execute($sql, array('newval' => $email_user->id, 'deleted' => 0, 'oldval' => $shibboleth_user->id)); // Compte Sésame.
        $DB->execute($sql, array('newval' => $shibboleth_user->id, 'deleted' => 1, 'oldval' => $email_user->id.'0000000')); // Compte email.

        $transaction->allow_commit();

        $oldid = $email_user->id;
        $email_user->id = $shibboleth_user->id;
        $shibboleth_user->id = $oldid;

        $params = new \stdClass();
        $params->wwwroot = $CFG->wwwroot;
        $params->id1 = $email_user->id;
        $params->username1 = $email_user->username;
        $params->id2 = $shibboleth_user->id;
        $params->username2 = $shibboleth_user->username;
        return get_string('users_accounts_merged', 'local_apsolu', $params);
    } catch(Exception $exception) {
        $transaction->rollback($exception);

        return get_string('error', 'error');
    }
}
