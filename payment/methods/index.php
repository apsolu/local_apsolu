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
 * Page permettant de gérer les méthodes de paiement.
 *
 * @package    local_apsolu
 * @copyright  2026 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\form\payment\methods_edit;
use local_apsolu\payment\method;

defined('MOODLE_INTERNAL') || die;

$mform = new methods_edit($PAGE->url->out(false));

if ($data = $mform->get_data()) {
    foreach (method::get_available_methods() as $stringid => $label) {
        $attribute = method::get_key_config($stringid);

        if (isset($data->{$attribute}) === false) {
            continue;
        }

        $currentvalue = get_config('local_apsolu', $attribute);
        if ($currentvalue == $data->{$attribute}) {
            // La valeur n'a pas été modifiée.
            continue;
        }

        add_to_config_log($attribute, $currentvalue, $data->{$attribute}, 'local_apsolu');
        set_config($attribute, $data->{$attribute}, 'local_apsolu');
    }

    echo $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');
}

$mform->display();
