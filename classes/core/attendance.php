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
 * Classe gérant les présences dans Apsolu.
 *
 * @package    local_apsolu
 * @copyright  2018 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_apsolu\core;

use stdClass;

class attendance {
    /**
     * Méthode permettant de récupérer le nombre de présences par étudiant et par semestre à partir d'un ID d'activité.
     *
     * @param int $categorid ID de l'activité sportive.
     * @param bool $active Si vrai, récupère uniquement les présences du semestre courant.
     *
     * @return array[userid][] = '4'.
     */
    public static function countActivityPresences($categoryid, $active = true) {
        global $DB;

        $conditions = array();
        $params = array();
        $params['categoryid'] = $categoryid;
        if ($active === true) {
            $conditions[] = 'AND ue.timeend >= :now'; // Limite les méthodes d'inscription en cours.
            $conditions[] = 'AND aas.sessiontime BETWEEN e.customint7 AND e.customint8';
            $params['now'] = time();
        }

        // Récupère toutes les présences dans une activité (note: permet de récupérer les présences d'étudiants non-inscrits au cours).
        $sql = "SELECT aap.studentid, COUNT(*) AS total".
            " FROM {apsolu_attendance_presences} aap".
            " JOIN {apsolu_attendance_sessions} aas ON aas.id = aap.sessionid".
            " JOIN {enrol} e ON e.courseid = aas.courseid".
            " JOIN {user_enrolments} ue ON e.id = ue.enrolid AND aap.studentid = ue.userid".
            " WHERE aas.activityid = :categoryid".
            " AND e.enrol = 'select'".
            " AND ue.status = 0".
            " AND aap.statusid != 4". // Exclus les absences.
            " ".implode(' ', $conditions).
            " GROUP BY aas.activityid, aap.studentid";
        $presences = array();
        foreach ($DB->get_recordset_sql($sql, $params) as $recordset) {
            $presences[$recordset->studentid] = $recordset->total;
        }

        // Récupère toutes les inscriptions actives (note: permet de récupérer les étudiants qui ne sont jamais venus en cours).
        $sql = "SELECT DISTINCT ue.userid".
            " FROM {user_enrolments} ue".
            " JOIN {enrol} e ON e.id = ue.enrolid".
            " JOIN {course} c ON c.id = e.courseid".
            " WHERE c.category = :categoryid".
            " AND e.enrol = 'select'".
            " AND ue.status = 0".
            " ".$conditions[0];
        foreach ($DB->get_recordset_sql($sql, $params) as $recordset) {
            if (isset($presences[$recordset->userid]) === false) {
                $presences[$recordset->userid] = 0;
            }
        }

        return $presences;
    }

    /**
     * Méthode permettant de récupérer les présences par étudiant et par semestre à partir d'un ID d'activité.
     *
     * @return array[userid][] = 'Sem. 1 : 4'.
     */
    public static function getActivityPresences($categoryid) {
        global $DB;

        $sql = "SELECT aap.studentid, act.name, COUNT(*) AS total".
            " FROM {apsolu_attendance_presences} aap".
            " JOIN {apsolu_attendance_sessions} aas ON aas.id = aap.sessionid".
            " JOIN {enrol} e ON e.courseid = aas.courseid".
            " JOIN {apsolu_calendars} ac ON e.customchar1 = ac.id".
            " JOIN {apsolu_calendars_types} act ON act.id = ac.typeid".
            " WHERE aas.activityid = :categoryid".
            " AND e.enrol = 'select'".
            " AND aas.sessiontime BETWEEN ac.coursestartdate AND ac.courseenddate".
            " AND aap.statusid != 4". // Exclus les absences.
            " GROUP BY act.id, aap.studentid".
            " ORDER BY act.name";
        $presences = array();
        foreach ($DB->get_recordset_sql($sql, array('categoryid' => $categoryid)) as $recordset) {
            if (isset($presences[$recordset->studentid]) === false) {
                $presences[$recordset->studentid] = array();
            }

            $presences[$recordset->studentid][] = substr($recordset->name, 0, 7).'.&nbsp;:&nbsp;'.$recordset->total;
        }

        return $presences;
    }

    /**
     * Méthode permettant de récupérer les présences par étudiant et par semestre à partir d'un ID de cours.
     *
     * @return array[userid][] = 'Sem. 1 : 4'.
     */
    public static function getCoursePresences($courseid) {
        global $DB;

        $sql = "SELECT aap.studentid, act.name, COUNT(*) AS total".
            " FROM {apsolu_attendance_presences} aap".
            " JOIN {apsolu_attendance_sessions} aas ON aas.id = aap.sessionid".
            " JOIN {enrol} e ON e.courseid = aas.courseid".
            " JOIN {apsolu_calendars} ac ON e.customchar1 = ac.id".
            " JOIN {apsolu_calendars_types} act ON act.id = ac.typeid".
            " WHERE aas.courseid = :courseid".
            " AND e.enrol = 'select'".
            " AND aas.sessiontime BETWEEN ac.coursestartdate AND ac.courseenddate".
            " AND aap.statusid != 4". // Exclus les absences.
            " GROUP BY act.id, aap.studentid".
            " ORDER BY act.name";
        $presences = array();
        foreach ($DB->get_recordset_sql($sql, array('courseid' => $courseid)) as $recordset) {
            if (isset($presences[$recordset->studentid]) === false) {
                $presences[$recordset->studentid] = array();
            }

            $presences[$recordset->studentid][] = substr($recordset->name, 0, 7).'.&nbsp;:&nbsp;'.$recordset->total;
        }

        return $presences;
    }

    /**
     * Méthode permettant de récupérer toutes les présences par méthode d'inscription à partir d'un ID d'un étudiant.
     *
     * @return array[enrolid] = (object) ['id' => enrolid, 'total' => total de présences].
     */
    public static function getUserPresences($userid) {
        global $DB;

        $sql = "SELECT e.id, COUNT(*) AS total".
            " FROM {apsolu_attendance_presences} aap".
            " JOIN {apsolu_attendance_sessions} aas ON aas.id = aap.sessionid".
            " JOIN {enrol} e ON e.courseid = aas.courseid".
            " JOIN {apsolu_calendars} ac ON e.customchar1 = ac.id".
            " WHERE aap.studentid = :userid".
            " AND e.enrol = 'select'".
            " AND aas.sessiontime BETWEEN ac.coursestartdate AND ac.courseenddate".
            " AND aap.statusid != 4". // Exclus les absences.
            " GROUP BY e.id";

        return $DB->get_records_sql($sql, array('userid' => $userid));
    }
}
