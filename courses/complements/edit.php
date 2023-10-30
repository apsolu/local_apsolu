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
 * Page pour gérer l'édition d'une activité complémentaire.
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require(__DIR__.'/edit_form.php');

// Get complement id.
$complementid = optional_param('complementid', 0, PARAM_INT);

// Generate object.
$complement = false;
if ($complementid != 0) {
    $sql = "SELECT *".
        " FROM {course} c".
        " JOIN {apsolu_complements} ac ON c.id = ac.id".
        " WHERE ac.id = ?".
        " ORDER BY c.fullname";
    $complement = $DB->get_record_sql($sql, ['id' => $complementid]);
}

if ($complement === false) {
    $complement = new stdClass();
    $complement->id = 0;
    $complement->fullname = '';
    $complement->shortname = '';
    $complement->price = 0;
    $complement->federation = 0;
    $complement->category = 1;
    $complement->summary = '';
}

// Load categories.
$sql = "SELECT id, name".
    " FROM {course_categories} cc".
    " WHERE parent=0".
    " ORDER BY cc.name";
$categories = [];
foreach ($DB->get_records_sql($sql) as $category) {
    $categories[$category->id] = $category->name;
}

// Build form.
$customdata = [$complement, $categories];
$mform = new local_apsolu_courses_complements_edit_form(null, $customdata);

if ($data = $mform->get_data()) {
    // Save data.
    $complement = new stdClass();
    $complement->id = $data->complementid;
    $complement->fullname = $data->fullname;
    $complement->shortname = $data->shortname;
    $complement->price = $data->price;
    $complement->category = $data->category;
    $complement->federation = $data->federation;
    $complement->summaryformat = FORMAT_PLAIN; // Possible values: 0=moodle, 1=HTML, 2=text, 4=markdown.
    $complement->summary = $data->summary;

    if ($complement->id == 0) {
        $course = create_course($complement);
        $complement->id = $course->id;

        $sql = "INSERT INTO {apsolu_complements} (id, price, federation)".
            " VALUES(?,?,?)";
        $params = [$complement->id, $complement->price, $complement->federation];
        $DB->execute($sql, $params);
    } else {
        $DB->update_record('course', $complement);
        $DB->update_record('apsolu_complements', $complement);
    }

    // Display notification and display elements list.
    $notificationform = $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');

    require(__DIR__.'/view.php');
} else {
    // Display form.
    echo '<h1>'.get_string('complement_add', 'local_apsolu').'</h1>';

    $mform->display();
}
