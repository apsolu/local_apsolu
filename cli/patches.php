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

use local_apsolu\core\attendancesession;
use local_apsolu\core\course;

require(__DIR__ . '/../../../config.php');

try {
    $dbman = $DB->get_manager();

    // Ajoute un champ "manual" à la table "apsolu_attendance_sessions".
    $table = new xmldb_table('apsolu_attendance_sessions');

    $nullable = null;
    $sequence = null;
    $default = 0;
    $previous = 'duration';
    $field = new xmldb_field('manual', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, $nullable, $sequence, $default, $previous);

    if ($dbman->field_exists($table, $field) === false) {
        $dbman->add_field($table, $field);
    }

    // Initialise la colonne "manual" pour les sessions qui n'ont pas été créée automatiquement.
    foreach ($DB->get_records('apsolu_attendance_sessions') as $session) {
        if (str_starts_with($session->name, 'Session du ') === false && preg_match('/^Cours n°[0-9]+$/', $session->name) !== 1) {
            continue;
        }

        $session->manual = 1;
        $DB->update_record('apsolu_attendance_sessions', $session);
    }

    // Renomme le nom des sessions des cours.
    $courseid = null;

    $courses = Course::get_records();
    foreach (Attendancesession::get_records($conditions = null, $sort = 'courseid, sessiontime') as $session) {
        if (isset($courses[$session->courseid]) === false) {
            continue;
        }

        if ($courseid !== $session->courseid) {
            // Initialise le compteur de sessions du cours.
            $count = 0;
            $courseid = $session->courseid;
        }

        if (empty($session->duration) === true) {
            // La session n'a pas une durée valide.
            continue;
        }

        if (empty($session->manual) === false) {
            // La session a été créée manuellement.
            continue;
        }

        $count++;
        $oldname = $session->name;

        $session->set_name($count, $courses[$session->courseid]);
        if ($oldname === $session->name) {
            continue;
        }

        $session->save();
    }

    mtrace(get_string('success'));
} catch (Exception $exception) {
    mtrace(get_string('error'));
    mtrace($exception->getMessage());
}
