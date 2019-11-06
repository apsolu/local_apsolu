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

use local_apsolu\local\statistics\programme\report;

$report = new report();
$data = new stdClass();
$render = $PAGE->get_renderer('local_apsolu');
 
/**
* Initialisation des filtres utilisés sur les graphes
*
*/
// Sites
$cities = $report->get_cities();
//$cities[1]->active = true; // Active par défaut la 1ère ville
// Type de calendriers
$calendarstypes = $report->get_calendarstypes();
//$calendarstypes[1]->active = true; // Actice par défaut la 1er type de calendrier

// Nombre de cours proposés par groupe d'activités
$data->groupslots['title'] = $report->getReport("groupslots")->label;
$data->groupslots['chart'] = $render->render_chart(['classname'=>'programme','reportid'=>'groupslots','criterias' => ['cities'=>array_values($cities),'calendarstypes'=>array_values($calendarstypes)]]);

// Nombre de cours proposés par groupe d'activités
$data->activityslots['title'] = $report->getReport("activityslots")->label;
$data->activityslots['chart'] = $render->render_chart(['classname'=>'programme','reportid'=>'activityslots','criterias' => ['cities'=>array_values($cities),'calendarstypes'=>array_values($calendarstypes)]]);

// Nombre de places en liste principale (potentiel d'accueil)
$data->countslotsmainlist['title'] = $report->getReport("countslotsmainlist")->label;
$data->countslotsmainlist['counter'] = $render->render_reportCounterSum(['classname'=>'programme','reportid'=>'countslotsmainlist']);
$data->countslotsmainlist['chart'] = $render->render_chart(['classname'=>'programme','reportid'=>'countslotsmainlist','criterias' => ['cities'=>array_values($cities),'calendarstypes'=>array_values($calendarstypes)]]);


echo $OUTPUT->header();
echo $OUTPUT->tabtree($tabtree, $page);
echo $OUTPUT->render_from_template('local_apsolu/statistics_programme_dashboard', $data);
echo $OUTPUT->footer();
