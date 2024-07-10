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
 * Classe gérant les activités sportives (catégories de cours Moodle).
 *
 * @package    local_apsolu
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_apsolu\core;

use coding_exception;
use core_course_category;
use local_apsolu\core\course as Course;
use local_apsolu\core\federation\activity as Activity;
use stdClass;

/**
 * Classe gérant les activités sportives (catégories de cours Moodle).
 *
 * @package    local_apsolu
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\AllowDynamicProperties]
class category extends record {
    /**
     * Nom de la table de référence en base de données.
     */
    const TABLENAME = 'apsolu_courses_categories';

    /** @var int|string Identifiant numérique de l'activité sportive. */
    public $id = 0;

    /** @var string $name Nom de l'activité sportive. */
    public $name = '';

    /** @var string $description Description de l'activité sportive. */
    public $description = '';

    /** @var int $descriptionformat Format de la description de l'activité sportive. */
    public $descriptionformat = 0;

    /** @var int $parent Entier représentant l'identifiant du groupement d'activité. */
    public $parent = '';

    /** @var string $url Page web décrivant l'activité sportive. */
    public $url = '';

    /**
     * Supprime un objet en base de données.
     *
     * @throws dml_exception A DML specific exception is thrown for any errors.
     *
     * @return bool true.
     */
    public function delete() {
        global $DB;

        // Démarre une transaction, si ce n'est pas déjà fait.
        if ($DB->is_transaction_started() === false) {
            $transaction = $DB->start_delegated_transaction();
        }

        // Supprime une éventuelle association dans la table des activités FFSU.
        $records = Activity::get_records(['categoryid' => $this->id]);
        foreach ($records as $record) {
            $record->categoryid = 0;
            $record->save();
        }

        // Supprime l'objet en base de données.
        $DB->delete_records(self::TABLENAME, ['id' => $this->id]);

        $coursecat = core_course_category::get($this->id, MUST_EXIST, true);
        $coursecat->delete_full();

        // Valide la transaction en cours.
        if (isset($transaction) === true) {
            $transaction->allow_commit();
        }

        return true;
    }

    /**
     * Charge un objet à partir de son identifiant.
     *
     * @param int|string $recordid Identifiant de l'objet à charger.
     * @param bool       $required Si true, lève une exception lorsque l'objet n'existe pas. Valeur par défaut: false (pas d'exception levée).
     *
     * @return void
     */
    public function load($recordid, bool $required = false) {
        global $DB;

        $strictness = IGNORE_MISSING;
        if ($required) {
            $strictness = MUST_EXIST;
        }

        $sql = "SELECT cc.id, cc.name, cc.description, cc.descriptionformat, cc.parent, acc.url".
            " FROM {course_categories} cc".
            " JOIN {apsolu_courses_categories} acc ON acc.id = cc.id".
            " WHERE acc.id = :recordid";
        $record = $DB->get_record_sql($sql, ['recordid' => $recordid], $strictness);

        if ($record === false) {
            return;
        }

        $this->set_vars($record);
    }

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
    public function save(object $data = null, object $mform = null) {
        global $DB;

        if ($data === null) {
            throw new coding_exception('$data parameter cannot be null for '.__METHOD__.'.');
        }

        if ($mform === null) {
            throw new coding_exception('$mform parameter cannot be null for '.__METHOD__.'.');
        }

        $this->set_vars($data);
        $this->description_editor = $data->description_editor;

        // Démarre une transaction, si ce n'est pas déjà fait.
        if ($DB->is_transaction_started() === false) {
            $transaction = $DB->start_delegated_transaction();
        }

        if (empty($this->id) === true) {
            $coursecat = core_course_category::create($this, $mform->get_description_editor_options());

            $this->id = $coursecat->id;

            // Note: insert_record() exige l'absence d'un id.
            $sql = "INSERT INTO {apsolu_courses_categories} (id, url) VALUES(:id, :url)";
            $DB->execute($sql, ['id' => $this->id, 'url' => $this->url]);
        } else {
            $DB->update_record(self::TABLENAME, $this);

            $coursecat = core_course_category::get($this->id, MUST_EXIST, true);
            $coursecat->update($data, $mform->get_description_editor_options());

            // Met à jour le nom complet des créneaux horaires.
            $sql = "SELECT ac.*
                      FROM {apsolu_courses} ac
                      JOIN {course} c ON c.id = ac.id
                     WHERE c.category = :category";
            $courses = $DB->get_records_sql($sql, ['category' => $this->id]);
            if (count($courses) > 0) {
                $skills = [];
                foreach ($DB->get_records('apsolu_skills', $conditions = null, $sort = 'name') as $skill) {
                    $skills[$skill->id] = $skill->name;
                }

                foreach ($courses as $course) {
                    $data = new stdClass();
                    $data->str_category = $this->name;
                    $data->str_skill = $skills[$course->skillid];

                    $moodlecourse = new stdClass();
                    $moodlecourse->id = $course->id;
                    $moodlecourse->fullname = Course::get_fullname($data->str_category, $course->event, $course->weekday,
                        $course->starttime, $course->endtime, $data->str_skill);
                    $moodlecourse->shortname = Course::get_shortname($course->id, $moodlecourse->fullname);
                    $DB->update_record('course', $moodlecourse);
                }
            }
        }

        // Valide la transaction en cours.
        if (isset($transaction) === true) {
            $transaction->allow_commit();
        }
    }
}
