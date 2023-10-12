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
 * Page d'édition des préférences d'affichage de l'offre de formations.
 *
 * @package    local_apsolu
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/local/apsolu/configuration/course_offerings_form.php');

// Build form.
$data = new stdClass();

$configurations = array();
$configurations[] = 'json_course_offerings_columns';
$configurations[] = 'json_course_offerings_filters';
$configurations[] = 'json_course_offerings_ranges';
foreach ($configurations as $configuration) {
    $jsondata = get_config('local_apsolu', $configuration);
    foreach (json_decode($jsondata) as $key => $value) {
        $data->$key = $value;
    }
}

$mform = new local_apsolu_course_offerings_form();
$mform->set_data($data);

if ($data = $mform->get_data()) {
    $columns = new stdClass();
    $filters = new stdClass();
    $ranges = new stdClass();

    foreach ($data as $key => $value) {
        switch ($key) {
            case 'show_city_column':
            case 'show_grouping_column':
            case 'show_category_column':
            case 'show_area_column':
            case 'show_period_column':
            case 'show_times_column':
            case 'show_weekday_column':
            case 'show_location_column':
            case 'show_skill_column':
            case 'show_role_column':
            case 'show_teachers_column':
                $columns->$key = $value;
                break;
            case 'show_city_filter':
            case 'show_grouping_filter':
            case 'show_category_filter':
            case 'show_area_filter':
            case 'show_period_filter':
            case 'show_times_filter':
            case 'show_weekday_filter':
            case 'show_location_filter':
            case 'show_skill_filter':
            case 'show_role_filter':
            case 'show_teachers_filter':
                $filters->$key = $value;
                break;
            case 'range1_end':
            case 'range2_start':
            case 'range2_end':
            case 'range3_start':
            case 'range3_end':
            case 'range4_start':
                if (strlen($value) === 4) {
                    $value = '0'.$value;
                }
                $ranges->$key = $value;
                break;
        }
    }

    $settings = [];
    $settings['json_course_offerings_columns'] = json_encode($columns);
    $settings['json_course_offerings_filters'] = json_encode($filters);
    $settings['json_course_offerings_ranges'] = json_encode($ranges);

    foreach ($settings as $key => $newvalue) {
        $oldvalue = get_config('local_apsolu', $key);
        if ($oldvalue === $newvalue) {
            // Aucune modification.
            continue;
        }

        add_to_config_log($key, $oldvalue, $newvalue, 'local_apsolu');
        set_config($key, $newvalue, 'local_apsolu');
    }

    // Redirige vers la page générale.
    $message = get_string('changessaved');
    $returnurl = new moodle_url('/local/apsolu/configuration/index.php', array('page' => 'courseofferings'));
    redirect($returnurl, $message, $delay = null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('settings_course_offerings', 'local_apsolu'));
$mform->display();
echo $OUTPUT->footer();
