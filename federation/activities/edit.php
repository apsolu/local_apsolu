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
 * Page pour éditer l'association d'un nom d'une activité FFSU à un nom d'un activité APSOLU.
 *
 * @package    local_apsolu
 * @copyright  2022 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\federation\activity;

defined('MOODLE_INTERNAL') || die;

require(__DIR__ . '/edit_form.php');

// Get activity id.
$activityid = optional_param('activityid', 0, PARAM_INT);

// Generate object.
$activity = new Activity();
$activity->load($activityid, $required = true);

// Récupère la liste des activités APSOLU.
$sql = "SELECT cc.id, cc.name" .
    " FROM {course_categories} cc" .
    " JOIN {apsolu_courses_categories} acc ON cc.id = acc.id" .
    " WHERE cc.id NOT IN (" .
        // Sauf les catégories déjà associées, à l'exception de l'activité en cours d'édition.
        "SELECT DISTINCT categoryid FROM {apsolu_federation_activities} WHERE categoryid != :categoryid" .
    ")" .
    " ORDER BY cc.name";
$categories = [];
$categories[0] = '';
foreach ($DB->get_records_sql($sql, ['categoryid' => $activity->categoryid]) as $category) {
    $categories[$category->id] = $category->name;
}

// Build form.
$customdata = ['activity' => $activity, 'categories' => $categories];
$mform = new local_apsolu_federation_activities_edit_form(null, $customdata);

if ($data = $mform->get_data()) {
    // Message à afficher à la fin de l'enregistrement.
    $message = get_string('data_updated', 'local_apsolu');

    // Save data.
    $activity->save($data, $mform);

    // Redirige vers la page générale.
    $returnurl = new moodle_url('/local/apsolu/federation/index.php', ['page' => 'view']);
    redirect($returnurl, $message, $delay = null, \core\output\notification::NOTIFY_SUCCESS);
}

// Display form.
$heading = get_string('edit_matching', 'local_apsolu');

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);
echo $OUTPUT->tabtree($tabtree, $page);

$mform->display();

echo $OUTPUT->footer();
