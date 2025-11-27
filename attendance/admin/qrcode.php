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
 * Page pour le paramétrage général des prises de présences par QR code.
 *
 * @package    local_apsolu
 * @copyright  2025 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\attendance\qrcode;
use local_apsolu\core\attendance\status;
use local_apsolu\form\attendance\admin\qrcode as qrcode_form;

defined('MOODLE_INTERNAL') || die;

// Récupère le paramétrage par défaut des QR codes.
$default = qrcode::get_default_settings();
$default->enabled = get_config('local_apsolu', 'qrcode_enabled');

$default->enablelatetime = 1;
if ($default->latetime == -1) {
    $default->enablelatetime = 0;
    $default->latetime = 0;
}

$default->enableendtime = 1;
if ($default->endtime == -1) {
    $default->enableendtime = 0;
    $default->endtime = 0;
}

// Récupère les différents types de présence.
$statuses = [];
foreach (Status::get_records() as $record) {
    $statuses[$record->id] = $record->longlabel;
}

// Construit le formulaire.
$customdata = [$default, $statuses];
$mform = new qrcode_form($PAGE->url->out(false), $customdata);

// Traite le formulaire.
if ($data = $mform->get_data()) {
    if (isset($data->enablelatetime) === false) {
        $data->latetime = -1;
    }

    if (isset($data->enableendtime) === false) {
        $data->endtime = -1;
    }

    foreach (get_object_vars($data) as $attribute => $unused) {
        if (isset($default->$attribute) === false) {
            continue;
        }

        if ($default->$attribute == $data->$attribute) {
            continue;
        }

        $configname = sprintf('qrcode_%s', $attribute);

        add_to_config_log($configname, $default->$attribute, $data->$attribute, 'local_apsolu');
        set_config($configname, $data->$attribute, 'local_apsolu');
    }

    echo $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');
}

// Affiche le formulaire.
$mform->display();
