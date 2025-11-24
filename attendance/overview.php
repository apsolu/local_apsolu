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
 * Redirige vers la nouvelle page de vue d'ensemble des présences.
 *
 * @package    local_apsolu
 * @copyright  2025 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:disable moodle.Files.RequireLogin.Missing

// UNDO: Supprimer ce fichier pour Moodle 5.2.x.
require(__DIR__ . '/../../../config.php');

$courseid = required_param('courseid', PARAM_INT);

$redirection = new moodle_url('/local/apsolu/attendance/index.php', ['page' => 'overview', 'courseid' => $courseid]);

header('Location: ' . $redirection->out($escape = false), $replace = true, $httpresponsecode = 301);
exit();
