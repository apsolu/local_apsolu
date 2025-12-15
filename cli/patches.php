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

    mtrace(get_string('success'));
} catch (Exception $exception) {
    mtrace(get_string('error'));
    mtrace($exception->getMessage());
}
