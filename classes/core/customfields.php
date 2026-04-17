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
use core_customfield\field_controller;
use core_customfield\handler;
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
     * Retourne la liste des champs personnalisés pour les cours APSOLU indéxés par nom abrégé.
     *
     * @param int|string $coursetypeid Identifiant du type de cours.
     *
     * @return array
     */
    public static function get_course_custom_fields($coursetypeid = null) {
        global $DB;

        $fields = [];
        $params = [];

        if ($coursetypeid === null) {
            $sql = "SELECT cf.shortname, cf.*
                      FROM {customfield_field} cf
                      JOIN {customfield_category} cc ON cc.id = cf.categoryid
                     WHERE cc.component = 'core_course'
                       AND cc.area = 'course'
                       AND cc.name = 'APSOLU'
                  ORDER BY cf.sortorder";
        } else {
            $sql = "SELECT cf.shortname, cf.*
                      FROM {customfield_field} cf
                      JOIN {customfield_category} cc ON cc.id = cf.categoryid
                      JOIN {apsolu_courses_fields} acf ON acf.customfieldid = cf.id
                     WHERE cc.component = 'core_course'
                       AND cc.area = 'course'
                       AND cc.name = 'APSOLU'
                       AND acf.coursetypeid = :coursetypeid
                  ORDER BY cf.sortorder";
            $params['coursetypeid'] = $coursetypeid;
        }

        foreach ($DB->get_records_sql($sql, $params) as $field) {
            $fields[$field->shortname] = $field;
        }

        return $fields;
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

    /**
     * Initialise les champs personnalisés de cours.
     *
     * @return void
     */
    public static function initialize_course_customfields() {
        global $DB;

        // Récupère ou crée une catégorie APSOLU pour les champs personnalisés de cours.
        $handler = handler::get_handler('core_course', 'course', 0);

        $params = ['name' => 'APSOLU', 'component' => 'core_course', 'area' => 'course', 'itemid' => 0];
        $category = $DB->get_record('customfield_category', $params);
        if ($category === false) {
            $category = (object) $params;
            $category->id = $handler->create_category('APSOLU');
        }

        $category = $handler->get_categories_with_fields()[$category->id];

        // Liste des champs personnalisés de cours APSOLU.
        $fields = [];
        $fields['type'] = ['lang' => 'type', 'type' => 'apsolu_course_type'];
        $fields['category'] = ['lang' => 'activity', 'type' => 'apsolu_category'];
        $fields['event'] = ['lang' => 'event', 'type' => 'text'];
        $fields['skill'] = ['lang' => 'skill', 'type' => 'apsolu_skill'];
        $fields['location'] = ['lang' => 'location', 'type' => 'apsolu_location'];
        $fields['weekday'] = ['lang' => 'weekday', 'type' => 'weekday'];
        $fields['start_time'] = ['lang' => 'start_time', 'type' => 'time'];
        $fields['end_time'] = ['lang' => 'end_time', 'type' => 'time'];
        $fields['start_date'] = ['lang' => 'start_date', 'type' => 'day'];
        $fields['end_date'] = ['lang' => 'end_date', 'type' => 'day'];
        $fields['license'] = ['lang' => 'federation', 'type' => 'select'];
        $fields['on_homepage'] = ['lang' => 'on_homepage', 'type' => 'select'];
        $fields['period'] = ['lang' => 'period', 'type' => 'apsolu_period'];
        $fields['show_policy'] = ['lang' => 'show_policy_on_enrolment', 'type' => 'select'];
        $fields['information'] = ['lang' => 'additional_information', 'type' => 'textarea'];

        foreach ($category->get_fields() as $field) {
            $name = $field->get('shortname');
            if (isset($fields[$name]) === false) {
                continue;
            }

            // Ce champ existe déjà. On le retire de la liste des champs à créer.
            unset($fields[$name]);
        }

        // Enregistre les nouveaux champs personnalisés de cours.
        foreach ($fields as $shortname => $values) {
            $lang = $values['lang'];
            $type = $values['type'];

            $configdata = ['defaultvalue' => '', 'locked' => '1', 'required' => '0', 'uniquevalues' => '0', 'visibility' => '0'];

            $field = new stdClass();
            $field->shortname = $shortname;
            $field->name = get_string($lang, 'local_apsolu');
            $field->type = $type;
            $field->categoryid = $category->get('id');

            switch ($type) {
                case 'date':
                    $configdata['includetime'] = '0';
                    $configdata['mindate'] = 0;
                    $configdata['maxdate'] = 0;
                    break;
                case 'select':
                    $options = [get_string('yes'), get_string('no')];
                    $configdata['options'] = implode("\r\n", $options);
                    break;
                case 'text':
                    $configdata['displaysize'] = 50;
                    $configdata['maxlength'] = 1333;
                    $configdata['ispassword'] = '0';
                    $configdata['link'] = '';
                    break;
                case 'textarea':
                    $configdata['defaultvalueformat'] = FORMAT_HTML;
                    break;
                case 'time':
                    $configdata['defaultvalue'] = '00:00';
                    break;
            }

            $field->configdata = json_encode($configdata);

            $fieldcontroller = field_controller::create(0, $field);
            $fieldcontroller->save();
        }
    }
}
