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

/**
 * Upgrade procedure.
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

    return $result;
}
