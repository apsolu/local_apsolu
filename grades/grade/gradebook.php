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
 * Page générant les données d'affichage du carnet de notes (le formulaire de filtres, les données du carnet de notes, etc)
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\customfields;
use local_apsolu\core\gradebook;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/local/apsolu/grades/filters_form.php');

$options = [];
$options['headers'] = [0 => ['sorter' => false]];
$options['sortLocaleCompare'] = true;
$options['widgets'] = ['stickyHeaders'];
$options['widgetOptions'] = ['stickyHeaders_filteredToTop' => true, 'stickyHeaders_offset' => '50px'];

$PAGE->requires->js_call_amd('local_apsolu/sort', 'initialise', [$options]);

$PAGE->requires->js_call_amd('local_apsolu/grades', 'initialise');

$customfields = customfields::getCustomFields();

// Calcul des filtres.
$filters = null;
$filtersdata = null;
if ($data = data_submitted()) {
    if (isset($data->filtersdata) === true) {
        $filtersdata = base64_decode($data->filtersdata);
        if ($filtersdata !== false) {
            $filtersdata = json_decode($filtersdata);
            if (is_object($filtersdata) === true) {
                $filters = $filtersdata;
            }
        }
    }
}

// Liste des activités.
$categories = $DB->get_records('course_categories');

// Liste des cours évaluables.
if (defined('APSOLU_GRADES_COURSE_SCOPE') === false) {
    define('APSOLU_GRADES_COURSE_SCOPE', CONTEXT_COURSE);
}

$courses = [];
foreach (Gradebook::get_courses(APSOLU_GRADES_COURSE_SCOPE) as $course) {
    $courses[$course->category . '-0'] = $categories[$course->category]->name;
    $courses[$course->category . '-' . $course->id] = $course->fullname;
}

// Vérifie les autorisations d'accès à la page.
if (APSOLU_GRADES_COURSE_SCOPE === CONTEXT_COURSE && $courses === []) {
    // Cet utilisateur n'a pas de cours à évaluer.
    throw new moodle_exception('no_courses_to_grade', 'local_apsolu');
} else if (
    APSOLU_GRADES_COURSE_SCOPE === CONTEXT_SYSTEM &&
    has_capability('local/apsolu:viewallgrades', context_system::instance()) === false
) {
    // Cet utilisateur n'a pas les droits de gestionnaires.
    throw new moodle_exception('nopermissions', 'error', '', get_capability_string('local/apsolu:viewallgrades'));
}

// Liste des rôles évaluables.
$roles = [];
foreach (Gradebook::get_gradable_roles() as $role) {
    $roles[$role->id] = $role->localname;
}

// Liste des types de calendrier.
$calendarstypes = [];
foreach ($DB->get_records('apsolu_calendars_types', null, 'name') as $calendar) {
    $calendarstypes[$calendar->id] = $calendar->name;
}

// Liste des éléments d'évaluation.
$gradeitems = [];
foreach ($DB->get_records('apsolu_grade_items') as $item) {
    $gradeitems[$item->id] = $item->name;
}

// Liste des sites géographiques.
$cities = [];
foreach ($DB->get_records('apsolu_cities', null, 'name') as $city) {
    $cities[$city->id] = $city->name;
}

// Liste des établissements.
$institutions = [];
$sql = "SELECT DISTINCT institution FROM {user} ORDER BY institution";
foreach ($DB->get_records_sql($sql) as $institution) {
    $name = $institution->institution;
    $institutions[$name] = $name;
}

// Liste des UFRS.
$ufrs = [];
$sql = "SELECT DISTINCT data FROM {user_info_data} WHERE fieldid = :fieldid ORDER BY data";
foreach ($DB->get_records_sql($sql, ['fieldid' => $customfields['apsoluufr']->id]) as $ufr) {
    $name = $ufr->data;
    $ufrs[$name] = $name;
}

// Liste des départements.
$departments = [];
$sql = "SELECT DISTINCT department FROM {user} ORDER BY department";
foreach ($DB->get_records_sql($sql) as $department) {
    $name = $department->department;
    $departments[$name] = $name;
}

// Liste des niveaux d'études.
$cycles = [];
$sql = "SELECT DISTINCT data FROM {user_info_data} WHERE fieldid = :fieldid ORDER BY data";
foreach ($DB->get_records_sql($sql, ['fieldid' => $customfields['apsolucycle']->id]) as $cycle) {
    $name = $cycle->data;
    $cycles[$name] = $name;
}

// Liste des enseignants.
$teachers = null;
if (
    APSOLU_GRADES_COURSE_SCOPE === CONTEXT_SYSTEM &&
    has_capability('local/apsolu:viewallgrades', context_system::instance()) === true
) {
    $teachers = [];
    $sql = "SELECT DISTINCT u.*" .
        " FROM {user} u" .
        " JOIN {role_assignments} ra ON u.id = ra.userid" .
        " JOIN {context} ctx ON ctx.id = ra.contextid" .
        " JOIN {apsolu_courses} c ON ctx.instanceid = c.id" .
        " WHERE ra.roleid = 3" . // Enseignant.
        " ORDER BY u.lastname, u.firstname";
    foreach ($DB->get_records_sql($sql) as $user) {
        $teachers[$user->id] = fullname($user);
    }
}

// Build form.
if ($filters === null) {
    // Définit les filtres par défaut.
    $filters = new stdClass();
    $filters->roles = array_keys($roles);

    $filters->calendarstypes = [];
    foreach ($DB->get_records('apsolu_calendars') as $calendar) {
        if ($calendar->gradestartdate > time()) {
            continue;
        }

        if ($calendar->gradeenddate < time()) {
            continue;
        }

        $filters->calendarstypes[$calendar->typeid] = 1;
    }
    $filters->calendarstypes = array_keys($filters->calendarstypes);

    if ($teachers !== null) {
        $filters->fields = ['teachers'];
    }
}
$customdata = [$filters, $courses, $roles, $calendarstypes, $gradeitems, $cities,
    $institutions, $ufrs, $departments, $cycles, $teachers];
$mform = new local_apsolu_grades_gradebooks_filters_form(null, $customdata);

if (($formdata = $mform->get_data()) || ($data = data_submitted())) {
    if (empty($formdata) === false) {
        $filtersdata = $formdata;
    }

    if (is_object($filtersdata) === true) {
        // Filtre les options.
        $acceptedoptions = ['courses', 'roles', 'calendarstypes', 'gradeitems', 'cities',
            'institutions', 'ufrs', 'departments', 'cycles', 'teachers', 'fullnameuser', 'idnumber'];
        foreach ($acceptedoptions as $option) {
            if (isset($filtersdata->$option) === true && empty($filtersdata->$option) === false) {
                $options[$option] = $filtersdata->$option;
            }
        }
    }

    if (isset($data->savebutton, $data->grades) === true) {
        // Enregistre les notes.
        try {
            Gradebook::set_grades($data->grades);
            $notification = $OUTPUT->notification(get_string('grades_have_been_saved', 'local_apsolu'), 'notifysuccess');
        } catch (Exception $exception) {
            $notification = $OUTPUT->notification(get_string('grades_have_not_been_saved', 'local_apsolu'), 'notifyproblem');
        }
    }

    if (isset($filtersdata->fields) === false) {
        $filtersdata->fields = [];
    }

    if (
        isset($options['courses']) === false || count($options['courses']) > 1 ||
        substr(current($options['courses']), -2) === '-0'
    ) {
        // Active par défaut le champ "cours" dès que la recherche ne porte pas sur un seul cours précis.
        // Soit l'utilisateur n'a pas sélectionné de cours, soit il y a plus d'un cours,
        // soit le seul cours sélectionné est une catégorie d'activités.
        if (in_array('courses', $filtersdata->fields, $strict = true) === false) {
            $filtersdata->fields[] = 'courses';
        }
    }

    if (isset($filtersdata->exportcsvbutton) === true || isset($data->exportcsvbutton) === true) {
        // Exporte le carnet de notes au format csv.
        Gradebook::export($options, $filtersdata->fields, 'csv');
    } else if (isset($filtersdata->exportxlsbutton) === true || isset($data->exportxlsbutton) === true) {
        // Exporte le carnet de notes au format excel.
        Gradebook::export($options, $filtersdata->fields, 'xls');
    }

    // Ajoute le champ "pictures" pour l'affichage.
    $filtersdata->fields[] = 'pictures';

    try {
        $gradebook = Gradebook::get_gradebook($options, $filtersdata->fields);
    } catch (Exception $exception) {
        $notification = $OUTPUT->notification($exception->getMessage(), 'notifyproblem');
    }

    // Supprime le champ "pictures" pour ne pas l'intégrer si la prochaine action est un export.
    array_pop($filtersdata->fields);
}
