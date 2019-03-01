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
     * Méthode permettant de récupérer les présences par étudiant et par semestre à partir d'un ID d'activité.
     *
     * @return array[userid][] = 'Sem. 1 : 4'.
     */
    public static function get_activity_presences($categoryid) {
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
    public static function get_course_presences($courseid) {
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
    public static function get_user_presences($userid) {
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
