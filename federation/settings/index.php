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
 * Script de paramétrage du module FFSU.
 *
 * @package    local_apsolu
 * @copyright  2023 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/settings_form.php');

// Chargement des préférences.
$attributes = [
    'enable_pass_sport_payment',
    'ffsu_acceptedfiles',
    'ffsu_maxfiles',
    'insurance_cohort',
    'insurance_field_default',
    'insurance_field_visibility',
    'licenseetype_field_default',
    'licenseetype_field_visibility',
    'licensetype_field_default',
    'licensetype_field_visibility',
    'otherfederation_field_visibility',
    ];

$defaults = new stdClass();
foreach ($attributes as $attribute) {
    $defaults->{$attribute} = get_config('local_apsolu', $attribute);

    if ($attribute === 'licensetype_field_default') {
        $defaults->{$attribute} = json_decode($defaults->{$attribute});
    }
}

$defaults->ffsu_agreement['text'] = get_config('local_apsolu', 'ffsu_agreement');
$defaults->ffsu_agreement['format'] = FORMAT_HTML;

$defaults->ffsu_introduction['text'] = get_config('local_apsolu', 'ffsu_introduction');
$defaults->ffsu_introduction['format'] = FORMAT_HTML;

$defaults->parental_authorization_description['text'] = get_config('local_apsolu', 'parental_authorization_description');
$defaults->parental_authorization_description['format'] = FORMAT_HTML;

// Chargement des cohortes.
$cohorts = ['0' => ''];
foreach ($DB->get_records('cohort', $conditions = null, $sort = 'name') as $cohort) {
    $cohorts[$cohort->id] = $cohort->name;
}

// Build form.
$customdata = [$defaults, $cohorts];
$mform = new local_apsolu_settings_form(null, $customdata);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('settings'));
echo $OUTPUT->tabtree($tabtree, $page);

if ($data = $mform->get_data()) {
    foreach ($attributes as $attribute) {
        if (isset($data->{$attribute}) === false) {
            continue;
        }

        if ($attribute === 'licensetype_field_default') {
            $data->{$attribute} = json_encode($data->{$attribute});
            $defaults->{$attribute} = json_encode($defaults->{$attribute});
        }

        if ($data->{$attribute} == $defaults->{$attribute}) {
            // La valeur n'a pas été modifiée.
            continue;
        }

        add_to_config_log($attribute, $defaults->{$attribute}, $data->{$attribute}, 'local_apsolu');
        set_config($attribute, $data->{$attribute}, 'local_apsolu');
    }

    foreach (['ffsu_agreement', 'ffsu_introduction', 'parental_authorization_description'] as $key) {
        if (isset($data->{$key}['text']) === false) {
            continue;
        }

        if (empty(trim(strip_tags($data->{$key}['text']))) === true) {
            // Force la valeur pour les données ne contenant que des balises HTML vides.
            $data->{$key}['text'] = '';
        }

        if ($data->{$key} === $defaults->{$key}) {
            // La valeur n'a pas été modifiée.
            continue;
        }

        add_to_config_log($key, json_encode($defaults->{$key}), json_encode($data->{$key}), 'local_apsolu');
        set_config($key, $data->{$key}['text'], 'local_apsolu');
    }

    echo $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');
}

$mform->display();
echo $OUTPUT->footer();
