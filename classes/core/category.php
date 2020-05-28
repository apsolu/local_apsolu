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

/**
 * Classe gérant les activités sportives (catégories de cours Moodle).
 *
 * @package    local_apsolu
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
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

    /** @var boolean $federation Indique si cette activité sportive est représentée à la FFSU. */
    public $federation = '';

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

        // Supprime l'objet en base de données.
        $DB->delete_records('apsolu_courses_categories', array('id' => $this->id));

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

        $sql = "SELECT cc.id, cc.name, cc.description, cc.descriptionformat, cc.parent, acc.url, acc.federation".
            " FROM {course_categories} cc".
            " JOIN {apsolu_courses_categories} acc ON acc.id = cc.id".
            " WHERE acc.id = :recordid";
        $record = $DB->get_record_sql($sql, array('recordid' => $recordid), $strictness);

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
            $sql = "INSERT INTO {apsolu_courses_categories} (id, url, federation) VALUES(:id, :url, :federation)";
            $DB->execute($sql, array('id' => $this->id, 'url' => $this->url, 'federation' => $this->federation));
        } else {
            $DB->update_record('apsolu_courses_categories', $this);

            $coursecat = core_course_category::get($this->id, MUST_EXIST, true);
            $coursecat->update($data, $mform->get_description_editor_options());
        }

        // Valide la transaction en cours.
        if (isset($transaction) === true) {
            $transaction->allow_commit();
        }
    }
}
