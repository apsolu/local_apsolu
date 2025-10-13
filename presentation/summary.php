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

require(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/enrol/select/locallib.php');

require_course_login($SITE);

$cityid = optional_param('siteid', 0, PARAM_INT); // Garder pour rétro-compatibilité.
if (empty($cityid) === true) {
    $cityid = optional_param('cityid', 0, PARAM_INT);
}

$cities = $DB->get_records('apsolu_cities', $params = [], $sort = 'name');
if (isset($cities[$cityid]) === true) {
    $cities[$cityid]->active = true;
} else {
    $cityid = 0;
}

$PAGE->set_url('/local/apsolu/presentation/summary.php');

$title = get_string('course_offerings', 'local_apsolu');

$PAGE->set_context(context_system::instance());
$PAGE->set_title($title);
$PAGE->navbar->add($title);

$PAGE->requires->css('/enrol/select/styles/select2.min.css');

$options = [];
$options['widthFixed'] = true;
$options['widgets'] = ['stickyHeaders'];
$options['widgetOptions'] = ['stickyHeaders_filteredToTop' => true, 'stickyHeaders_offset' => '50px'];
$PAGE->requires->js_call_amd('local_apsolu/sort', 'initialise', [$options]);

$PAGE->requires->js_call_amd('local_apsolu/presentation', 'initialise');

echo $OUTPUT->header();
echo $OUTPUT->heading($title);

$roles = enrol_select_get_activities_roles();
$teachers = enrol_select_get_activities_teachers();

$filters = [];

$filters['city'] = new \stdClass();
$filters['city']->label = get_string('city', 'local_apsolu');
$filters['city']->values = [];

$filters['grouping'] = new \stdClass();
$filters['grouping']->label = get_string('grouping', 'local_apsolu');
$filters['grouping']->values = [];

$filters['category'] = new \stdClass();
$filters['category']->label = get_string('activity', 'local_apsolu');
$filters['category']->values = [];

$filters['area'] = new \stdClass();
$filters['area']->label = get_string('area', 'local_apsolu');
$filters['area']->values = [];

$filters['period'] = new \stdClass();
$filters['period']->label = get_string('period', 'local_apsolu');
$filters['period']->values = [];

$filters['times'] = new \stdClass();
$filters['times']->label = get_string('time_of_day', 'local_apsolu');
$filters['times']->values = [];
$filters['times']->values[] = get_string('morning', 'local_apsolu');
$filters['times']->values[] = get_string('midday', 'local_apsolu');
$filters['times']->values[] = get_string('afternoon', 'local_apsolu');
$filters['times']->values[] = get_string('evening', 'local_apsolu');

$filters['weekday'] = new \stdClass();
$filters['weekday']->label = get_string('day_of_the_week', 'local_apsolu');
$filters['weekday']->values = [];
$filters['weekday']->values[] = get_string('monday', 'local_apsolu');
$filters['weekday']->values[] = get_string('tuesday', 'local_apsolu');
$filters['weekday']->values[] = get_string('wednesday', 'local_apsolu');
$filters['weekday']->values[] = get_string('thursday', 'local_apsolu');
$filters['weekday']->values[] = get_string('friday', 'local_apsolu');
$filters['weekday']->values[] = get_string('saturday', 'local_apsolu');
$filters['weekday']->values[] = get_string('sunday', 'local_apsolu');

$filters['location'] = new \stdClass();
$filters['location']->label = get_string('location', 'local_apsolu');
$filters['location']->values = [];

$filters['skill'] = new \stdClass();
$filters['skill']->label = get_string('skill', 'local_apsolu');
$filters['skill']->values = [];

$filters['role'] = new \stdClass();
$filters['role']->label = get_string('role', 'local_apsolu');
$filters['role']->values = [];

$filters['teachers'] = new \stdClass();
$filters['teachers']->label = get_string('teachers');
$filters['teachers']->values = [];

$jsondata = get_config('local_apsolu', 'json_course_offerings_ranges');
$ranges = json_decode($jsondata);

// Category, site, activity, period, jour, start, end, level, zone geo, zone, enroltype, enseignant.
$courses = [];
foreach (enrol_select_get_activities($cityid) as $activity) {
    // TODO: la méthode enrol_select_get_activities() ne retourne pas le nom "officiel" de ces 3 champs.
    $activity->category = $activity->sport;
    $activity->categoryid = $activity->sportid;
    $activity->city = $activity->site;

    if (isset($courses[$activity->category]) === false) {
        $courses[$activity->category] = new \stdClass();
        $courses[$activity->category]->id = $activity->categoryid;
        $courses[$activity->category]->name = $activity->category;
        $courses[$activity->category]->url = $activity->url;
        $courses[$activity->category]->description = nl2br($activity->description);
        $courses[$activity->category]->modal = (empty($activity->url) === false || empty($activity->description) === false);
        $courses[$activity->category]->courses = [];

        $filters['grouping']->values[$activity->domainid] = $activity->domain;
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

    // Times.
    $time = [];
    if ($activity->endtime < $ranges->range1_end) {
        // Matin.
        $time[] = $filters['times']->values[0];
    }

    if ($activity->starttime >= $ranges->range2_start && $activity->starttime < $ranges->range2_end) {
        // Midi.
        $time[] = $filters['times']->values[1];
    }

    if ($activity->starttime >= $ranges->range3_start && $activity->starttime < $ranges->range3_end) {
        // Après-midi.
        $time[] = $filters['times']->values[2];
    }

    if ($activity->starttime >= $ranges->range4_start) {
        // Soir.
        $time[] = $filters['times']->values[3];
    }

    $activity->time = implode(' ', $time);

    if ($cityid === 0) {
        $activity->area = $activity->site . ' - ' . $activity->area;
    }

    $courses[$activity->category]->courses[] = $activity;

    $activity->period = $activity->generic_name;

    // Filtres.
    foreach (['area', 'category', 'city', 'location', 'period', 'skill'] as $type) {
        if (in_array($activity->{$type}, $filters[$type]->values, $strict = true) === false) {
            $filters[$type]->values[] = $activity->{$type};
        }
    }

    foreach ($activity->roles as $role) {
        if (isset($filters['role']->values[$role->id]) === false) {
            $filters['role']->values[$role->id] = $role->name;
        }
    }

    foreach ($activity->teachers as $teacher) {
        if (isset($filters['teachers']->values[$teacher->lastname . ' ' . $teacher->firstname]) === false) {
            $teacherfullname = $teacher->lastname . ' ' . $teacher->firstname;
            $filters['teachers']->values[$teacherfullname] = $teacherfullname;
        }
    }
}

ksort($courses);
$courses = array_values($courses);

$filters['role']->values = array_values($filters['role']->values);

ksort($filters['teachers']->values);
$filters['teachers']->values = array_values($filters['teachers']->values);

foreach ($filters as $name => $filter) {
    if (in_array($name, ['teachers', 'times', 'weekday'], $strict = true) === false) {
        sort($filter->values, SORT_LOCALE_STRING);
    }

    $attributes = ['class' => 'apsolu-presentation-selects', 'multiple' => 'multiple'];
    $filter->html = \html_writer::select($filter->values, $name, $selected = '', $nothing = false, $attributes);
}

$data = [];
$data['wwwroot'] = $CFG->wwwroot;
$data['courses'] = $courses;
if (isset($cities[$cityid]->name) === true) {
    $data['selected_site'] = $cities[$cityid]->name;
} else {
    $data['selected_site'] = '';
}
$data['sites'] = array_values($cities);
$data['sites_single'] = (count($data['sites']) < 2);

$data['count_columns'] = 0;
$jsondata = get_config('local_apsolu', 'json_course_offerings_columns');
foreach (json_decode($jsondata) as $column => $value) {
    if (empty($value) === false) {
        $data['count_columns']++;
    }
    $data[$column] = $value;
}

$jsondata = get_config('local_apsolu', 'json_course_offerings_filters');
foreach (json_decode($jsondata) as $filter => $value) {
    if (empty($value) === false) {
        continue;
    }

    // Pattern: show_grouping_filter.
    $filtername = substr($filter, 5, -7);
    if (isset($filters[$filtername]) === false) {
        debugging(
            'Invalid filter: ' . $filtername . ', valid filters: ' . implode(', ', array_keys($filters)),
            $level = DEBUG_DEVELOPER
        );
    }
    unset($filters[$filtername]);
}

if (empty($data['selected_site']) === false && empty($data['show_city_column']) === false) {
    // Si un site est sélectionné, et qu'on affiche d'habitude la colonne site ; alors on masque la colonne site.
    $data['show_city_column'] = 0;
    unset($filters['show_city_filter']);
}

$data['filters'] = array_values($filters);
$data['count_filters'] = count($filters);
$data['is_siuaps_rennes'] = isset($CFG->is_siuaps_rennes);
$data['permalink'] = has_capability('moodle/category:manage', context_system::instance());

echo $OUTPUT->render_from_template('local_apsolu/presentation_summary', $data);

echo $OUTPUT->footer();
