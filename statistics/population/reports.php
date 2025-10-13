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
 * Affiche les effectifs du SIUAPS.
 *
 * @package    local_apsolu
 * @copyright  2019 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once('../../externallib.php');
require_once($CFG->dirroot . '/local/apsolu/statistics/population/report_form.php');

use local_apsolu\local\statistics\population\report;

// Output.
$PAGE->requires->js_call_amd('local_apsolu/population_reports', 'init', [".report-enrolList-table"]);
$PAGE->requires->css('/local/apsolu/lib/jquery/DataTables/datatables.min.css');

// Data.
$data = new stdClass();
if ($CFG->is_siuaps_rennes) {
    $data->is_siuaps_rennes = $CFG->is_siuaps_rennes;
}

$report = new report();
$reportid = optional_param('reportid', null, PARAM_TEXT);
$reportdata = [$report->getReport(), $reportid];
$mform = new local_apsolu_statistics_population_report_form(null, $reportdata);

echo $OUTPUT->header();
echo $OUTPUT->tabtree($tabtree, $page);
$mform->display();
echo $OUTPUT->render_from_template('local_apsolu/statistics_population_reports', $reportdata);
echo $OUTPUT->footer();
