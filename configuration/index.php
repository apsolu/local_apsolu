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
 * Contrôleur pour les pages d'administration de la configuration générale.
 *
 * @package    local_apsolu
 * @copyright  2017 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$page = optional_param('page', 'calendars', PARAM_ALPHA);

// Set tabs.
$pages = [];
$pages['calendars'] = 'calendars';
$pages['calendarstypes'] = 'calendars_types';
$pages['courseofferings'] = 'course_offerings';
$pages['dates'] = 'dates';
$pages['headermessage'] = 'header_message';
$pages['messaging'] = 'messaging';
$pages['userprofile'] = 'user_profile';
$pages['attendancestatuses'] = 'attendance_statuses';
$pages['roles'] = 'roles';
$pages['specialcourses'] = 'special_courses';

$tabtree = [];
foreach ($pages as $pagename => $name) {
    $url = new moodle_url('/local/apsolu/index.php', ['page' => $pagename]);
    $tabtree[] = new tabobject($name, $url, get_string($name, 'local_apsolu'));
}

// Set default tabs.
if (isset($pages[$page]) === false) {
    $page = $pages['calendars'];
}

// Setup admin access requirement.
admin_externalpage_setup('local_apsolu_configuration_' . $pages[$page]);

require(__DIR__ . '/' . $pages[$page] . '.php');
