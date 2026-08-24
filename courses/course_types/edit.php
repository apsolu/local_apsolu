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
 * Page pour gérer l'édition d'un type de format.
 *
 * @package   local_apsolu
 * @copyright 2026 Université Rennes 2
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\coursetype;
use local_apsolu\form\courses\course_type\edit as EditForm;

defined('MOODLE_INTERNAL') || die;

// Get coursetype id.
$coursetypeid = optional_param('coursetypeid', 0, PARAM_INT);

// Generate object.
$coursetype = new CourseType();
if ($coursetypeid !== 0) {
    $coursetype->load($coursetypeid);
}

$coursetype->fields = [];
if (empty($coursetype->id) === false) {
    $sql = "SELECT acf.id, acf.customfieldid, acf.showinadministration, acf.showonpublicpages, cf.shortname
              FROM {apsolu_courses_fields} acf
              JOIN {customfield_field} cf ON cf.id = acf.customfieldid
             WHERE acf.coursetypeid = :coursetypeid";
    foreach ($DB->get_records_sql($sql, ['coursetypeid' => $coursetype->id]) as $field) {
        $coursetype->fields[$field->shortname]['fieldid'] = $field->customfieldid;
        $coursetype->fields[$field->shortname]['admin'] = $field->showinadministration;
        $coursetype->fields[$field->shortname]['public'] = $field->showonpublicpages;
    }
}

$PAGE->set_url('/local/apsolu/courses/index.php', ['tab' => 'course_types', 'action' => 'edit', 'coursetypeid' => $coursetypeid]);

// Build form.
$customdata = ['coursetype' => $coursetype];
$mform = new EditForm($PAGE->url->out(false), $customdata);

if ($data = $mform->get_data()) {
    // Message à afficher à la fin de l'enregistrement.
    $message = get_string('course_type_updated', 'local_apsolu');
    if (empty($coursetype->id) === true) {
        $message = get_string('course_type_saved', 'local_apsolu');
    }

    // Save data.
    $coursetype->save($data);

    // Redirige vers la page générale.
    $returnurl = new moodle_url('/local/apsolu/courses/index.php', ['tab' => 'course_types']);
    redirect($returnurl, $message, $delay = null, \core\output\notification::NOTIFY_SUCCESS);
}

// Display form.
$PAGE->requires->js_call_amd('local_apsolu/colorpicker', 'initialise');

$heading = get_string('edit_course_type', 'local_apsolu');
if (empty($coursetype->id) === true) {
    $heading = get_string('add_course_type', 'local_apsolu');
}

echo $OUTPUT->heading($heading);
$mform->display();
