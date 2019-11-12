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
 * Tasks to grant access to webservice.
 *
 * @package    local
 * @subpackage apsolu
 * @copyright  2019 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_apsolu\local\statistics\programme;

defined('MOODLE_INTERNAL') || die();
 
class report extends \local_apsolu\local\statistics\report {

    /**
     * @var sous-requête contenant l'ensemble des créneaux
     * TODO transformer en vue
     */
    public $WithProgramme;
    
    public function __construct(){
      $this->configFilePath = '/local/apsolu/statistics/programme/config.json';
      
      // SET lc_time_names = 'fr_FR'; 
      $this->WithProgramme = 'WITH programme AS ( 
      	SELECT 
      		Grouping.id as groupid, Grouping.name as groupname,
      		Activity.id as activityid, Activity.name as activityname, 
      		AC.id as calendarid, AC.name as calendarname,
      		ACT.id as calendartypeid,ACT.name as calendartypename,
      		ACI.id as cityid,ACI.name as cityname,
      		APSOLU_C.id as slotid, APSOLU_C.event as slotevent,APSOLU_C.numweekday as slotnumweekday,DAYNAME(CONCAT("1970-09-2", APSOLU_C.numweekday)) as slotweekday, APSOLU_C.starttime as slotstart,	APSOLU_C.endtime as slotend,
      		CASE WHEN E.customint3 THEN \'Oui\' ELSE \'Non\' END AS \'actifQuota\', 	
      		E.customint1 AS \'mainQuota\',
      	 	E.customint2 AS \'waitQuota\',
      		CASE WHEN E.customint4 = 0 THEN \'\' ELSE DATE_FORMAT(FROM_UNIXTIME(E.customint4),\'%d/%m/%Y %H:%i\') END AS \'reenrolstartdate\',	
				  CASE WHEN E.customint5 = 0 THEN \'\' ELSE DATE_FORMAT(FROM_UNIXTIME(E.customint5),\'%d/%m/%Y %H:%i\') END AS \'reenrolenddate\',	
				  CASE WHEN E.customint7 = 0 THEN \'\' ELSE DATE_FORMAT(FROM_UNIXTIME(E.customint7),\'%d/%m/%Y\') END AS \'coursestartdate\',	
				  CASE WHEN E.customint8 = 0 THEN \'\' ELSE DATE_FORMAT(FROM_UNIXTIME(E.customint8),\'%d/%m/%Y\') END AS \'courseenddate\',
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
      * Retourne les critères d'affichage du rapport
      *
      * @return array
      */
    public function getReportDisplay($datatype = null) {
      $columns = [
          [ 'data' => "groupname", 'title' => "Groupe"],
          [ 'data' => "activityname", 'title' => "Activité"],
          [ 'data' => "calendartypename", 'title' => "Type de calendrier"] ,
          [ 'data' => "cityname", 'title' => "Site"] ,
          [ 'data' => "placename", 'title' => "Lieux"] ,
          [ 'data' => "placeaddress", 'title' => "Adresse"] ,
          [ 'data' => "slotweekday", 'title' => "Jour"] ,
          [ 'data' => "slotstart", 'title' => "Début"] ,                        
          [ 'data' => "slotend", 'title' => "Fin"] ,
          [ 'data' => "actifquota", 'title' => "Quota activé"] ,
          [ 'data' => "mainquota", 'title' => "Quota liste principale"] ,
          [ 'data' => "waitquota", 'title' => "Quota liste d'attente"],
          [ 'data' => "coursestartdate", 'title' => "Débute le"] ,
          [ 'data' => "courseenddate", 'title' => "Termine le"],
          [ 'data' => "reenrolstartdate", 'title' => "Début des réinscriptions"] ,
          [ 'data' => "reenrolenddate", 'title' => "Fin des réinscriptions"]          
        ];
        $orders = [0 => 'asc', 1 => 'asc'];
        $filters = ['input' => [8,9,10,11,12,13,14,15],'select' => [0,1,2,3,4,5,6,7,8,9] ];
        
        return array(
          'columns'=>$columns,
          'orders'=>$orders,
          'filters'=>$filters,
        ); 
    
    }
    
    /**
      * Retourne les résultats en fonction de la vue choisie et des critères de recherche
      *
      * @return array
      */
    public function getReportData($queryBuilder,$criterias=null) {
      global $DB;
      
      $condition = json_decode($queryBuilder);
      
      $with = $this->WithProgramme;
      $select = 'SELECT ROW_NUMBER() OVER (ORDER BY p.groupid,p.activityid,p.calendartypeid,p.cityid ASC) AS row_num, p.* ';
      $where = "WHERE 1=1 ";
      $groupby = "";
      $having = "";
      $orderby = "";
      $from = 'FROM programme p ';

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
      * Retourne le Nombre de cours proposés par groupe d'activités
      *
      * @return array
      */
    public static function groupslots($params) {
      global $DB;

      if (array_key_exists("cityid",$params) && $params["cityid"] != '') {
        $cityid = $params["cityid"]; 
      } else {
        $cities = self::get_cities();
        $cityid = implode(', ', array_map(
            function ($v, $k) { return sprintf("%s", $v->id); },
            $cities,
            array_keys($cities)
        ));
      }
      if (array_key_exists("calendarstypeid",$params) && $params["calendarstypeid"] != '') {
        $calendarstypeid = $params["calendarstypeid"]; 
      } else {
        $calendarstypes = self::get_calendarstypes();
        $calendarstypeid = implode(', ', array_map(
            function ($v, $k) { return sprintf("%s", $v->id); },
            $calendarstypes,
            array_keys($calendarstypes)
        ));
      }      
      
      $sql = $params["WithProgramme"] . "SELECT 
      	 p.groupid, p.groupname, count(p.slotid) as total
        FROM programme p
        WHERE cityid in (". $cityid .") AND calendartypeid in (". $calendarstypeid .")
        GROUP BY p.groupid
        ORDER BY p.groupname";

      return $DB->get_records_sql($sql);
    }     

    /**
      * Retourne le Nombre de cours proposés par activité
      *
      * @return array
      */
    public static function activityslots($params) {
      global $DB;

      if (array_key_exists("cityid",$params) && $params["cityid"] != '') {
        $cityid = $params["cityid"]; 
      } else {
        $cities = self::get_cities();
        $cityid = implode(', ', array_map(
            function ($v, $k) { return sprintf("%s", $v->id); },
            $cities,
            array_keys($cities)
        ));
      }
      if (array_key_exists("calendarstypeid",$params) && $params["calendarstypeid"] != '') {
        $calendarstypeid = $params["calendarstypeid"]; 
      } else {
        $calendarstypes = self::get_calendarstypes();
        $calendarstypeid = implode(', ', array_map(
            function ($v, $k) { return sprintf("%s", $v->id); },
            $calendarstypes,
            array_keys($calendarstypes)
        ));
      }      
      
      $sql = $params["WithProgramme"] . "SELECT
          ROW_NUMBER() OVER (ORDER BY p.groupid,p.activityid ASC) AS row_num, 
        	p.groupid, p.groupname, 
        	p.activityid, p.activityname, 
        	count(p.slotid) as total
        FROM programme p
        WHERE cityid in (". $cityid .") AND calendartypeid in (". $calendarstypeid .") 
        GROUP BY p.groupid, p.activityid      
        ORDER BY p.activityname";

      return $DB->get_records_sql($sql);
    }    

    /**
      * Retourne le Nombre de places en liste principale (potentiel d'accueil) - (à croiser avec le nombre d'inscrits ?)
      *
      * @return array
      */
    public static function countslotsmainlist($params) {
      global $DB;

      if (array_key_exists("cityid",$params) && $params["cityid"] != '') {
        $cityid = $params["cityid"]; 
      } else {
        $cities = self::get_cities();
        $cityid = implode(', ', array_map(
            function ($v, $k) { return sprintf("%s", $v->id); },
            $cities,
            array_keys($cities)
        ));
      }
      if (array_key_exists("calendarstypeid",$params) && $params["calendarstypeid"] != '') {
        $calendarstypeid = $params["calendarstypeid"]; 
      } else {
        $calendarstypes = self::get_calendarstypes();
        $calendarstypeid = implode(', ', array_map(
            function ($v, $k) { return sprintf("%s", $v->id); },
            $calendarstypes,
            array_keys($calendarstypes)
        ));
      }      
      
      $sql = $params["WithProgramme"] . "SELECT 
	       SUM(mainQuota) as total
        FROM programme p
        WHERE p.actifQuota = 'Oui'
          and cityid in (". $cityid .") AND calendartypeid in (". $calendarstypeid .")";

      return $DB->get_records_sql($sql);
    }    
}