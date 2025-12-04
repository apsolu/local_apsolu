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

namespace local_apsolu;

use local_apsolu\core\customfields;

/**
 * Classe gérant les inscriptions.
 *
 * @package    local_apsolu
 * @copyright  2025 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrolment {
    /**
     * Retourne la liste des inscrits à un cours de type "étudiant".
     *
     * @param int $courseid Identifiant du cours.
     * @param int|null $status État de l'inscription des utilisateurs. Voir les valeurs possibles avec enrol_select_plugin::$states.
     * @param int|null $startedbefore
     * @param int|null $endedafter
     *
     * @return array
     */
    public static function get_enrolled_users(
        int $courseid,
        ?int $status = 0,
        ?int $startedbefore = null,
        ?int $endedafter = null
    ): array {
        global $DB;

        $customfields = customfields::getCustomFields();

        $where = [];
        $where[] = 'e.courseid = :courseid';
        $where[] = 'e.status = 0'; // Seulement les méthodes d'inscription actives.
        $where[] = 'ctx.contextlevel = 50'; // Course level.
        $where[] = 'r.archetype = "student"';

        $params = [];
        $params['courseid'] = $courseid;
        $params['apsolusesame'] = $customfields['apsolusesame']->id;

        if ($status !== null) {
            // Récupération de toutes les inscriptions validées.
            $where[] = 'ue.status = :status';
            $params['status'] = $status;
        }

        if ($startedbefore !== null) {
            $where[] = '(ue.timestart <= :timestart OR ue.timestart = 0)';
            $params['timestart'] = $startedbefore;
        }

        if ($endedafter !== null) {
            $where[] = '(ue.timeend >= :timeend OR ue.timeend = 0)';
            $params['timeend'] = $endedafter;
        }

        // Récupérer tous les inscrits.
        // TODO: jointure avec colleges.
        // TODO: retrouver pourquoi on affiche les utilisateurs inscrits manuellement.
        $sql = "SELECT u.*, ue.id AS ueid, ue.status, ue.timestart, ue.timeend, ue.enrolid,
                       e.enrol, ra.id AS raid, ra.roleid, uid1.data AS apsolusesame
                  FROM {user} u
             LEFT JOIN {user_info_data} uid1 ON u.id = uid1.userid AND uid1.fieldid = :apsolusesame
                  JOIN {user_enrolments} ue ON u.id = ue.userid
                  JOIN {enrol} e ON e.id = ue.enrolid
                  JOIN {role_assignments} ra ON u.id = ra.userid AND ((e.id = ra.itemid) OR (e.enrol = 'manual' AND ra.itemid = 0))
                  JOIN {role} r ON r.id = ra.roleid
                  JOIN {context} ctx ON ra.contextid = ctx.id AND ctx.instanceid = e.courseid
                 WHERE " . implode(' AND ', $where) . "
              ORDER BY u.lastname, u.firstname";

        return $DB->get_records_sql($sql, $params);
    }
}
