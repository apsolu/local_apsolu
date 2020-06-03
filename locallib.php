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

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/user/selector/lib.php');

/**
 * Affiche le widget de recherche d'utilisateurs sur la page de paiement.
 *
 * @package    local_apsolu
 * @copyright  2018 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_payment_user_selector extends \user_selector_base {
    /**
     * Candidate users
     *
     * @param string $search
     *
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
 * Retourne la liste des enseignants.
 *
 * @param int|string $courseid
 *
 * @return array
 */
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

/**
 * Affiche le widget de recherche d'utilisateurs sur la page de la FFSU.
 *
 * @package    local_apsolu
 * @copyright  2018 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_courses_federation_user_selector extends \user_selector_base {
    /**
     * Candidate users
     *
     * @param string $search
     *
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

/**
 * Initialise les paramètres par défaut pour l'offre de formation.
 *
 * @return void
 */
function set_initial_course_offerings_settings() {
    // Configure les colonnes à afficher sur la page de présentation de l'offre de formations.
    $columns = new \stdClass();
    $columns->show_city_column = 1;
    $columns->show_grouping_column = 0;
    $columns->show_category_column = 0;
    $columns->show_area_column = 1;
    $columns->show_period_column = 1;
    $columns->show_times_column = 1;
    $columns->show_weekday_column = 1;
    $columns->show_location_column = 1;
    $columns->show_skill_column = 1;
    $columns->show_role_column = 1;
    $columns->show_teachers_column = 1;
    set_config('json_course_offerings_columns', json_encode($columns), 'local_apsolu');

    // Configure les filtres à afficher sur la page de présentation de l'offre de formations.
    $filters = new \stdClass();
    $filters->show_city_filter = 0;
    $filters->show_grouping_filter = 0;
    $filters->show_category_filter = 1;
    $filters->show_area_filter = 1;
    $filters->show_period_filter = 1;
    $filters->show_times_filter = 1;
    $filters->show_weekday_filter = 1;
    $filters->show_location_filter = 0;
    $filters->show_skill_filter = 0;
    $filters->show_role_filter = 0;
    $filters->show_teachers_filter = 0;
    set_config('json_course_offerings_filters', json_encode($filters), 'local_apsolu');

    // Configure la définition des plages horaires pour les périodes de la journée.
    $ranges = new \stdClass();
    $ranges->range1_end = '12:30';
    $ranges->range2_start = '11:30';
    $ranges->range2_end = '14:00';
    $ranges->range3_start = '13:30';
    $ranges->range3_end = '18:30';
    $ranges->range4_start = '18:30';
    set_config('json_course_offerings_ranges', json_encode($ranges), 'local_apsolu');
}
