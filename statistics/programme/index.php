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
 * Interface for paybox payment.
 *
 * @package    local_apsolu
 * @copyright  2019 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$page = optional_param('page', 'dashboard', PARAM_ALPHA);

// Set tabs.
$pages = ['dashboard', 'reports', 'custom'];

$tabtree = [];
foreach ($pages as $pagename) {
    $url = new moodle_url('/local/apsolu/statistics/programme/index.php', ['page' => $pagename]);
    $tabtree[] = new tabobject($pagename, $url, get_string('statistics_' . $pagename, 'local_apsolu'));
}

// Set default tabs.
if (in_array($page, $pages, true) === false) {
    $page = $pages[0];
}

// Setup admin access requirement.
admin_externalpage_setup('local_apsolu_statistics_programme_' . $page);

$heading = sprintf('%s : %s', get_string('statistics', 'local_apsolu'), get_string('statistics_programme', 'local_apsolu'));
$PAGE->set_heading($heading);

require(__DIR__ . '/' . $page . '.php');
