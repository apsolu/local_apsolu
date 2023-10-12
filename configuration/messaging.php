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
 * Page d'édition des préférences de messagerie.
 *
 * @package    local_apsolu
 * @copyright  2022 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

use local_apsolu\core\messaging;

require_once($CFG->dirroot.'/local/apsolu/configuration/messaging_form.php');

// Chargement des préférences.
$attributes = array(
    'functional_contact',
    'technical_contact',
    'replytoaddresspreference',
    'defaultreplytoaddresspreference',
    );

$defaults = new stdClass();
foreach ($attributes as $attribute) {
    $defaults->{$attribute} = get_config('local_apsolu', $attribute);
}

// Build form.
$customdata = array($defaults);
$mform = new local_apsolu_messaging_form(null, $customdata);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('messaging', 'local_apsolu'));

if ($data = $mform->get_data()) {
    foreach ($attributes as $attribute) {
        if (isset($data->{$attribute}) === false) {
            continue;
        }

        if ($data->{$attribute} == $defaults->{$attribute}) {
            // La valeur n'a pas été modifiée.
            continue;
        }

        add_to_config_log($attribute, $defaults->{$attribute}, $data->{$attribute}, 'local_apsolu');
        set_config($attribute, $data->{$attribute}, 'local_apsolu');
    }

    echo $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');
}

$mform->display();
echo $OUTPUT->footer();
