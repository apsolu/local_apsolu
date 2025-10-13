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

namespace local_apsolu\core;

use local_apsolu\core\course;
use stdClass;

/**
 * Classe gérant les niveaux de pratique.
 *
 * @package    local_apsolu
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class skill extends record {
    /**
     * Nom de la table de référence en base de données.
     */
    const TABLENAME = 'apsolu_skills';

    /** @var int|string Identifiant numérique du niveau de pratique. */
    public $id = 0;

    /** @var string $shortname Nom abrégé. */
    public $shortname = '';

    /** @var string $name Nom complet. */
    public $name = '';

    /**
     * Enregistre un objet en base de données.
     *
     * @throws coding_exception A coding exception is thrown for null parameters.
     * @throws dml_exception A DML specific exception is thrown for any errors.
     *
     * @param object|null $data  StdClass représentant l'objet à enregistrer.
     * @param object|null $mform Mform représentant un formulaire Moodle nécessaire à la gestion d'un champ de type editor.
     *
     * @return void
     */
    public function save(?object $data = null, ?object $mform = null) {
        global $DB;

        if ($data !== null) {
            $this->set_vars($data);
        }

        if (empty($this->id) === true) {
            $this->id = $DB->insert_record(get_called_class()::TABLENAME, $this);
        } else {
            $DB->update_record(get_called_class()::TABLENAME, $this);

            // Met à jour le nom complet des créneaux horaires.
            $sql = "SELECT ac.*, c.category
                      FROM {apsolu_courses} ac
                      JOIN {course} c ON c.id = ac.id
                     WHERE ac.skillid = :skillid";
            $courses = $DB->get_records_sql($sql, ['skillid' => $this->id]);
            if (count($courses) > 0) {
                $categories = [];
                $sql = "SELECT cc.*
                          FROM {course_categories} cc
                          JOIN {apsolu_courses_categories} acc ON cc.id = acc.id";
                foreach ($DB->get_records_sql($sql) as $category) {
                    $categories[$category->id] = $category->name;
                }

                foreach ($courses as $course) {
                    $data = new stdClass();
                    $data->str_category = $categories[$course->category];
                    $data->str_skill = $this->name;

                    $moodlecourse = new stdClass();
                    $moodlecourse->id = $course->id;
                    $moodlecourse->fullname = Course::get_fullname(
                        $data->str_category,
                        $course->event,
                        $course->weekday,
                        $course->starttime,
                        $course->endtime,
                        $data->str_skill
                    );
                    $moodlecourse->shortname = Course::get_shortname($course->id, $moodlecourse->fullname);
                    $DB->update_record('course', $moodlecourse);
                }
            }
        }
    }
}
