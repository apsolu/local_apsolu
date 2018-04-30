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
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require(__DIR__.'/edit_form.php');
require_once($CFG->dirroot.'/lib/coursecatlib.php');

// Get category id.
$categoryid = optional_param('categoryid', 0, PARAM_INT);

// Generate object.
$category = false;
if ($categoryid != 0) {
    $sql = "SELECT *".
        " FROM {apsolu_courses_categories} s, {course_categories} cc".
        " WHERE s.id=cc.id".
        " AND s.id=?";
    $category = $DB->get_record_sql($sql, array($categoryid));
}

if ($category === false) {
    $category = new stdClass();
    $category->id = 0;
    $category->name = '';
    $category->url = '';
    $category->description = '';
    $category->descriptionformat = 0;
    $category->grouping = 0;
    $category->parent = '';
}

// Categories.
$sql = "SELECT *".
    " FROM {apsolu_courses_groupings} s, {course_categories} cc".
    " WHERE s.id=cc.id".
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
$mform->set_data(file_prepare_standard_editor(
    $category,
    'description',
    $mform->get_description_editor_options(),
    $context,
    'coursecat',
    'description',
    $itemid
));

if ($data = $mform->get_data()) {
    // Save data.
    $category = new stdClass();
    $category->id = $data->categoryid;
    $category->url = $data->url;
    $category->description_editor = $data->description_editor;
    $category->federation = $data->federation;
    $category->parent = $data->parent;

    if ($category->id == 0) {
        $category = new stdClass();
        $category->name = trim($data->name);
        $category->description_editor = $data->description_editor;
        $category->parent = $data->parent;
        $coursecat = coursecat::create($category, $mform->get_description_editor_options());

        $category->id = $coursecat->id;
        $category->url = $data->url;
        $category->federation = $data->federation;

        $sql = "INSERT INTO {apsolu_courses_categories} (id, url, federation) VALUES(?,?,?)";
        $DB->execute($sql, array($category->id, $category->url, $category->federation));
    } else {
        $DB->update_record('apsolu_courses_categories', $category);

        $coursecat = coursecat::get($category->id, MUST_EXIST, true);
        $coursecat->update($data, $mform->get_description_editor_options());
    }

    // Display notification and display elements list.
    $notificationform = $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');

    require(__DIR__.'/view.php');
} else {
    // Display form.
    echo '<h1>'.get_string('category_add', 'local_apsolu').'</h1>';

    $mform->display();
}
