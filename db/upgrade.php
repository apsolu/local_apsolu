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

    if ($result && $oldversion < 2016082400) {
        $cachedir = $CFG->dataroot.'/apsolu/local_apsolu/cache/homepage';

        $result = mkdir($cachedir, $CFG->directorypermissions, $recursive = true);
    }

    $version = 2017072100;
    if ($result && $oldversion < $version) {
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

        // Savepoint reached.
        upgrade_plugin_savepoint(true, $version, 'local', 'apsolu');
    }

    $version = 2017082201;
    if ($result && $oldversion < $version) {
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

    return $result;
}
