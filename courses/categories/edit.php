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
 * Page pour gérer l'édition d'une activité sportive.
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\category as Category;
use local_apsolu\core\grouping as Grouping;
use local_apsolu\core\manager as Manager;

defined('MOODLE_INTERNAL') || die;

require(__DIR__.'/edit_form.php');

// Get category id.
$categoryid = optional_param('categoryid', 0, PARAM_INT);

// Generate object.
$category = new Category();
if ($categoryid !== 0) {
    $category->load($categoryid);
}

// Groupements d'activités sportives.
$sql = "SELECT cc.id, cc.name".
    " FROM {course_categories} cc".
    " JOIN {apsolu_courses_groupings} acg ON cc.id = acg.id".
    " ORDER BY cc.name";
$groupings = array();
foreach ($DB->get_records_sql($sql) as $grouping) {
    $groupings[$grouping->id] = $grouping->name;
}

if ($groupings === array()) {
    print_error('error_no_grouping', 'local_apsolu', $CFG->wwwroot.'/local/apsolu/courses/index.php?tab=groupings');
}

// Build form.
$context = context_system::instance();
$itemid = 0;
$customdata = array('category' => $category, 'groupings' => $groupings, 'context' => $context, 'itemid' => $itemid);
$mform = new local_apsolu_courses_categories_edit_form(null, $customdata);

$editor = file_prepare_standard_editor($category, 'description', $mform->get_description_editor_options(), $context, 'coursecat', 'description', $itemid);
$mform->set_data($editor);

if ($data = $mform->get_data()) {
    // Message à afficher à la fin de l'enregistrement.
    $message = get_string('category_updated', 'local_apsolu');
    if (empty($category->id) === true) {
        $message = get_string('category_saved', 'local_apsolu');
    }

    // Save data.
    $category->save($data, $mform);

    // Redirige vers la page générale.
    $returnurl = new moodle_url('/local/apsolu/courses/index.php', array('tab' => 'categories'));
    redirect($returnurl, $message, $delay = null, \core\output\notification::NOTIFY_SUCCESS);
}

// Display form.
$heading = get_string('edit_category', 'local_apsolu');
if (empty($category->id) === true) {
    $heading = get_string('add_category', 'local_apsolu');
}

echo $OUTPUT->heading($heading);
$mform->display();
