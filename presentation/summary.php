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
 * Affiche l'offre de formations.
 *
 * @package    local_apsolu
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use UniversiteRennes2\Apsolu;

require __DIR__.'/../../../config.php';
require_once($CFG->dirroot.'/enrol/select/locallib.php');

$siteid = optional_param('siteid', 0, PARAM_INT);

$sites = $DB->get_records('apsolu_cities', $params = array(), $sort = 'name');
if (isset($sites[$siteid]) === true) {
    $sites[$siteid]->active = true;
} else {
    $siteid = 0;
}

$PAGE->set_url('/local/apsolu/presentation/summary.php');

$title = get_string('courses', 'local_apsolu');

$PAGE->set_context(context_system::instance());
$PAGE->set_title($title);
// $PAGE->set_heading($SITE->fullname);
$PAGE->navbar->add($title);

$PAGE->requires->css(new moodle_url($CFG->wwwroot.'/enrol/select/styles/select2.min.css'));
$PAGE->requires->js_call_amd('local_apsolu/presentation', 'initialise');

echo $OUTPUT->header();
echo $OUTPUT->heading($title);

$roles = UniversiteRennes2\Apsolu\get_activities_roles();
$teachers = UniversiteRennes2\Apsolu\get_activities_teachers();

$filters = array();

$filters['sites'] = new \stdClass();
$filters['sites']->label = 'Site de pratique';
$filters['sites']->values = array();

$filters['groupings'] = new \stdClass();
$filters['groupings']->label = get_string('grouping', 'local_apsolu');
$filters['groupings']->values = array();

$filters['sports'] = new \stdClass();
$filters['sports']->label = 'Activité';
$filters['sports']->values = array();

$filters['areas'] = new \stdClass();
$filters['areas']->label = 'Zone géographique';
$filters['areas']->values = array();

$filters['periods'] = new \stdClass();
$filters['periods']->label = 'Période de l\'année';
$filters['periods']->values = array('S1', 'S2');

$filters['times'] = new \stdClass();
$filters['times']->label = 'Période de la journée';
$filters['times']->values = array();
$filters['times']->values[] = '7h00-12h00';
$filters['times']->values[] = '12h00 - 13h30';
$filters['times']->values[] = '13h30 - 17h00';
$filters['times']->values[] = '17h00 - 22h00';

$filters['weekdays'] = new \stdClass();
$filters['weekdays']->label = 'Jour de la semaine';
$filters['weekdays']->values = array();
$filters['weekdays']->values[] = get_string('monday', 'calendar');
$filters['weekdays']->values[] = get_string('tuesday', 'calendar');
$filters['weekdays']->values[] = get_string('wednesday', 'calendar');
$filters['weekdays']->values[] = get_string('thursday', 'calendar');
$filters['weekdays']->values[] = get_string('friday', 'calendar');
$filters['weekdays']->values[] = get_string('saturday', 'calendar');
$filters['weekdays']->values[] = get_string('sunday', 'calendar');

$filters['locations'] = new \stdClass();
$filters['locations']->label = 'Lieu';
$filters['locations']->values = array();

$filters['skills'] = new \stdClass();
$filters['skills']->label = 'Niveau';
$filters['skills']->values = array();

$filters['roles'] = new \stdClass();
$filters['roles']->label = 'Type d\'inscription';
$filters['roles']->values = array();

$filters['teachers'] = new \stdClass();
$filters['teachers']->label = 'Enseignants';
$filters['teachers']->values = array();

// category, site, activity, period, jour, start, end, level, zone geo, zone, enroltype, enseignant
$courses = array();
foreach (UniversiteRennes2\Apsolu\get_activities($siteid) as $activity) {
    if (isset($courses[$activity->sport]) === false) {
        $courses[$activity->sport] = new \stdClass();
        $courses[$activity->sport]->id = $activity->sportid;
        $courses[$activity->sport]->name = $activity->sport;
        $courses[$activity->sport]->url = $activity->url;
        $courses[$activity->sport]->description = $activity->description;
        $courses[$activity->sport]->modal = (empty($activity->url) === false || empty($activity->description) === false);
        $courses[$activity->sport]->courses = array();

        $filters['groupings']->values[$activity->domainid] = $activity->domain;
    }

    $activity->weekday = get_string($activity->weekday, 'calendar');

    $activity->roles = array();
    if (isset($roles[$activity->id]) === true) {
        $activity->roles = array_values($roles[$activity->id]);
    }

    $activity->teachers = array();
    if (isset($teachers[$activity->id]) === true) {
        $activity->teachers = array_values($teachers[$activity->id]);
    }

    // Times.
    $time = array();
    if ($activity->endtime < '12:00') {
        // Matin.
        $time[] = $filters['times']->values[0];
    }

    if ($activity->starttime >= '12:00' && $activity->starttime < '13:30') {
        // Midi.
        $time[] = $filters['times']->values[1];
    }

    if ($activity->starttime >= '13:30' && $activity->starttime < '17:00') {
        // Après-midi.
        $time[] = $filters['times']->values[2];
    }

    if ($activity->starttime >= '17:00') {
        // Soir.
        $time[] = $filters['times']->values[3];
    }

    $activity->time = implode(' ', $time);

    if ($siteid === 0) {
        $activity->area = $activity->site.' - '.$activity->area;
    }

    $courses[$activity->sport]->courses[] = $activity;


    // Filtres.
    foreach (array('area', 'location', 'skill', 'site', 'sport') as $type) {
        if (in_array($activity->{$type}, $filters[$type.'s']->values, $strict = true) === false) {
            $filters[$type.'s']->values[] = $activity->{$type};
        }
    }

    foreach ($activity->roles as $role) {
        if (isset($filters['roles']->values[$role->id]) === false) {
            $filters['roles']->values[$role->id] = $role->name;
        }
    }

    foreach ($activity->teachers as $teacher) {
        if (isset($filters['teachers']->values[$teacher->lastname.' '.$teacher->firstname]) === false) {
            $filters['teachers']->values[$teacher->lastname.' '.$teacher->firstname] = $teacher->firstname.' '.$teacher->lastname;
        }
    }
}

ksort($courses);
$courses = array_values($courses);

$filters['roles']->values = array_values($filters['roles']->values);
ksort($filters['teachers']->values);
$filters['teachers']->values = array_values($filters['teachers']->values);
unset($filters['teachers'], $filters['roles'], $filters['locations'], $filters['sites'], $filters['skills']);
foreach ($filters as $name => $filter) {
    if (in_array($name, array('periods', 'weekdays', 'teachers', 'times'), $strict = true) === false) {
        sort($filter->values);
    }

    $filter->html = \html_writer::select($filter->values, $name, $selected = '', $nothing = false, $attributes = array('class' => 'apsolu-enrol-selects', 'multiple' => 'multiple'));
}

$data = array();
$data['wwwroot'] = $CFG->wwwroot;
$data['courses'] = $courses;
if (isset($sites[$siteid]->name) === true) {
    $data['selected_site'] = $sites[$siteid]->name;
} else {
    $data['selected_site'] = '';
}
$data['sites'] = array_values($sites);
$data['sites_single'] = (count($data['sites']) < 2);
$data['filters'] = array_values($filters);
$data['is_siuaps_rennes'] = isset($CFG->is_siuaps_rennes);

echo $OUTPUT->render_from_template('local_apsolu/presentation_summary', $data);

echo $OUTPUT->footer();
