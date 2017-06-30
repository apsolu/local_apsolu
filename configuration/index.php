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
 * @package    local_apsolu
 * @copyright  2017 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$page = optional_param('page', 'calendar', PARAM_ALPHA);

// Set tabs.
$pages = array('calendar');

$tabtree = array();
foreach ($pages as $pagename) {
    $url = new moodle_url('/local/apsolu/index.php', array('page' => $pagename));
    $tabtree[] = new tabobject($pagename, $url, get_string('settings_configuration_'.$pagename, 'local_apsolu'));
}

// Set default tabs.
if (in_array($page, $pages, true) === false) {
    $page = $pages[0];
}

// Setup admin access requirement.
admin_externalpage_setup('local_apsolu_configuration_'.$page);

require(__DIR__.'/calendar.php');
