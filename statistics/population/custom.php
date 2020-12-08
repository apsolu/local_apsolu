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

use local_apsolu\local\statistics\population\report;

$report = new report();
$data = new stdClass();

// Output.
$PAGE->requires->jquery();
$PAGE->requires->css( new moodle_url($CFG->wwwroot . '/local/apsolu/lib/jquery/jQuery-QueryBuilder/css/query-builder.default.min.css'));
$PAGE->requires->css('/local/apsolu/lib/jquery/DataTables/datatables.min.css');

if ($CFG->is_siuaps_rennes){
    $data->is_siuaps_rennes = $CFG->is_siuaps_rennes;
}
$data->Filters = $report->getFilters();
$data->langcode = ($USER->lang != '' ? $USER->lang : 'fr');

echo $OUTPUT->header();
echo $OUTPUT->tabtree($tabtree, $page);
echo $OUTPUT->render_from_template('local_apsolu/statistics_population_custom', $data);
echo $OUTPUT->footer();
