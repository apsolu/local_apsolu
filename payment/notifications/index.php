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
 * Contrôleur pour les pages d'administration du paiement.
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$actions = ['compose', 'history', 'posts', 'export'];

if (in_array($action, $actions, $strict = true) === false) {
    $action = 'compose';
}

// Création d'un sous-menu.
$tabsbar2 = [];
foreach ($actions as $key) {
    if ($key === 'posts') {
        continue;
    }

    $url = new moodle_url('/local/apsolu/payment/admin.php', ['tab' => 'notifications', 'action' => $key]);
    $tabsbar2[] = new tabobject($key, $url, get_string($key, 'local_apsolu'));
}

// Affichage du sous-menu.
$submenu = $OUTPUT->heading(get_string('dunning', 'local_apsolu'));
$submenu .= $OUTPUT->tabtree($tabsbar2, $action);

if ($action !== 'export') {
    echo $submenu;
}

require(__DIR__ . '/' . $action . '.php');
