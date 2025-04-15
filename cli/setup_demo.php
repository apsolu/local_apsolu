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
 * Ce script fournit un jeu de données pour APSOLU afin de tester rapidement l'application.
 *
 * Avant d'initialiser le jeu de données, le script nettoie certaines données (cours, catégories, utilisateurs et toutes les données
 * en relation avec APSOLU).
 *
 * @package    local_apsolu
 * @copyright  2025 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\tests\behat\dataset_provider;

define('CLI_SCRIPT', true);
define('APSOLU_DEMO', true);

require(__DIR__.'/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot.'/local/apsolu/tests/behat/dataset_provider.php');

list($options, $unrecognized) = cli_get_params(
    [
        'help'                      => false,
        'non-interactive'           => false,
    ],
    [
        'h' => 'help',
    ]
);

$interactive = empty($options['non-interactive']);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help'] === true) {
    cli_writeln(get_string('cli_setup_behat_help', 'local_apsolu'));
    exit(0);
}

cli_heading(get_string('initializing_the_demo_dataset', 'local_apsolu'));

cli_writeln('');
cli_separator();
cli_writeln(get_string('warning_this_operation_will_destroy_data', 'local_apsolu'));
cli_writeln('');
cli_writeln(get_string('once_you_do_this_you_can_not_go_back_again', 'local_apsolu'));
cli_separator();

cli_writeln('');
cli_writeln(get_string('please_note_that_this_process_can_take_a_long_time', 'local_apsolu'));
cli_writeln('');

if ($interactive) {
    cli_writeln(get_string('do_you_really_want_to_initialize_the_demo_dataset', 'local_apsolu'));
    cli_writeln('');

    $prompt = get_string('cliyesnoprompt', 'admin');
    $input = cli_input($prompt, '', [get_string('clianswerno', 'admin'), get_string('cliansweryes', 'admin')]);
    if ($input == get_string('clianswerno', 'admin')) {
        exit(1);
    }

    cli_writeln('');
}

// Supprime toutes les catégories (sauf la 1ère).
cli_writeln(get_string('cli_step_deletes_all_categories_and_courses', 'local_apsolu'));
foreach ($DB->get_records('course_categories', ['parent' => 0]) as $category) {
    if ($category->id == 1) {
        continue;
    }

    $coursecat = core_course_category::get($category->id, MUST_EXIST, true);
    $coursecat->delete_full();
}

// Supprime tous les cours de la catégorie 1.
foreach ($DB->get_records('course', ['category' => 1]) as $course) {
    delete_course($course, $showfeedback = false);
}

// Supprime tous les utilisateurs.
cli_writeln(get_string('cli_step_deletes_all_users', 'local_apsolu'));
foreach ($DB->get_records('user', ['deleted' => 0]) as $user) {
    if ($user->id < 3) {
        continue;
    }

    delete_user($user);
}

// Supprime toutes les données des tables APSOLU.
cli_writeln(get_string('cli_step_deletes_all_apsolu_table_data', 'local_apsolu'));
$queries = [];
$queries[] = 'TRUNCATE {apsolu_areas}';
$queries[] = 'TRUNCATE {apsolu_attendance_presences}';
$queries[] = 'TRUNCATE {apsolu_attendance_sessions}';
$queries[] = 'TRUNCATE {apsolu_attendance_statuses}';
$queries[] = 'TRUNCATE {apsolu_calendars}';
$queries[] = 'TRUNCATE {apsolu_calendars_types}';
$queries[] = 'TRUNCATE {apsolu_cities}';
$queries[] = 'TRUNCATE {apsolu_colleges}';
$queries[] = 'TRUNCATE {apsolu_colleges_members}';
$queries[] = 'TRUNCATE {apsolu_communication_templates}';
$queries[] = 'TRUNCATE {apsolu_complements}';
$queries[] = 'TRUNCATE {apsolu_courses}';
$queries[] = 'TRUNCATE {apsolu_courses_categories}';
$queries[] = 'TRUNCATE {apsolu_courses_groupings}';
$queries[] = 'TRUNCATE {apsolu_dunnings}';
$queries[] = 'TRUNCATE {apsolu_dunnings_cards}';
$queries[] = 'TRUNCATE {apsolu_dunnings_posts}';
$queries[] = 'TRUNCATE {apsolu_federation_activities}';
$queries[] = 'TRUNCATE {apsolu_federation_adhesions}';
$queries[] = 'TRUNCATE {apsolu_federation_numbers}';
$queries[] = 'TRUNCATE {apsolu_grade_items}';
$queries[] = 'TRUNCATE {apsolu_holidays}';
$queries[] = 'TRUNCATE {apsolu_locations}';
$queries[] = 'TRUNCATE {apsolu_managers}';
$queries[] = 'TRUNCATE {apsolu_payments}';
$queries[] = 'TRUNCATE {apsolu_payments_addresses}';
$queries[] = 'TRUNCATE {apsolu_payments_cards}';
$queries[] = 'TRUNCATE {apsolu_payments_cards_cals}';
$queries[] = 'TRUNCATE {apsolu_payments_cards_cohort}';
$queries[] = 'TRUNCATE {apsolu_payments_cards_roles}';
$queries[] = 'TRUNCATE {apsolu_payments_centers}';
$queries[] = 'TRUNCATE {apsolu_payments_items}';
$queries[] = 'TRUNCATE {apsolu_payments_transactions}';
$queries[] = 'TRUNCATE {apsolu_periods}';
$queries[] = 'TRUNCATE {apsolu_roles}';
$queries[] = 'TRUNCATE {apsolu_skills}';
$queries[] = 'TRUNCATE {apsolu_skills_descriptions}';

foreach ($queries as $query) {
    $DB->execute($query);
}

// Initialise un nouveau jeu de données.
cli_writeln(get_string('cli_step_initialize_a_new_dataset', 'local_apsolu'));
dataset_provider::execute();
cli_writeln('');

// Initialise un espace-cours spécifique pour l'instance de démonstration.
if (is_readable($CFG->dirroot.'/mod/scheduler/version.php') === true) {
    // Récupère les droits admin.
    $USER = get_admin();

    $category = $DB->get_record('course_categories', ['name' => 'Badminton'], $fields = '*', MUST_EXIST);

    // Crée l'espace-cours.
    $data = new stdClass();
    $data->fullname = 'Badminton (réservation à la séance)';
    $data->shortname = 'Badminton (réservation à la séance)';
    $data->category = $category->id;
    $course = create_course($data);

    // Récupère la 1ère section du cours.
    $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 0]);
    if ($section === false) {
        $section = course_create_section($course->id);
    }

    // Ajoute une activité Rendez-vous.
    require_once($CFG->dirroot.'/course/modlib.php');

    list($module, $context, $cw) = can_add_moduleinfo($course, 'scheduler', $section->section);

    $data = new stdClass();
    $data->section = $section->section;
    $data->visible = '1';
    $data->visibleoncoursepage = '1';
    $data->course = $course->id;
    $data->module = $module->id;
    $data->modulename = $module->name;
    $data->modname = $module->name;
    $data->groupmode = $course->groupmode;
    $data->groupingid = $course->defaultgroupingid;
    $data->id = '';
    $data->instance = '';
    $data->coursemodule = '';
    $data->add = 'scheduler';
    $data->return = 0; // Must be false if this is an add, go back to course view on cancel.
    $data->sr = 0;
    $data->grade = 0;
    $data->name = 'Gestion des rendez-vous';
    $data->schedulermode = 'onetime';
    $data->intro = '';
    $data->introformat = 1;
    $data->availabilityconditionsjson = null;

    $moduleinfo = add_moduleinfo($data, $course, null);

    // Ajoute l'utilisateur "lenseignante".
    $manualplugin = enrol_get_plugin('manual');
    $enrol = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'manual']);
    if ($enrol === false) {
        $enrolid = $manualplugin->add_instance($course, $manualplugin->get_instance_defaults());
        $enrol = $DB->get_record('enrol', ['id' => $enrolid]);
    }
    $teacher = $DB->get_record('user', ['username' => 'lenseignante', 'deleted' => 0], $fields = '*', MUST_EXIST);
    $manualplugin->enrol_user($enrol, $teacher->id, $teacherroleid = 3, $timestart = 0, $timeend = 0, $status = ENROL_USER_ACTIVE);

    // Ajoute une méthode d'inscription par voeux.
    $cohort = $DB->get_record('cohort', ['idnumber' => 'FFSU']);
    $role = $DB->get_record('role', ['shortname' => 'libre']);

    $plugin = enrol_get_plugin('select');
    $enrolid = $plugin->add_instance($course, $plugin->get_instance_defaults());
    $enrol = $DB->get_record('enrol', ['id' => $enrolid]);
    $enrol->customchar3 = $plugin::ACCEPTED;
    $DB->update_record('enrol', $enrol);
    $DB->execute('INSERT INTO {enrol_select_cohorts}(enrolid, cohortid) VALUES(?, ?)', [$enrol->id, $cohort->id]);
    $DB->execute('INSERT INTO {enrol_select_roles}(enrolid, roleid) VALUES(?, ?)', [$enrol->id, $role->id]);

    // Ajoute des étudiants dans la méthode d'inscription.
    $i = 0;
    $enroled = [];
    $selectplugin = enrol_get_plugin('select');
    $users = $DB->get_records('user', ['deleted' => 0], $sort = '', $fields = 'username, id');

    // Inscrit l'utilisateur "letudiant".
    $status = $selectplugin::ACCEPTED;
    $userid = $users['letudiant']->id;
    $selectplugin->enrol_user($enrol, $userid, $role->id, $timestart = 0, $timeend = 0, $status);
    $enroled[$userid] = $userid;

    // Inscrit aléatoirement d'autres étudiants.
    $sql = "SELECT cm.*, c.idnumber
              FROM {cohort_members} cm
              JOIN {cohort} c ON c.id = cm.cohortid
              JOIN {enrol_select_cohorts} esc ON cm.cohortid = esc.cohortid
             WHERE esc.enrolid = :enrolid";
    $records = $DB->get_records_sql($sql, ['enrolid' => $enrol->id]);
    shuffle($records);
    foreach ($records as $record) {
        if (isset($enroled[$record->userid]) === true) {
            continue;
        }

        if ($DB->count_records('user_enrolments', ['userid' => $record->userid]) > 3) {
            // Empêche un même étudiant d'être inscrit sur 20 créneaux.
            continue;
        }

        if ($i < $enrol->customint1) {
            $status = $selectplugin::ACCEPTED;
        } else if ($i < ($enrol->customint1 + $enrol->customint2)) {
            $status = $selectplugin::WAIT;
        } else {
            break;
        }

        $selectplugin->enrol_user($enrol, $record->userid, $role->id, $timestart = 0, $timeend = 0, $status);
        $i++;

        $enroled[$record->userid] = $record->userid;
    }
}

cli_writeln(get_string('the_demo_dataset_has_been_successfully_initialized', 'local_apsolu'));
