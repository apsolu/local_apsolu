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
 * Tasks for component 'local_apsolu'
 *
 * @package    local_apsolu
 * @copyright  2017 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        // Tâche exécutée toutes les 15 minutes.
        'classname' => 'local_apsolu\task\set_high_level_athletes',
        'blocking' => 0,
        'minute' => '*/15',
        'hour' => '*',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*',
    ],
    [
        // Tâche exécutée toutes les heures.
        'classname' => 'local_apsolu\task\send_dunnings',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '*',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*',
    ],
    [
        // Tâche exécutée toutes les heures.
        'classname' => 'local_apsolu\task\grant_ws_access',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '*',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*',
    ],
    [
        // Tâche exécutée une fois par jour.
        'classname' => 'local_apsolu\task\follow_up_incomplete_federation_adhesions',
        'blocking' => 0,
        'minute' => '30',
        'hour' => '7',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*',
    ],
    [
        // Tâche exécutée toutes les heures.
        'classname' => 'local_apsolu\task\notify_new_federation_adhesions',
        'blocking' => 0,
        'minute' => '15',
        'hour' => '*',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*',
    ],
];
