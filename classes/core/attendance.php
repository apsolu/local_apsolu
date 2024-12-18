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

// phpcs:disable moodle.NamingConventions.ValidFunctionName.LowercaseMethod

namespace local_apsolu\core;

use stdClass;

/**
 * Classe gérant les présences dans Apsolu.
 *
 * @package    local_apsolu
 * @copyright  2018 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attendance {
    /**
     * Méthode permettant de récupérer le nombre total de présences par étudiant à partir d'un ID d'activité.
     *
     * @param int|string $categoryid ID de l'activité sportive.
     * @param bool       $active     Si vrai, récupère uniquement les présences du semestre courant.
     *
     * @return array Tableau sous la forme array[userid] = 4.
     */
    public static function countActivityPresences($categoryid, $active = true) {
        $presences = [];

        foreach (self::getActivityPresences($categoryid, $active) as $studentid => $records) {
            $presences[$studentid] = 0;
            foreach ($records as $record) {
                $presences[$studentid] += $record->total;
            }
        }

        return $presences;
    }

    /**
     * Méthode permettant de récupérer le nombre total de présences par étudiant à partir d'un ID d'un cours.
     *
     * @param int|string $courseid ID du créneau.
     * @param bool       $active   Si vrai, récupère uniquement les présences du semestre courant.
     *
     * @return array Tableau sous la forme array[userid] = 4.
     */
    public static function countCoursePresences($courseid, $active = true) {
        $presences = [];

        foreach (self::getCoursePresences($courseid, $active) as $studentid => $records) {
            $presences[$studentid] = 0;
            foreach ($records as $record) {
                $presences[$studentid] += $record->total;
            }
        }

        return $presences;
    }

    /**
     * Méthode permettant de récupérer les présences par étudiant et par semestre à partir d'un ID d'activité.
     *
     * @param int|string $categoryid ID de l'activité sportive.
     * @param bool       $active     Si vrai, récupère uniquement les présences du semestre courant.
     *
     * @return array Tableau sous la forme array[userid][] = (object) ['studentid' => '123', 'name' => 'Sem. 1', 'total' => '4']
     */
    public static function getActivityPresences($categoryid, $active = false) {
        global $DB;

        $conditions = [];
        $params = [];
        $params['categoryid'] = $categoryid;

        if ($active === true) {
            $conditions[] = 'AND e.customint8 >= :now'; // Limite aux méthodes d'inscription en cours.
            $params['now'] = time();
        }

        $sql = "SELECT aap.studentid, act.name, COUNT(*) AS total".
            " FROM {apsolu_attendance_presences} aap".
            " JOIN {apsolu_attendance_sessions} aas ON aas.id = aap.sessionid".
            " JOIN {enrol} e ON e.courseid = aas.courseid".
            " JOIN {apsolu_calendars} ac ON e.customchar1 = ac.id".
            " JOIN {apsolu_calendars_types} act ON act.id = ac.typeid".
            " JOIN {course} c ON c.id = aas.courseid".
            " WHERE c.category = :categoryid".
            " AND e.enrol = 'select'".
            " AND aas.sessiontime BETWEEN ac.coursestartdate AND ac.courseenddate".
            " AND aap.statusid != 4". // Exclus les absences.
            " ".implode(' ', $conditions).
            " GROUP BY act.id, aap.studentid".
            " ORDER BY act.name";
        $presences = [];
        $recordset = $DB->get_recordset_sql($sql, $params);
        foreach ($recordset as $record) {
            if (isset($presences[$record->studentid]) === false) {
                $presences[$record->studentid] = [];
            }

            $presences[$record->studentid][] = $record;
        }
        $recordset->close();

        return $presences;
    }

    /**
     * Méthode permettant de récupérer les présences par étudiant et par semestre à partir d'un ID de cours.
     *
     * @param int|string $courseid ID du créneau.
     * @param bool       $active     Si vrai, récupère uniquement les présences du semestre courant.
     *
     * @return array Tableau sous la forme array[userid][] = (object) ['studentid' => '123', 'name' => 'Sem. 1', 'total' => '4']
     */
    public static function getCoursePresences($courseid, $active = false) {
        global $DB;

        $conditions = [];
        $params = [];
        $params['courseid'] = $courseid;

        if ($active === true) {
            $conditions[] = 'AND e.customint8 >= :now'; // Limite aux méthodes d'inscription en cours.
            $params['now'] = time();
        }

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
            " ".implode(' ', $conditions).
            " GROUP BY act.id, aap.studentid".
            " ORDER BY act.name";
        $presences = [];
        $recordset = $DB->get_recordset_sql($sql, $params);
        foreach ($recordset as $record) {
            if (isset($presences[$record->studentid]) === false) {
                $presences[$record->studentid] = [];
            }

            $presences[$record->studentid][] = $record;
        }
        $recordset->close();

        return $presences;
    }

    /**
     * Méthode renvoyant la classe boostrap 4 correspondant au code du statut.
     *
     * Ex: présent en vert, en retard en orange, absence en rouge, etc.
     *
     * @param string $color Nom de classe Bootstrap (success, warning, info ou danger).
     *
     * @return string Nom d'une classe CSS Bootstrap.
     */
    public static function getStatusBootstrapStyle(string $color) {
        return sprintf('text-%s', $color);
    }

    /**
     * Méthode listant toutes les présences d'un utilisateur par cours.
     *
     * @param int|string $userid Identifiant Moodle de l'utilisateur.
     *
     * @return array Tableau sous la forme array[courseid] = (object) ['courseid' => courseid,
     *               'fullname' => nom complet du cours, 'session' => nom de la session, 'status' => statut de la présence].
     */
    public static function getUserPresencesPerCourses($userid) {
        global $DB;

        $courses = [];

        $sql = "SELECT c.id, c.fullname, aas.name AS sessionname, aas.sessiontime,".
            " aass.shortlabel, aass.longlabel, aass.sumlabel, aass.color, ac.starttime, ac.endtime".
            " FROM {course} c".
            " JOIN {apsolu_courses} ac ON c.id = ac.id".
            " JOIN {apsolu_attendance_sessions} aas ON c.id = aas.courseid".
            " JOIN {apsolu_attendance_presences} aap ON aas.id = aap.sessionid".
            " JOIN {apsolu_attendance_statuses} aass ON aass.id = aap.statusid".
            " WHERE aap.studentid = :userid".
            " ORDER BY c.fullname, aas.sessiontime";
        $recordset = $DB->get_recordset_sql($sql, ['userid' => $userid]);
        foreach ($recordset as $record) {
            if (isset($courses[$record->id]) === false) {
                $course = new stdClass();
                $course->id = $record->id;
                $course->fullname = $record->fullname;
                $course->sessions = [];

                $courses[$course->id] = $course;
            }

            $record->duration = course::getDuration($record->starttime, $record->endtime);
            $record->style = self::getStatusBootstrapStyle($record->color);
            $record->status = $record->longlabel;

            $courses[$record->id]->sessions[] = $record;
        }
        $recordset->close();

        return $courses;
    }

    /**
     * Méthode permettant de récupérer toutes les présences par méthode d'inscription à partir d'un ID d'un étudiant.
     *
     * @param int|string $userid Identifiant Moodle de l'utilisateur.
     *
     * @return array Tableau sous la forme array[enrolid] = (object) ['id' => enrolid, 'total' => total de présences].
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

        return $DB->get_records_sql($sql, ['userid' => $userid]);
    }
}
