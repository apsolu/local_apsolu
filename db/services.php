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
 * Web service local plugin template external functions and service definitions.
 *
 * @package    local_apsolu
 * @copyright  2017 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// We defined the web service functions to install.
$functions = [
    // Fonction get_users().
    'local_apsolu_get_users' => [
        'classname'   => 'local_apsolu_webservices',
        'methodname'  => 'get_users',
        'classpath'   => 'local/apsolu/externallib.php',
        'description' => get_string('ws_local_apsolu_get_users_description', 'local_apsolu'),
        'type'        => 'read',
        'loginrequired' => true,
        'ajax' => true,
    ],
    // Fonction get_activities().
    'local_apsolu_get_activities' => [
        'classname'   => 'local_apsolu_webservices',
        'methodname'  => 'get_activities',
        'classpath'   => 'local/apsolu/externallib.php',
        'description' => get_string('ws_local_apsolu_get_activities_description', 'local_apsolu'),
        'type'        => 'read',
        'loginrequired' => true,
        'ajax' => true,
    ],
    // Fonction get_courses().
    'local_apsolu_get_courses' => [
        'classname'   => 'local_apsolu_webservices',
        'methodname'  => 'get_courses',
        'classpath'   => 'local/apsolu/externallib.php',
        'description' => get_string('ws_local_apsolu_get_courses_description', 'local_apsolu'),
        'type'        => 'read',
        'loginrequired' => true,
        'ajax' => true,
    ],
    // Fonction get_courses_list().
    'local_apsolu_get_courses_list' => [
        'classname'   => 'local_apsolu_webservices',
        'methodname'  => 'get_courses_list',
        'classpath'   => 'local/apsolu/externallib.php',
        'description' => get_string('ws_local_apsolu_get_courses_description', 'local_apsolu'),
        'type'        => 'read',
        'loginrequired' => false,
        'ajax' => true,
    ],
    // Fonction get_groupings().
    'local_apsolu_get_groupings' => [
        'classname'   => 'local_apsolu_webservices',
        'methodname'  => 'get_groupings',
        'classpath'   => 'local/apsolu/externallib.php',
        'description' => get_string('ws_local_apsolu_get_groupings_description', 'local_apsolu'),
        'type'        => 'read',
        'loginrequired' => false,
        'ajax' => true,
    ],
    // Fonction get_registrations().
    'local_apsolu_get_registrations' => [
        'classname'   => 'local_apsolu_webservices',
        'methodname'  => 'get_registrations',
        'classpath'   => 'local/apsolu/externallib.php',
        'description' => get_string('ws_local_apsolu_get_registrations_description', 'local_apsolu'),
        'type'        => 'read',
        'loginrequired' => true,
        'ajax' => true,
    ],
    // Fonction get_sports().
    'local_apsolu_get_sports' => [
        'classname'     => 'local_apsolu_webservices',
        'methodname'    => 'get_sports',
        'classpath'     => 'local/apsolu/externallib.php',
        'description'   => get_string('ws_local_apsolu_get_sports_description', 'local_apsolu'),
        'type'          => 'read',
        'loginrequired' => false,
        'ajax'          => true,
    ],
    // Fonction get_unenrolments().
    'local_apsolu_get_unenrolments' => [
        'classname'   => 'local_apsolu_webservices',
        'methodname'  => 'get_unenrolments',
        'classpath'   => 'local/apsolu/externallib.php',
        'description' => get_string('ws_local_apsolu_get_unenrolments_description', 'local_apsolu'),
        'type'        => 'read',
        'loginrequired' => true,
        'ajax' => true,
    ],
    // Fonction get_teachers().
    'local_apsolu_get_teachers' => [
        'classname'   => 'local_apsolu_webservices',
        'methodname'  => 'get_teachers',
        'classpath'   => 'local/apsolu/externallib.php',
        'description' => get_string('ws_local_apsolu_get_teachers_description', 'local_apsolu'),
        'type'        => 'read',
        'loginrequired' => true,
        'ajax' => true,
    ],
    // Fonction get_attendances().
    'local_apsolu_get_attendances' => [
        'classname'   => 'local_apsolu_webservices',
        'methodname'  => 'get_attendances',
        'classpath'   => 'local/apsolu/externallib.php',
        'description' => get_string('ws_local_apsolu_get_attendances_description', 'local_apsolu'),
        'type'        => 'read',
        'loginrequired' => true,
        'ajax' => true,
    ],
    // Fonction set_card().
    'local_apsolu_set_card' => [
        'classname'   => 'local_apsolu_webservices',
        'methodname'  => 'set_card',
        'classpath'   => 'local/apsolu/externallib.php',
        'description' => get_string('ws_local_apsolu_set_card_description', 'local_apsolu'),
        'type'        => 'write',
        'loginrequired' => true,
        'ajax' => true,
    ],
    // Fonction set_course().
    'local_apsolu_set_course' => [
        'classname' => 'local_apsolu\external\course',
        'methodname' => 'set_course',
        'description' => get_string('ws_local_apsolu_set_course_description', 'local_apsolu'),
        'type'        => 'write',
        'loginrequired' => true,
        'ajax' => true,
    ],
    // Fonction set_presence().
    'local_apsolu_set_presence' => [
        'classname'   => 'local_apsolu_webservices',
        'methodname'  => 'set_presence',
        'classpath'   => 'local/apsolu/externallib.php',
        'description' => get_string('ws_local_apsolu_set_presence_description', 'local_apsolu'),
        'type'        => 'write',
        'loginrequired' => true,
        'ajax' => true,
    ],
    // Fonction debugging().
    'local_apsolu_debugging' => [
        'classname'   => 'local_apsolu_webservices',
        'methodname'  => 'debugging',
        'classpath'   => 'local/apsolu/externallib.php',
        'description' => get_string('ws_local_apsolu_debugging_description', 'local_apsolu'),
        'type'        => 'write',
        'loginrequired' => true,
        'ajax' => true,
    ],
    // Fonction get_chartdataset().
    'local_apsolu_get_chartdataset' => [
        'classname'   => 'local_apsolu_webservices',
        'methodname'  => 'get_chartdataset',
        'classpath'   => 'local/apsolu/externallib.php',
        'description' => get_string('ws_local_apsolu_get_chartdataset_description', 'local_apsolu'),
        'type'        => 'read',
        'loginrequired' => true,
        'ajax' => true,
    ],
    // Fonction get_reportdataset().
    'local_apsolu_get_reportdataset' => [
        'classname'   => 'local_apsolu_webservices',
        'methodname'  => 'get_reportdataset',
        'classpath'   => 'local/apsolu/externallib.php',
        'description' => get_string('ws_local_apsolu_get_reportdataset_description', 'local_apsolu'),
        'type'        => 'read',
        'loginrequired' => true,
        'ajax' => true,
    ],
    // Fonction get_reportfilters().
    'local_apsolu_get_reportfilters' => [
        'classname'   => 'local_apsolu_webservices',
        'methodname'  => 'get_reportfilters',
        'classpath'   => 'local/apsolu/externallib.php',
        'description' => get_string('ws_local_apsolu_get_reportfilters_description', 'local_apsolu'),
        'type'        => 'read',
        'loginrequired' => true,
        'ajax' => true,
    ],
    'core_message_send_instant_emails' => [
        'classname' => 'local_apsolu\external\email',
        'methodname' => 'send_instant_emails',
        'description' => 'Send instant emails',
        'type' => 'write',
        'capabilities' => 'moodle/site:sendmessage',
        'ajax' => true,
    ],
    'local_apsolu_get_relevant_municipalities' => [
        'classname' => 'local_apsolu\external\municipality',
        'methodname' => 'get_relevant_municipalities',
        'description' => 'Gets relevant municipalities for a search request.',
        'type' => 'read',
        'ajax' => true,
    ],
];

// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = [
    'apsolu' => [
        'functions' => [
            'local_apsolu_get_users',
            'local_apsolu_get_activities',
            'local_apsolu_get_courses',
            'local_apsolu_get_registrations',
            'local_apsolu_get_unenrolments',
            'local_apsolu_get_teachers',
            'local_apsolu_get_attendances',
            'local_apsolu_set_card',
            'local_apsolu_set_course',
            'local_apsolu_set_presence',
            'local_apsolu_debugging',
            'local_apsolu_get_chartdataset',
            'local_apsolu_get_reportdataset',
            'local_apsolu_get_reportfilters',
            ],
        'restrictedusers' => 1,
        'enabled' => 0,
        'shortname' => 'apsolu',
        'downloadfiles' => 0,
        'uploadfiles' => 0,
    ],
    'apsolu_public' => [
        'functions' => [
            'local_apsolu_get_courses_list',
            'local_apsolu_get_groupings',
            'local_apsolu_get_sports',
            ],
        'restrictedusers' => 1,
        'enabled' => 0,
        'shortname' => 'apsolu_public',
        'downloadfiles' => 0,
        'uploadfiles' => 0,
    ],
];
