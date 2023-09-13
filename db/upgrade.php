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
 * Post installation hook for adding data.
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\course as Course;
use local_apsolu\core\federation\activity as Activity;
use local_apsolu\core\federation\adhesion as Adhesion;
use local_apsolu\core\federation\course as FederationCourse;
use local_apsolu\core\messaging;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/group/lib.php');
require_once($CFG->dirroot.'/local/apsolu/locallib.php');
require_once($CFG->dirroot.'/user/profile/definelib.php');

/**
 * Procédure de mise à jour.
 *
 * @param int $oldversion Numéro de la version du module theme_apsolu actuellement installé.
 *
 * @return bool
 */
function xmldb_local_apsolu_upgrade($oldversion = 0) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    $result = true;

    $version = 2017120600;
    if ($result && $oldversion < $version) {
        // Create cache directory for homepage.
        $cachedir = $CFG->dataroot.'/apsolu/local_apsolu/cache/homepage';

        if (is_dir($cachedir) === false) {
            $result = mkdir($cachedir, $CFG->directorypermissions, $recursive = true);
        }

        // Create attendance tables.
        $table = new xmldb_table('apsolu_attendance_sessions');

        // If the table does not exist, create it along with its fields.
        if ($dbman->table_exists($table) === false) {
            // Adding fields.
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
            $table->add_field('name', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
            $table->add_field('sessiontime', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = 0, $previous = null);
            $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = 0, null);
            $table->add_field('activityid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = 0, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = 0, null);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = 0, null);

            // Adding key.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

            // Create table.
            $dbman->create_table($table);
        }

        $table = new xmldb_table('apsolu_attendance_presences');
        if ($dbman->table_exists($table) === false) {
            // Adding fields.
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
            $table->add_field('studentid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = 0, $previous = null);
            $table->add_field('teacherid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = 0, $previous = null);
            $table->add_field('status', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = 0, null);
            $table->add_field('description', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = 0, null);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = 0, null);
            $table->add_field('sessionid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, null, null);

            // Adding key.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
            $table->add_key('unique_presence', XMLDB_KEY_UNIQUE, array('studentid', 'sessionid'));

            // Create table.
            $dbman->create_table($table);
        }

        $table = new xmldb_table('apsolu_attendance_statuses');

        // If the table does not exist, create it along with its fields.
        if ($dbman->table_exists($table) === false) {
            // Adding fields.
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
            $table->add_field('name', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
            $table->add_field('code', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);

            // Adding key.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

            // Create table.
            $dbman->create_table($table);

            $statuses = array('present', 'late', 'excused', 'absent');
            foreach ($statuses as $status) {
                $record = new stdClass();
                $record->name = $status;
                $record->code = 'attendance_'.$status;

                $DB->insert_record('apsolu_attendance_statuses', $record);
            }
        }

        // Ajoute un champ locationid dans la table apsolu_attendance_sessions.
        $table = new xmldb_table('apsolu_attendance_sessions');
        $field = new xmldb_field('locationid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, $nullable = null, $sequence = null, null, null);

        if ($dbman->field_exists($table, $field) === false) {
            $dbman->add_field($table, $field);
        }

        // Renomme le champ status en statusid dans la table apsolu_attendance_presences.
        $table = new xmldb_table('apsolu_attendance_presences');
        $field = new xmldb_field('status', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = null, null);

        if ($dbman->field_exists($table, $field) === true) {
            $dbman->rename_field($table, $field, 'statusid');
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, $version, 'local', 'apsolu');
    }

    $version = 2018042500;
    if ($result && $oldversion < $version) {
        $table = new xmldb_table('apsolu_complements');

        // If the table does not exist, create it along with its fields.
        if ($dbman->table_exists($table) === false) {
            // Adding fields.
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
            $table->add_field('price', XMLDB_TYPE_FLOAT, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', null);

            // Adding key.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

            // Create table.
            $dbman->create_table($table);
        }

        $field = new xmldb_field('paymentcenterid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, $not_null = false, null, null, null);

        foreach (array('apsolu_courses', 'apsolu_complements') as $tablename) {
            $table = new xmldb_table($tablename);

            if ($dbman->field_exists($table, $field) === false) {
                $dbman->add_field($table, $field);
            }
        }

        $field = new xmldb_field('generic_name', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, $not_null = false, null, null, null);

        $table = new xmldb_table('apsolu_periods');

        if ($dbman->field_exists($table, $field) === false) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('federation', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, $not_null = false, null, $default = 0, null);

        $table = new xmldb_table('apsolu_courses_categories');

        if ($dbman->field_exists($table, $field) === false) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('license', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, $not_null = false, null, $default = 0, null);

        $table = new xmldb_table('apsolu_courses');

        if ($dbman->field_exists($table, $field) === false) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('federation', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, $not_null = false, null, $default = 0, null);

        $table = new xmldb_table('apsolu_complements');

        if ($dbman->field_exists($table, $field) === false) {
            $dbman->add_field($table, $field);
        }

        // Add missing indexes !
        $tables = array();
        $tables['apsolu_courses'] = array('skillid', 'locationid', 'periodid', 'paymentcenterid');
        $tables['apsolu_locations'] = array('areaid', 'managerid');
        $tables['apsolu_complements'] = array('paymentcenterid');

        foreach ($tables as $tablename => $indexes) {
            $table = new xmldb_table($tablename);
            foreach ($indexes as $indexname) {
                $index = new xmldb_index($indexname, XMLDB_INDEX_NOTUNIQUE, array($indexname));

                if ($dbman->index_exists($table, $index) === false) {
                    $dbman->add_index($table, $index);
                }
            }
        }

        $field = new xmldb_field('on_homepage', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, $not_null = false, null, $default = 1, null);

        $table = new xmldb_table('apsolu_courses');

        if ($dbman->field_exists($table, $field) === false) {
            $dbman->add_field($table, $field);
        }

        // Create cities table.
        $table = new xmldb_table('apsolu_cities');

        // If the table does not exist, create it along with its fields.
        if ($dbman->table_exists($table) === false) {
            // Adding fields.
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
            $table->add_field('name', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);

            // Adding key.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

            // Create table.
            $dbman->create_table($table);
        }

        // Add cityid field to areas table.
        $field = new xmldb_field('cityid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, $not_null = false, null, $default = 1, null);

        $table = new xmldb_table('apsolu_areas');

        if ($dbman->field_exists($table, $field) === false) {
            $dbman->add_field($table, $field);

            $table->add_key('cityid', XMLDB_KEY_FOREIGN, array('cityid'), 'apsolu_cities', array('id'));
        }

        // Add missing indexes !
        $tables = array();
        $tables['apsolu_payments'] = array('userid', 'paymentcenterid');
        $tables['apsolu_payments_items'] = array('paymentid', 'courseid', 'roleid');

        foreach ($tables as $tablename => $indexes) {
            $table = new xmldb_table($tablename);
            foreach ($indexes as $indexname) {
                $index = new xmldb_index($indexname, XMLDB_INDEX_NOTUNIQUE, array($indexname));

                if ($dbman->index_exists($table, $index) === false) {
                    $dbman->add_index($table, $index);
                }
            }
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, $version, 'local', 'apsolu');
    }

    $version = 2018071704;
    if ($result && $oldversion < $version) {
        $fields = $DB->get_records('user_info_field', array(), $sort = 'sortorder DESC');
        if (count($fields) === 0) {
            // Ajoute une sous-catégorie de champs complémentaires.
            $category = $DB->get_record('user_info_category', array('sortorder' => 1));
            if ($category === false) {
                $category = new stdClass();
                $category->name = get_string('fields_complements_category', 'local_apsolu');
                $category->sortorder = 1;
                $category->id = $DB->insert_record('user_info_category', $category);
            }

            $field = (object) [
                'datatype' => 'text',
                'description' => '',
                'descriptionformat' => '',
                'categoryid' => $category->id,
                'sortorder' => '0',
                'required' => '0',
                'locked' => '1',
                'visible' => '1',
                'forceunique' => '0',
                'signup' => '0',
                'defaultdata' => '',
                'defaultdataformat' => '0',
                'param1' => '30',
                'param2' => '2048',
                'param3' => '0',
                'param4' => '',
                'param5' => '',
               ];
        } else {
            $field = current($fields);
            unset($field->id);
        }

        // Ajoute ou renomme les champs de profil complémentaires.
        $customs = array();
        $customs['postalcode'] = (object) ['shortname' => 'apsolupostalcode', 'datatype' => 'text', 'param1' => 30, 'param2' => 2048, 'param3' => 0, 'visible' => 0];
        $customs['sex'] = (object) ['shortname' => 'apsolusex', 'datatype' => 'text', 'param1' => 30, 'param2' => 2048, 'param3' => 0, 'visible' => 0];
        $customs['birthday'] = (object) ['shortname' => 'apsolubirthday', 'datatype' => 'text', 'param1' => 30, 'param2' => 2048, 'param3' => 0, 'visible' => 1];
        $customs['ufr'] = (object) ['shortname' => 'apsoluufr', 'datatype' => 'text', 'param1' => 30, 'param2' => 2048, 'param3' => 0, 'visible' => 1];
        $customs['lmd'] = (object) ['shortname' => 'apsolucycle', 'datatype' => 'text', 'param1' => 30, 'param2' => 2048, 'param3' => 0, 'visible' => 1];
        $customs['cardpaid'] = (object) ['shortname' => 'apsolucardpaid', 'datatype' => 'checkbox', 'param1' => null, 'param2' => null, 'param3' => null, 'visible' => 0];
        $customs['federationpaid'] = (object) ['shortname' => 'apsolufederationpaid', 'datatype' => 'checkbox', 'param1' => null, 'param2' => null, 'param3' => null, 'visible' => 0];
        $customs['muscupaid'] = (object) ['shortname' => 'apsolumuscupaid', 'datatype' => 'checkbox', 'param1' => null, 'param2' => null, 'param3' => null, 'visible' => 0];
        $customs['validsesame'] = (object) ['shortname' => 'apsolusesame', 'datatype' => 'checkbox', 'param1' => null, 'param2' => null, 'param3' => null, 'visible' => 0];
        $customs['medicalcertificate'] = (object) ['shortname' => 'apsolumedicalcertificate', 'datatype' => 'checkbox', 'param1' => null, 'param2' => null, 'param3' => null, 'visible' => 1];
        $customs['federationnumber'] = (object) ['shortname' => 'apsolufederationnumber', 'datatype' => 'text', 'param1' => 30, 'param2' => 2048, 'param3' => 0, 'visible' => 1];
        $customs['highlevelathlete'] = (object) ['shortname' => 'apsoluhighlevelathlete', 'datatype' => 'checkbox', 'param1' => null, 'param2' => null, 'param3' => null, 'visible' => 0];
        $customs['apsoluidcardnumber'] = (object) ['shortname' => 'apsoluidcardnumber', 'datatype' => 'text', 'param1' => 30, 'param2' => 2048, 'param3' => 0, 'visible' => 0];
        $customs['apsoludoublecursus'] = (object) ['shortname' => 'apsoludoublecursus', 'datatype' => 'checkbox', 'param1' => null, 'param2' => null, 'param3' => null, 'visible' => 0];

        foreach ($customs as $oldname => $custom) {
            $field_ = $DB->get_record('user_info_field', array('shortname' => $oldname));
            if ($field_ !== false) {
                // Renomme le champ.
                $field_->shortname = $custom->shortname;
                $field_->name = get_string('fields_'.$field_->shortname, 'local_apsolu');

                $DB->update_record('user_info_field', $field_);

                continue;
            }

            $field_ = $DB->get_record('user_info_field', array('shortname' => $custom->shortname));
            if ($field_ === false) {
                // Insert les nouveaux champs.
                $field->shortname = $custom->shortname;
                $field->name = get_string('fields_'.$field->shortname, 'local_apsolu');
                $field->datatype = $custom->datatype;
                $field->visible = $custom->visible;
                $field->param1 = $custom->param1;
                $field->param2 = $custom->param2;
                $field->param3 = $custom->param3;
                $field->sortorder++;

                $DB->insert_record('user_info_field', $field);
            }
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, $version, 'local', 'apsolu');
    }

    $version = 2018071801;
    if ($result && $oldversion < $version) {
        // Create skills descriptions tables.
        $table = new xmldb_table('apsolu_skills_descriptions');

        // If the table does not exist, create it along with its fields.
        if ($dbman->table_exists($table) === false) {
            // Adding fields.
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
            $table->add_field('description', XMLDB_TYPE_TEXT, $precision = null, $unsigned = null, $notnull = null, $sequence = null, $default = null, $previous = null);
            $table->add_field('activityid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = 0, null);
            $table->add_field('skillid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = 0, null);

            // Adding key.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

            // Create table.
            $dbman->create_table($table);
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, $version, 'local', 'apsolu');
    }

    $version = 2018092000;
    if ($result && $oldversion < $version) {
        $table = new xmldb_table('apsolu_calendars');
        if ($dbman->table_exists($table) === false) {
            // Adding fields.
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
            $table->add_field('name', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
            $table->add_field('enrolstartdate', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
            $table->add_field('enrolenddate', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
            $table->add_field('coursestartdate', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
            $table->add_field('courseenddate', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
            $table->add_field('reenrolstartdate', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
            $table->add_field('reenrolenddate', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
            $table->add_field('gradestartdate', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
            $table->add_field('gradeenddate', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
            $table->add_field('typeid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = null, null);

            // Adding key.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

            // Create table.
            $dbman->create_table($table);
        }

        $table = new xmldb_table('apsolu_calendars_types');
        if ($dbman->table_exists($table) === false) {
            // Adding fields.
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
            $table->add_field('name', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);

            // Adding key.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

            // Create table.
            $dbman->create_table($table);
        }

        $table = new xmldb_table('apsolu_payments_cards');

        if ($dbman->table_exists($table) === false) {
            // Adding fields.
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
            $table->add_field('name', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
            $table->add_field('fullname', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
            $table->add_field('trial', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = 0, null);
            $table->add_field('price', XMLDB_TYPE_FLOAT, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', null);
            $table->add_field('centerid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = null, null);

            // Adding key.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

            // Create table.
            $dbman->create_table($table);
        }

        $table = new xmldb_table('apsolu_payments_cards_cohort');
        if ($dbman->table_exists($table) === false) {
            // Adding fields.
            $table->add_field('cardid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = null, null);
            $table->add_field('cohortid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = null, null);

            // Adding key.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('cardid', 'cohortid'));

            // Create table.
            $dbman->create_table($table);
        }

        $table = new xmldb_table('apsolu_payments_cards_roles');
        if ($dbman->table_exists($table) === false) {
            // Adding fields.
            $table->add_field('cardid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = null, null);
            $table->add_field('roleid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = null, null);

            // Adding key.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('cardid', 'roleid'));

            // Create table.
            $dbman->create_table($table);
        }

        $table = new xmldb_table('apsolu_payments_cards_cals');
        if ($dbman->table_exists($table) === false) {
            // Adding fields.
            $table->add_field('cardid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = null, null);
            $table->add_field('calendartypeid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = null, null);
            $table->add_field('value', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = null, null);

            // Adding key.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('cardid', 'calendartypeid'));

            // Create table.
            $dbman->create_table($table);
        }

        $table = new xmldb_table('apsolu_payments_items');
        if ($dbman->table_exists($table) === true) {
            $dbman->drop_table($table);
        }

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('paymentid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = 0, null);
        $table->add_field('cardid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = 0, null);

        // Adding key.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Create table.
        $dbman->create_table($table);

        // Corrige un problème sur le champ apsoludoublecursus.
        $record = $DB->get_record('user_info_field', array('shortname' => 'apsoludoublecursus'));
        if ($record !== false) {
            $record->forceunique = 0;
            $record->defaultdata = 0;

            $DB->update_record('user_info_field', $record);
        }
    }

    $version = 2018102500;
    if ($result && $oldversion < $version) {
        $table = new xmldb_table('apsolu_dunnings');
        if ($dbman->table_exists($table) === false) {
            // Adding fields.
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
            $table->add_field('subject', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
            $table->add_field('message', XMLDB_TYPE_TEXT, $precision = null, $unsigned = null, $notnull = null, $sequence = null, $default = null, $previous = null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
            $table->add_field('timestarted', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, $notnull = null, null, null, null);
            $table->add_field('timeended', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, $notnull = null, null, null, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = null, null);

            // Adding key.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

            $table->add_index($indexname = 'timestarted', XMLDB_INDEX_NOTUNIQUE, $fields = array('timestarted'));

            // Create table.
            $dbman->create_table($table);
        }

        $table = new xmldb_table('apsolu_dunnings_posts');
        if ($dbman->table_exists($table) === false) {
            // Adding fields.
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
            $table->add_field('dunningid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = null, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = null, null);

            // Adding key.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

            // Create table.
            $dbman->create_table($table);
        }

        $table = new xmldb_table('apsolu_dunnings_cards');
        if ($dbman->table_exists($table) === false) {
            // Adding fields.
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
            $table->add_field('dunningid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = null, null);
            $table->add_field('cardid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = null, null);

            // Adding key.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

            // Create table.
            $dbman->create_table($table);
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, $version, 'local', 'apsolu');
    }

    $version = 2019030700;
    if ($result && $oldversion < $version) {
        // Corrige tous les indexes manquants en base de données.
        $tables = array();
        $tables['apsolu_areas'] = array('cityid');
        $tables['apsolu_attendance_presences'] = array('studentid', 'teacherid', 'statusid', 'sessionid');
        $tables['apsolu_attendance_sessions'] = array('courseid', 'locationid', 'activityid');
        $tables['apsolu_calendars'] = array('typeid');
        $tables['apsolu_colleges'] = array('roleid');
        $tables['apsolu_dunnings'] = array('userid');
        $tables['apsolu_dunnings_cards'] = array('dunningid', 'cardid');
        $tables['apsolu_dunnings_posts'] = array('dunningid', 'userid');
        $tables['apsolu_grades'] = array('courseid', 'userid', 'teacherid');
        $tables['apsolu_grades_history'] = array('gradeid', 'teacherid');
        $tables['apsolu_payments_cards'] = array('centerid');
        $tables['apsolu_payments_items'] = array('paymentid', 'cardid');
        $tables['apsolu_skills_descriptions'] = array('activityid', 'skillid');

        foreach ($tables as $tablename => $indexes) {
            $table = new xmldb_table($tablename);
            foreach ($indexes as $indexname) {
                $index = new xmldb_index($indexname, XMLDB_INDEX_NOTUNIQUE, array($indexname));

                if ($dbman->index_exists($table, $index) === false) {
                    $dbman->add_index($table, $index);
                }
            }
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, $version, 'local', 'apsolu');
    }

    $version = 2019041200;
    if ($result && $oldversion < $version) {
        // Augmente la taille du champ weeks de la table apsolu_periods à 1024 caractères.
        $table = new xmldb_table('apsolu_periods');
        $field = new xmldb_field('weeks', XMLDB_TYPE_CHAR, '1024', null, null, null, null);

        $dbman->change_field_precision($table, $field);

        // Savepoint reached.
        upgrade_plugin_savepoint(true, $version, 'local', 'apsolu');
    }

    $version = 2020021800;
    if ($result && $oldversion < $version) {
        // Ajoute une colonne 'prefix' sur la table 'apsolu_payments_centers'.
        $table = new xmldb_table('apsolu_payments_centers');
        $field = new xmldb_field('prefix', XMLDB_TYPE_TEXT, $precision = null, $unsigned = null, $notnull = null, $sequence = null, $default = null, $previous = 'name');

        if ($dbman->field_exists($table, $field) === false) {
            $dbman->add_field($table, $field);
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, $version, 'local', 'apsolu');
    }

    $version = 2020030300;
    if ($result && $oldversion < $version) {
        $table = new xmldb_table('apsolu_dunnings');

        // Renomme le champ 'timeend' de la table 'apsolu_dunnings'.
        $field = new xmldb_field('timeend', XMLDB_TYPE_INTEGER, $precision = '10', $unsigned = XMLDB_UNSIGNED, $notnull = null, $sequence = null, $default = null, $previous = null);
        if ($dbman->field_exists($table, $field) === true) {
            $dbman->rename_field($table, $field, 'timeended');
        }

        // Rend nullable le champ 'timestarted' de la table 'apsolu_dunnings'.
        $index = new xmldb_index($indexname = 'timestarted', XMLDB_INDEX_NOTUNIQUE, $fields = array('timestarted'));
        $dbman->drop_index($table, $index);

        $field = new xmldb_field('timestarted', XMLDB_TYPE_INTEGER, $precision = '10', $unsigned = XMLDB_UNSIGNED, $notnull = null, $sequence = null, $default = null, $previous = null);
        $dbman->change_field_type($table, $field);

        $dbman->add_index($table, $index);

        // Rend nullable le champ 'timeend' de la table 'apsolu_dunnings'.
        $table = new xmldb_table('apsolu_dunnings');
        $field = new xmldb_field('timeended', XMLDB_TYPE_INTEGER, $precision = '10', $unsigned = XMLDB_UNSIGNED, $notnull = null, $sequence = null, $default = null, $previous = null);
        $dbman->change_field_type($table, $field);

        // Permet de saisir des montants de paiement avec des virgules.
        $table = new xmldb_table('apsolu_payments');
        $field = new xmldb_field('amount', XMLDB_TYPE_NUMBER, $precision = '10,2', $unsigned = XMLDB_UNSIGNED, $notnull = null, $sequence = null, $default = null, $previous = null);
        $dbman->change_field_type($table, $field);

        $table = new xmldb_table('apsolu_complements');
        $field = new xmldb_field('price', XMLDB_TYPE_NUMBER, $precision = '10,2', $unsigned = XMLDB_UNSIGNED, $notnull = null, $sequence = null, $default = null, $previous = null);
        $dbman->change_field_type($table, $field);

        $table = new xmldb_table('apsolu_payments_cards');
        $field = new xmldb_field('price', XMLDB_TYPE_NUMBER, $precision = '10,2', $unsigned = XMLDB_UNSIGNED, $notnull = null, $sequence = null, $default = null, $previous = null);
        $dbman->change_field_type($table, $field);

        // Savepoint reached.
        upgrade_plugin_savepoint(true, $version, 'local', 'apsolu');
    }

    $version = 2020050700;
    if ($result && $oldversion < $version) {
        $field = new xmldb_field('paymentcenterid');

        foreach (array('apsolu_courses', 'apsolu_complements') as $tablename) {
            $table = new xmldb_table($tablename);

            $index = new xmldb_index('paymentcenterid', XMLDB_INDEX_UNIQUE, array('paymentcenterid'));
            if ($dbman->index_exists($table, $index) === true) {
                $dbman->drop_index($table, $index);
            }

            if ($dbman->field_exists($table, $field) === true) {
                $dbman->drop_field($table, $field);
            }
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, $version, 'local', 'apsolu');
    }

    $version = 2020060300;
    if ($result && $oldversion < $version) {
        // Initialise les paramètres de l'offre de formations.
        UniversiteRennes2\Apsolu\set_initial_course_offerings_settings();

        // Savepoint reached.
        upgrade_plugin_savepoint(true, $version, 'local', 'apsolu');
    }

    $version = 2020071000;
    if ($result && $oldversion < $version) {
        // Ajoute la table `apsolu_holidays`.
        $table = new xmldb_table('apsolu_holidays');

        if ($dbman->table_exists($table) === false) {
            // Adding fields.
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
            $table->add_field('day', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = 0, null);

            // Adding key.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
            $table->add_key('unique_day', XMLDB_KEY_UNIQUE, array('day'));

            // Create table.
            $dbman->create_table($table);
        }

        // Génère les sessions de cours.
        $courses = local_apsolu\core\course::get_records();
        foreach ($courses as $course) {
            $course->set_sessions();
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, $version, 'local', 'apsolu');
    }

    $version = 2020092300;
    if ($result && $oldversion < $version) {
        $field = $DB->get_record('user_info_field', array('shortname' => 'apsoluusertype'));
        if ($field === false) {
            // Ajoute un champ de profil "type d'utilisateur".
            $sql = "SELECT id, MAX(sortorder) AS sortorder".
                " FROM {user_info_field}".
                " WHERE categoryid IN (SELECT categoryid FROM {user_info_field} WHERE shortname = 'apsolupostalcode')";
            $category = $DB->get_record_sql($sql);
            if ($category !== false) {
                $category->sortorder++;

                $field = (object) [
                    'shortname' => 'apsoluusertype',
                    'name' => get_string('fields_apsoluusertype', 'local_apsolu'),
                    'datatype' => 'text',
                    'description' => '',
                    'descriptionformat' => '',
                    'categoryid' => $category->id,
                    'sortorder' => $category->sortorder,
                    'required' => '0',
                    'locked' => '1',
                    'visible' => '1',
                    'forceunique' => '0',
                    'signup' => '0',
                    'defaultdata' => '',
                    'defaultdataformat' => '0',
                    'param1' => '30',
                    'param2' => '2048',
                    'param3' => '0',
                    'param4' => '',
                    'param5' => '',
                   ];

                $DB->insert_record('user_info_field', $field);
            }
        }

        // Corrige la mauvaise initialisation du lieu d'une session lors de la génération des sessions d'un cours.
        $courses = $DB->get_records('apsolu_courses');
        $sessions = $DB->get_records('apsolu_attendance_sessions', array('locationid' => 0));
        foreach ($sessions as $session) {
            if (isset($courses[$session->courseid]) === false) {
                continue;
            }

            $session->locationid = $courses[$session->courseid]->locationid;

            $DB->update_record('apsolu_attendance_sessions', $session);
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, $version, 'local', 'apsolu');
    }

    $version = 2020121800;
    if ($result && $oldversion < $version) {
        $table = new xmldb_table('apsolu_grade_items');
        if ($dbman->table_exists($table) === false) {
            // Adding fields.
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
            $table->add_field('name', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
            $table->add_field('roleid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = null, null);
            $table->add_field('calendarid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, $notnull = null, null, null, null);

            // Adding keys.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
            $table->add_key('unique_name', XMLDB_KEY_UNIQUE, array('name', 'calendarid', 'roleid'));

            // Adding indexes.
            $table->add_index($indexname = 'name', XMLDB_INDEX_NOTUNIQUE, $fields = array('name'));
            $table->add_index($indexname = 'calendarid', XMLDB_INDEX_NOTUNIQUE, $fields = array('calendarid'));
            $table->add_index($indexname = 'roleid', XMLDB_INDEX_NOTUNIQUE, $fields = array('roleid'));

            // Create table.
            $dbman->create_table($table);
        }

        $tables = array('apsolu_grades', 'apsolu_grades_history');
        foreach ($tables as $tablename) {
            $table = new xmldb_table($tablename);
            if ($dbman->table_exists($table) === true) {
                $dbman->drop_table($table);
            }
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, $version, 'local', 'apsolu');
    }

    $version = 2021071600;
    if ($oldversion < $version) {
        // Change le type du champ "rank" de la table "apsolu_payments_centers" de bigint à varchar.
        $table = new xmldb_table('apsolu_payments_centers');
        $field = new xmldb_field('rank', XMLDB_TYPE_CHAR, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = null, null);

        $dbman->change_field_type($table, $field);

        // Savepoint reached.
        upgrade_plugin_savepoint(true, $version, 'local', 'apsolu');
    }

    $version = 2021072300;
    if ($result && $oldversion < $version) {
        // Vérifie et corrige les valeurs des adresses de contact.
        $settings = array();
        $settings[] = 'functional_contact';
        $settings[] = 'technical_contact';

        foreach ($settings as $setting) {
            $value = get_config('local_apsolu', $setting);

            if (filter_var($value, FILTER_VALIDATE_EMAIL) !== false) {
                continue;
            }

            set_config($setting, '', 'local_apsolu');
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, $version, 'local', 'apsolu');
    }

    $version = 2022011200;
    if ($result && $oldversion < $version) {
        // Ajoute la table apsolu_payments_addresses.
        $table = new xmldb_table('apsolu_payments_addresses');
        if ($dbman->table_exists($table) === false) {
            // Adding fields.
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
            $table->add_field('firstname', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
            $table->add_field('lastname', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
            $table->add_field('address1', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
            $table->add_field('address2', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
            $table->add_field('zipcode', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
            $table->add_field('city', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
            $table->add_field('countrycode', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, $notnull = null, null, null, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = null, null);

            // Adding keys.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

            // Adding indexes.
            $table->add_index($indexname = 'userid', XMLDB_INDEX_UNIQUE, $fields = array('userid'));

            // Create table.
            $dbman->create_table($table);
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, $version, 'local', 'apsolu');
    }

    $version = 2022031800;
    if ($result && $oldversion < $version) {
        // Ajoute 2 champs `information` et `informationformat` dans la table `apsolu_courses`.
        $table = new xmldb_table('apsolu_courses');

        $fields = array();
        $fields[] = new xmldb_field('information', XMLDB_TYPE_TEXT, $precision = null, $unsigned = null, $notnull = null, $sequence = null, $default = null, $previous = null);
        $fields[] = new xmldb_field('informationformat', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, $nullable = null, $sequence = null, null, null);

        foreach ($fields as $field) {
            if ($dbman->field_exists($table, $field) === false) {
                $dbman->add_field($table, $field);
            }
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, $version, 'local', 'apsolu');
    }

    $version = 2022091600;
    if ($result && $oldversion < $version) {
        // Ajoute 1 champ `showpolicy` dans la table `apsolu_courses`.
        $table = new xmldb_table('apsolu_courses');

        $fields = array();
        $fields[] = new xmldb_field('showpolicy', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, $nullable = null, $sequence = null, null, null);

        foreach ($fields as $field) {
            if ($dbman->field_exists($table, $field) === false) {
                $dbman->add_field($table, $field);
            }
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, $version, 'local', 'apsolu');
    }

    $version = 2022111800;
    if ($result && $oldversion < $version) {
        // Nettoie la table `apsolu_payments_cards_cohort`.
        $sql = "DELETE FROM {apsolu_payments_cards_cohort} WHERE cohortid NOT IN (SELECT id FROM {cohort})";
        $DB->execute($sql);

        // Savepoint reached.
        upgrade_plugin_savepoint(true, $version, 'local', 'apsolu');
    }

    $version = 2022111801;
    if ($result && $oldversion < $version) {
        set_config('replytoaddresspreference', messaging::DISABLE_REPLYTO_ADDRESS, 'local_apsolu');
        set_config('defaultreplytoaddresspreference', messaging::USE_REPLYTO_ADDRESS, 'local_apsolu');

        // Savepoint reached.
        upgrade_plugin_savepoint(true, $version, 'local', 'apsolu');
    }

    $version = 2023012400;
    if ($result && $oldversion < $version) {
        // Ajoute la table apsolu_federation_activities.
        $table = new xmldb_table('apsolu_federation_activities');
        if ($dbman->table_exists($table) === false) {
            // Ajoute les champs.
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
            $table->add_field('name', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
            $table->add_field('mainsport', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = 0, null);
            $table->add_field('restriction', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = 0, null);
            $table->add_field('categoryid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, $nullable = null, $sequence = null, $default = null, null);

            // Ajoute la clé primaire.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

            // Ajoute les index.
            $table->add_index($indexname = 'idx_mainsport', XMLDB_INDEX_NOTUNIQUE, $fields = array('mainsport'));
            $table->add_index($indexname = 'idx_restriction', XMLDB_INDEX_NOTUNIQUE, $fields = array('restriction'));
            $table->add_index($indexname = 'idx_categoryid', XMLDB_INDEX_NOTUNIQUE, $fields = array('categoryid'));

            // Crée la table.
            $dbman->create_table($table);

            // Ajoute les données à la table.
            foreach (Activity::get_activity_data() as $data) {
                $sql = "INSERT INTO {apsolu_federation_activities} (id, name, mainsport, restriction, categoryid)".
                    " VALUES(:id, :name, :mainsport, :restriction, NULL)";
                $DB->execute($sql, $data);
            }
        }

        // Ajoute la table apsolu_federation_numbers.
        $table = new xmldb_table('apsolu_federation_numbers');
        if ($dbman->table_exists($table) === false) {
            // Ajoute les champs.
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
            $table->add_field('number', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
            $table->add_field('field', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
            $table->add_field('value', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
            $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = null, null);

            // Ajoute la clé primaire.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

            // Ajoute les index.
            $table->add_index($indexname = 'unique_number', XMLDB_INDEX_NOTUNIQUE, $fields = array('number'));
            $table->add_index($indexname = 'unique_sortorder', XMLDB_INDEX_NOTUNIQUE, $fields = array('sortorder'));

            // Crée la table.
            $dbman->create_table($table);
        }

        // Ajoute la table apsolu_federation_memberships.
        $table = new xmldb_table('apsolu_federation_adhesions');
        if ($dbman->table_exists($table) === false) {
            // Ajoute les champs.
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
            $table->add_field('sex', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
            $table->add_field('insurance', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = null, null);
            $table->add_field('birthday', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = null, null);
            $table->add_field('address1', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
            $table->add_field('address2', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, $nullable = null, null, null, null);
            $table->add_field('postalcode', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
            $table->add_field('city', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
            $table->add_field('phone', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, $nullable = null, null, null, null);
            $table->add_field('instagram', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, $nullable = null, null, null, null);
            $table->add_field('disciplineid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = null, null);
            $table->add_field('otherfederation', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, $nullable = null, null, null, null);
            $table->add_field('mainsport', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = null, null);
            $table->add_field('complementaryconstraintsport', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = null, null);
            $table->add_field('sportlicense', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = null, null);
            $table->add_field('managerlicense', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = null, null);
            $table->add_field('managerlicensetype', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = null, null);
            $table->add_field('refereelicense', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = null, null);
            $table->add_field('starlicense', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
            $table->add_field('usepersonaldata', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, $nullable = null, $sequence = null, $default = null, null);
            $table->add_field('sport1', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = null, null);
            $table->add_field('sport2', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = null, null);
            $table->add_field('sport3', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = null, null);
            $table->add_field('sport4', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = null, null);
            $table->add_field('sport5', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = null, null);
            $table->add_field('constraintsport1', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = null, null);
            $table->add_field('constraintsport2', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = null, null);
            $table->add_field('constraintsport3', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = null, null);
            $table->add_field('constraintsport4', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = null, null);
            $table->add_field('constraintsport5', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = null, null);
            $table->add_field('questionnairestatus', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, $nullable = null, $sequence = null, $default = null, null);
            $table->add_field('medicalcertificatedate', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, $nullable = null, null, null, null);
            $table->add_field('medicalcertificatestatus', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, $nullable = null, $sequence = null, $default = null, null);
            $table->add_field('federationnumberprefix', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, $nullable = null, $sequence = null, $default = null, null);
            $table->add_field('federationnumber', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, $nullable = null, $sequence = null, $default = null, null);
            $table->add_field('federationnumberrequestdate', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, $nullable = null, $sequence = null, $default = null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = 0, null);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = 0, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence = null, $default = null, null);

            // Ajoute la clé primaire.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

            // Ajoute les index.
            $table->add_index($indexname = 'unique_userid', XMLDB_INDEX_UNIQUE, $fields = array('userid'));
            $table->add_index($indexname = 'idx_mainsport', XMLDB_INDEX_NOTUNIQUE, $fields = array('mainsport'));

            // Crée la table.
            $dbman->create_table($table);
        }

        // Supprime la colonne federation sur la table apsolu_courses_categories.
        $table = new xmldb_table('apsolu_courses_categories');
        $field = new xmldb_field('federation', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, $nullable = null, $sequence = null, null, null);

        if ($dbman->field_exists($table, $field) === true) {
            $dbman->drop_field($table, $field);
        }

        // Ajoute les nouveaux paramètres pour le module FFSU.
        set_config('ffsu_acceptedfiles', '.pdf .odt .doc .docx .jpe .jpeg .jpg .png', 'local_apsolu');
        set_config('ffsu_maxfiles', 1, 'local_apsolu');

        set_config('insurance_field_default', '0', 'local_apsolu');
        set_config('managerlicense_field_default', '0', 'local_apsolu');
        set_config('managerlicensetype_field_default', '', 'local_apsolu');
        set_config('refereelicense_field_default', '0', 'local_apsolu');
        set_config('sportlicense_field_default', '1', 'local_apsolu');
        set_config('starlicense_field_default', '0', 'local_apsolu');

        set_config('instagram_field_visibility', Adhesion::FIELD_HIDDEN, 'local_apsolu');
        set_config('insurance_field_visibility', Adhesion::FIELD_HIDDEN, 'local_apsolu');
        set_config('managerlicense_field_visibility', Adhesion::FIELD_HIDDEN, 'local_apsolu');
        set_config('managerlicensetype_field_visibility', Adhesion::FIELD_HIDDEN, 'local_apsolu');
        set_config('otherfederation_field_visibility', Adhesion::FIELD_VISIBLE, 'local_apsolu');
        set_config('refereelicense_field_visibility', Adhesion::FIELD_HIDDEN, 'local_apsolu');
        set_config('sportlicense_field_visibility', Adhesion::FIELD_VISIBLE, 'local_apsolu');
        set_config('starlicense_field_visibility', Adhesion::FIELD_HIDDEN, 'local_apsolu');

        // Savepoint reached.
        upgrade_plugin_savepoint(true, $version, 'local', 'apsolu');
    }

    $version = 2023061400;
    if ($result && $oldversion < $version) {
        // Ajoute les champs sur la table apsolu_attendance_statuses.
        $statuses = $DB->get_records('apsolu_attendance_statuses');

        $table = new xmldb_table('apsolu_attendance_statuses');

        $fields = array();
        $fields[] = new xmldb_field('shortlabel', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
        $fields[] = new xmldb_field('longlabel', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
        $fields[] = new xmldb_field('sumlabel', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
        $fields[] = new xmldb_field('color', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
        $fields[] = new xmldb_field('sortorder', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, $default = 0, null);
        foreach ($fields as $field) {
            if ($dbman->field_exists($table, $field) === true) {
                continue;
            }

            $dbman->add_field($table, $field);
        }

        $fields = array();
        $fields[] = new xmldb_field('name', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
        $fields[] = new xmldb_field('code', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
        foreach ($fields as $field) {
            if ($dbman->field_exists($table, $field) === false) {
                continue;
            }

            $dbman->drop_field($table, $field);
        }

        // Réintègre les données dans la table apsolu_attendance_statuses.
        $sortorder = 1;
        foreach ($statuses as $status) {
            $data = array();
            $data['id'] = $status->id;
            $data['shortlabel'] = get_string(sprintf('%s_short', $status->code), 'local_apsolu');
            $data['longlabel'] = get_string($status->code, 'local_apsolu');
            $data['sumlabel'] = get_string(sprintf('%s_total', $status->code), 'local_apsolu');
            $data['color'] = get_string(sprintf('%s_style', $status->code), 'local_apsolu');
            $data['sortorder'] = $sortorder;

            $sql = "UPDATE {apsolu_attendance_statuses} SET shortlabel = :shortlabel, longlabel = :longlabel,".
                " sumlabel = :sumlabel, color = :color, sortorder = :sortorder WHERE id = :id";
            $DB->execute($sql, $data);

            $sortorder++;
        }

        $indexes = array('shortlabel', 'longlabel', 'sumlabel', 'sortorder');
        foreach ($indexes as $field) {
            $index = new xmldb_index(sprintf('unique_%s', $field), XMLDB_INDEX_UNIQUE, array($field));

            if ($dbman->index_exists($table, $index) === false) {
                $dbman->add_index($table, $index);
            }
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, $version, 'local', 'apsolu');
    }

    $version = 2023061401;
    if ($result && $oldversion < $version) {
        // Ajoute la table apsolu_roles.
        $table = new xmldb_table('apsolu_roles');
        if ($dbman->table_exists($table) === false) {
            // Ajoute les champs.
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
            $table->add_field('color', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
            $table->add_field('fontawesomeid', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);

            // Ajoute la clé primaire.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

            // Crée la table.
            $dbman->create_table($table);

            // Initialise le jeu de couleurs.
            $roles = array();
            $roles['bonification'] = '#ffb844';
            $roles['libre'] = '#99cc33';
            $roles['option'] = '#e60000';
            $roles['decouverte'] = '#888888';
            foreach ($roles as $shortname => $color) {
                $role = $DB->get_record('role', array('shortname' => $shortname, 'archetype' => 'student'));

                if ($role === false) {
                    continue;
                }

                $params = array();
                $params['id'] = $role->id;
                $params['color'] = $color;
                $params['fontawesomeid'] = 'check';

                $sql = "INSERT INTO {apsolu_roles} (id, color, fontawesomeid)
                             VALUES (:id, :color, :fontawesomeid)";
                $DB->execute($sql, $params);
            }
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, $version, 'local', 'apsolu');
    }

    $version = 2023061600;
    if ($result && $oldversion < $version) {
        $fields = array();
        $fields[] = 'apsolucardpaid';
        $fields[] = 'apsolufederationnumber';
        $fields[] = 'apsolufederationpaid';
        $fields[] = 'apsolumedicalcertificate';
        $fields[] = 'apsolumuscupaid';
        $fields[] = 'bonificationpaid';
        $fields[] = 'librepaid';
        $fields[] = 'optionpaid';

        foreach ($fields as $field) {
            $record = $DB->get_record('user_info_field', array('shortname' => $field));
            if ($record === false) {
                continue;
            }

            profile_delete_field($record->id);
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, $version, 'local', 'apsolu');
    }

    $version = 2023061900;
    if ($result && $oldversion < $version) {
        // Ajoute 1 champ `agreementaccepted` dans la table `apsolu_federation_adhesions`.
        $table = new xmldb_table('apsolu_federation_adhesions');
        $field = new xmldb_field('agreementaccepted', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, $nullable = null, $sequence = null, null, null);

        if ($dbman->field_exists($table, $field) === false) {
            $dbman->add_field($table, $field);
        }

        $text = get_string('default_federation_agreement', 'local_apsolu');
        set_config('ffsu_agreement', $text, 'local_apsolu');

        // Savepoint reached.
        upgrade_plugin_savepoint(true, $version, 'local', 'apsolu');
    }

    $version = 2023091300;
    if ($result && $oldversion < $version) {
        // Ajoute les nouveaux champs dans la table `apsolu_federation_adhesions`.
        $fields = array();
        $fields[] = new xmldb_field('birthname', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $fields[] = new xmldb_field('nativecountry', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $fields[] = new xmldb_field('departmentofbirth', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL,
            null, $default = 0, null);
        $fields[] = new xmldb_field('cityofbirth', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $fields[] = new xmldb_field('honorabilityagreement', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL,
            null, $default = 0, null);
        $fields[] = new xmldb_field('usepersonalimage', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, $nullable = null,
            null, $default = 0, null);

        $table = new xmldb_table('apsolu_federation_adhesions');
        foreach ($fields as $field) {
            if ($dbman->field_exists($table, $field) === false) {
                $dbman->add_field($table, $field);
            }
        }

        // Met à jour les activités FFSU.
        $federationcourse = new FederationCourse();
        $federationcourseid = $federationcourse->get_courseid();
        $federationgroups = array();
        if ($federationcourseid !== false) {
            $fields = 'name, id, courseid, timecreated, timemodified';
            $federationgroups = $DB->get_records('groups', array('courseid' => $federationcourseid), $sort = '', $fields);
        }

        $activities = $DB->get_records('apsolu_federation_activities');
        foreach (Activity::get_activity_data() as $data) {
            if (isset($activities[$data['id']]) !== false) {
                // Met à jour l'activité FFSU.
                $activity = $activities[$data['id']];

                if ($activity->name !== $data['name'] ||
                    $activity->mainsport != $data['mainsport'] ||
                    $activity->restriction != $data['restriction']) {
                    // Met à jour l'enregistrement dans la table apsolu_federation_activities.
                    $sql = "UPDATE {apsolu_federation_activities}
                               SET name = :name, mainsport = :mainsport, restriction = :restriction
                             WHERE id = :id";
                    $DB->execute($sql, $data);

                    // Met à jour le nom du groupe dans le cours FFSU.
                    if (isset($federationgroups[$activity->name]) === true) {
                        $federationgroups[$activity->name]->name = $data['name'];
                        $federationgroups[$activity->name]->timemodified = time();
                        $DB->update_record('groups', $federationgroups[$activity->name]);
                    }
                }
                continue;
            }

            // Insère une nouvelle activité FFSU.
            $sql = "INSERT INTO {apsolu_federation_activities} (id, name, mainsport, restriction, categoryid)
                                                        VALUES (:id, :name, :mainsport, :restriction, NULL)";
            $DB->execute($sql, $data);

            // Ajoute le groupe dans le cours FFSU.
            if ($federationcourseid !== false) {
                $group = new stdClass();
                $group->name = $data['name'];
                $group->courseid = $federationcourseid;
                $group->timecreated = time();
                $group->timemodified = $group->timecreated;
                groups_create_group($group);
            }
        }

        // Corrige les libellés des noms des créneaux horaires.
        $sql = "SELECT cc.id, cc.name".
            " FROM {course_categories} cc".
            " JOIN {apsolu_courses_categories} acc ON cc.id = acc.id".
            " ORDER BY cc.name";
        $categories = array();
        foreach ($DB->get_records_sql($sql) as $category) {
            $categories[$category->id] = $category->name;
        }

        $skills = array();
        foreach ($DB->get_records('apsolu_skills', $conditions = null, $sort = 'name') as $skill) {
            $skills[$skill->id] = $skill->name;
        }

        $courses = $DB->get_records('course');
        foreach (Course::get_records() as $course) {
            if (isset($courses[$course->id]) === false) {
                continue;
            }

            $data = new stdClass();
            $data->str_category = $categories[$courses[$course->id]->category];
            $data->str_skill = $skills[$course->skillid];

            $fullname = Course::get_fullname($data->str_category, $course->event, $course->weekday,
                $course->starttime, $course->endtime, $data->str_skill);
            $shortname = Course::get_shortname($course->id, $fullname);

            if ($courses[$course->id]->shortname === $shortname && $courses[$course->id]->fullname === $fullname) {
                continue;
            }

            $courses[$course->id]->shortname = $shortname;
            $courses[$course->id]->fullname = $fullname;
            $DB->update_record('course', $courses[$course->id]);
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, $version, 'local', 'apsolu');
    }

    return $result;
}
