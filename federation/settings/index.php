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
 * @copyright  2023 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/settings_form.php');

// Chargement des préférences.
$attributes = array(
    'ffsu_acceptedfiles',
    'ffsu_maxfiles',
    'instagram_field_visibility',
    'insurance_cohort',
    'insurance_field_default',
    'insurance_field_visibility',
    'managerlicense_field_default',
    'managerlicense_field_visibility',
    'managerlicensetype_field_default',
    'managerlicensetype_field_visibility',
    'otherfederation_field_visibility',
    'refereelicense_field_default',
    'refereelicense_field_visibility',
    'sportlicense_field_default',
    'sportlicense_field_visibility',
    'starlicense_field_default',
    'starlicense_field_visibility',
    );

$defaults = new stdClass();
foreach ($attributes as $attribute) {
    $defaults->{$attribute} = get_config('local_apsolu', $attribute);
}


$defaults->ffsu_agreement['text'] = get_config('local_apsolu', 'ffsu_agreement');
$defaults->ffsu_agreement['format'] = FORMAT_HTML;

// Chargement des cohortes.
$cohorts = array('0' => '');
foreach ($DB->get_records('cohort', $conditions = null, $sort = 'name') as $cohort) {
    $cohorts[$cohort->id] = $cohort->name;
}

// Build form.
$customdata = array($defaults, $cohorts);
$mform = new local_apsolu_settings_form(null, $customdata);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('settings'));
echo $OUTPUT->tabtree($tabtree, $page);

if ($data = $mform->get_data()) {
    foreach ($attributes as $attribute) {
        if (isset($data->{$attribute}) === false) {
            continue;
        }
        set_config($attribute, $data->{$attribute}, 'local_apsolu');
    }

    if (isset($data->ffsu_agreement['text']) === true) {
        set_config('ffsu_agreement', $data->ffsu_agreement['text'], 'local_apsolu');
    }

    echo $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');
}

$mform->display();
echo $OUTPUT->footer();
