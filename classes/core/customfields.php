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

use coding_exception;
use html_writer;
use stdClass;

/**
 * Fonctions pour le module apsolu.
 *
 * @package    local_apsolu
 * @copyright  2018 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class customfields {
    /** @var array Tableau contenant le résultat de profile_get_custom_fields() indexé par fieldname. */
    public static $profilecustomfields = null;

    /**
     * Formate les données retournées par la fonction profile_user_record().
     *
     * @param array         $fields       Tableau des champs à traiter.
     * @param stdClass      $customdata   Objet contenant les données personnalisées à traiter.
     * @param stdClass|null $user         Objet contenant les données utilisateur à traiter.
     * @param bool          $ishtmloutput Booléen indiquant si les données doivent être encodées pour une sortie HTML.
     *
     * @return array
     */
    public static function format_extra_fields(
        array $fields,
        stdClass $customdata,
        ?stdClass $user = null,
        bool $ishtmloutput = true
    ): array {
        $extrafields = [];

        self::get_profile_custom_fields();

        foreach ($fields as $fieldname) {
            if (isset($user->$fieldname) === true) {
                $extrafields[] = s($user->$fieldname);
            } else if (isset($customdata->$fieldname) === true) {
                if ($fieldname === 'apsoluothertrainings') {
                    // Hack spécifique pour le champ 'apsoluothertrainings'.
                    if ($ishtmloutput === true) {
                        $values = explode(PHP_EOL, $customdata->$fieldname);
                        array_map('s', $values);
                        if (empty($values[0]) === false) {
                            $extrafields[] = html_writer::alist($values, $attributes = [], $tag = 'ul');
                        } else {
                            $extrafields[] = '';
                        }
                    } else {
                        $extrafields[] = $customdata->$fieldname;
                    }
                    continue;
                }

                switch (self::$profilecustomfields[$fieldname]->datatype) {
                    case 'checkbox':
                        if (empty($customdata->$fieldname) === true) {
                            $extrafields[] = s(get_string('no'));
                        } else {
                            $extrafields[] = s(get_string('yes'));
                        }
                        break;
                    case 'textarea':
                    case 'text':
                    default:
                        if ($ishtmloutput === true) {
                            $extrafields[] = s($customdata->$fieldname);
                        } else {
                            $extrafields[] = $customdata->$fieldname;
                        }
                }
            } else {
                $extrafields[] = '';
            }
        }

        return $extrafields;
    }

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
     * Retourne la liste des champs additionnels.
     *
     * @param string $context Contexte d'utilisation des champs additionnels. Peut être display ou export.
     *
     * @return array
     */
    public static function get_extra_fields(string $context): array {
        // Valide le contexte d'utilisation.
        if (in_array($context, ['display', 'export'], $strict = true) === false) {
            throw new coding_exception('Unexpected value for $context argument in ' . __METHOD__);
        }

        $extrafields = [];

        $fields = json_decode(get_config('local_apsolu', sprintf('%s_fields', $context)));
        if (is_array($fields) === false) {
            $fields = [];
        }

        $customfields = false;
        foreach ($fields as $field) {
            if (str_starts_with($field, 'extra_') === false) {
                $extrafields[$field] = get_string($field);
                continue;
            }

            if ($customfields === false) {
                $customfields = [];
                foreach (self::get_profile_custom_fields() as $customfield) {
                    $customfields['extra_' . $customfield->shortname] = $customfield;
                }
            }

            if (isset($customfields[$field]) === false) {
                continue;
            }

            $customfield = $customfields[$field];
            $extrafields[$customfield->shortname] = $customfield->name;
        }

        return $extrafields;
    }

    /**
     * Retourne la liste des champs additionnels à exporter.
     *
     * @return array
     */
    #[\core\attribute\deprecated('local_apsolu\core\customfields::get_extra_fields("export")')]
    public static function get_extra_fields_for_export(): array {
        \core\deprecation::emit_deprecation(__METHOD__);

        return self::get_extra_fields('export');
    }

    /**
     * Initialise la variable self::$profilecustomfields et retourne la liste des champs de profil utilisateur personnalisés.
     *
     * La valeur retournée provient de la fonction profile_get_custom_fields(). Le résultat est indexé par le nom abrégé des champs.
     *
     * @return array
     */
    public static function get_profile_custom_fields() {
        if (self::$profilecustomfields !== null) {
            return self::$profilecustomfields;
        }

        self::$profilecustomfields = [];
        foreach (profile_get_custom_fields() as $field) {
            self::$profilecustomfields[$field->shortname] = $field;
        }

        return self::$profilecustomfields;
    }
}
