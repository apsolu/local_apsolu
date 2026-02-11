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

namespace local_apsolu\external;

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/externallib.php');

/**
 * Webservice gérer les cours.
 *
 * @package   local_apsolu
 * @copyright 2026 Université Rennes 2
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course extends external_api {
    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function set_course_parameters() {
        return new external_function_parameters(
            new external_single_structure([
                // Elements descriptifs d'un cours.
                'courseid' => new external_value(PARAM_INT, 'course id', VALUE_OPTIONAL),
                'event' => new external_value(PARAM_TEXT, 'event', VALUE_OPTIONAL),
                'idnumber' => new external_value(PARAM_RAW, 'id number', VALUE_OPTIONAL),
                'weekday' => new external_value(PARAM_TEXT, 'weekday'),
                'starttime' => new external_value(PARAM_TEXT, 'start time'),
                'endtime' => new external_value(PARAM_TEXT, 'end time'),
                'license' => new external_value(PARAM_BOOL, 'license', VALUE_OPTIONAL),
                'on_homepage' => new external_value(PARAM_BOOL, 'course on homepage', VALUE_OPTIONAL),
                'showpolicy' => new external_value(PARAM_BOOL, 'show policy', VALUE_OPTIONAL),
                'information' => new external_value(PARAM_TEXT, 'information', VALUE_OPTIONAL),
                // Elements descriptifs d'une activité.
                'activityid' => new external_value(PARAM_INT, 'activity id', VALUE_OPTIONAL),
                'activity' => new external_value(PARAM_TEXT, 'activity name', VALUE_OPTIONAL),
                // Elements descriptifs d'un groupement d'activités.
                'groupingid' => new external_value(PARAM_INT, 'grouping id', VALUE_OPTIONAL),
                'grouping' => new external_value(PARAM_TEXT, 'grouping name', VALUE_OPTIONAL),
                // Elements descriptifs d'un niveau de pratique.
                'skillid' => new external_value(PARAM_INT, 'skill id', VALUE_OPTIONAL),
                'skill' => new external_value(PARAM_TEXT, 'skill name', VALUE_OPTIONAL),
                // Elements descriptifs d'un gestionnaire de lieu.
                'managerid' => new external_value(PARAM_INT, 'manager id', VALUE_OPTIONAL),
                'manager' => new external_value(PARAM_TEXT, 'manager name', VALUE_OPTIONAL),
                // Elements descriptifs d'un site.
                'siteid' => new external_value(PARAM_INT, 'site id', VALUE_OPTIONAL),
                'site' => new external_value(PARAM_TEXT, 'site name', VALUE_OPTIONAL),
                // Elements descriptifs d'une zone géographique.
                'areaid' => new external_value(PARAM_INT, 'area id', VALUE_OPTIONAL),
                'area' => new external_value(PARAM_TEXT, 'area name', VALUE_OPTIONAL),
                // Elements descriptifs d'un lieu.
                'locationid' => new external_value(PARAM_INT, 'location id', VALUE_OPTIONAL),
                'location' => new external_value(PARAM_TEXT, 'location name', VALUE_OPTIONAL),
                // Elements descriptifs d'une période.
                'periodid' => new external_value(PARAM_INT, 'period id', VALUE_OPTIONAL),
                'period' => new external_value(PARAM_TEXT, 'period name', VALUE_OPTIONAL),
                'generic_name' => new external_value(PARAM_TEXT, 'generic name', VALUE_OPTIONAL),
                'weeks' => new external_value(PARAM_RAW, 'weeks', VALUE_OPTIONAL),
            ]),
            'course to create'
        );
    }

    /**
     * Create course.
     *
     * @param stdClass $data
     * @return stdClass course (id and shortname only)
     */
    public static function set_course(stdClass $data): stdClass {
        // TODO: implémenter la création ou la mise à jour d'un créneau horaire.
        throw new moodle_exception('cannotimportformat', 'error');
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function set_course_returns() {
        return new external_single_structure([
            'id'       => new external_value(PARAM_INT, 'course id'),
            'shortname' => new external_value(PARAM_RAW, 'short name'),
            ]);
    }
}
