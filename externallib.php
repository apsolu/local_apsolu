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

/*
 * Fonction qui génère ou retire les tokens d'accès aux webservices Apsolu pour les utilisateurs ayant un rôle enseignant au SIUAPS.
 *
 * @return void
 */
function local_apsolu_grant_ws_access() {
    global $CFG, $DB;

    require_once($CFG->libdir . '/externallib.php');
    require_once($CFG->dirroot . '/webservice/lib.php');

    $service = $DB->get_record('external_services', array('shortname' => 'apsolu'));
    if ($service === false) {
        mtrace('Le webservice apsolu n\'existe pas.');
        return;
    }

    $webservicemanager = new \webservice();

    $tokens = $DB->get_records('external_tokens', array('externalserviceid' => $service->id), $sort = '', $fields = 'userid, id');

    // Ajoute les tokens.
    $sql = "SELECT DISTINCT u.id, u.firstname, u.lastname".
        " FROM {user} u".
        " JOIN {role_assignments} ra ON u.id = ra.userid".
        " JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50".
        " JOIN {apsolu_courses} ac ON ac.id = ctx.instanceid".
        " JOIN {course} c ON c.id = ac.id AND c.visible = 1".
        " WHERE ra.roleid = 3"; // Teacher.
    $users = $DB->get_records_sql($sql);
    foreach ($users as $user) {
        if (isset($tokens[$user->id]) === true) {
            continue;
        }

        $serviceuser = new \stdClass();
        $serviceuser->externalserviceid = $service->id;
        $serviceuser->userid = $user->id;
        $webservicemanager->add_ws_authorised_user($serviceuser);

        $params = array(
            'objectid' => $serviceuser->externalserviceid,
            'relateduserid' => $serviceuser->userid
            );
        $event = \core\event\webservice_service_user_added::create($params);
        $event->trigger();

        external_generate_token(EXTERNAL_TOKEN_PERMANENT, $service->id, $user->id, \context_system::instance(), $validuntil = 0, $iprestriction = '');

        mtrace('-> Token créé pour l\'utilisateur #'.$user->id.' '.$user->firstname.' '.$user->lastname);
    }

    // Supprime les tokens.
    foreach ($tokens as $token) {
        if (isset($users[$token->userid]) === true) {
            continue;
        }

        $user = $DB->get_record('user', array('id' => $token->userid));

        if ($user === false) {
            mtrace('Token supprimé pour l\'utilisateur #'.$token->userid.' (non trouvable dans la table user)');
            continue;
        }

        $webservicemanager->delete_user_ws_token($token->id);

        $webservicemanager->remove_ws_authorised_user($user, $service->id);

        $params = array(
            'objectid' => $service->id,
            'relateduserid' => $user->id,
        );
        $event = \core\event\webservice_service_user_removed::create($params);
        $event->trigger();

        mtrace('-> Token supprimé pour l\'utilisateur #'.$user->id.' '.$user->firstname.' '.$user->lastname);
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
            local_apsolu_write_log(__METHOD__, ['since='.$since, get_string('invalidtoken', 'webservice')]);
            return $data;
        }

        $fields = $DB->get_records('user_info_field', $conditions = array(), $sort = '', $fields = 'shortname, id');

        $sql = "SELECT DISTINCT u.id AS iduser, u.username, u.auth, u.firstname, u.lastname, IFNULL(uid1.data, '') AS cardnumber, 'category', u.institution, IFNULL(uid2.data, '') AS nosportcard".
            " FROM {user} u".
            " JOIN {user_enrolments} ue ON u.id = ue.userid AND status = 0". // Restreint le téléchargement des utilisateurs à ceux qui ont ou ont eu au moins une inscription active dans une activité. TODO: à supprimer à la fin du test.
            " LEFT JOIN {user_info_data} uid1 ON u.id = uid1.userid AND uid1.fieldid = :apsoluidcardnumber". // Numéro de carte VU.
            " LEFT JOIN {user_info_data} uid2 ON u.id = uid2.userid AND uid1.fieldid = :apsolucardpaid". // Carte sport.
            " WHERE u.timemodified >= :timemodified".
            " AND u.deleted = 0".
            " ORDER BY u.lastname, u.firstname";
        foreach ($DB->get_records_sql($sql, array('timemodified' => $since, 'apsoluidcardnumber' => $fields['apsoluidcardnumber']->id, 'apsolucardpaid' => $fields['apsolucardpaid']->id)) as $record) {
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
            local_apsolu_write_log(__METHOD__, ['since='.$since, get_string('invalidtoken', 'webservice')]);
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
            local_apsolu_write_log(__METHOD__, ['since='.$since, get_string('invalidtoken', 'webservice')]);
            return $data;
        }

        $semester1_enrol_startdate = get_config('local_apsolu', 'semester1_enrol_startdate');
        $semester1_enrol_startdate = strftime('%Y-%m-%d', $semester1_enrol_startdate);

        $sql = "SELECT c.id AS idcourse, c.category AS idactivity, ac.event, sk.name AS skill, ac.numweekday, ac.starttime, ac.endtime".
            " FROM {course} c".
            " JOIN {apsolu_courses} ac ON c.id = ac.id".
            " JOIN {apsolu_skills} sk ON sk.id = ac.skillid".
            " JOIN {apsolu_periods} ap ON ap.id = ac.periodid".
            " WHERE c.timemodified >= :timemodified".
            " AND ap.weeks >= :semester1_enrol_startdate". // On ne propose que les cours de l'année en cours ; pas les cours antérieurs au S1.
            " ORDER BY c.fullname";
        foreach ($DB->get_records_sql($sql, array('timemodified' => $since, 'semester1_enrol_startdate' => $semester1_enrol_startdate)) as $record) {
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
            local_apsolu_write_log(__METHOD__, ['since='.$since, get_string('invalidtoken', 'webservice')]);
            return $data;
        }

        $fields = $DB->get_records('user_info_field', $conditions = array(), $sort = '', $fields = 'shortname, id');

        $sql = "SELECT ra.id AS idregistration, ra.userid, ctx.instanceid AS idcourse, COUNT(aap.id) AS nbpresence, ra.roleid, ra.itemid AS enrolid, uid1.data AS sesame".
            " FROM {role_assignments} ra".
            " JOIN {role} r ON r.id = ra.roleid AND r.archetype = 'student'".
            " JOIN {user_enrolments} ue ON ra.userid = ue.userid AND ra.itemid = ue.enrolid AND ue.status = 0".
            " JOIN {context} ctx ON ctx.id = ra.contextid".
            " JOIN {apsolu_courses} c ON ctx.instanceid = c.id".
            " JOIN {apsolu_attendance_sessions} aas ON ctx.instanceid = aas.courseid".
            " JOIN {apsolu_attendance_presences} aap ON aas.id = aap.sessionid AND ra.userid = aap.studentid AND aap.statusid IN (1, 2)". // Present + late.
            " LEFT JOIN {user_info_data} uid1 ON ra.userid = uid1.userid AND uid1.fieldid = :apsolusesame". // Compte Sésame validé.
            " WHERE ra.component = 'enrol_select'".
            " AND ue.timeend >= :now".
            " AND ue.timemodified >= :timemodified".
            " GROUP BY ra.id, ra.userid, ctx.instanceid";

        foreach ($DB->get_records_sql($sql, array('apsolusesame' => $fields['apsolusesame']->id, 'now' => time(), 'timemodified' => $since)) as $record) {
            // Calcul la validité de l'inscription.
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
            local_apsolu_write_log(__METHOD__, ['since='.$since, get_string('invalidtoken', 'webservice')]);
            return $data;
        }

        if (empty($since) === true) {
            // Normalement, à l'initialisation, on n'a pas besoin des désinscriptions.
            return array();
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
            " AND (ue.timemodified >= :timemodified".
            " OR ue.timeend BETWEEN :since AND :now)";
        return $DB->get_records_sql($sql, array('timemodified' => $since, 'since' => $since, 'now' => time()));
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
            local_apsolu_write_log(__METHOD__, ['since='.$since, get_string('invalidtoken', 'webservice')]);
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
            local_apsolu_write_log(__METHOD__, ['since='.$since, 'from='.$from, get_string('invalidtoken', 'webservice')]);
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
            local_apsolu_write_log(__METHOD__, ['iduser='.$iduser, 'cardnumber='.$cardnumber, get_string('invalidtoken', 'webservice')]);
            return $data;
        }

        try {
            $userfield = (object) ['id' => $iduser, 'profile_field_apsoluidcardnumber' => $cardnumber];

            $errors = profile_validation($userfield, $files = array());
            if (count($errors) > 0) {
                local_apsolu_write_log(__METHOD__, ['iduser='.$iduser, 'cardnumber='.$cardnumber, 'impossible d\'enregistrer la carte ('.json_encode($errors).')']);
                throw new Exception(json_encode($errors));
            }

            profile_save_data($userfield);
            local_apsolu_write_log(__METHOD__, ['iduser='.$iduser, 'cardnumber='.$cardnumber, 'enregistrement d\'une nouvelle carte']);

            // Ajoute le témoin que la carte provient d'une source externe.
            $userfield = (object) ['id' => $iduser, 'profile_field_apsoluidcardnumberexternal' => 1];
            profile_save_data($userfield);

            // TODO: proposer un bug report à Moodle.org ?
            $user = $DB->get_record('user', array('id' => $iduser));
            $user->timemodified = time();
            $DB->update_record('user', $user);
            local_apsolu_write_log(__METHOD__, ['iduser='.$iduser, 'timemodified='.$user->timemodified, 'mise à jour du champ timemodified de l\'utilisateur']);
        } catch (Exception $exception) {
            local_apsolu_write_log(__METHOD__, ['iduser='.$iduser, 'cardnumber='.$cardnumber, 'impossible d\'enregistrer la carte en DB']);

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
            local_apsolu_write_log(__METHOD__, ['iduser='.$iduser, 'idcourse='.$idcourse, 'timestamp='.$timestamp, get_string('invalidtoken', 'webservice')]);
            return $data;
        }

        $course = $DB->get_record('course', array('id' => $idcourse), '*', MUST_EXIST);

        $beforesessiontime = $timestamp - 2 * 60 * 60; // 2h avant le badgeage.
        $aftersessiontime = $timestamp + 2 * 60 * 60; // 2h après le badgeage.

        $sql = "SELECT id".
            " FROM {apsolu_attendance_sessions}".
            " WHERE courseid = :courseid".
            " AND sessiontime BETWEEN :beforesessiontime AND :aftersessiontime";

        // Cherche la première session dont l'heure de début est comprise dans un interval de 2h avec l'horodatage de la présence saisie.
        $sessions = $DB->get_records_sql($sql, array('courseid' => $course->id, 'beforesessiontime' => $beforesessiontime, 'aftersessiontime' => $aftersessiontime));
        $session = current($sessions);

        if (isset($session->id) === false) {
            local_apsolu_write_log(__METHOD__, ['iduser='.$iduser, 'idcourse='.$idcourse, 'timestamp='.$timestamp, 'session non trouvée dans un interval de 2h']);

            $beforesessiontime = strtotime('monday this week', $timestamp);
            $aftersessiontime = strtotime('sunday this week', $timestamp);

            // Cherche la première session dont l'heure de début est comprise dans l'interval de la semaine correspondant à l'horodatage de la présence saisie.
            $sessions = $DB->get_records_sql($sql, array('courseid' => $course->id, 'beforesessiontime' => $beforesessiontime, 'aftersessiontime' => $aftersessiontime));
            $session = current($sessions);
        }

        if (isset($session->id) === false) {
            local_apsolu_write_log(__METHOD__, ['iduser='.$iduser, 'idcourse='.$idcourse, 'timestamp='.$timestamp, 'session non trouvée dans un interval de la semaine']);

            $sql = "SELECT id".
                " FROM {apsolu_attendance_sessions}".
                " WHERE courseid = :courseid".
                " AND sessiontime >= :timestamp";

            // Cherche la première session dont l'heure de début est supérieure à l'horodatage de la présence saisie.
            $sessions = $DB->get_records_sql($sql, array('courseid' => $course->id, 'timestamp' => $timestamp));
            $session = current($sessions);
        }

        if (isset($session->id) === false) {
            local_apsolu_write_log(__METHOD__, ['iduser='.$iduser, 'idcourse='.$idcourse, 'timestamp='.$timestamp, 'impossible de trouver une session supérieure à la date du timestamp']);

            $sql = "SELECT id".
                " FROM {apsolu_attendance_sessions}".
                " WHERE courseid = :courseid".
                " AND sessiontime <= :timestamp".
                " ORDER BY sessiontime DESC";

            // Cherche la première session dont l'heure de début est inférieure à l'horodatage de la présence saisie.
            $sessions = $DB->get_records_sql($sql, array('courseid' => $course->id, 'timestamp' => $timestamp));
            $session = current($sessions);
        }

        if (isset($session->id) === false) {
            local_apsolu_write_log(__METHOD__, ['iduser='.$iduser, 'idcourse='.$idcourse, 'timestamp='.$timestamp, 'impossible de trouver une session inférieure à la date du timestamp']);
            local_apsolu_write_log(__METHOD__, ['iduser='.$iduser, 'idcourse='.$idcourse, 'timestamp='.$timestamp, 'impossible de trouver une session pour ce cours']);

            return array('success' => true);
        }

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
            local_apsolu_write_log(__METHOD__, ['iduser='.$iduser, 'idcourse='.$idcourse, 'timestamp='.$timestamp, 'impossible d\'enregistrer la présence']);

            return array('success' => true);
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

    /**
     * Get debug messages.
     *
     * @return array
     */
    public static function debugging($serial, $idteacher, $message, $timestamp) {
        global $CFG, $DB;

        local_apsolu_write_log(__METHOD__, ['serial='.$serial, 'idteacher='.$idteacher, 'message='.$message, 'timestamp='.$timestamp]);

        // Vérifier que le token appartienne à un enseignant du SIUAPS.
        if (local_apsolu_is_valid_token() === false) {
            local_apsolu_write_log(__METHOD__, ['serial='.$serial, 'idteacher='.$idteacher, 'message='.$message, 'timestamp='.$timestamp, get_string('invalidtoken', 'webservice')]);

            return array('success' => false);
        }

        if (isset($CFG->apsolu_enable_ws_debugging) === true) {
            file_put_contents($CFG->apsolu_enable_ws_debugging, strftime('%c').' '.__METHOD__.' '.$message.PHP_EOL, FILE_APPEND);

            return array('success' => true);
        }

        return array('success' => false);
    }

    /**
     * Describes the parameters for debugging.
     *
     * @return external_external_function_parameters
     */
    public static function debugging_parameters() {
        return new external_function_parameters(
            array(
                'serial' => new external_value(PARAM_ALPHANUM, get_string('ws_value_serial', 'local_apsolu'), VALUE_DEFAULT, ''),
                'idteacher' => new external_value(PARAM_INT, get_string('ws_value_idteacher', 'local_apsolu'), VALUE_DEFAULT, '0'),
                'message' => new external_value(PARAM_TEXT, get_string('ws_value_message', 'local_apsolu'), VALUE_DEFAULT, ''),
                'timestamp' => new external_value(PARAM_INT, get_string('ws_value_timestamp', 'local_apsolu'), VALUE_DEFAULT, '0'),
                )
        );
    }

    /**
     * Describes the debugging return value.
     *
     * @return external_single_structure
     */
    public static function debugging_returns() {
        return new external_single_structure(
            array(
                'success' => new external_value(PARAM_BOOL, get_string('ws_value_boolean', 'local_apsolu')),
            )
        );
    }
}
