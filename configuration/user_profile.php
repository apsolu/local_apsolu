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
 * Page de configuration profil utilisateur.
 *
 * @package    local_apsolu
 * @copyright  2024 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\form\configuration\user_profile as user_profile_form;

defined('MOODLE_INTERNAL') || die;

// Build form.
$attributes = ['userhiddenfields'];

$defaults = new stdClass();
foreach ($attributes as $attribute) {
    $defaults->{$attribute} = get_config('local_apsolu', $attribute);
}

// Liste les champs masquables.
$hiddenfields = [];
$hiddenfields['auth'] = get_string('authentication');
foreach (['idnumber', 'institution', 'department', 'address', 'city', 'country', 'phone1', 'phone2', 'role'] as $value) {
    $hiddenfields[$value] = get_string($value);
}
foreach ($DB->get_records('user_info_field') as $record) {
    $hiddenfields[$record->shortname] = $record->name;
}

asort($hiddenfields);

$mform = new user_profile_form(null, [$defaults, $hiddenfields]);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('user_profile', 'local_apsolu'));

if ($data = $mform->get_data()) {
    foreach ($attributes as $attribute) {
        if (isset($data->{$attribute}) === false) {
            $data->{$attribute} = '';
        }

        if ($attribute === 'userhiddenfields') {
            $data->{$attribute} = implode(',', $data->{$attribute});
        }

        if ($data->{$attribute} != $defaults->{$attribute}) {
            // La valeur a été modifiée.
            add_to_config_log($attribute, $defaults->{$attribute}, $data->{$attribute}, 'local_apsolu');
        }

        set_config($attribute, $data->{$attribute}, 'local_apsolu');
    }

    echo $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');
}

$mform->display();
echo $OUTPUT->footer();
