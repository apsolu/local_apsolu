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
 * @copyright  2022 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\federation;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/apsolu/configuration/special_courses_form.php');
require_once($CFG->dirroot . '/group/lib.php');

// Build form.
$attributes = [
    'collaborative_course',
    'federation_course',
    ];

$defaults = new stdClass();
foreach ($attributes as $attribute) {
    $defaults->{$attribute} = get_config('local_apsolu', $attribute);
}

$collaborativecourses = [0 => get_string('none')];
$sql = "SELECT c.id, c.fullname
          FROM {course} c
         WHERE c.id NOT IN (SELECT ac.id FROM {apsolu_courses} ac)
           AND c.id != :siteid
      ORDER BY c.fullname";
foreach ($DB->get_records_sql($sql, ['siteid' => SITEID]) as $course) {
    $collaborativecourses[$course->id] = $course->fullname;
}

$federationcourses = [0 => get_string('none')];
$sql = "SELECT c.id, c.fullname
          FROM {course} c
          JOIN {enrol} e ON c.id = e.courseid
         WHERE c.id NOT IN (SELECT ac.id FROM {apsolu_courses} ac)
           AND e.enrol = 'select'
      ORDER BY c.fullname";
foreach ($DB->get_records_sql($sql) as $course) {
    $federationcourses[$course->id] = $course->fullname;
}

$customdata = [$defaults, $collaborativecourses, $federationcourses];

$mform = new local_apsolu_special_courses_form(null, $customdata);

if ($data = $mform->get_data()) {
    foreach ($attributes as $attribute) {
        if (isset($data->{$attribute}) === false) {
            $data->{$attribute} = '';
        }

        if ($data->{$attribute} != $defaults->{$attribute}) {
            // La valeur a été modifiée.
            add_to_config_log($attribute, $defaults->{$attribute}, $data->{$attribute}, 'local_apsolu');
        }

        set_config($attribute, $data->{$attribute}, 'local_apsolu');

        if ($attribute === 'federation_course') {
            if (empty($data->{$attribute}) === true) {
                // Supprime la référence au cours FFSU.
                $DB->delete_records('apsolu_complements', ['federation' => 1]);
            } else {
                // Met à jour la référence au cours FFSU.
                $complement = $DB->get_record('apsolu_complements', ['federation' => 1]);
                if ($complement === false) {
                    $sql = "INSERT INTO {apsolu_complements} (id, price, federation) VALUES(:id, 0, 1)";
                    $DB->execute($sql, ['id' => $data->{$attribute}]);
                } else {
                    $sql = "UPDATE {apsolu_complements} SET id = :id WHERE federation = 1";
                    $DB->execute($sql, ['id' => $data->{$attribute}]);
                }

                // Génère les groupes correspondant aux activités FFSU.
                $course = new Federation\course();
                $course->set_groups();
            }
        }
    }

    $returnurl = new moodle_url('/local/apsolu/configuration/index.php', ['page' => 'specialcourses']);
    redirect($returnurl, $message = get_string('changessaved'), $delay = null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('special_courses', 'local_apsolu'));
$mform->display();
echo $OUTPUT->footer();
