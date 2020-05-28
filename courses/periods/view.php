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
 * Liste les périodes.
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$periods = array();
foreach ($DB->get_records('apsolu_periods', null, 'name') as $period) {
    $period->weeks = explode(',', $period->weeks);
    $period->count_weeks = 0;

    if ($period->weeks[0] !== '') {
        foreach ($period->weeks as $i => $week) {
            $date = new DateTime($week);

            $range = 'du lun. '.$date->format('d').' au sam. '.strftime('%d %b %Y', $date->getTimestamp() + 5 * 24 * 60 * 60);
            $period->weeks[$i] = 'Semaine '.$date->format('W').' ('.$range.')';

            $period->count_weeks++;
        }
    }
    $periods[] = $period;
}

$data = new stdClass();
$data->wwwroot = $CFG->wwwroot;
$data->periods = $periods;
$data->count_periods = count($periods);

if (isset($notificationform)) {
    $data->notification = $notificationform;
}

echo $OUTPUT->render_from_template('local_apsolu/courses_periods', $data);
