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

/**
 * Fonctions pour le module apsolu.
 *
 * @package    local_apsolu
 * @copyright  2018 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class customfields {
    /**
     * Retourne la liste des champs personnalisés APSOLU indéxés par nom abrégé.
     *
     * @return array
     */
    public static function getCustomFields() {
        global $DB;

        $fields = [];

        $sql = "SELECT * FROM {user_info_field} WHERE shortname LIKE 'apsolu%'";
        foreach ($DB->get_records_sql($sql) as $field) {
            $fields[$field->shortname] = $field;
        }

        return $fields;
    }

    /**
     * Retourne la liste des champs additionnels à exporter.
     *
     * @return array
     */
    public static function get_extra_fields_for_export(): array {
        $fields = [];

        $exportfields = json_decode(get_config('local_apsolu', 'export_fields'));
        if (is_array($exportfields) === false) {
            $exportfields = [];
        }

        $customfields = false;
        foreach ($exportfields as $field) {
            if (str_starts_with($field, 'extra_') === false) {
                $fields[$field] = get_string($field);
                continue;
            }

            if ($customfields === false) {
                $customfields = [];
                foreach (profile_get_custom_fields() as $customfield) {
                    $customfields['extra_' . $customfield->shortname] = $customfield;
                }
            }

            if (isset($customfields[$field]) === false) {
                continue;
            }

            $customfield = $customfields[$field];
            $fields[$customfield->shortname] = $customfield->name;
        }

        return $fields;
    }
}
