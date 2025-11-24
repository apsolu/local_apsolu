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

    // Initialise la variable display_fields.
    $value = get_config('local_apsolu', 'display_fields');
    if ($value === false) {
        set_config('display_fields', '["email","institution","department"]', 'local_apsolu');
    }

    // Initialise la variable export_fields.
    $value = get_config('local_apsolu', 'export_fields');
    if ($value === false) {
        set_config('export_fields', '["email","institution","department"]', 'local_apsolu');
    }

    unset_config('parental_authorization_enabled', 'local_apsolu');

    // Ajoute un champ à la table apsolu_attendance_presences.
    $table = new xmldb_table('apsolu_attendance_presences');
    $field = new xmldb_field('fingerprint', XMLDB_TYPE_CHAR, '255', null, null, null, null, $previous = 'description');

    if ($dbman->field_exists($table, $field) === false) {
        $dbman->add_field($table, $field);
    }

    // Ajoute une table apsolu_attendance_qrcodes.
    $table = new xmldb_table('apsolu_attendance_qrcodes');
    if ($dbman->table_exists($table) === false) {
        // Ajoute les champs.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('keycode', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'id');
        $table->add_field('settings', XMLDB_TYPE_TEXT, null, null, null, null, null, 'keycode');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'timecreated');
        $table->add_field('sessionid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'settings');

        // Ajoute les clés.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Ajoute les index.
        $table->add_index($indexname = 'keycode', XMLDB_INDEX_UNIQUE, $fields = ['keycode']);
        $table->add_index($indexname = 'sessionid', XMLDB_INDEX_UNIQUE, $fields = ['sessionid']);

        // Génère la table.
        $dbman->create_table($table);
    }

    // Initialise les variables liées à la prise de présences.
    $qrcodedefaultsettings = [];
    $qrcodedefaultsettings['qrcode_enabled'] = 0;
    $qrcodedefaultsettings['qrcode_starttime'] = 15 * 60;
    $qrcodedefaultsettings['qrcode_presentstatus'] = 1;
    $qrcodedefaultsettings['qrcode_latetime'] = 15 * 60;
    $qrcodedefaultsettings['qrcode_latestatus'] = 2;
    $qrcodedefaultsettings['qrcode_endtime'] = 30 * 60;
    $qrcodedefaultsettings['qrcode_automark'] = 1;
    $qrcodedefaultsettings['qrcode_automarkstatus'] = 4;
    $qrcodedefaultsettings['qrcode_allowguests'] = 0;
    $qrcodedefaultsettings['qrcode_autologout'] = 1;
    $qrcodedefaultsettings['qrcode_rotate'] = 0;
    foreach ($qrcodedefaultsettings as $key => $value) {
        $config = get_config('local_apsolu', $key);

        if ($config !== false) {
            // Déjà défini.
            continue;
        }

        set_config($key, $value, 'local_apsolu');
    }

    mtrace(get_string('success'));
} catch (Exception $exception) {
    mtrace(get_string('error'));
    mtrace($exception->getMessage());
}
