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

namespace local_apsolu\external;

use context_system;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use core_user\external\user_summary_exporter;
use moodle_exception;

/**
 * External municipality API
 *
 * @package   local_apsolu
 * @copyright 2025 Université Rennes 2
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class municipality extends external_api {
    /**
     * Retourne le type de paramètre pour la méthode get_relevant_municipalities.
     *
     * @return external_function_parameters Structure des paramètres possibles.
     */
    public static function get_relevant_municipalities_parameters() {
        return new external_function_parameters([
            'query' => new external_value(
                PARAM_RAW,
                'Query string (full or partial municipality name or other details)'
            ),
        ]);
    }

    /**
     * Retourne le type de résultat rendu par la méthode get_relevant_municipalities.
     *
     * @return external_description Structure des valeurs de retour.
     */
    public static function get_relevant_municipalities_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'departmentid' => new external_value(PARAM_RAW, 'Department id'),
                'inseecode' => new external_value(PARAM_RAW, 'INSEE code'),
                'name' => new external_value(PARAM_RAW, 'Name'),
            ])
        );
    }

    /**
     * Recherche les municipalités selon le critère de recherche donnée.
     *
     * @param string $query Critère de recherche
     *
     * @return array
     */
    public static function get_relevant_municipalities($query) {
        global $DB;

        // Validate parameter.
        [
            'query' => $query,
        ] = self::validate_parameters(self::get_relevant_municipalities_parameters(), [
            'query' => $query,
        ]);

        if ($query < 3) {
            return [];
        }

        // Validate the context (search page is always system context).
        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);

        $sql = "SELECT DISTINCT am.inseecode, am.name, am.departmentid
                  FROM {apsolu_municipalities} am
                 WHERE am.postalcode LIKE :query1
                    OR am.name LIKE :query2
              ORDER BY am.name
                 LIMIT 20";
        return $DB->get_records_sql($sql, ['query1' => $query.'%', 'query2' => $query.'%']);
    }
}
