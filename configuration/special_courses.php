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
 * Page d'édition des cours spéciaux.
 *
 * @package    local_apsolu
 * @copyright  2022 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/local/apsolu/configuration/special_courses_form.php');

// Build form.
$attributes = array(
    'collaborative_course',
    'federation_course',
    );

$defaults = new stdClass();
foreach ($attributes as $attribute) {
    $defaults->{$attribute} = get_config('local_apsolu', $attribute);
}

$courses = array(0 => get_string('none'));
$sql = "SELECT c.id, c.fullname FROM {course} c WHERE c.id NOT IN (SELECT ac.id FROM {apsolu_courses} ac) ORDER BY c.fullname";
foreach ($DB->get_records_sql($sql) as $course) {
    $courses[$course->id] = $course->fullname;
}

$customdata = array($defaults, $courses);

$mform = new local_apsolu_special_courses_form(null, $customdata);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('special_courses', 'local_apsolu'));

if ($data = $mform->get_data()) {
    foreach ($attributes as $attribute) {
        if (isset($data->{$attribute}) === false) {
            $data->{$attribute} = '';
        }

        set_config($attribute, $data->{$attribute}, 'local_apsolu');
    }

    echo $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');
}

$mform->display();
echo $OUTPUT->footer();
