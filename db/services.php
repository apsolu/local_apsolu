<?php
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
 * Web service local plugin template external functions and service definitions.
 *
 * @package    local_apsolu
 * @copyright  2017 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

// We defined the web service functions to install.
$functions = array(
    // Function get_users().
    'local_apsolu_get_users' => array(
        'classname'   => 'local_apsolu_webservices',
        'methodname'  => 'get_users',
        'classpath'   => 'local/apsolu/externallib.php',
        'description' => get_string('ws_local_apsolu_get_users_description', 'local_apsolu'),
        'type'        => 'read',
        'loginrequired' => true,
        'ajax' => true,
    ),
    // Function get_activities().
    'local_apsolu_get_activities' => array(
        'classname'   => 'local_apsolu_webservices',
        'methodname'  => 'get_activities',
        'classpath'   => 'local/apsolu/externallib.php',
        'description' => get_string('ws_local_apsolu_get_activities_description', 'local_apsolu'),
        'type'        => 'read',
        'loginrequired' => true,
        'ajax' => true,
    ),
    // Function get_courses().
    'local_apsolu_get_courses' => array(
        'classname'   => 'local_apsolu_webservices',
        'methodname'  => 'get_courses',
        'classpath'   => 'local/apsolu/externallib.php',
        'description' => get_string('ws_local_apsolu_get_courses_description', 'local_apsolu'),
        'type'        => 'read',
        'loginrequired' => true,
        'ajax' => true,
    ),
    // Function get_registrations().
    'local_apsolu_get_registrations' => array(
        'classname'   => 'local_apsolu_webservices',
        'methodname'  => 'get_registrations',
        'classpath'   => 'local/apsolu/externallib.php',
        'description' => get_string('ws_local_apsolu_get_registrations_description', 'local_apsolu'),
        'type'        => 'read',
        'loginrequired' => true,
        'ajax' => true,
    ),
    // Function get_unenrolments().
    'local_apsolu_get_unenrolments' => array(
        'classname'   => 'local_apsolu_webservices',
        'methodname'  => 'get_unenrolments',
        'classpath'   => 'local/apsolu/externallib.php',
        'description' => get_string('ws_local_apsolu_get_unenrolments_description', 'local_apsolu'),
        'type'        => 'read',
        'loginrequired' => true,
        'ajax' => true,
    ),
    // Function get_teachers().
    'local_apsolu_get_teachers' => array(
        'classname'   => 'local_apsolu_webservices',
        'methodname'  => 'get_teachers',
        'classpath'   => 'local/apsolu/externallib.php',
        'description' => get_string('ws_local_apsolu_get_teachers_description', 'local_apsolu'),
        'type'        => 'read',
        'loginrequired' => true,
        'ajax' => true,
    ),
    // Function get_attendances().
    'local_apsolu_get_attendances' => array(
        'classname'   => 'local_apsolu_webservices',
        'methodname'  => 'get_attendances',
        'classpath'   => 'local/apsolu/externallib.php',
        'description' => get_string('ws_local_apsolu_get_attendances_description', 'local_apsolu'),
        'type'        => 'read',
        'loginrequired' => true,
        'ajax' => true,
    ),
    'local_apsolu_set_card' => array(
        'classname'   => 'local_apsolu_webservices',
        'methodname'  => 'set_card',
        'classpath'   => 'local/apsolu/externallib.php',
        'description' => get_string('ws_local_apsolu_set_card_description', 'local_apsolu'),
        'type'        => 'write',
        'loginrequired' => true,
        'ajax' => true,
    ),
    'local_apsolu_set_presence' => array(
        'classname'   => 'local_apsolu_webservices',
        'methodname'  => 'set_presence',
        'classpath'   => 'local/apsolu/externallib.php',
        'description' => get_string('ws_local_apsolu_set_presence_description', 'local_apsolu'),
        'type'        => 'write',
        'loginrequired' => true,
        'ajax' => true,
    ),
);

// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = array(
    'apsolu' => array(
        'functions' => array(
            'local_apsolu_get_users',
            'local_apsolu_get_activities',
            'local_apsolu_get_courses',
            'local_apsolu_get_registrations',
            'local_apsolu_get_unenrolments',
            'local_apsolu_get_teachers',
            'local_apsolu_get_attendances',
            'local_apsolu_set_card',
            'local_apsolu_set_presence',
            ),
        'restrictedusers' => 1,
        'enabled' => 0,
    ),
);
