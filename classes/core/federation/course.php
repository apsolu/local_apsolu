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

namespace local_apsolu\core\federation;

use stdClass;

/**
 * Classe gérant le cours FFSU.
 *
 * @package    local_apsolu
 * @copyright  2023 Université Rennes 2
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

        if ($this->course !== null) {
            if ($required === true && $this->course === false) {
                // Lève une exception.
                $courseid = $this->get_courseid();
                $this->course = $DB->get_record('course', ['id' => $courseid], $fields = '*', MUST_EXIST);
            }

            return $this->course;
        }

        $strictness = IGNORE_MISSING;
        if ($required === true) {
            $strictness = MUST_EXIST;
        }

        $courseid = $this->get_courseid();
        $this->course = $DB->get_record('course', ['id' => $courseid], $fields = '*', $strictness);
        return $this->course;
    }

    /**
     * Retourne l'id du cours de FFSU.
     *
     * @return string|false Retourne l'id du cours de FFSU ou false si il n'est pas défini.
     */
    public function get_courseid() {
        if ($this->id !== null) {
            return $this->id;
        }

        $federationcourse = get_config('local_apsolu', 'federation_course');

        if (empty($federationcourse) === true) {
            $this->id = false;
            return $this->id;
        }

        $this->id = $federationcourse;
        return $this->id;
    }

    /**
     * Génère les groupes dans le cours FFSU.
     *
     * @throws dml_exception A DML specific exception is thrown for any errors.
     *
     * @return void
     */
    public function set_groups() {
        global $CFG, $DB;

        $federationcourseid = $this->get_courseid();
        if ($federationcourseid === false) {
            // Le cours FFSU n'est pas défini.
            return;
        }

        require_once($CFG->dirroot . '/group/lib.php');

        $groups = $DB->get_records('groups', ['courseid' => $federationcourseid], $sort = '', $fields = 'name');
        foreach (activity::get_records() as $activity) {
            if (isset($groups[$activity->name]) === true) {
                // Ce groupe existe déjà dans le cours FFSU.
                continue;
            }

            $group = new stdClass();
            $group->name = $activity->name;
            $group->courseid = $federationcourseid;
            $group->timecreated = time();
            $group->timemodified = $group->timecreated;
            groups_create_group($group);
        }
    }
}
