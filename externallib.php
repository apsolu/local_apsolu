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
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/externallib.php');

function local_apsolu_is_valid_token() {
    global $DB;

    // Vérifier que le token appartienne à un enseignant du SIUAPS.
    $sql = "SELECT DISTINCT et.*".
        " FROM {external_tokens} et".
        " JOIN {external_services} es ON es.id = et.externalserviceid".
        " JOIN {role_assignments} ra ON et.userid = ra.userid AND ra.roleid = 3". // Enseignant.
        " JOIN {context} ctx ON ctx.id = ra.contextid".
        " JOIN {apsolu_courses} c ON ctx.instanceid = c.id".
        " WHERE et.token = :token".
        " AND et.token != ''".
        " AND es.component = 'local_apsolu'";
    $token = $DB->get_record_sql($sql, array('token' => optional_param('wstoken', '', PARAM_ALPHANUM)));

    return ($token !== false);
}

function local_apsolu_write_log($method, $arguments) {
    global $CFG;

    if (isset($CFG->apsolu_enable_ws_logging) === true) {
        if (is_file($CFG->apsolu_enable_ws_logging) === true) {
            // Place systématiquement en début de tableau le token utilisé.
            array_unshift($arguments, 'token='.optional_param('wstoken', '', PARAM_ALPHANUM));

            file_put_contents($CFG->apsolu_enable_ws_logging, strftime('%c').' '.$method.' '.implode(', ', $arguments).PHP_EOL, FILE_APPEND);
        }
    }
}

class local_apsolu_webservices extends external_api {
    /**
     * Returns users list.
     *
     * @return array
     */
    public static function get_users($since) {
        global $DB;

        $data = array();

        local_apsolu_write_log(__METHOD__, ['since='.$since]);

        // Vérifier que le token appartienne à un enseignant du SIUAPS.
        if (local_apsolu_is_valid_token() === false) {
            return $data;
        }

        $sql = "SELECT DISTINCT u.id AS iduser, u.username, u.auth, u.firstname, u.lastname, uid1.data AS cardnumber, 'category', u.institution, uid2.data AS nosportcard".
            " FROM {user} u".
            " JOIN {user_enrolments} ue ON u.id = ue.userid AND status = 0". // Restreint le téléchargement des utilisateurs à ceux qui ont ou ont eu au moins une inscription active dans une activité. TODO: à supprimer à la fin du test.
            " LEFT JOIN {user_info_data} uid1 ON u.id = uid1.userid AND uid1.fieldid = 16". // Numéro de carte VU.
            " LEFT JOIN {user_info_data} uid2 ON u.id = uid2.userid AND uid2.fieldid = 12". // Carte sport.
            " WHERE u.timemodified >= :timemodified".
            " AND u.deleted = 0".
            " ORDER BY u.lastname, u.firstname";
        foreach ($DB->get_records_sql($sql, array('timemodified' => $since)) as $record) {
            $user = new stdClass();
            $user->iduser = $record->iduser;
            $user->instuid = $record->auth.'|'.$record->username;
            $user->firstname = $record->firstname;
            $user->lastname = $record->lastname;
            $user->cardnumber = $record->cardnumber;
            $user->category = $record->category;
            $user->institution = $record->institution;
            $user->nosportcard = ($record->nosportcard !== '1');

            $data[] = $user;
        }

        return $data;
    }

    /**
     * Describes the parameters for get_users.
     *
     * @return external_external_function_parameters
     */
    public static function get_users_parameters() {
        return new external_function_parameters(
            array('since' => new external_value(PARAM_INT, get_string('ws_value_since', 'local_apsolu'), VALUE_DEFAULT, '0'))
        );
    }

    /**
     * Describes the get_users return value.
     *
     * @return external_single_structure
     */
    public static function get_users_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'iduser' => new external_value(PARAM_INT, get_string('ws_value_iduser', 'local_apsolu')),
                    'instuid' => new external_value(PARAM_RAW, get_string('ws_value_instuid', 'local_apsolu')),
                    'firstname' => new external_value(PARAM_RAW, get_string('ws_value_firstname', 'local_apsolu')),
                    'lastname' => new external_value(PARAM_RAW, get_string('ws_value_lastname', 'local_apsolu')),
                    'cardnumber' => new external_value(PARAM_RAW, get_string('ws_value_cardnumber', 'local_apsolu')),
                    'category' => new external_value(PARAM_RAW, get_string('ws_value_category', 'local_apsolu')),
                    'institution' => new external_value(PARAM_RAW, get_string('ws_value_institution', 'local_apsolu')),
                    'nosportcard' => new external_value(PARAM_BOOL, get_string('ws_value_nosportcard', 'local_apsolu')),
                )
            )
        );
    }

    /**
     * Returns activities list.
     *
     * @return array
     */
    public static function get_activities($since) {
        global $DB;

        $data = array();

        local_apsolu_write_log(__METHOD__, ['since='.$since]);

        // Vérifier que le token appartienne à un enseignant du SIUAPS.
        if (local_apsolu_is_valid_token() === false) {
            return $data;
        }

        $sql = "SELECT DISTINCT cc.id AS idactivity, cc.name".
            " FROM {course_categories} cc".
            " JOIN {course} c ON cc.id = c.category".
            " JOIN {apsolu_courses} ac ON c.id = ac.id".
            " WHERE c.timemodified >= :timemodified".
            " ORDER BY cc.name";
        foreach ($DB->get_records_sql($sql, array('timemodified' => $since)) as $record) {
            $activity = new stdClass();
            $activity->idactivity = $record->idactivity;
            $activity->name = $record->name;

            $data[] = $activity;
        }

        return $data;
    }

    /**
     * Describes the parameters for get_activities.
     *
     * @return external_external_function_parameters
     */
    public static function get_activities_parameters() {
        return new external_function_parameters(
            array('since' => new external_value(PARAM_INT, get_string('ws_value_since', 'local_apsolu'), VALUE_DEFAULT, '0'))
        );
    }

    /**
     * Describes the get_activities return value.
     *
     * @return external_single_structure
     */
    public static function get_activities_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'idactivity' => new external_value(PARAM_INT, get_string('ws_value_idactivity', 'local_apsolu')),
                    'name' => new external_value(PARAM_RAW, get_string('ws_value_activity_name', 'local_apsolu')),
                )
            )
        );
    }

    /**
     * Returns courses list.
     *
     * @return array
     */
    public static function get_courses($since) {
        global $DB;

        $data = array();

        local_apsolu_write_log(__METHOD__, ['since='.$since]);

        // Vérifier que le token appartienne à un enseignant du SIUAPS.
        if (local_apsolu_is_valid_token() === false) {
            return $data;
        }

        $sql = "SELECT c.id AS idcourse, c.category AS idactivity, ac.event, sk.name AS skill, ac.numweekday, ac.starttime, ac.endtime".
            " FROM {course} c".
            " JOIN {apsolu_courses} ac ON c.id = ac.id".
            " JOIN {apsolu_skills} sk ON sk.id = ac.skillid".
            " WHERE c.timemodified >= :timemodified".
            " ORDER BY c.fullname";
        foreach ($DB->get_records_sql($sql, array('timemodified' => $since)) as $record) {
            $course = new stdClass();
            $course->idcourse = $record->idcourse;
            $course->idactivity = $record->idactivity;
            $course->event = $record->event;
            $course->skill = $record->skill;
            $course->numweekday = $record->numweekday;
            $course->starttime = $record->starttime;
            $course->endtime = $record->endtime;

            $data[] = $course;
        }

        return $data;
    }

    /**
     * Describes the parameters for get_courses.
     *
     * @return external_external_function_parameters
     */
    public static function get_courses_parameters() {
        return new external_function_parameters(
            array('since' => new external_value(PARAM_INT, get_string('ws_value_since', 'local_apsolu'), VALUE_DEFAULT, '0'))
        );
    }

    /**
     * Describes the get_courses return value.
     *
     * @return external_single_structure
     */
    public static function get_courses_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'idcourse' => new external_value(PARAM_INT, get_string('ws_value_idcourse', 'local_apsolu')),
                    'idactivity' => new external_value(PARAM_INT, get_string('ws_value_idactivity', 'local_apsolu')),
                    'event' => new external_value(PARAM_RAW, get_string('ws_value_event', 'local_apsolu')),
                    'skill' => new external_value(PARAM_RAW, get_string('ws_value_skill', 'local_apsolu')),
                    'numweekday' => new external_value(PARAM_INT, get_string('ws_value_numweekday', 'local_apsolu')),
                    'starttime' => new external_value(PARAM_RAW, get_string('ws_value_starttime', 'local_apsolu')),
                    'endtime' => new external_value(PARAM_RAW, get_string('ws_value_endtime', 'local_apsolu')),
                )
            )
        );
    }

    /**
     * Returns registrations list.
     *
     * @return array
     */
    public static function get_registrations($since) {
        global $DB;

        $data = array();

        local_apsolu_write_log(__METHOD__, ['since='.$since]);

        // Vérifier que le token appartienne à un enseignant du SIUAPS.
        if (local_apsolu_is_valid_token() === false) {
            return $data;
        }

        $sql = "SELECT ra.id AS idregistration, ra.userid, ctx.instanceid AS idcourse, COUNT(aap.id) AS nbpresence, ra.roleid, ra.itemid AS enrolid, uid1.data AS sesame".
            " FROM {role_assignments} ra".
            " JOIN {role} r ON r.id = ra.roleid AND r.archetype = 'student'".
            " JOIN {user_enrolments} ue ON ra.userid = ue.userid AND ra.itemid = ue.enrolid AND ue.status = 0".
            " JOIN {context} ctx ON ctx.id = ra.contextid".
            " JOIN {apsolu_courses} c ON ctx.instanceid = c.id".
            " LEFT JOIN {apsolu_attendance_presences} aap ON ra.userid = aap.studentid AND aap.statusid IN (1, 2)". // Present + late.
            " LEFT JOIN {apsolu_attendance_sessions} aas ON aas.id = aap.sessionid".
            " LEFT JOIN {user_info_data} uid1 ON ra.userid = uid1.userid AND uid1.fieldid = 11". // Compte Sésame validé.
            " WHERE ra.timemodified >= :timemodified".
            " AND ra.component = 'enrol_select'".
            " AND ue.timeend > :now".
            " GROUP BY ra.id, ra.userid, ctx.instanceid";

        foreach ($DB->get_records_sql($sql, array('timemodified' => $since, 'now' => time())) as $record) {
            $sql = "SELECT DISTINCT ac.id".
                " FROM {apsolu_colleges} ac".
                " JOIN {apsolu_colleges_members} acm ON ac.id = acm.collegeid".
                " JOIN {cohort_members} cm ON cm.cohortid = acm.cohortid".
                " JOIN {enrol_select_cohorts} esc ON cm.cohortid = esc.cohortid".
                " WHERE cm.userid = :userid".
                " AND ac.roleid = :roleid".
                " AND esc.enrolid = :enrolid";
            $colleges = $DB->get_records_sql($sql, array('userid' => $record->userid, 'roleid' => $record->roleid, 'enrolid' => $record->enrolid));

            $registration = new stdClass();
            $registration->idregistration = $record->idregistration;
            $registration->iduser = $record->userid;
            $registration->idcourse = $record->idcourse;
            $registration->nbpresence = $record->nbpresence;
            $registration->validity = (count($colleges) > 0 && $record->sesame === '1');

            $data[] = $registration;
        }

        return $data;
    }

    /**
     * Describes the parameters for get_registrations.
     *
     * @return external_external_function_parameters
     */
    public static function get_registrations_parameters() {
        return new external_function_parameters(
            array('since' => new external_value(PARAM_INT, get_string('ws_value_since', 'local_apsolu'), VALUE_DEFAULT, '0'))
        );
    }

    /**
     * Describes the get_registrations return value.
     *
     * @return external_single_structure
     */
    public static function get_registrations_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'idregistration' => new external_value(PARAM_INT, get_string('ws_value_idregistration', 'local_apsolu')),
                    'iduser' => new external_value(PARAM_INT, get_string('ws_value_iduser', 'local_apsolu')),
                    'idcourse' => new external_value(PARAM_INT, get_string('ws_value_idcourse', 'local_apsolu')),
                    'nbpresence' => new external_value(PARAM_INT, get_string('ws_value_nbpresence', 'local_apsolu')),
                    'validity' => new external_value(PARAM_BOOL, get_string('ws_value_validity', 'local_apsolu')),
                )
            )
        );
    }

    /**
     * Returns unenrolment list.
     *
     * @return array
     */
    public static function get_unenrolments($since) {
        global $DB;

        $data = array();

        local_apsolu_write_log(__METHOD__, ['since='.$since]);

        // Vérifier que le token appartienne à un enseignant du SIUAPS.
        if (local_apsolu_is_valid_token() === false) {
            return $data;
        }

        $sql = "SELECT DISTINCT ra.id AS idregistration, ra.userid AS iduser, ctx.instanceid AS idcourse".
            " FROM {role_assignments} ra".
            " JOIN {role} r ON r.id = ra.roleid AND r.archetype = 'student'".
            " JOIN {context} ctx ON ctx.id = ra.contextid".
            " JOIN {enrol} e ON ctx.instanceid = e.courseid AND e.id = ra.itemid".
            " JOIN {user_enrolments} ue ON e.id = ue.enrolid AND ra.userid = ue.userid".
            " WHERE ra.component = 'enrol_select'".
            " AND e.enrol = 'select'".
            " AND e.status = 0".  // Active.
            " AND ue.status > 0". // Inactive.
            // " AND ue.timecreated != ue.timemodified".
            " AND ue.timemodified >= :timemodified";
        // uhb_dump_sql($sql, array('timemodified' => $since));
        return $DB->get_records_sql($sql, array('timemodified' => $since));
    }

    /**
     * Describes the parameters for get_unenrolments.
     *
     * @return external_external_function_parameters
     */
    public static function get_unenrolments_parameters() {
        return new external_function_parameters(
            array('since' => new external_value(PARAM_INT, get_string('ws_value_since', 'local_apsolu'), VALUE_DEFAULT, '0'))
        );
    }

    /**
     * Describes the get_unenrolments return value.
     *
     * @return external_single_structure
     */
    public static function get_unenrolments_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'idregistration' => new external_value(PARAM_INT, get_string('ws_value_idregistration', 'local_apsolu')),
                    'iduser' => new external_value(PARAM_INT, get_string('ws_value_iduser', 'local_apsolu')),
                    'idcourse' => new external_value(PARAM_INT, get_string('ws_value_idcourse', 'local_apsolu')),
                )
            )
        );
    }

    /**
     * Returns teachers list.
     *
     * @return array
     */
    public static function get_teachers($since) {
        global $DB;

        $data = array();

        local_apsolu_write_log(__METHOD__, ['since='.$since]);

        // Vérifier que le token appartienne à un enseignant du SIUAPS.
        if (local_apsolu_is_valid_token() === false) {
            return $data;
        }

        $sql = "SELECT ra.id, ra.userid, ctx.instanceid AS idcourse".
            " FROM {role_assignments} ra".
            " JOIN {context} ctx ON ctx.id = ra.contextid".
            " JOIN {apsolu_courses} c ON ctx.instanceid = c.id".
            " WHERE ra.timemodified >= :timemodified".
            " AND ra.roleid = 3". // Enseignant.
            " ORDER BY ra.userid, ctx.instanceid";
        foreach ($DB->get_records_sql($sql, array('timemodified' => $since)) as $record) {
            $teacher = new stdClass();
            $teacher->iduser = $record->userid;
            $teacher->idcourse = $record->idcourse;

            $data[] = $teacher;
        }

        return $data;
    }

    /**
     * Describes the parameters for get_teachers.
     *
     * @return external_external_function_parameters
     */
    public static function get_teachers_parameters() {
        return new external_function_parameters(
            array('since' => new external_value(PARAM_INT, get_string('ws_value_since', 'local_apsolu'), VALUE_DEFAULT, '0'))
        );
    }

    /**
     * Describes the get_teachers return value.
     *
     * @return external_single_structure
     */
    public static function get_teachers_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'iduser' => new external_value(PARAM_INT, get_string('ws_value_iduser', 'local_apsolu')),
                    'idcourse' => new external_value(PARAM_INT, get_string('ws_value_idcourse', 'local_apsolu')),
                )
            )
        );
    }

    /**
     * Returns attendances list.
     *
     * @return array
     */
    public static function get_attendances($since, $from) {
        global $DB;

        $data = array();

        local_apsolu_write_log(__METHOD__, ['since='.$since, 'from='.$from]);

        // Vérifier que le token appartienne à un enseignant du SIUAPS.
        if (local_apsolu_is_valid_token() === false) {
            return $data;
        }

        $sql = "SELECT aap.id, aap.studentid AS iduser, aas.courseid, aap.timemodified".
            " FROM {apsolu_attendance_presences} aap".
            " JOIN {apsolu_attendance_sessions} aas ON aas.id = aap.sessionid".
            " JOIN {apsolu_courses} ac ON ac.id = aas.courseid".
            " WHERE aap.statusid IN (1,2)". // Present + late.
            " AND aap.timecreated >= :from".
            " AND aap.timemodified >= :since".
            " ORDER BY aas.courseid";

        // uhb_dump_sql($sql, array('since' => $since));
        foreach ($DB->get_records_sql($sql, array('from' => $from, 'since' => $since)) as $record) {
            $attendance = new stdClass();
            $attendance->iduser = $record->iduser;
            $attendance->idcourse = $record->courseid;
            $attendance->timestamp = $record->timemodified;

            $data[] = $attendance;
        }

        return $data;
    }

    /**
     * Describes the parameters for get_attendances.
     *
     * @return external_external_function_parameters
     */
    public static function get_attendances_parameters() {
        return new external_function_parameters(
            array(
                'since' => new external_value(PARAM_INT, get_string('ws_value_since', 'local_apsolu'), VALUE_DEFAULT, '0'),
                'from' => new external_value(PARAM_INT, get_string('ws_value_from', 'local_apsolu'), VALUE_DEFAULT, '0'),
                )
        );
    }

    /**
     * Describes the get_attendances return value.
     *
     * @return external_single_structure
     */
    public static function get_attendances_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'iduser' => new external_value(PARAM_INT, get_string('ws_value_iduser', 'local_apsolu')),
                    'idcourse' => new external_value(PARAM_INT, get_string('ws_value_idcourse', 'local_apsolu')),
                    'timestamp' => new external_value(PARAM_INT, get_string('ws_value_timestamp', 'local_apsolu')),
                )
            )
        );
    }

    /**
     * Set cardnumber to user profile.
     *
     * @return array
     */
    public static function set_card($iduser, $cardnumber) {
        global $CFG, $DB;

        require_once($CFG->dirroot.'/user/profile/lib.php');

        $data = array('success' => false);

        local_apsolu_write_log(__METHOD__, ['iduser='.$iduser, 'cardnumber='.$cardnumber]);

        // Vérifier que le token appartienne à un enseignant du SIUAPS.
        if (local_apsolu_is_valid_token() === false) {
            return $data;
        }

        try {
            $userfield = (object) ['id' => $iduser, 'profile_field_apsoluidcardnumber' => $cardnumber];
            profile_save_data($userfield);
        } catch (Exception $exception) {
            return array('success' => false);
        }

        return array('success' => true);
    }

    /**
     * Describes the parameters for set_card.
     *
     * @return external_external_function_parameters
     */
    public static function set_card_parameters() {
        return new external_function_parameters(
            array(
                'iduser' => new external_value(PARAM_INT, get_string('ws_value_iduser', 'local_apsolu'), VALUE_DEFAULT, '0'),
                'cardnumber' => new external_value(PARAM_ALPHANUM, get_string('ws_value_cardnumber', 'local_apsolu'), VALUE_DEFAULT, ''),
                )
        );
    }

    /**
     * Describes the set_card return value.
     *
     * @return external_single_structure
     */
    public static function set_card_returns() {
        return new external_single_structure(
            array(
                'success' => new external_value(PARAM_BOOL, get_string('ws_value_boolean', 'local_apsolu')),
            )
        );
    }

    /**
     * Set course presence for a user.
     *
     * @return array
     */
    public static function set_presence($iduser, $idcourse, $timestamp) {
        global $DB;

        $data = array('success' => false);

        local_apsolu_write_log(__METHOD__, ['iduser='.$iduser, 'idcourse='.$idcourse, 'timestamp='.$timestamp]);

        // Vérifier que le token appartienne à un enseignant du SIUAPS.
        if (local_apsolu_is_valid_token() === false) {
            return $data;
        }

        $course = $DB->get_record('course', array('id' => $idcourse), '*', MUST_EXIST);

        $beforesessiontime = $timestamp - 2 * 60 * 60; // 2h avant le badgeage.
        $aftersessiontime = $timestamp + 2 * 60 * 60; // 2h après le badgeage.

        $sql = "SELECT id".
            " FROM {apsolu_attendance_sessions}".
            " WHERE courseid = :courseid";
            " AND sessiontime BETWEEN :beforesessiontime AND :aftersessiontime";
        $session = $DB->get_record_sql($sql, array('courseid' => $course->id, 'beforesessiontime' => $beforesessiontime, 'aftersessiontime' => $aftersessiontime), MUST_EXIST);

        $presence = new stdClass();
        $presence->studentid = $iduser;
        $presence->teacherid = $iduser;
        $presence->statusid = 1;
        $presence->description = '';
        $presence->timecreated = $timestamp;
        $presence->timemodified = time();
        $presence->sessionid = $session->id;

        try {
            $DB->insert_record('apsolu_attendance_presences', $presence);
        } catch (Exception $exception) {
            return array('success' => false);
        }

        return array('success' => true);
    }

    /**
     * Describes the parameters for set_presence.
     *
     * @return external_external_function_parameters
     */
    public static function set_presence_parameters() {
        return new external_function_parameters(
            array(
                'iduser' => new external_value(PARAM_INT, get_string('ws_value_iduser', 'local_apsolu'), VALUE_DEFAULT, '0'),
                'idcourse' => new external_value(PARAM_INT, get_string('ws_value_idcourse', 'local_apsolu'), VALUE_DEFAULT, '0'),
                'timestamp' => new external_value(PARAM_INT, get_string('ws_value_timestamp', 'local_apsolu'), VALUE_DEFAULT, '0'),
                )
        );
    }

    /**
     * Describes the set_presence return value.
     *
     * @return external_single_structure
     */
    public static function set_presence_returns() {
        return new external_single_structure(
            array(
                'success' => new external_value(PARAM_BOOL, get_string('ws_value_boolean', 'local_apsolu')),
            )
        );
    }
}
