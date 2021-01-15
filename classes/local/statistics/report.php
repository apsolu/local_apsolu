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
 * Classe pour les statistiques APSOLU.
 *
 * @package    local_apsolu
 * @copyright  2019 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_apsolu\local\statistics;

use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Classe pour les statistiques APSOLU.
 *
 * @package    local_apsolu
 * @copyright  2019 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report {

    /**
     * @var fichier de configuration des critères de recherche
     *
     */
    public $configFilePath;

    /**
     * Retourne les paramètres d'un rapport.
     *
     * @param null|int|string $reportid
     *
     * @return array
     */
    public function getReport($reportid = null) {
        global $CFG;

        $jsonConfigPath = $CFG->dirroot.$this->configFilePath;
        $jsonModeldata = file_get_contents($jsonConfigPath);
        $model = json_decode($jsonModeldata);

        // custom reports
        if ($CFG->is_siuaps_rennes){
            $model->reports = array_merge($model->reports,$model->reportsCustomRennes);
        }

        $model = self::localize_reports($model);

        if (!is_null ($reportid)) {
            for ($i = 0; $i < count($model->reports); $i++) {
                $report = $model->reports[$i];
                if ($report->id == $reportid) {
                    return $report;
                }
            }
            return null;
        } else {
            foreach($model->reports as $property => $value) {
                // ne pas inclure les rapports masqués
                if (property_exists($value, 'hidden')) {
                    unset($model->reports[$property]);
                }
            }
            return $model->reports;
        }

    }

    /**
     * Retourne La liste des critères de recherche.
     *
     * @return array
     */
    public function getFilters() {
        global $CFG, $DB;

        $jsonConfigPath = $CFG->dirroot.$this->configFilePath;
        $jsonModeldata = file_get_contents($jsonConfigPath);
        $model = json_decode($jsonModeldata);

        // custom filter
        if ($CFG->is_siuaps_rennes){
            $model->filters = array_merge($model->filters,$model->filtersCustomRennes);
        }

        $model = self::localize_filters($model);

        for ($i = 0; $i < count($model->filters); $i++) {
            $filter = $model->filters[$i];
            if(property_exists($filter, "input")) {

                if (property_exists($filter->values, "table")){
                    $where = "";
                    if (property_exists($filter->values, "conditions")){
                        $where = "WHERE ".$filter->values->conditions;
                    }
                    $records = $DB->get_records_sql('SELECT DISTINCT '.$filter->values->fields.' FROM {'.$filter->values->table.'} ' .$where.' ORDER BY '.$filter->values->sort);
                    $records = json_decode(json_encode($records), true);
                    $fields = explode(",",$filter->values->fields);
                    $values = [];
                    foreach($records as $record){
                        if (sizeof($fields) > 1) {
                            $values[] = [$record[$fields[0]] => ($record[$fields[1]] == "" ? "(Vide)" : $record[$fields[1]])];
                        } else {
                            $values[] = [($record[$fields[0]] == "" ? " " : $record[$fields[0]]) => ($record[$fields[0]] == "" ? "(Vide)" : $record[$fields[0]])];
                        }
                    }
                    $model->filters[$i]->values = $values;
                }
            }
        }

        return json_encode($model->filters);

    }

    /**
     * Retourne la liste des sites.
     *
     * @return array
     */
    public static function get_cities() {
        global $DB;
        return $DB->get_records('apsolu_cities', $conditions = [], $sort = 'name');
    }

    /**
     * Retourne la liste des types de calendriers.
     *
     * @return array
     */
    public static function get_calendarstypes() {
        global $DB;
        return $DB->get_records('apsolu_calendars_types', $conditions = [], $sort = 'name');
    }

    /**
     * Retourne la liste des activités complémentaires.
     *
     * @return array
     */
    public static function get_complementaries() {
        global $DB;

        $sql = "SELECT DISTINCT c.id, shortname as name
        FROM  mdl_apsolu_complements ac
        INNER JOIN mdl_course c on c.id = ac.id
        ORDER BY c.shortname";

        return $DB->get_records_sql($sql);
    }

    /**
     * Traduit les libellés des filtres.
     *
     * @param stdClass $model Objet représentant la configuration json du rapport.
     *
     * @return stdClass
     */
    public static function localize_filters(stdClass $model) {
        if (isset($model->filters) === true) {
            foreach ($model->filters as $key1 => $filter) {
                if (isset($filter->label) === false) {
                    continue;
                }

                list($stringid, $component) = explode(',', $filter->label);
                $model->filters[$key1]->label = get_string($stringid, $component);
            }
        }

        return $model;
    }

    /**
     * Traduit les libellés des entêtes de colonne des rapports.
     *
     * @param stdClass $model Objet représentant la configuration json du rapport.
     *
     * @return stdClass
     */
    public static function localize_reports(stdClass $model) {
        if (isset($model->reports) === true) {
            foreach ($model->reports as $key1 => $report) {
                if (isset($report->values->columns) === false) {
                    continue;
                }

                foreach ($report->values->columns as $key2 => $column) {
                    if (isset($column->title) === false) {
                        continue;
                    }

                    list($stringid, $component) = explode(',', $column->title);
                    $model->reports[$key1]->values->columns[$key2]->title = get_string($stringid, $component);
                }
            }
        }

        return $model;
    }
}
