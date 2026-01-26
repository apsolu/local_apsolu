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
// phpcs:disable moodle.NamingConventions.ValidVariableName.MemberNameUnderscore

namespace local_apsolu\local\statistics\programme;

/**
 * Classe pour les statistiques APSOLU.
 *
 * @package    local_apsolu
 * @copyright  2019 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report extends \local_apsolu\local\statistics\report {
    /**
     * @var sous-requête contenant l'ensemble des créneaux
     * TODO transformer en vue
     */
    public $WithProgramme;

    /**
     * Constructeur de la classe.
     *
     * @return void
     */
    public function __construct() {
        $this->configFilePath = '/local/apsolu/statistics/programme/config.json';

        // Note: SET lc_time_names = 'fr_FR'.
        $this->WithProgramme = 'WITH programme AS (
      	SELECT
      		Grouping.id as groupid, Grouping.name as groupname,
      		Activity.id as activityid, Activity.name as activityname,
      		AC.id as calendarid, AC.name as calendarname,
      		ACT.id as calendartypeid,ACT.name as calendartypename,
      		ACI.id as cityid,ACI.name as cityname,
            APSOLU_C.id as slotid, APSOLU_C.event as slotevent,APSOLU_C.numweekday as slotnumweekday,
                DAYNAME(CONCAT("1970-09-2", APSOLU_C.numweekday)) as slotweekday,
                APSOLU_C.starttime as slotstart,	APSOLU_C.endtime as slotend,
      		CASE WHEN E.customint3 THEN \'Oui\' ELSE \'Non\' END AS \'actifQuota\',
      		E.customint1 AS \'mainQuota\',
      	 	E.customint2 AS \'waitQuota\',
            CASE WHEN E.customint4 = 0 THEN \'\'
                    ELSE DATE_FORMAT(FROM_UNIXTIME(E.customint4),\'%d/%m/%Y %H:%i\') END AS \'reenrolstartdate\',
                  CASE WHEN E.customint5 = 0 THEN \'\'
                    ELSE DATE_FORMAT(FROM_UNIXTIME(E.customint5),\'%d/%m/%Y %H:%i\') END AS \'reenrolenddate\',
                  CASE WHEN E.customint7 = 0 THEN \'\'
                    ELSE DATE_FORMAT(FROM_UNIXTIME(E.customint7),\'%d/%m/%Y\') END AS \'coursestartdate\',
                  CASE WHEN E.customint8 = 0 THEN \'\'
                    ELSE DATE_FORMAT(FROM_UNIXTIME(E.customint8),\'%d/%m/%Y\') END AS \'courseenddate\',
          AL.id as placeid, AL.name as placename, AL.address as placeaddress
      	FROM {course} C
      	INNER JOIN {enrol} E ON E.courseid = C.id AND E.enrol = \'select\'
      	INNER JOIN {apsolu_courses} APSOLU_C on APSOLU_C.id = C.id
      	INNER JOIN {apsolu_locations} AL ON AL.id = APSOLU_C.locationid
      	INNER JOIN {apsolu_areas} AA ON AA.id = AL.areaId
      	INNER JOIN {apsolu_cities} ACI ON ACI.id = AA.cityId
      	INNER JOIN {course_categories} Activity ON Activity.id = C.category
      	INNER JOIN {course_categories} Grouping on Grouping.id = Activity.parent
      	LEFT JOIN {apsolu_calendars} AC ON AC.id = E.customchar1
      	LEFT JOIN {apsolu_calendars_types} ACT ON ACT.id = AC.typeid
         ORDER BY Grouping.id, Activity.id
      ) ';
    }

    /**
     * Retourne les critères d'affichage du rapport.
     *
     * @return array
     */
    public function getReportDisplay() {
        $columns = [
          [ 'data' => "groupname", 'title' => get_string("statistics_groupe", 'local_apsolu')],
          [ 'data' => "activityname", 'title' => get_string("activity", 'local_apsolu')],
          [ 'data' => "slotevent", 'title' => get_string("event", 'local_apsolu')],
          [ 'data' => "calendartypename", 'title' => get_string("calendartype", 'local_apsolu')],
          [ 'data' => "cityname", 'title' => get_string("city", 'local_apsolu')],
          [ 'data' => "placename", 'title' => get_string("locations", 'local_apsolu')],
          [ 'data' => "placeaddress", 'title' => get_string("address", 'local_apsolu')],
          [ 'data' => "slotnumweekday", 'title' => get_string("weekday", 'local_apsolu'),
            "render" => "function ( data, type, row ) {return moment.weekdays()[(data==7) ? 0 : data];}"],
          [ 'data' => "slotstart", 'title' => get_string("coursestartdate", 'local_apsolu')],
          [ 'data' => "slotend", 'title' => get_string("courseenddate", 'local_apsolu')],
          [ 'data' => "actifquota", 'title' => get_string("statistics_active_quota", 'local_apsolu')],
          [ 'data' => "mainquota", 'title' => get_string("statistics_main_quota", 'local_apsolu')],
          [ 'data' => "waitquota", 'title' => get_string("statistics_wait_quota", 'local_apsolu')],
          [ 'data' => "coursestartdate", 'title' => get_string("statistics_start_the", 'local_apsolu')],
          [ 'data' => "courseenddate", 'title' => get_string("statistics_end_the", 'local_apsolu')],
          [ 'data' => "reenrolstartdate", 'title' => get_string("reenrolstartdate", 'local_apsolu')],
          [ 'data' => "reenrolenddate", 'title' => get_string("reenrolenddate", 'local_apsolu')],
        ];
        $orders = [0 => 'asc', 1 => 'asc'];
        $filters = ['input' => [8, 9, 10, 11, 12, 13, 14, 15], 'select' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9] ];

        return [
          'columns' => $columns,
          'orders' => $orders,
          'filters' => $filters,
        ];
    }

    /**
     * Retourne les résultats en fonction de la vue choisie et des critères de recherche.
     *
     * @param string     $querybuilder Chaine de caractères au format JSON.
     * @param null|array $criterias
     *
     * @return array
     */
    public function getReportData($querybuilder, $criterias = null) {
        global $DB;

        $condition = json_decode($querybuilder);

        $with = $this->WithProgramme;
        $select = 'SELECT ROW_NUMBER() OVER (ORDER BY p.groupid,p.activityid,p.calendartypeid,p.cityid ASC) AS row_num, p.* ';
        $where = "WHERE 1=1 ";
        $groupby = "";
        $having = "";
        $orderby = "";
        $from = 'FROM programme p ';

        if (property_exists($condition, "sql")) {
            $where .= " AND " . $condition->sql;
        }
        if (!is_null($criterias)) {
            if (array_key_exists("cityid", $criterias) && $criterias["cityid"] != '') {
                $where .= " AND cityid = " . $criterias["cityid"];
            }
            if (array_key_exists("calendarstypeid", $criterias) && $criterias["calendarstypeid"] != '') {
                $where .= " AND calendarstypeid = " . $criterias["calendarstypeid"];
            }
        }

        if (property_exists($condition, "having")) {
            $having = 'HAVING ' . $condition->having;
        }

        if (property_exists($condition, "order")) {
            $orderby = 'ORDER BY ' . $condition->order;
        }

        $sql = $with . $select . $from . $where . $groupby . $having . $orderby;

        if (property_exists($condition, "params")) {
            return $DB->get_records_sql($sql, $condition->params);
        } else {
            return $DB->get_records_sql($sql);
        }
    }

    /**
     * Retourne le Nombre de cours proposés par groupe d'activités.
     *
     * @param array $params
     *
     * @return array
     */
    public static function groupslots($params) {
        global $DB;

        if (array_key_exists("cityid", $params) && $params["cityid"] != '') {
            $cityid = $params["cityid"];
        } else {
            $cities = self::get_cities();
            $cityid = implode(', ', array_map(
                function ($v, $k) {
                    return sprintf("%s", $v->id);
                },
                $cities,
                array_keys($cities)
            ));
        }
        if (array_key_exists("calendarstypeid", $params) && $params["calendarstypeid"] != '') {
            $calendarstypeid = $params["calendarstypeid"];
        } else {
            $calendarstypes = self::get_calendarstypes();
            $calendarstypeid = implode(', ', array_map(
                function ($v, $k) {
                    return sprintf("%s", $v->id);
                },
                $calendarstypes,
                array_keys($calendarstypes)
            ));
        }

        $sql = $params["WithProgramme"] . "SELECT
      	 p.groupid, p.groupname, count(p.slotid) as total
        FROM programme p
        WHERE cityid in (" . $cityid . ") AND calendartypeid in (" . $calendarstypeid . ")
        GROUP BY p.groupid
        ORDER BY p.groupname";

        return $DB->get_records_sql($sql);
    }

    /**
     * Retourne le Nombre de cours proposés par activité.
     *
     * @param array $params
     *
     * @return array
     */
    public static function activityslots($params) {
        global $DB;

        if (array_key_exists("cityid", $params) && $params["cityid"] != '') {
            $cityid = $params["cityid"];
        } else {
            $cities = self::get_cities();
            $cityid = implode(', ', array_map(
                function ($v, $k) {
                    return sprintf("%s", $v->id);
                },
                $cities,
                array_keys($cities)
            ));
        }
        if (array_key_exists("calendarstypeid", $params) && $params["calendarstypeid"] != '') {
            $calendarstypeid = $params["calendarstypeid"];
        } else {
            $calendarstypes = self::get_calendarstypes();
            $calendarstypeid = implode(', ', array_map(
                function ($v, $k) {
                    return sprintf("%s", $v->id);
                },
                $calendarstypes,
                array_keys($calendarstypes)
            ));
        }

        $sql = $params["WithProgramme"] . "SELECT
          ROW_NUMBER() OVER (ORDER BY p.groupid,p.activityid ASC) AS row_num,
        	p.groupid, p.groupname,
            p.activityid, p.activityname, p.slotevent,
        	count(p.slotid) as total
        FROM programme p
        WHERE cityid in (" . $cityid . ") AND calendartypeid in (" . $calendarstypeid . ")
        GROUP BY p.groupid, p.activityid
        ORDER BY p.activityname";

        return $DB->get_records_sql($sql);
    }

    /**
     * Retourne le Nombre de places en liste principale (potentiel d'accueil) - (à croiser avec le nombre d'inscrits ?).
     *
     * @param array $params
     *
     * @return array
     */
    public static function countslotsmainlist($params) {
        global $DB;

        if (array_key_exists("cityid", $params) && $params["cityid"] != '') {
            $cityid = $params["cityid"];
        } else {
            $cities = self::get_cities();
            $cityid = implode(', ', array_map(
                function ($v, $k) {
                    return sprintf("%s", $v->id);
                },
                $cities,
                array_keys($cities)
            ));
        }
        if (array_key_exists("calendarstypeid", $params) && $params["calendarstypeid"] != '') {
            $calendarstypeid = $params["calendarstypeid"];
        } else {
            $calendarstypes = self::get_calendarstypes();
            $calendarstypeid = implode(', ', array_map(
                function ($v, $k) {
                    return sprintf("%s", $v->id);
                },
                $calendarstypes,
                array_keys($calendarstypes)
            ));
        }

        $sql = $params["WithProgramme"] . "SELECT
	       SUM(mainQuota) as total
        FROM programme p
        WHERE p.actifQuota = 'Oui'
          and cityid in (" . $cityid . ") AND calendartypeid in (" . $calendarstypeid . ")";

        return $DB->get_records_sql($sql);
    }
}
