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
// Activité complémentaires
$complementaries = $report->get_complementaries();


/**
* ACTIVITÉS PHYSIQUES
*
*/
// INSCRIPTIONS : Nombre d'inscriptions
$data->enrollment['counter'] = $render->render_reportCounter(['classname'=>'population','reportid'=>'enrollment']);
$data->enrollment['report'] = new moodle_url('/local/apsolu/statistics/population/index.php?page=reports&reportid=enrollment');
// INSCRIPTIONS : Nombre d'inscriptions acceptées
$data->enrollment_acceptedlist['counter'] = $render->render_reportCounter(['classname'=>'population','reportid'=>'enrollment_acceptedlist']);
$data->enrollment_acceptedlist['report'] = new moodle_url('/local/apsolu/statistics/population/index.php?page=reports&reportid=enrollment_acceptedlist');
// INSCRIPTIONS : Nombre d'inscriptions sur liste principale
$data->enrollment_mainlist['counter'] = $render->render_reportCounter(['classname'=>'population','reportid'=>'enrollment_mainlist']);
$data->enrollment_mainlist['report'] = new moodle_url('/local/apsolu/statistics/population/index.php?page=reports&reportid=enrollment_mainlist');
// INSCRIPTIONS : Nombre d'inscriptions sur liste d'attente
$data->enrollment_waitinglist['counter'] = $render->render_reportCounter(['classname'=>'population','reportid'=>'enrollment_waitinglist']);
$data->enrollment_waitinglist['report'] = new moodle_url('/local/apsolu/statistics/population/index.php?page=reports&reportid=enrollment_waitinglist');
// INSCRIPTIONS : Nombre d'inscriptions refusé
$data->enrollment_deletedlist['counter'] = $render->render_reportCounter(['classname'=>'population','reportid'=>'enrollment_deletedlist']);
$data->enrollment_deletedlist['report'] = new moodle_url('/local/apsolu/statistics/population/index.php?page=reports&reportid=enrollment_deletedlist');
// INSCRITS : Nombre d'inscrits
$data->enrollee['counter'] = $render->render_reportCounter(['classname'=>'population','reportid'=>'enrollee']);
$data->enrollee['report'] = new moodle_url('/local/apsolu/statistics/population/index.php?page=reports&reportid=enrollee');


/**
* ACTIVITÉS COMPLÉMENTAIRES
*
*
*/
// INSCRIPTIONS : Nombre d'inscriptions
$complementaries_enrollment_counter = $render->render_reportCounter(['classname'=>'population','reportid'=>'complementaries_enrollment']);
if ($complementaries_enrollment_counter > 0) {
  $data->has_complementaries_enrollment = true;
  $data->complementaries_enrollment['counter'] = $render->render_reportCounter(['classname'=>'population','reportid'=>'complementaries_enrollment']);
  $data->complementaries_enrollment['report'] = new moodle_url('/local/apsolu/statistics/population/index.php?page=reports&reportid=complementaries_enrollment');
  // INSCRITS : Nombre d'inscrits
  $data->complementaries_enrollee['counter'] = $render->render_reportCounter(['classname'=>'population','reportid'=>'complementaries_enrollee']);
  $data->complementaries_enrollee['report'] = new moodle_url('/local/apsolu/statistics/population/index.php?page=reports&reportid=complementaries_enrollee');
  $data->complementaries_enrollee['title'] = $report->getReport("complementaries_enrollee")->label;
  $data->complementaries_enrollee['chart'] = $render->render_chart(['classname'=>'population','reportid'=>'complementaries_enrollee','criterias' => ['complementaries'=>array_values($complementaries)]]);
  if ($CFG->is_siuaps_rennes){
    // INSCRIPTIONS : Nombre d'inscriptions en musculation
    $data->complementaries_enrollment_mus['counter'] = $render->render_reportCounter(['classname'=>'population','reportid'=>'complementaries_enrollment_mus']);
    $data->complementaries_enrollment_mus['report'] = new moodle_url('/local/apsolu/statistics/population/index.php?page=reports&reportid=complementaries_enrollment_mus');
    // INSCRIPTIONS : Nombre d'inscriptions en FFSU
    $data->complementaries_enrollment_ffsu['counter'] = $render->render_reportCounter(['classname'=>'population','reportid'=>'complementaries_enrollment_ffsu']);
    $data->complementaries_enrollment_ffsu['report'] = new moodle_url('/local/apsolu/statistics/population/index.php?page=reports&reportid=complementaries_enrollment_ffsu');
  }
}
/**
* CHARTS
*
*
*/
// INSCRITS : Nombre d'inscrits ayant au moins une activité physique / Refusés en cours
$data->enrollee_accepted_refused['title'] = $report->getReport("accepted_refused")->label;
$data->enrollee_accepted_refused['chart'] = $render->render_chart(['classname'=>'population','reportid'=>'accepted_refused','criterias' => ['cities'=>array_values($cities),'calendarstypes'=>array_values($calendarstypes)]]);

// INSCRITS : Répartition : Nb Personnels VS Nb étudiants
$data->distribution_userprofile['title'] = $report->getReport("distribution_userprofile")->label;
$data->distribution_userprofile['chart'] = $render->render_chart(['classname'=>'population','reportid'=>'distribution_userprofile','criterias' => ['cities'=>array_values($cities),'calendarstypes'=>array_values($calendarstypes)]]);
                         
// INSCRITS : Répartition : Nb garçons VS Nb filles
$data->distribution_genders['title'] = $report->getReport("distribution_genders")->label;
$data->distribution_genders['chart'] = $render->render_chart(['classname'=>'population','reportid'=>'distribution_genders','criterias' => ['cities'=>array_values($cities),'calendarstypes'=>array_values($calendarstypes)]]);

// INSCRIPTIONS : Nombre de libres/Option évalués/Bonification évalués
$data->enrol_roles['title'] = $report->getReport("enrol_roles")->label;
$data->enrol_roles['chart'] = $render->render_chart(['classname'=>'population','reportid'=>'enrol_roles','criterias' => ['cities'=>array_values($cities),'calendarstypes'=>array_values($calendarstypes)]]);

// INSCRIPTIONS : Répartition : Nb Personnels VS Nb étudiants
$data->enrol_userprofile['title'] = $report->getReport("enrol_userprofile")->label;
$data->enrol_userprofile['chart'] = $render->render_chart(['classname'=>'population','reportid'=>'enrol_userprofile','criterias' => ['cities'=>array_values($cities),'calendarstypes'=>array_values($calendarstypes)]]);
                         
// INSCRIPTIONS : Répartition : Nb garçons VS Nb filles
$data->enrol_genders['title'] = $report->getReport("enrol_genders")->label;
$data->enrol_genders['chart'] = $render->render_chart(['classname'=>'population','reportid'=>'enrol_genders','criterias' => ['cities'=>array_values($cities),'calendarstypes'=>array_values($calendarstypes)]]);

if ($CFG->is_siuaps_rennes){
  $data->is_siuaps_rennes = $CFG->is_siuaps_rennes;
  
  // INSCRITS : Nombre de sportif de haut niveau inscrit à une pratique une activité physique / complémentaire
  $data->shnu_user['title'] = $report->getReport("custom_apsoluhighlevelathlete")->label;  
  $data->shnu_user['chart'] = $render->render_chart(['classname'=>'population','reportid'=>'custom_apsoluhighlevelathlete']);
  
  $data->shnu['title'] = $report->getReport("custom_shnu")->label;
  $data->shnu['counter'] = $render->render_reportCounter(['classname'=>'population','reportid'=>'custom_shnu']);
  $data->shnu['report'] = new moodle_url('/local/apsolu/statistics/population/index.php?page=reports&reportid=custom_shnu');
  
}


echo $OUTPUT->header();
echo $OUTPUT->tabtree($tabtree, $page);
echo $OUTPUT->render_from_template('local_apsolu/statistics_population_dashboard', $data);
echo $OUTPUT->footer();
