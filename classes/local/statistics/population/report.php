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
 * Classe Moodle.
 *
 * @package    local_apsolu
 * @copyright  2019 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_apsolu\local\statistics\population;

defined('MOODLE_INTERNAL') || die();

/**
 * Classe Moodle.
 *
 * @package    local_apsolu
 * @copyright  2019 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report extends \local_apsolu\local\statistics\report {

    /**
     * @var sous-requête contenant l'ensemble des inscriptions aux activités
     * TODO transformer en vue
     */
    public $WithEnrolments;

    /**
     * @var sous-requête contenant l'ensemble des inscriptions aux activités complémentaires
     * TODO transformer en vue
     */
    public $WithComplementary;

    /**
     * Constructeur de la classe.
     *
     * @return void
     */
    public function __construct() {
        $this->configFilePath = '/local/apsolu/statistics/population/config.json';

        $this->WithEnrolments = 'WITH enrolments AS (
      		SELECT DISTINCT
            AC.id as calendarid, AC.name as calendarname,
      			ACT.id as calendartypeid,ACT.name as calendartypename,
            ACI.id as cityid,ACI.name as cityname,
      			U.institution, U.department, U.id as userid, U.idnumber, U.firstname, U.lastname, U.email,
            Sexe.data as sexe, UFR.data as ufr, LMD.data as lmd, apsoluhighlevelathlete.data as shnu,
            CASE WHEN apsolucardpaid.data THEN "Oui" ELSE "Non" END as apsolucardpaid,
      			E.id as enrolid, E.name as enrolname, UE.status,
            CASE WHEN (U.email LIKE \'%@etudiant.univ-%\' OR U.email LIKE \'%@eleves.%\' OR U.email LIKE \'%@etud.univ-%\' OR U.email LIKE \'%@etudiant.%\')
            	THEN \'Étudiant\'
              ELSE CASE WHEN (U.institution IS NOT NULL AND U.institution <> \'\') OR U.email LIKE \'%@univ-%\'
            		THEN \'Personnel\'
            		ELSE \'Inconnu\'
            END END AS userprofile,
            CASE WHEN UE.status = 0 THEN "Liste des étudiants acceptés"
      				ELSE CASE WHEN UE.status = 2 THEN "Liste principale"
      				ELSE CASE WHEN UE.status = 3 THEN "Liste complémentaire"
      				ELSE CASE WHEN UE.status = 4 THEN "Liste des étudiants désinscrits"
      			END END END END AS statusname,
      			APSOLU_C.id as slotid, APSOLU_C.event as slotevent,APSOLU_C.numweekday as slotnumweekday,DAYNAME(CONCAT("1970-09-2", APSOLU_C.numweekday)) as slotweekday, APSOLU_C.starttime as slotstart,	APSOLU_C.endtime as slotend,
      			Activity.id as activityid, Activity.name as activityname,
      			Grouping.id as groupid, Grouping.name as groupname,
            (SELECT GROUP_CONCAT(DISTINCT R.shortname ORDER BY R.shortname SEPARATOR \', \')
  					FROM mdl_role_assignments ra
  					INNER JOIN mdl_context ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
  				   INNER JOIN mdl_role R ON ra.roleid = R.id AND R.archetype = \'student\'
  					WHERE ra.userid = U.id AND ctx.instanceid = C.id AND ra.itemid = UE.enrolid
  					) as roleshortname,
  					(SELECT GROUP_CONCAT(DISTINCT CONCAT(U.firstname,\' \', U.lastname) ORDER BY U.firstname,U.lastname SEPARATOR \', \')
  					FROM mdl_role_assignments ra
  					INNER JOIN mdl_user U ON U.id = ra.userid AND U.deleted = 0
  					INNER JOIN mdl_context ctx ON ctx.id = ra.contextid  AND ctx.contextlevel = 50
  					INNER JOIN mdl_role R ON ra.roleid = R.id AND R.archetype != \'student\'
  					WHERE ctx.instanceid = E.courseid) as teachers,
            AL.name as locationname,
            APSOLU_S.id,APSOLU_S.name as skillsname,
            CONCAT(APSOLU_C.starttime,\'-\',APSOLU_C.endtime) as slotstartend,
            CASE WHEN MONTH(CAST(FROM_UNIXTIME(UE.timestart) as date)) > 8 THEN AG.grade1 ELSE AG.grade3 END AS practicegrade,
            CASE WHEN MONTH(CAST(FROM_UNIXTIME(UE.timestart) as date)) > 8 THEN AG.grade2 ELSE AG.grade4 END AS theorygrade 
                        
          FROM {user_enrolments} UE
          INNER JOIN {user} U ON U.id = UE.userid AND U.deleted = 0
          LEFT JOIN {user_info_data} Sexe ON Sexe.userid = U.id AND Sexe.fieldid = (select id from mdl_user_info_field where shortname = \'apsolusex\')
          LEFT JOIN {user_info_data} UFR ON UFR.userid = U.id AND UFR.fieldid = (select id from mdl_user_info_field where shortname = \'apsoluufr\')
          LEFT JOIN {user_info_data} LMD ON LMD.userid = U.id AND LMD.fieldid = (select id from mdl_user_info_field where shortname = \'apsolucycle\')
          LEFT JOIN {user_info_data} apsolucardpaid ON apsolucardpaid.userid = U.id AND apsolucardpaid.fieldid = (select id from mdl_user_info_field where shortname = \'apsolucardpaid\')
          LEFT JOIN {user_info_data} apsoluhighlevelathlete ON apsoluhighlevelathlete.userid = U.id AND apsoluhighlevelathlete.fieldid = (select id from mdl_user_info_field where shortname = \'apsoluhighlevelathlete\')
          INNER JOIN {enrol} E ON E.id = UE.enrolid AND E.enrol = \'select\'
          INNER JOIN {course} C on C.id = E.courseid
          INNER JOIN {apsolu_courses} APSOLU_C on APSOLU_C.id = C.id
          INNER JOIN {apsolu_skills} APSOLU_S on APSOLU_S.id = APSOLU_C.skillid
          INNER JOIN {apsolu_locations} AL ON AL.id = APSOLU_C.locationid
          INNER JOIN {apsolu_areas} AA ON AA.id = AL.areaId
          INNER JOIN {apsolu_cities} ACI ON ACI.id = AA.cityId
          INNER JOIN {course_categories} Activity ON Activity.id = C.category
          INNER JOIN {course_categories} Grouping on Grouping.id = Activity.parent
          LEFT JOIN {apsolu_calendars} AC ON AC.id = E.customchar1
          LEFT JOIN {apsolu_calendars_types} ACT ON ACT.id = AC.typeid
          INNER JOIN {role_assignments} ra ON ra.userid = UE.userid
          INNER JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50 AND ctx.instanceid = C.id
          INNER JOIN {role} R ON ra.roleid = R.id AND R.archetype = \'student\'
          LEFT JOIN {apsolu_grades} AG ON AG.courseid = C.id AND AG.userid = U.id
          ORDER BY ACT.id,U.institution, U.department, U.id, U.idnumber
      	) ';

        $this->WithComplementary = 'WITH enrolments AS (
      		SELECT DISTINCT
            AC.id as calendarid, AC.name as calendarname,
      			ACT.id as calendartypeid,ACT.name as calendartypename,
      			U.institution, U.department, U.id as userid, U.idnumber, U.firstname, U.lastname, U.email,
            Sexe.data as sexe, UFR.data as ufr, LMD.data as lmd, apsoluhighlevelathlete.data as shnu,
            CASE WHEN apsolucardpaid.data THEN "Oui" ELSE "Non" END as apsolucardpaid,
      			E.id as enrolid, E.name as enrolname, UE.status,
            CASE WHEN (U.email LIKE \'%@etudiant.univ-%\' OR U.email LIKE \'%@eleves.%\' OR U.email LIKE \'%@etud.univ-%\' OR U.email LIKE \'%@etudiant.%\')
            	THEN \'Étudiant\'
              ELSE CASE WHEN (U.institution IS NOT NULL AND U.institution <> \'\') OR U.email LIKE \'%@univ-%\'
            		THEN \'Personnel\'
            		ELSE \'Inconnu\'
            END END AS userprofile,
            CASE WHEN UE.status = 0 THEN "Liste des étudiants acceptés"
      				ELSE CASE WHEN UE.status = 2 THEN "Liste principale"
      				ELSE CASE WHEN UE.status = 3 THEN "Liste complémentaire"
      				ELSE CASE WHEN UE.status = 4 THEN "Liste des étudiants désinscrits"
      			END END END END AS statusname,
      			APSOLU_C.id as slotid,
      			C.id as activityid, C.fullname as activityname,
				    Grouping.id as groupid, Grouping.name as groupname,
            (SELECT GROUP_CONCAT(DISTINCT R.shortname ORDER BY R.shortname SEPARATOR \', \')
  					FROM mdl_role_assignments ra
  					INNER JOIN mdl_context ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
  				   INNER JOIN mdl_role R ON ra.roleid = R.id AND R.archetype = \'student\'
  					WHERE ra.userid = U.id AND ctx.instanceid = C.id AND ra.itemid = UE.enrolid
  					) as roleshortname
      		FROM {user_enrolments} UE
      		INNER JOIN {user} U ON U.id = UE.userid AND U.deleted = 0
          LEFT JOIN {user_info_data} Sexe ON Sexe.userid = U.id AND Sexe.fieldid = (select id from mdl_user_info_field where shortname = \'apsolusex\')
          LEFT JOIN {user_info_data} UFR ON UFR.userid = U.id AND UFR.fieldid = (select id from mdl_user_info_field where shortname = \'apsoluufr\')
          LEFT JOIN {user_info_data} LMD ON LMD.userid = U.id AND LMD.fieldid = (select id from mdl_user_info_field where shortname = \'apsolucycle\')
          LEFT JOIN {user_info_data} apsolucardpaid ON apsolucardpaid.userid = U.id AND apsolucardpaid.fieldid = (select id from mdl_user_info_field where shortname = \'apsolucardpaid\')
          LEFT JOIN {user_info_data} apsoluhighlevelathlete ON apsoluhighlevelathlete.userid = U.id AND apsoluhighlevelathlete.fieldid = (select id from mdl_user_info_field where shortname = \'apsoluhighlevelathlete\')
      		INNER JOIN {enrol} E ON E.id = UE.enrolid AND E.enrol = \'select\'
      		INNER JOIN {course} C on C.id = E.courseid
      		INNER JOIN {apsolu_complements} APSOLU_C on APSOLU_C.id = C.id
      		INNER JOIN {course_categories} Grouping ON Grouping.id = C.category
      		LEFT JOIN {apsolu_calendars} AC ON AC.id = E.customchar1
      		LEFT JOIN {apsolu_calendars_types} ACT ON ACT.id = AC.typeid
          INNER JOIN {role_assignments} ra ON ra.userid = UE.userid
		      INNER JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50 AND ctx.instanceid = C.id
      		INNER JOIN {role} R ON ra.roleid = R.id AND R.archetype = \'student\'
     		  ORDER BY ACT.id,U.institution, U.department, U.id, U.idnumber
      	) ';

    }

    /**
     * Retourne les critères d'affichage du rapport.
     *
     * @param null|int $datatype
     *
     * @return array
     */
    public function getReportDisplay($datatype = null) {
        $columns = [
          [ 'data' => "userprofile", 'title' => "Type"],
          [ 'data' => "idnumber", 'title' => "Numéro étudiant"] ,
          [ 'data' => "firstname", 'title' => "Prénom"] ,
          [ 'data' => "lastname", 'title' => "Nom"] ,
          [ 'data' => "email", 'title' => "Email"] ,
          [ 'data' => "sexe", 'title' => "Sexe"] ,
          [ 'data' => "institution", 'title' => "Institution"] ,
          [ 'data' => "department", 'title' => "Département"] ,
          [ 'data' => "ufr", 'title' => "UFR"] ,
          [ 'data' => "lmd", 'title' => "Cycle LMD"]
        ];

        switch ($datatype) {
            case 2: // Vue activités physiques par candidats
                $columnsDatatype = [
                [ 'data' => "wish_list", 'title' => "Nombre de voeux"] ,
                [ 'data' => "accepted_list", 'title' => "Nombre d'inscriptions acceptées"] ,
                [ 'data' => "main_list", 'title' => "Nombre d'inscriptions sur liste principale"] ,
                [ 'data' => "wait_list", 'title' => "Nombre d'inscriptions sur liste d'attente"] ,
                [ 'data' => "deleted_list", 'title' => "Nombre de déinscriptions"]
                ];

                $orders = [2 => 'asc', 3 => 'asc'];
                $filters = ['input' => [1,2,3,4,7,8],'select' => [0,5,6,9,10,11,12,13,14] ];
              break;
            case 4: // Vue activités complémentaires par candidats
                $columnsDatatype = [
                [ 'data' => "wish_list", 'title' => "Nombre de voeux"] ,
                [ 'data' => "accepted_list", 'title' => "Nombre d'inscriptions acceptées"] ,
                [ 'data' => "deleted_list", 'title' => "Nombre de déinscriptions"]
                ];

                $orders = [2 => 'asc', 3 => 'asc'];
                $filters = ['input' => [1,2,3,4,7,8],'select' => [0,5,6,9,10,11,12] ];
              break;
            case 3: // 1 : Vue activités compmlémentaires par inscription
                $columnsDatatype = [
                [ 'data' => "activityname", 'title' => "Activité complémentaire"],
                [ 'data' => "apsolucardpaid", 'title' => "Carte sport"] ,
                [ 'data' => "statusname", 'title' => "Liste (statut inscription)"],
                ];
                $orders = [2 => 'asc', 3 => 'asc'];
                $filters = ['input' => [1,2,3,4,7,8],'select' => [0,5,6,9,10,11,12] ];

              break;
            default: // 1 : Vue activités physiques par inscription
                $columnsDatatype = [
                [ 'data' => "calendarname", 'title' => "Calendrier"] ,
                [ 'data' => "calendartypename", 'title' => "Type de calendrier"] ,
                [ 'data' => "cityname", 'title' => "Site"] ,
                [ 'data' => "roleshortname", 'title' => "Type d'inscription"] ,
                [ 'data' => "enrolname", 'title' => "Inscriptions (semestre)"] ,
                [ 'data' => "apsolucardpaid", 'title' => "Carte sport"] ,
                [ 'data' => "statusname", 'title' => "Liste (statut inscription)"],
                [ 'data' => "groupname", 'title' => "Groupements d'activités"],
                [ 'data' => "activityname", 'title' => "Activité sportive"],
                [ 'data' => "skillsname", 'title' => "Niveaux"],
                [ 'data' => "slotnumweekday", 'title' => "Jours","render" => "function ( data, type, row ) {return moment.weekdays()[data];}"],
                [ 'data' => "slotstartend", 'title' => "Horaires"],
                [ 'data' => "locationname", 'title' => "Lieu"],
                [ 'data' => "teachers", 'title' => "Enseignants"],
                [ 'data' => "practicegrade", 'title' => get_string('practicegrade', 'local_apsolu')],
                [ 'data' => "theorygrade", 'title' => get_string('theorygrade', 'local_apsolu')],
                [ 'data' => null, 'visible' => false, 'title' => "Activité détaillée","render" => "function ( data, type, row ) {return data.activityname.replace(/\s/g,'&nbsp;') + '&nbsp;/&nbsp;' + moment.weekdays()[data.slotnumweekday] +'&nbsp;/&nbsp;' + data.slotstart + '&nbsp;-&nbsp;' + data.slotend + '&nbsp;/&nbsp;' + data.skillsname.replace(/\s/g,'&nbsp;');}"],
                ];
                $orders = [2 => 'asc', 3 => 'asc'];
                $filters = ['input' => [1,2,3,4,7,8,13],'select' => [0,5,6,9,10,11,12,14,15,16,17,18,19,20,21,22,23] ];

              break;
        }
        $columns = array_merge($columns, $columnsDatatype);

        return array(
          'columns' => $columns,
          'orders' => $orders,
          'filters' => $filters,
        );

    }

    /**
     * Retourne les résultats en fonction de la vue choisie et des critères de recherche.
     *
     * @param string     $queryBuilder Chaine de caractères au format JSON.
     * @param null|array $criterias
     *
     * @return array
     */
    public function getReportData($queryBuilder,$criterias=null) {
        global $DB;

        $condition = json_decode($queryBuilder);

        $with = $this->WithEnrolments;
        if ($condition->datatype == 3 || $condition->datatype == 4) { $with = $this->WithComplementary;
        }

        $select = "";
        $where = "WHERE 1=1 ";
        $groupby = "";
        $having = "";
        $orderby = "";
        switch ($condition->datatype) {
            case 2:
            case 4:
                // Vue activités physiques par candidats
                // Vue activités complémentaires par candidats
                $select = 'SELECT DISTINCT e.userid,e.idnumber,e.firstname,e.lastname,e.email,e.sexe,e.institution, e.department, e.ufr, e.lmd, e.userprofile,
          		COUNT(status) AS wish_list,
          		COUNT(CASE WHEN status=0 THEN 1 ELSE NULL END) AS accepted_list,
          		COUNT(CASE WHEN status=2 THEN 1 ELSE NULL END) AS main_list,
          		COUNT(CASE WHEN status=3 THEN 1 ELSE NULL END) AS wait_list,
          		COUNT(CASE WHEN status=4 THEN 1 ELSE NULL END) AS deleted_list ';
                $groupby = " GROUP BY e.userid ";
              break;
            default: // 1 : Vue activités physiques par inscription
                $select = 'SELECT ROW_NUMBER() OVER (ORDER BY e.enrolid ASC) AS row_num, e.* ';
              break;
        }

        $from = 'FROM enrolments e ';

        if(property_exists($condition, "sql")) {
            $where .= " AND ". $condition->sql;
        }
        if (!is_null($criterias)) {
            if (array_key_exists("cityid",$criterias) && $criterias["cityid"] != '') {
                $where .= " AND cityid = " . $criterias["cityid"];
            }
            if (array_key_exists("calendarstypeid",$criterias) && $criterias["calendarstypeid"] != '') {
                $where .= " AND calendarstypeid = " . $criterias["calendarstypeid"];
            }
            if (array_key_exists("activityid",$criterias) && $criterias["activityid"] != '') {
                $where .= " AND activityid = " . $criterias["activityid"];
            }
        }

        if(property_exists($condition, "having")) {
            $having = 'HAVING '.$condition->having;
        }

        if(property_exists($condition, "order")) {
            $orderby = 'ORDER BY '.$condition->order;
        }

        $sql = $with. $select . $from . $where . $groupby . $having . $orderby;

        if(property_exists($condition, "params")) {
            return $DB->get_records_sql($sql,$condition->params);
        } else {
            return $DB->get_records_sql($sql);
        }

    }

    /**
     * Nombre de candidats à l'inscription n'ayant obtenu aucune inscription.
     *
     * @param array $params
     *
     * @return array
     */
    public function get_nowishes_list($params) {
        global $DB;

        $sql = $params["WithEnrolments"] . 'SELECT DISTINCT U.id,U.idnumber, U.firstname, U.lastname, U.email,U.institution,U.department
        FROM {user} U
        INNER JOIN {cohort_members} CM ON CM.userid = U.id
        WHERE U.id NOT IN (SELECT DISTINCT userid FROM enrolments) AND U.deleted = 0;';

        return $DB->get_records_sql($sql);
    }

    /**
     * Nombre de sportifs de haut-niveau par UFR.
     *
     * @param array $params
     *
     * @return array
     */
    public function get_SHNU_list($params) {
        global $DB;

        $sql = "SELECT DISTINCT
          ROW_NUMBER() OVER (ORDER BY institution, ufr ASC) AS row_num,
          institution, ufr, count(distinct userid) as total
        FROM (
          SELECT DISTINCT	userid, institution, ufr
          FROM (" . $params["WithEnrolments"] . "	SELECT DISTINCT e.userid,e.institution, e.ufr,
          	COUNT(CASE WHEN status=0 THEN 1 ELSE NULL END) AS accepted_list
          	FROM enrolments e
          	WHERE shnu = true
          	GROUP BY e.userid
          	HAVING accepted_list > 0
          ) SET1
          UNION
          SELECT DISTINCT userid, institution, ufr
          FROM (" . $params["WithComplementary"] . " SELECT DISTINCT userid, institution, ufr
			     FROM enrolments e
			     WHERE shnu = true
          ) SET2
        ) result
        GROUP BY institution,ufr
        ORDER BY institution,ufr";

        return $DB->get_records_sql($sql);
    }


    /**
     * Retourne le nombre d'utilisateurs ayant au moins 1 inscriptions ou refusé par institution/période/lieu.
     *
     * @param array $params
     *
     * @return array
     */
    public static function accepted_refused($params) {
        global $DB;

        if (array_key_exists("cityid",$params) && $params["cityid"] != '') {
            $cityid = $params["cityid"];
        } else {
            $cities = self::get_cities();
            $cityid = implode(', ', array_map(
            function ($v, $k) { return sprintf("%s", $v->id);
            },
            $cities,
            array_keys($cities)
            ));
        }
        if (array_key_exists("calendarstypeid",$params) && $params["calendarstypeid"] != '') {
            $calendarstypeid = $params["calendarstypeid"];
        } else {
            $calendarstypes = self::get_calendarstypes();
            $calendarstypeid = implode(', ', array_map(
            function ($v, $k) { return sprintf("%s", $v->id);
            },
            $calendarstypes,
            array_keys($calendarstypes)
            ));
        }

        $sql = $params["WithEnrolments"] . "
        SELECT DISTINCT
        	institution,
        	SUM(if(accepted_list > 0, 1, 0)) AS accepted,
        	SUM(if(accepted_list = 0, 1, 0)) AS refused
        FROM (
          	SELECT DISTINCT e.userid,
              cityid,cityname,e.institution,
             	COUNT(CASE WHEN status=0 THEN 1 ELSE NULL END) AS accepted_list
          	FROM enrolments e
            WHERE cityid in (". $cityid .") AND calendartypeid in (". $calendarstypeid .")
          	GROUP BY e.userid,e.cityid,e.institution
        ) results
        GROUP BY institution
        ORDER BY institution";

        return $DB->get_records_sql($sql);
    }


    /**
     * Nombre de sportifs de haut-niveau inscrits à une activité physique/complémentaire.
     *
     * @param array $params
     *
     * @return array
     */
    public static function custom_shnu($params) {
        global $DB;

        $sql = "SELECT DISTINCT
          ROW_NUMBER() OVER (ORDER BY userid ASC) AS row_num,
          result.*
        FROM (
          SELECT DISTINCT userid,idnumber,firstname,lastname,email,sexe,institution, department, ufr, lmd, userprofile
          FROM (" . $params["WithEnrolments"] . "	SELECT DISTINCT e.userid,e.idnumber,e.firstname,e.lastname,e.email,e.sexe,e.institution, e.department, e.ufr, e.lmd, e.userprofile,
          	COUNT(CASE WHEN status=0 THEN 1 ELSE NULL END) AS accepted_list
          	FROM enrolments e
          	WHERE shnu = true
          	GROUP BY e.userid
          	HAVING accepted_list > 0
          ) SET1
          UNION
          SELECT DISTINCT userid,idnumber,firstname,lastname,email,sexe,institution, department, ufr, lmd, userprofile
          FROM (" . $params["WithComplementary"] . " SELECT DISTINCT e.userid,e.idnumber,e.firstname,e.lastname,e.email,e.sexe,e.institution, e.department, e.ufr, e.lmd, e.userprofile
			     FROM enrolments e
			     WHERE shnu = true
          ) SET2
        ) result
        ORDER BY idnumber,firstname,lastname";

        return $DB->get_records_sql($sql);
    }

    /**
     * Nombre de sportifs de haut-niveau inscrits depuis l'espace cours.
     *
     * @param array $params
     *
     * @return array
     */
    public static function custom_apsoluhighlevelathlete($params) {
        global $DB;

        $sql = "SELECT DISTINCT u.id,u.idnumber,u.firstname,u.lastname,u.email,u.institution, u.department, ufr.data as ufr
        FROM mdl_user u
        LEFT JOIN mdl_user_info_data ufr ON ufr.userid = u.id AND ufr.fieldid = (select id from mdl_user_info_field where shortname = 'apsoluufr')
        LEFT JOIN mdl_user_info_data apsoluhighlevelathlete ON apsoluhighlevelathlete.userid = u.id AND apsoluhighlevelathlete.fieldid = (select id from mdl_user_info_field where shortname = 'apsoluhighlevelathlete')
        WHERE apsoluhighlevelathlete.data = true
        AND u.deleted = 0
        ORDER BY u.institution, ufr.data, u.firstname,u.lastname";

        return $DB->get_records_sql($sql);
    }


    /**
     * Retourne le nombre d'inscriptions par semestre.
     *
     * @param array $params
     *
     * @return array
     */
    public static function get_semesters($params) {
        global $DB;

        if (array_key_exists("cityid",$params)) {
            $cityid = $params["cityid"];
        } else {
            $cities = self::get_cities();
            $cityid = implode(', ', array_map(
            function ($v, $k) { return sprintf("%s", $v->id);
            },
            $cities,
            array_keys($cities)
            ));
        }

        $sql = $params["WithEnrolments"] . "SELECT
          ROW_NUMBER() OVER (ORDER BY cityname,institution,calendartypeid,calendartypename ASC) AS row_num,
          cityname,cityid,institution,calendartypeid,calendartypename,
     	    (COUNT(CASE WHEN accepted_list >=1 THEN 1 ELSE NULL END)
        	 + COUNT(CASE WHEN main_list >=1 THEN 1 ELSE NULL END)
        	 + COUNT(CASE WHEN wait_list >=1 THEN 1 ELSE NULL END)
        	) AS total
        FROM (
        	SELECT DISTINCT e.userid,
        		CASE WHEN (e.institution IS NULL OR e.institution = '') THEN 'Autre' ELSE e.institution END AS institution,
           	COUNT(CASE WHEN status=0 THEN 1 ELSE NULL END) AS accepted_list,
           	COUNT(CASE WHEN status=2 THEN 1 ELSE NULL END) AS main_list,
        		COUNT(CASE WHEN status=3 THEN 1 ELSE NULL END) AS wait_list,
        		cityid,cityname,
        		calendartypeid,CASE WHEN (e.calendartypename IS NULL OR e.calendartypename = '') THEN '[NR]' ELSE e.calendartypename END AS calendartypename
        	FROM enrolments e
          WHERE cityid in(". $cityid .")
        	GROUP BY e.userid,e.cityid,e.institution, e.calendartypename
        ) results
        GROUP BY cityid,institution,calendartypeid
        ORDER BY cityname,institution,calendartypename";

        return $DB->get_records_sql($sql);
    }


    /**
     * Répartition des inscrits Personnels/Étudiants par université.
     *
     * @param array $params
     *
     * @return array
     */
    public static function distribution_userprofile($params) {
        global $DB;

        if (array_key_exists("cityid",$params) && $params["cityid"] != '') {
            $cityid = $params["cityid"];
        } else {
            $cities = self::get_cities();
            $cityid = implode(', ', array_map(
            function ($v, $k) { return sprintf("%s", $v->id);
            },
            $cities,
            array_keys($cities)
            ));
        }
        if (array_key_exists("calendarstypeid",$params) && $params["calendarstypeid"] != '') {
            $calendarstypeid = $params["calendarstypeid"];
        } else {
            $calendarstypes = self::get_calendarstypes();
            $calendarstypeid = implode(', ', array_map(
            function ($v, $k) { return sprintf("%s", $v->id);
            },
            $calendarstypes,
            array_keys($calendarstypes)
            ));
        }

        $sql = $params["WithEnrolments"] . " SELECT DISTINCT
          ROW_NUMBER() OVER (ORDER BY e.institution ASC) AS row_num,
        	e.institution,e.userprofile,count(distinct e.userid) as total
        FROM enrolments e
        WHERE e.status=0 AND cityid in (". $cityid .") AND calendartypeid in (". $calendarstypeid .")
        GROUP BY e.institution,e.userprofile";

        return $DB->get_records_sql($sql);
    }

    /**
     * Répartition des inscrits filles/garçons par université.
     *
     * @param array $params
     *
     * @return array
     */
    public static function distribution_genders($params) {
        global $DB;

        if (array_key_exists("cityid",$params) && $params["cityid"] != '') {
            $cityid = $params["cityid"];
        } else {
            $cities = self::get_cities();
            $cityid = implode(', ', array_map(
            function ($v, $k) { return sprintf("%s", $v->id);
            },
            $cities,
            array_keys($cities)
            ));
        }
        if (array_key_exists("calendarstypeid",$params) && $params["calendarstypeid"] != '') {
            $calendarstypeid = $params["calendarstypeid"];
        } else {
            $calendarstypes = self::get_calendarstypes();
            $calendarstypeid = implode(', ', array_map(
            function ($v, $k) { return sprintf("%s", $v->id);
            },
            $calendarstypes,
            array_keys($calendarstypes)
            ));
        }

        $sql = $params["WithEnrolments"] . " SELECT DISTINCT
          ROW_NUMBER() OVER (ORDER BY e.institution ASC) AS row_num,
        	e.institution,IF(e.sexe IS NULL,'', e.sexe) AS gender,count(distinct e.userid) as total
        FROM enrolments e
        WHERE e.status=0 AND cityid in (". $cityid .") AND calendartypeid in (". $calendarstypeid .")
        GROUP BY e.institution,IF(e.sexe IS NULL,'', e.sexe)";

        return $DB->get_records_sql($sql);
    }

    /**
     * Répartition des inscriptions Personnels/Étudiants par université.
     *
     * @param array $params
     *
     * @return array
     */
    public static function enrol_userprofile($params) {
        global $DB;

        if (array_key_exists("cityid",$params) && $params["cityid"] != '') {
            $cityid = $params["cityid"];
        } else {
            $cities = self::get_cities();
            $cityid = implode(', ', array_map(
            function ($v, $k) { return sprintf("%s", $v->id);
            },
            $cities,
            array_keys($cities)
            ));
        }
        if (array_key_exists("calendarstypeid",$params) && $params["calendarstypeid"] != '') {
            $calendarstypeid = $params["calendarstypeid"];
        } else {
            $calendarstypes = self::get_calendarstypes();
            $calendarstypeid = implode(', ', array_map(
            function ($v, $k) { return sprintf("%s", $v->id);
            },
            $calendarstypes,
            array_keys($calendarstypes)
            ));
        }

        $sql = $params["WithEnrolments"] . " SELECT DISTINCT
          ROW_NUMBER() OVER (ORDER BY e.institution ASC) AS row_num,
        	e.institution,e.userprofile,count(e.userid) as total
        FROM enrolments e
        WHERE e.status=0 AND cityid in (". $cityid .") AND calendartypeid in (". $calendarstypeid .")
        GROUP BY e.institution,e.userprofile";

        return $DB->get_records_sql($sql);
    }

    /**
     * Répartition des inscriptions filles/garçons par université.
     *
     * @param array $params
     *
     * @return array
     */
    public static function enrol_genders($params) {
        global $DB;

        if (array_key_exists("cityid",$params) && $params["cityid"] != '') {
            $cityid = $params["cityid"];
        } else {
            $cities = self::get_cities();
            $cityid = implode(', ', array_map(
            function ($v, $k) { return sprintf("%s", $v->id);
            },
            $cities,
            array_keys($cities)
            ));
        }
        if (array_key_exists("calendarstypeid",$params) && $params["calendarstypeid"] != '') {
            $calendarstypeid = $params["calendarstypeid"];
        } else {
            $calendarstypes = self::get_calendarstypes();
            $calendarstypeid = implode(', ', array_map(
            function ($v, $k) { return sprintf("%s", $v->id);
            },
            $calendarstypes,
            array_keys($calendarstypes)
            ));
        }

        $sql = $params["WithEnrolments"] . " SELECT DISTINCT
          ROW_NUMBER() OVER (ORDER BY e.institution ASC) AS row_num,
        	e.institution,IF(e.sexe IS NULL,'', e.sexe) AS gender,count(e.userid) as total
        FROM enrolments e
        WHERE e.status=0 AND cityid in (". $cityid .") AND calendartypeid in (". $calendarstypeid .")
        GROUP BY e.institution,IF(e.sexe IS NULL,'', e.sexe)";

        return $DB->get_records_sql($sql);
    }


    /**
     * Répartition Libres/option et bonification.
     *
     * @param array $params
     *
     * @return array
     */
    public static function get_roles_ratio($params) {
        global $DB;

        $sql = $params["WithEnrolments"] . " SELECT DISTINCT
          ROW_NUMBER() OVER (ORDER BY institution, roleshortname ASC) AS row_num,
        	institution, roleshortname, count(distinct userid) as total
        FROM enrolments e";
        if (array_key_exists("institution",$params)) {
            $sql .= " WHERE institution = '".$params["institution"]."'";
        }
        $sql .= " GROUP BY institution, roleshortname
        ORDER BY institution, roleshortname";

        return $DB->get_records_sql($sql);
    }


    /**
     * Nombre de libres/Option évalués/Bonification évalués.
     *
     * @param array $params
     *
     * @return array
     */
    public static function enrol_roles($params) {
        global $DB;

        if (array_key_exists("cityid",$params) && $params["cityid"] != '') {
            $cityid = $params["cityid"];
        } else {
            $cities = self::get_cities();
            $cityid = implode(', ', array_map(
            function ($v, $k) { return sprintf("%s", $v->id);
            },
            $cities,
            array_keys($cities)
            ));
        }
        if (array_key_exists("calendarstypeid",$params) && $params["calendarstypeid"] != '') {
            $calendarstypeid = $params["calendarstypeid"];
        } else {
            $calendarstypes = self::get_calendarstypes();
            $calendarstypeid = implode(', ', array_map(
            function ($v, $k) { return sprintf("%s", $v->id);
            },
            $calendarstypes,
            array_keys($calendarstypes)
            ));
        }

        $sql = $params["WithEnrolments"] . " SELECT DISTINCT
        	ROW_NUMBER() OVER (ORDER BY institution, shortname ASC) AS row_num,
        	institution, shortname as roleshortname, sum(total) as total
        FROM (
        	SELECT DISTINCT e.institution,R.shortname, e.roleshortname, count(userid) as total
        	FROM enrolments e
        	INNER JOIN mdl_role R ON e.roleshortname like CONCAT('%',R.shortname,'%') AND R.archetype = 'student' and shortname <> 'student'
          WHERE e.status=0
          AND cityid in (". $cityid .") AND calendartypeid in (". $calendarstypeid .")
        	GROUP BY e.institution, R.shortname, e.roleshortname
        ) ROLES
        GROUP BY institution, shortname
        ORDER BY institution, shortname";

        return $DB->get_records_sql($sql);
    }


    /**
     * Tableau de bord des activités physiques.
     *
     * @param array $params
     *
     * @return array
     */
    public static function dashboard($params) {
        global $DB;

        $sql = $params["WithEnrolments"] . " SELECT COUNT(e.status) AS wish_list,
      	SUM(if(e.status=0, 1, 0)) AS accepted_list,
      	SUM(if(e.status=2, 1, 0)) AS main_list,
      	SUM(if(e.status=3, 1, 0)) AS wait_list,
      	SUM(if(e.status=4, 1, 0)) AS deleted_list,
      	COUNT(DISTINCT e.userid) AS enrollee_wish_list,
      	COUNT(DISTINCT if(e.status=0, e.userid, NULL)) AS enrollee_accepted_list,
      	COUNT(DISTINCT if(e.status=2, e.userid, NULL)) AS enrollee_main_list,
      	COUNT(DISTINCT if(e.status=3, e.userid, NULL)) AS enrollee_wait_list,
      	COUNT(DISTINCT if(e.status=4, e.userid, NULL)) AS enrollee_deleted_list
      FROM enrolments e";

        return $DB->get_records_sql($sql);
    }

    /**
     * Tableau de bord des activités complémentaires.
     *
     * @param array $params
     *
     * @return array
     */
    public static function dashboard_complementaries($params) {
        global $DB;

        $complementaries = parent::get_complementaries();

        $sql = $params["WithComplementary"] . " SELECT
        COUNT(e.status) AS enrollment,
      	COUNT(DISTINCT e.userid) AS enrollee ";

        foreach ($complementaries as $complementary) {
            $sql .= " ,SUM(if(e.activityid=".$complementary->id.", 1, 0)) AS enrollment_".$complementary->id;
        }

        $sql .= " FROM enrolments e";

        return $DB->get_records_sql($sql);
    }
}
