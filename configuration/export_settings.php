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
 * Page d'édition des paramètres d'exportation.
 *
 * @package    local_apsolu
 * @copyright  2025 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\form\configuration\export_settings as export_settings_form;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/user/profile/lib.php');

$fieldtypes = ['display', 'export'];
$currentjsonfields = [];
$currentarrayfields = [];

// Charge le paramétrage actuel.
foreach ($fieldtypes as $fieldtype) {
    $configkey = sprintf('%s_fields', $fieldtype);

    $currentjsonfields[$fieldtype] = get_config('local_apsolu', $configkey);

    $currentarrayfields[$fieldtype] = json_decode($currentjsonfields[$fieldtype]);
    if (is_array($currentarrayfields[$fieldtype]) === false) {
        $currentarrayfields[$fieldtype] = [];
    }
}

// Build form.
$defaults = new stdClass();
foreach ($fieldtypes as $fieldtype) {
    $attribute = sprintf('additional%sfields', $fieldtype);

    $defaults->$attribute = [];
    foreach ($currentarrayfields[$fieldtype] as $field) {
        $defaults->{$attribute}[] = $field;
    }
}

$fields = [];
$fields['email'] = get_string('email');
$fields['institution'] = get_string('institution');
$fields['department'] = get_string('department');
$fields['phone1'] = get_string('phone1');
$fields['phone2'] = get_string('phone2');

foreach (profile_get_custom_fields() as $field) {
    $fields['extra_' . $field->shortname] = $field->name;
}

$customdata = [$defaults, $fields];

$mform = new export_settings_form($PAGE->url->out(false), $customdata);

if ($data = $mform->get_data()) {
    foreach ($fieldtypes as $fieldtype) {
        $configkey = sprintf('%s_fields', $fieldtype);
        $selectfield = sprintf('additional%sfields', $fieldtype);

        $values = [];
        foreach ($fields as $field => $unused) {
            if (in_array($field, $data->{$selectfield}, $strict = true) === false) {
                continue;
            }

            $values[] = $field;
        }

        if ($currentarrayfields[$fieldtype] !== $values) {
            // La valeur a été modifiée.
            $newjsonfield = json_encode($values);
            add_to_config_log($configkey, $currentjsonfields[$fieldtype], $newjsonfield, 'local_apsolu');

            set_config($configkey, $newjsonfield, 'local_apsolu');
        }
    }

    $notificationform = $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('export_settings', 'local_apsolu'));
if (isset($notificationform) === true) {
    echo $notificationform;
}
$mform->display();
echo $OUTPUT->footer();
