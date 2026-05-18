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

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');

try {
    $dbman = $DB->get_manager();

    // Supprime le champ "activityid" de la table "apsolu_attendance_sessions".
    $table = new xmldb_table('apsolu_attendance_sessions');

    $index = new xmldb_index('activityid', XMLDB_INDEX_NOTUNIQUE, ['activityid']);
    if ($dbman->index_exists($table, $index) === true) {
        $dbman->drop_index($table, $index);
    }

    $field = new xmldb_field('activityid');
    if ($dbman->field_exists($table, $field) === true) {
        $dbman->drop_field($table, $field);
    }

    // Ajoute un champ "duration" dans la table "apsolu_attendance_sessions".
    $table = new xmldb_table('apsolu_attendance_sessions');
    $field = new xmldb_field('duration', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, $sequence = null, 0, 'sessiontime');

    if ($dbman->field_exists($table, $field) === false) {
        $dbman->add_field($table, $field);

        // Initialise le champ "duration".
        foreach (\local_apsolu\core\attendancesession::get_records() as $session) {
            if (empty($session->duration) === false) {
                continue;
            }

            $session->duration = $session->get_duration();
            $session->save();
        }
    }

    mtrace(get_string('success'));
} catch (Exception $exception) {
    mtrace(get_string('error'));
    mtrace($exception->getMessage());
}
