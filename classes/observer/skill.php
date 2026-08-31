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

namespace local_apsolu\observer;

use local_apsolu\core\course;
use local_apsolu\event\skill_updated;
use stdClass;

/**
 * Classe permettant d'écouter les évènements diffusés par Moodle.
 *
 * @package   local_apsolu
 * @copyright 2026 Université Rennes 2
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class skill {
    /**
     * Écoute l'évènement skill_updated.
     *
     * @param skill_updated $event Évènement diffusé par Moodle.
     *
     * @return void
     */
    public static function updated(skill_updated $event) {
        global $DB;

        $skillid = $event->objectid;

        // On met à jour tous les libellés des cours utilisant ce niveau de pratique.
        foreach (Course::get_records() as $course) {
            if (isset($course->customfields['skill']) === false) {
                // Le cours n'utilise pas de niveau de pratique.
                continue;
            }

            if ($course->customfields['skill']->get_value() != $skillid) {
                // Le cours n'utilise pas le niveau de pratique modifié dans cet évènement.
                continue;
            }

            $data = $course->get_course_data();
            $fullname = Course::get_fullname($data);

            if ($course->fullname === $fullname) {
                // Le nom du cours reste identique.
                continue;
            }

            $record = new stdClass();
            $record->id = $course->id;
            $record->fullname = $fullname;
            $record->shortname = Course::get_shortname($course->id, $fullname);
            $DB->update_record('course', $record);

            Course::purge_cache($course->id);
        }
    }
}
