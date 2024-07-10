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
 * Génère les jours fériés.
 *
 * @package   local_apsolu
 * @copyright 2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

use local_apsolu\core\holiday as Holiday;

require(__DIR__.'/generate_form.php');

// Build form.
$customdata = ['holiday' => (object) ['from' => time(), 'until' => time() + 365 * 24 * 60 * 60]];
$mform = new local_apsolu_courses_holidays_generate_form(null, $customdata);

if ($data = $mform->get_data()) {
    // Message à afficher à la fin de l'enregistrement.
    $message = get_string('holidays_have_been_generated', 'local_apsolu');

    // Récupère tous les jours fériés déjà enregistrés.
    $holidays = $DB->get_records('apsolu_holidays', null, null, 'day');

    // Parcourt chaque année dans l'intervalle donné.
    $year = core_date::strftime('%Y', $data->from);
    $endyear = core_date::strftime('%Y', $data->until);
    for ($year; $year <= $endyear; $year++) {
        // Parcourt les jours fériés de l'année.
        foreach (Holiday::get_holidays($year) as $holidaytimestamp) {
            if ($holidaytimestamp < $data->from) {
                // Le jour férié est en deçà de l'intervalle donné.
                continue;
            }

            if ($holidaytimestamp > $data->until) {
                // Le jour férié est au deçà de l'intervalle donné.
                continue;
            }

            if (isset($holidays[$holidaytimestamp]) === true) {
                // Le jour férié est déjà enregistré.
                continue;
            }

            // Save data.
            $holiday = new Holiday();
            $holiday->day = $holidaytimestamp;
            $holiday->save();

            // Régénère les sessions.
            if (isset($data->regensessions) === true) {
                $holiday->regenerate_sessions();
            }
        }
    }

    // Redirige vers la page générale.
    $returnurl = new moodle_url('/local/apsolu/courses/index.php', ['tab' => 'holidays']);
    redirect($returnurl, $message, $delay = null, \core\output\notification::NOTIFY_SUCCESS);
}

// Display form.
$heading = get_string('generate_holidays', 'local_apsolu');

echo $OUTPUT->heading($heading);
$mform->display();
