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
 * Classe gérant le cours FFSU.
 *
 * @package    local_apsolu
 * @copyright  2023 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_apsolu\core\federation;

/**
 * Classe gérant le cours FFSU.
 *
 * @package    local_apsolu
 * @copyright  2023 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course {
    /** @var string|false $id Identifiant numérique du cours FFSU. */
    public $id = null;

    /** @var stdClass $course Objet représentant le cours de FFSU. */
    public $course = null;

    /**
     * Retourne l'objet représentant le cours de FFSU.
     *
     * @throws dml_exception A DML specific exception is thrown for any errors.
     *
     * @param bool $required Témoin indiquant si le cours doit exister (sinon, lève une exception)
     *
     * @return stdclass|false Retourne l'objet du cours de FFSU ou false si il n'est pas défini.
     */
    public function get_course($required = false) {
        global $DB;

        if ($course !== null) {
            if ($required === true && $course === false) {
                // Lève une exception.
                $courseid = $this->get_courseid();
                $this->course = $DB->get_record('course', array('id' => $courseid), $fields = '*', MUST_EXIST);
            }

            return $course;
        }

        $strictness = IGNORE_MISSING;
        if ($required === true) {
            $strictness = MUST_EXIST;
        }

        $courseid = $this->get_courseid();
        $this->course = $DB->get_record('course', array('id' => $courseid), $fields = '*', $strictness);
        return $this->course;
    }

    /**
     * Retourne l'id du cours de FFSU.
     *
     * @return string|false Retourne l'id du cours de FFSU ou false si il n'est pas défini.
     */
    public function get_courseid() {
        if ($id !== null) {
            return $id;
        }

        $federation_course = get_config('local_apsolu', 'federation_course');

        if (empty($federation_course) === true) {
            $this->id = false;
            return $this->id;
        }

        $this->id = $federation_course;
        return $this->id;
    }
}
