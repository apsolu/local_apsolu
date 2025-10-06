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
 * Affiche l'offre de formations d'une activité.
 *
 * @package    local_apsolu
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use UniversiteRennes2\Apsolu;

require(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot.'/enrol/select/locallib.php');

require_course_login($SITE);

$activityid = optional_param('id', 0, PARAM_INT);
$activityname = optional_param('name', '', PARAM_TAG);

if (empty($activityid) === true && empty($activityname) === true) {
    throw new moodle_exception('invalidrecordunknown');
}

$activities = enrol_select_get_activities($siteid = 0, $activityid, $activityname);

if (count($activities) === 0) {
    throw new moodle_exception('invalidrecordunknown');
}

$PAGE->set_url('/local/apsolu/presentation/activity.php');

$title = current($activities)->sport;

$PAGE->set_context(context_system::instance());
$PAGE->set_title($title);
$PAGE->navbar->add(get_string('courses', 'local_apsolu'), new moodle_url('/local/apsolu/presentation/summary.php'));
$PAGE->navbar->add($title);

$options = [];
$options['widthFixed'] = true;
$options['widgets'] = ['stickyHeaders'];
$options['widgetOptions'] = ['stickyHeaders_filteredToTop' => true, 'stickyHeaders_offset' => '50px'];
$PAGE->requires->js_call_amd('local_apsolu/sort', 'initialise', [$options]);

echo $OUTPUT->header();
echo $OUTPUT->heading($title);

$roles = enrol_select_get_activities_roles();
$teachers = enrol_select_get_activities_teachers();

// Category, site, activity, period, jour, start, end, level, zone geo, zone, enroltype, enseignant.
$courses = [];
foreach ($activities as $activity) {
    if (isset($courses[$activity->sport]) === false) {
        $courses[$activity->sport] = new \stdClass();
        $courses[$activity->sport]->name = $activity->sport;
        $courses[$activity->sport]->url = $activity->url;
        $courses[$activity->sport]->description = nl2br($activity->description);
        $courses[$activity->sport]->courses = [];
    }

    $activity->weekday = get_string($activity->weekday, 'local_apsolu');

    $activity->roles = [];
    if (isset($roles[$activity->id]) === true) {
        $activity->roles = array_values($roles[$activity->id]);
    }

    $activity->teachers = [];
    if (isset($teachers[$activity->id]) === true) {
        $activity->teachers = array_values($teachers[$activity->id]);
    }

    $activity->area = $activity->site.' - '.$activity->area;

    $courses[$activity->sport]->courses[] = $activity;
}

ksort($courses);
$courses = array_values($courses);

$data = ['courses' => $courses];
echo $OUTPUT->render_from_template('local_apsolu/presentation_activity', $data);

echo $OUTPUT->footer();
