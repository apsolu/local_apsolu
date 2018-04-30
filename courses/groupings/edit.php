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

// Get grouping id.
$groupingid = optional_param('groupingid', 0, PARAM_INT);

// Generate object.
$grouping = false;
if ($groupingid != 0) {
    $sql = "SELECT *".
        " FROM {apsolu_courses_groupings} acc".
        " JOIN {course_categories} cc ON  acc.id=cc.id".
        " WHERE cc.id = ?".
        " ORDER BY cc.name, cc.sortorder";
    $grouping = $DB->get_record_sql($sql, array($groupingid));
}

if ($grouping === false) {
    $grouping = new stdClass();
    $grouping->id = 0;
    $grouping->name = '';
    $grouping->url = '';
    $grouping->parent = 0;
}

// Build form.
$customdata = array('grouping' => $grouping);
$mform = new local_apsolu_courses_groupings_edit_form(null, $customdata);

if ($data = $mform->get_data()) {
    // Save data.
    $grouping = new stdClass();
    $grouping->id = $data->groupingid;
    $grouping->url = $data->url;

    if ($grouping->id == 0) {
        require_once($CFG->dirroot.'/lib/coursecatlib.php');

        $grouping = new stdClass();
        $grouping->name = trim($data->name);
        $grouping->parent = 0;
        $coursecat = coursecat::create($grouping);

        $grouping->id = $coursecat->id;
        $grouping->url = $data->url;

        $sql = "INSERT INTO {apsolu_courses_groupings} (id, url) VALUES(?,?)";
        $DB->execute($sql, array($grouping->id, $grouping->url));
    } else {
        $DB->update_record('apsolu_courses_groupings', $grouping);

        $grouping = $DB->get_record('course_categories', array('id' => $grouping->id));
        if ($grouping) {
            $grouping->name = $data->name;
            $grouping->parent = 0;
            $DB->update_record('course_categories', $grouping);
        }
    }

    // Display notification and display elements list.
    $notificationform = $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');

    require(__DIR__.'/view.php');
} else {
    // Display form.
    echo '<h1>'.get_string('grouping_add', 'local_apsolu').'</h1>';

    $mform->display();
}
