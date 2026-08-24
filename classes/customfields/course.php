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

namespace local_apsolu\customfields;

use coding_exception;
use core_course\customfield\course_handler;
use core_customfield\field_controller;
use core_customfield\handler;
use html_writer;
use stdClass;

/**
 * Gère les fonctions autour des champs personnalisés de cours.
 *
 * @package    local_apsolu
 * @copyright  2026 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course {
    /**
     * Retourne toutes les cours utilisant une valeur pour un champ personnalisé donné.
     *
     * @param string $customfieldtype
     * @param string $value
     *
     * @return array
     */
    public static function find_records(string $customfieldtype, string $value): array {
        global $DB;

        $sql = "SELECT c.id, c.shortname, c.fullname, c.idnumber, c.category, c.visible
                  FROM {course} c
                  JOIN {customfield_data} cd ON c.id = cd.instanceid
                  JOIN {customfield_field} cf ON cf.id = cd.fieldid
                  JOIN {customfield_category} cc ON cc.id = cf.categoryid
                 WHERE cc.area = 'course'
                   AND cc.component = 'core_course'
                   AND cc.name = 'APSOLU'
                   AND cf.type = :customfieldtype
                   AND cd.value = :value
              ORDER BY c.fullname";

        return $DB->get_records_sql($sql, ['customfieldtype' => $customfieldtype, 'value' => $value]);
    }

    /**
     * Retourne la liste des champs personnalisés pour les cours APSOLU indéxés par nom abrégé.
     *
     * @param int|string|null $coursetypeid Identifiant du type de cours.
     *
     * @return array
     */
    public static function get_course_custom_fields(int|string|null $coursetypeid = null): array {
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
     * Retourne la liste des champs personnalisés de cours de la catégorie APSOLU.
     *
     * @return array
     */
    public static function get_apsolu_courses_custom_fields(): array {
        global $DB;

        $fields = [];

        $sql = "SELECT f.*
                  FROM {customfield_field} f
                  JOIN {customfield_category} c ON c.id = f.categoryid
                 WHERE c.name = 'APSOLU'
                   AND c.component = 'core_course'
                   AND c.area = 'course'
             ORDER BY f.sortorder";
        foreach ($DB->get_records_sql($sql) as $field) {
            $fields[$field->shortname] = $field;
        }

        return $fields;
    }

    /**
     * Initialise les champs personnalisés de cours.
     *
     * Cette méthode est réservée pour le processus d'installation.
     *
     * @return void
     */
    public static function initialize_course_customfields(): void {
        global $DB;

        // Récupère ou crée une catégorie APSOLU pour les champs personnalisés de cours.
        $handler = handler::get_handler('core_course', 'course', 0);

        $params = ['name' => 'APSOLU', 'component' => 'core_course', 'area' => 'course', 'itemid' => 0];
        $category = $DB->get_record('customfield_category', $params);
        if ($category === false) {
            $handler->create_category('APSOLU');
        }

        $category = false;
        foreach ($handler->get_categories_with_fields() as $customfieldcategory) {
            if ($customfieldcategory->get('name') !== 'APSOLU') {
                continue;
            }

            $category = $customfieldcategory;
            break;
        }

        if ($category === false) {
            throw new coding_exception('La catégorie de champs personnalisés de cours "APSOLU" n\'existe pas.');
        }

        // Liste des champs personnalisés de cours APSOLU.
        $fields = [];
        $fields['type'] = ['lang' => 'type', 'type' => 'apsolu_course_type'];
        $fields['category'] = ['lang' => 'activity', 'type' => 'apsolu_category'];
        $fields['skill'] = ['lang' => 'skill', 'type' => 'apsolu_skill'];
        $fields['weekday'] = ['lang' => 'weekday', 'type' => 'weekday'];
        $fields['daterange'] = ['lang' => 'date', 'type' => 'daterange'];
        $fields['timerange'] = ['lang' => 'time', 'type' => 'timerange'];
        $fields['location'] = ['lang' => 'location', 'type' => 'apsolu_location'];
        $fields['period'] = ['lang' => 'period', 'type' => 'apsolu_period'];
        $fields['federation'] = ['lang' => 'federation', 'type' => 'select'];
        $fields['on_homepage'] = ['lang' => 'on_homepage', 'type' => 'select'];
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

        $sortorder = $DB->count_records('customfield_field', ['categoryid' => $category->get('id')]);

        // Enregistre les nouveaux champs personnalisés de cours.
        foreach ($fields as $shortname => $values) {
            $sortorder++;

            $lang = $values['lang'];
            $type = $values['type'];

            $configdata = [
                'defaultvalue' => '',
                'locked' => '1',
                'required' => '0',
                'uniquevalues' => '0',
                'visibility' => course_handler::NOTVISIBLE,
            ];

            $field = new stdClass();
            $field->shortname = $shortname;
            $field->name = get_string($lang, 'local_apsolu');
            $field->type = $type;
            $field->categoryid = $category->get('id');
            $field->sortorder = $sortorder;

            switch ($type) {
                case 'apsolu_category':
                    $configdata['defaultvalue'] = ['categoryid' => '', 'additionalstr' => ''];
                    break;
                case 'daterange':
                    $configdata['defaultvalue'] = ['start' => 0, 'end' => 0];
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
                case 'timerange':
                    $configdata['defaultvalue'] = ['start' => ['hour' => 0, 'minute' => 0], 'end' => ['hour' => 0, 'minute' => 5]];
                    break;
            }

            $field->configdata = json_encode($configdata);

            $fieldcontroller = field_controller::create(0, $field);
            $fieldcontroller->save();
        }
    }
}
