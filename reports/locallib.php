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
 * Fonctions pour le module payment.
 *
 * @package    local_apsolu_payment
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace UniversiteRennes2\Apsolu;

require_once($CFG->dirroot.'/user/selector/lib.php');

class blocks_apsolu_teachers_students_selector extends \user_selector_base {
    /**
     * Candidate users
     * @param string $search
     * @return array
     */
    public function find_users($search) {
        global $DB, $USER;
        // By default wherecondition retrieves all users except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'u');

        // Vérifie que l'utilisateur enseigne dans au moins 1 cours.
        $sql = "SELECT DISTINCT e.*".
            " FROM {enrol} e".
            " JOIN {course} c ON c.id = e.courseid".
            " JOIN {apsolu_courses} ac ON ac.id = c.id".
            " JOIN {context} ctx ON c.id = ctx.instanceid AND ctx.contextlevel = 50".
            " JOIN {role_assignments} ra ON ctx.id = ra.contextid AND ra.roleid = 3".
            " WHERE ra.userid = ?".
            " AND e.enrol = 'select'".
            " AND e.status = 0";
        $enrols = $DB->get_records_sql($sql, array($USER->id));
        if (!$enrols) {
            return array();
        }

        $in = array();
        foreach ($enrols as $enrol) {
            $in[] = $enrol->id;
        }

        $fields      = 'SELECT ' . $this->required_fields_sql('u');
        $countfields = 'SELECT COUNT(1)';

        $sql = " FROM {user} u".
            " JOIN {user_enrolments} ue ON u.id = ue.userid".
            " WHERE ".$wherecondition.
            " AND ue.status = 0".
            " AND ue.enrolid IN (".implode(', ', $in).")";
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

        $groupname = get_string('enrolledusers', 'enrol');

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
            'file' => '/blocks/apsolu_teachers/locallib.php',
            'name' => $this->name,
            'exclude' => $this->exclude,
            'extrafields' => $this->extrafields,
            'multiselect' => $this->multiselect,
            'accesscontext' => $this->accesscontext,
        );
    }
}
