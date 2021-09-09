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
 * Classe permettant d'écouter les évènements diffusés par Moodle.
 *
 * @package   local_apsolu
 * @copyright 2021 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_apsolu\observer;

use Exception;
use core\event\course_deleted;
use core\event\course_updated;
use core\notification;
use local_apsolu\core\attendancesession;
use local_apsolu\core\course as apsolu_course;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Classe permettant d'écouter les évènements diffusés par Moodle.
 *
 * @package   local_apsolu
 * @copyright 2021 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course {
    /**
     * Écoute l'évènement course_deleted.
     *
     * @param course_deleted $event Évènement diffusé par Moodle.
     *
     * @return void
     */
    public static function deleted(course_deleted $event) {
        global $DB;

        $context = $event->get_context();

        $course = $DB->get_record(apsolu_course::TABLENAME, array('id' => $context->instanceid));
        if ($course === false) {
            // Ce n'est pas un cours de type APSOLU.
            return;
        }

        $sessions = attendancesession::get_records(array('courseid' => $course->id));
        foreach ($sessions as $session) {
            $session->delete();
        }

        $DB->delete_records(apsolu_course::TABLENAME, array('id' => $course->id));
    }

    /**
     * Écoute l'évènement course_updated.
     *
     * Pour les cours APSOLU, gère lorsque le cours change de catégorie.
     *
     * @param course_updated $event Évènement diffusé par Moodle.
     *
     * @return void
     */
    public static function updated(course_updated $event) {
        global $DB, $OUTPUT;

        $context = $event->get_context();

        $sql = "SELECT c.*, ac.event, ac.weekday, ac.starttime, ac.endtime, ask.name AS skill".
            " FROM {course} c".
            " JOIN {apsolu_courses} ac ON c.id = ac.id".
            " JOIN {apsolu_skills} ask ON ask.id = ac.skillid".
            " WHERE c.id = :courseid";
        $course = $DB->get_record_sql($sql, array('courseid' => $context->instanceid));
        if ($course === false) {
            // Ce n'est pas un cours de type APSOLU.
            return;
        }

        if (isset($event->other['updatedfields']['category']) === false) {
            // La catégorie n'a pas été modifiée.
            return;
        }

        $categoryid = $event->other['updatedfields']['category'];
        $sql = "SELECT cc.id, cc.name".
            " FROM {course_categories} cc".
            " JOIN {apsolu_courses_categories} acc ON cc.id = acc.id".
            " WHERE cc.id = :categoryid";
        $category = $DB->get_record_sql($sql, array('categoryid' => $categoryid));

        if ($category === false) {
            // Le cours n'a pas été déplacé dans une autre catégorie "activité sportive".
            preg_match('/^(.*) [A-Za-z]+ \d\d:\d\d/', $course->fullname, $matches);

            $category = false;
            if (isset($matches[1]) === true) {
                // On essaye de déterminer la catégorie d'origine du cours.
                $sql = "SELECT cc.id, cc.name".
                    " FROM {course_categories} cc".
                    " JOIN {apsolu_courses_categories} acc ON cc.id = acc.id".
                    " WHERE cc.name = :name";
                $category = $DB->get_record_sql($sql, array('name' => $matches[1]));
            }

            if ($category === false) {
                // On prend n'importe quelle catégorie "activité sportive".
                $sql = "SELECT cc.id, cc.name".
                    " FROM {course_categories} cc".
                    " JOIN {apsolu_courses_categories} acc ON cc.id = acc.id";
                $categories = $DB->get_records_sql($sql);
                $category = current($categories);
            }

            $course->category = $category->id;

            $params = new stdClass();
            $params->fullname = $course->fullname;
            $params->category = $category->name;
            $message = get_string('course_has_been_moved_to_because_selected_category_did_not_match_to_grouping_of_sports_activities', 'local_apsolu', $params);
            notification::add($message, notification::WARNING);
        }

        // Recalcule les noms complets et abrégés du cours.
        $fullname = apsolu_course::get_fullname($category->name, $course->event, $course->weekday, $course->starttime, $course->endtime, $course->skill);
        if ($course->fullname !== $fullname) {
            $params = new stdClass();
            $params->oldname = $course->fullname;
            $params->newname = $fullname;
            $message = get_string('course_has_been_renamed_to', 'local_apsolu', $params);
            notification::add($message, notification::WARNING);
        }
        $course->fullname = $fullname;
        $course->shortname = apsolu_course::get_shortname($course->id, $fullname);

        // Enregistre les modifications.
        $DB->update_record('course', $course);
    }
}
