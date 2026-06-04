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

    // Génère un champ de profil "Site" si il n'existe pas.
    require_once($CFG->dirroot . '/user/profile/definelib.php');
    require_once($CFG->dirroot . '/user/profile/lib.php');

    $category = $DB->get_record('user_info_field', ['shortname' => 'apsoluusertype'], $fields = '*', MUST_EXIST);

    $shortname = 'apsolusite';
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

    // Ajout de la table pour la gestion du paiement via Atouts Normandie.
    $table = new xmldb_table('apsolu_atouts_payments');

    if (!$dbman->table_exists($table)) {
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('paymentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('nocarte', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('amount', XMLDB_TYPE_NUMBER, '10, 2', null, XMLDB_NOTNULL, null, '0.00');
        $table->add_field('ticket', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('status', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('paymentid', XMLDB_KEY_FOREIGN, ['paymentid'], 'apsolu_payments', ['id']);

        $table->add_index($indexname = 'userid', XMLDB_INDEX_NOTUNIQUE, $fields = ['userid']);

        $dbman->create_table($table);
    }

    mtrace(get_string('success'));
} catch (Exception $exception) {
    mtrace(get_string('error'));
    mtrace($exception->getMessage());
}
