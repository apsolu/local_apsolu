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
 * Ce script permet de modifier le schéma de base de données sans avoir à changer le numéro de version du module.
 *
 * Ce script est utile pour déployer des fonctionnalités sans avoir à mettre en maintenance Moodle.
 *
 * @package    local_apsolu
 * @copyright  2025 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_customfield\data_controller;
use core_customfield\handler;
use local_apsolu\core\customfields;

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');

try {
    $dbman = $DB->get_manager();

    // Ajoute l'incrémentation automatique sur le champ "id" de la table "apsolu_communication_templates".
    $table = new xmldb_table('apsolu_communication_templates');
    $field = new xmldb_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
    $dbman->change_field_type($table, $field);

    // Met à jour les adresses Paybox.
    $oldvalue = get_config('local_apsolu', 'paybox_servers_incoming');
    if ($oldvalue === '194.2.122.158,194.2.122.190,195.25.7.166,195.25.67.22') {
        $newvalue = '62.161.13.193,62.161.15.193,195.25.67.22';
        set_config('paybox_servers_incoming', $newvalue, 'local_apsolu');
        add_to_config_log('paybox_servers_incoming', $oldvalue, $newvalue, 'local_apsolu');
    }

    // Génère les champs de profil "Formation" et "Autres cursus" si ils n'existent pas.
    require_once($CFG->dirroot . '/user/profile/definelib.php');
    require_once($CFG->dirroot . '/user/profile/lib.php');

    $category = $DB->get_record('user_info_field', ['shortname' => 'apsoluusertype'], $fields = '*', MUST_EXIST);

    $shortname = 'apsolumaintraining';
    if ($DB->get_record('user_info_field', ['shortname' => $shortname]) === false) {
        $data = [
            'shortname' => $shortname,
            'name' => get_string('fields_' . $shortname, 'local_apsolu'),
            'datatype' => 'text',
            'description' => ['format' => FORMAT_HTML, 'text' => ''],
            'categoryid' => $category->categoryid,
            'required' => 0,
            'locked' => 1,
            'visible' => 1,
            'forceunique' => 0,
            'signup' => 0,
            'defaultdata' => '',
            'defaultdataformat' => FORMAT_MOODLE,
            'param1' => 30,
            'param2' => 2048,
            'param3' => 0,
            'param4' => '',
            'param5' => '',
        ];

        profile_save_field((object) $data, $editors = []);
    }

    $shortname = 'apsoluothertrainings';
    if ($DB->get_record('user_info_field', ['shortname' => $shortname]) === false) {
        $data = [
            'shortname' => $shortname,
            'name' => get_string('fields_' . $shortname, 'local_apsolu'),
            'datatype' => 'textarea',
            'description' => ['format' => FORMAT_HTML, 'text' => ''],
            'categoryid' => $category->categoryid,
            'required' => 0,
            'locked' => 1,
            'visible' => 1,
            'forceunique' => 0,
            'signup' => 0,
            'defaultdata' => '',
            'defaultdataformat' => FORMAT_PLAIN,
            'param1' => null,
            'param2' => null,
            'param3' => null,
            'param4' => null,
            'param5' => null,
        ];

        profile_save_field((object) $data, $editors = []);
    }

    $table = new xmldb_table('apsolu_courses_types');
    if ($dbman->table_exists($table) === false) {
        $previous = null;
        $nodefault = null;
        $nosequence = null;
        $notnull = XMLDB_NOTNULL;

        // Adding fields.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, $notnull, XMLDB_SEQUENCE, $nodefault, $previous);
        $table->add_field('shortname', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, $notnull, $nosequence, $nodefault, $previous);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, $notnull, $nosequence, $nodefault, $previous);
        $table->add_field('fullnametemplate', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, $notnull, $nosequence, $nodefault, $previous);
        $table->add_field('color', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, $notnull, $nosequence, $nodefault, $previous);
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, $notnull, $nosequence, $default = 0, $previous);

        // Adding key.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Adding indexes.
        $table->add_index('shortname', XMLDB_INDEX_UNIQUE, ['shortname']);
        $table->add_index('sortorder', XMLDB_INDEX_NOTUNIQUE, ['sortorder']);

        // Create table.
        $dbman->create_table($table);

        // Initialise les champs personnalisés de cours.
        customfields::initialize_course_customfields();
    }

    $table = new xmldb_table('apsolu_courses_fields');
    if ($dbman->table_exists($table) === false) {
        $previous = null;
        $nodefault = null;
        $nosequence = null;
        $notnull = XMLDB_NOTNULL;

        // Adding fields.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, $notnull, XMLDB_SEQUENCE, $nodefault, $previous);
        $table->add_field('coursetypeid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, $notnull, $nosequence, $nodefault, $previous);
        $table->add_field('customfieldid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, $notnull, $nosequence, $nodefault, $previous);
        $table->add_field('showinadministration', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, $notnull, $nosequence, $nodefault);
        $table->add_field('showonpublicpages', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, $notnull, $nosequence, $nodefault);

        // Adding key.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Adding indexes.
        $table->add_index('unique', XMLDB_INDEX_UNIQUE, ['coursetypeid, customfieldid']);

        // Create table.
        $dbman->create_table($table);
    }

/*
    // Ajoute une colonne 'coursetypeid' sur la table 'apsolu_courses'.
    $table = new xmldb_table('apsolu_courses');
    $field = new xmldb_field('coursetypeid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = '1', $previous = 'on_homepage');

    if ($dbman->field_exists($table, $field) === false) {
        $dbman->add_field($table, $field);
    }
 */

    $table = new xmldb_table('apsolu_courses');
    if ($dbman->table_exists($table) === true) {
        // Initialise un type de format "cours".
        $params = ['name' => 'APSOLU', 'component' => 'core_course', 'area' => 'course', 'itemid' => 0];
        $category = $DB->get_record('customfield_category', $params, '*', MUST_EXIST);

        $handler = handler::get_handler('core_course', 'course', 0);

        $fields = [];
        foreach ($handler->get_categories_with_fields()[$category->id]->get_fields() as $field) {
            $fields[$field->get('shortname')] = $field->get('id');
        }

        $record = new stdClass();
        $record->shortname = strtolower(get_string('course'));
        $record->name = get_string('course');
        // Modèle: [activité] [jour] [heure début] [heure fin] [niveau].
        $record->fullnametemplate = sprintf(
            '%%%02d %%%02d %%%02d %%%02d %%%02d',
            $fields['category'],
            $fields['weekday'],
            $fields['start_time'],
            $fields['end_time'],
            $fields['skill']
        );
        $record->color = '#f66151';
        $DB->insert_record('apsolu_courses_types', $record);

        // Associe les champs personnalisés au type de format "cours".
        $params = ['name' => 'APSOLU', 'component' => 'core_course', 'area' => 'course', 'itemid' => 0];
        $category = $DB->get_record('customfield_category', $params, '*', MUST_EXIST);
        $coursetype = $DB->get_record('apsolu_courses_types', ['shortname' => strtolower(get_string('course'))], '*', MUST_EXIST);

        $coursefields = ['category', 'event', 'skill', 'location', 'weekday', 'start_time', 'end_time', 'license', 'on_homepage',
            'period', 'show_policy', 'information'];

        $handler = handler::get_handler('core_course', 'course', 0);
        foreach ($handler->get_categories_with_fields()[$category->id]->get_fields() as $field) {
            if (in_array($field->get('shortname'), $coursefields, $strict = true) === false) {
                continue;
            }

            $record = new stdClass();
            $record->coursetypeid = $coursetype->id;
            $record->customfieldid = $field->get('id');
            $record->showinadministration = 1;
            $record->showonpublicpages = 1;
            $DB->insert_record('apsolu_courses_fields', $record);
        }

        // Migre l'ancienne table mdl_apsolu_courses.
        $handler = handler::get_handler('core_course', 'course', 0);

        $params = ['name' => 'APSOLU', 'component' => 'core_course', 'area' => 'course', 'itemid' => 0];
        $category = $DB->get_record('customfield_category', $params, '*', MUST_EXIST);
        $customcategory = $handler->get_categories_with_fields()[$category->id];

        $courses = $DB->get_records('course', $conditions = null, $sort = '', $fields = 'id, category');
        foreach ($DB->get_records('apsolu_courses') as $course) {
            if (isset($courses[$course->id]) === false) {
                // Note: cette situation ne devrait pas exister.
                continue;
            }

            $coursecontext = context_course::instance($course->id);
            foreach ($customcategory->get_fields() as $field) {
                $shortname = $field->get('shortname');
                $fieldnameform = 'customfield_' . $shortname;

                $params = (object) ['instanceid' => $course->id, 'contextid' => $coursecontext->id];
                $data = data_controller::create(0, $params, $field);

                switch ($shortname) {
                    case 'category':
                        $value = $courses[$course->id]->category;
                        break;
                    case 'end_date':
                    case 'start_date':
                        $value = 0;
                        break;
                    case 'end_time':
                        $values = explode(':', $course->endtime);
                        $value = $values[0] * HOURSECS + $values[1] * MINSECS;
                        break;
                    case 'start_time':
                        $values = explode(':', $course->starttime);
                        $value = $values[0] * HOURSECS + $values[1] * MINSECS;
                        break;
                    case 'information':
                        if ($course->information === null) {
                            $course->information = '';
                        }

                        if ($course->informationformat === null) {
                            $course->informationformat = FORMAT_HTML;
                        }
                        $value = ['text' => $course->information, 'format' => $course->informationformat];
                        $fieldnameform .= '_editor';
                        break;
                    case 'location':
                    case 'period':
                    case 'skill':
                        $key = $shortname . 'id';
                        $value = $course->{$key};
                        break;
                    case 'show_policy':
                        $value = $course->showpolicy;
                        if ($value === null) {
                            $value = 0;
                        }
                        break;
                    case 'event':
                    case 'license':
                    case 'on_homepage':
                    // numweek ?
                    // weekdayid ?
                        $value = $course->{$shortname};
                        break;
                    case 'weekday':
                        $value = $course->numweekday;
                        break;
                    case 'type':
                        $value = '1';
                        break;
                }

                $mformdata = (object) [$fieldnameform => $value];
                $data->instance_form_save($mformdata);
            }

            // TODO: later...
            /*
            $tables = ['apsolu_courses', 'apsolu_courses_fieldsxxx'];
            // TODO: apsolu_complements
            foreach ($tables as $tablename) {
                $table = new xmldb_table($tablename);
                if ($dbman->table_exists($table) === true) {
                    $dbman->drop_table($table);
                }
            }
             */
        }
    }

    mtrace(get_string('success'));
} catch (Exception $exception) {
    mtrace(get_string('error'));
    mtrace($exception->getMessage());
}
