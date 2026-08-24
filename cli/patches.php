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

use core_customfield\api as CustomfieldAPI;
use core_customfield\data_controller;
use local_apsolu\core\coursetype;
use local_apsolu\core\customfields;
use local_apsolu\customfields\course as CustomfieldsCourse;
use local_apsolu\payment\method;

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');

try {
    $dbman = $DB->get_manager();

    // Initialise les variables pour les méthodes de paiement.
    method::init_config();

    // Ajoute un champ "timelicensed" dans la table "apsolu_federation_adhesions".
    $table = new xmldb_table('apsolu_federation_adhesions');
    $field = new xmldb_field('timelicensed', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, 'timemodified');

    if ($dbman->field_exists($table, $field) === false) {
        $dbman->add_field($table, $field);
    }

    // Génère les tables pour la gestion des formats de cours.
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
        CustomfieldsCourse::initialize_course_customfields();
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

    $params = ['name' => 'APSOLU', 'component' => 'core_course', 'area' => 'course', 'itemid' => 0];
    $category = $DB->get_record('customfield_category', $params, '*', MUST_EXIST);

    $fields = [];
    foreach (CustomfieldAPI::get_categories_with_fields('core_course', 'course', 0) as $customcategory) {
        if ($customcategory->get('id') != $category->id) {
            continue;
        }

        foreach ($customcategory->get_fields() as $field) {
            $fields[$field->get('shortname')] = $field;
        }

        break;
    }

    $table = new xmldb_table('apsolu_courses');
    if ($dbman->table_exists($table) === true) {
        // Initialise un type de format "cours".
        CourseType::initialize_course_type();
        $coursetype = $DB->get_record('apsolu_courses_types', ['shortname' => strtolower(get_string('course'))], '*', MUST_EXIST);

        // Migre l'ancienne table mdl_apsolu_courses.
        $courses = $DB->get_records('course', $conditions = null, $sort = '', 'id, fullname, category');
        foreach ($DB->get_records('apsolu_courses') as $course) {
            if (isset($courses[$course->id]) === false) {
                // Note: cette situation ne devrait pas exister.
                continue;
            }

            if (defined('CLI_SCRIPT') === true) {
                mtrace('Migre ' . $courses[$course->id]->fullname);
            }

            $coursecontext = context_course::instance($course->id);
            foreach ($fields as $field) {
                $shortname = $field->get('shortname');
                $fieldnameform = 'customfield_' . $shortname;

                $params = (object) ['instanceid' => $course->id, 'contextid' => $coursecontext->id];
                $data = data_controller::create(0, $params, $field);

                $value = null;
                switch ($shortname) {
                    case 'type':
                        $value = $coursetype->id;
                        break;
                    case 'category':
                        $value = ['categoryid' => $courses[$course->id]->category, 'additionalstr' => trim($course->event)];
                        break;
                    case 'location':
                    case 'period':
                    case 'skill':
                        $key = $shortname . 'id';
                        $value = $course->{$key};
                        break;
                    case 'weekday':
                        $value = $course->numweekday;
                        break;
                    case 'timerange':
                        $value = [];
                        foreach (['start', 'end'] as $lapse) {
                            $attribute = sprintf('%stime', $lapse);
                            $raw = explode(':', $course->{$attribute});

                            $value[$lapse] = [];
                            $value[$lapse]['hour'] = $raw[0];
                            $value[$lapse]['minute'] = $raw[1];
                        }
                        break;
                    case 'daterange':
                        $value = ['start' => 0, 'end' => 0];
                        break;
                    case 'federation':
                    case 'on_homepage':
                        if (empty($course->{$shortname}) === false) {
                            $value = 1; // Oui.
                        } else {
                            $value = 2; // Non.
                        }
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
                    case 'show_policy':
                        $value = $course->showpolicy;
                        if ($value === null) {
                            $value = 0;
                        }
                        break;
                }

                if ($value === null) {
                    continue;
                }

                $mformdata = (object) [$fieldnameform => $value];
                $data->instance_form_save($mformdata);
            }
        }

        // Supprime la table apsolu_courses.
        $dbman->drop_table($table);
    }

    $table = new xmldb_table('apsolu_complements');
    if ($dbman->table_exists($table) === true) {
        $complements = $DB->get_records('apsolu_complements', ['federation' => 0]);
        if (count($complements) > 0) {
            // Initialise un type de format "cours".
            $record = new stdClass();
            $record->shortname = 'autonome';
            $record->name = 'Pratique autonome';
            // Modèle du libellé de cours: [activité].
            $record->fullnametemplate = sprintf('%%%02d', $fields['category']->get('id'));
            $record->color = '#f66151';
            $record->sortorder = 2;
            if ($DB->get_record('apsolu_courses_types', ['shortname' => $record->shortname]) === false) {
                $DB->insert_record('apsolu_courses_types', $record);
            }

            // Associe les champs personnalisés au type de format "Pratique autonome".
            $params = ['name' => 'APSOLU', 'component' => 'core_course', 'area' => 'course', 'itemid' => 0];
            $category = $DB->get_record('customfield_category', $params, '*', MUST_EXIST);
            $coursetype = $DB->get_record('apsolu_courses_types', ['shortname' => 'autonome'], '*', MUST_EXIST);

            $coursefields = [
                'type' => ['showinadministration' => 1, 'showonpublicpages' => 1],
                'category' => ['showinadministration' => 1, 'showonpublicpages' => 1],
            ];

            foreach ($fields as $field) {
                if (isset($coursefields[$field->get('shortname')]) === false) {
                    continue;
                }

                $record = new stdClass();
                $record->coursetypeid = $coursetype->id;
                $record->customfieldid = $field->get('id');
                $record->showinadministration = $coursefields[$field->get('shortname')]['showinadministration'];
                $record->showonpublicpages = $coursefields[$field->get('shortname')]['showonpublicpages'];

                $conditions = ['coursetypeid' => $record->coursetypeid, 'customfieldid' => $record->customfieldid];
                if ($DB->get_record('apsolu_courses_fields', $conditions) !== false) {
                    continue;
                }
                $DB->insert_record('apsolu_courses_fields', $record);
            }

            // Migre l'ancienne table mdl_apsolu_complements.
            foreach ($complements as $complement) {
                $course = $DB->get_record('course', ['id' => $complement->id]);
                if ($course === false) {
                    continue;
                }

                $coursecontext = context_course::instance($course->id);
                foreach ($customcategory->get_fields() as $field) {
                    $shortname = $field->get('shortname');
                    $fieldnameform = 'customfield_' . $shortname;

                    $params = (object) ['instanceid' => $course->id, 'contextid' => $coursecontext->id];
                    $data = data_controller::create(0, $params, $field);

                    $value = null;
                    switch ($shortname) {
                        case 'type':
                            $value = $coursetype->id;
                            break;
                        case 'category':
                            $category = $DB->get_record('apsolu_courses_categories', ['id' => $course->category]);
                            if ($category === false) {
                                $category = current($DB->get_records('apsolu_courses_categories'));
                                if (empty($category) === false) {
                                    // Met à jour la catégorie du cours pour une catégorie d'activités APSOLU.
                                    $course->category = $category->id;
                                    $DB->update_record('course', $course);

                                    $value = ['categoryid' => $category->id, 'additionalstr' => trim($course->fullname)];
                                }
                            } else {
                                $value = ['categoryid' => $course->category, 'additionalstr' => ''];
                            }
                            break;
                    }

                    if ($value === null) {
                        continue;
                    }

                    $mformdata = (object) [$fieldnameform => $value];
                    $data->instance_form_save($mformdata);
                }
            }
        }

        // Supprime la table apsolu_complements.
        $dbman->drop_table($table);
    }

    mtrace(get_string('success'));
} catch (Exception $exception) {
    mtrace(get_string('error'));
    mtrace($exception->getMessage());
}
