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

use core_course_category;

/**
 * Classe gérant les groupements d'activités sportives (sous-catégories de cours Moodle).
 *
 * @package    local_apsolu
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grouping extends record {
    /**
     * Nom de la table de référence en base de données.
     */
    const TABLENAME = 'apsolu_courses_groupings';

    /** @var int|string Identifiant numérique du groupement d'activités sportives. */
    public $id = 0;

    /** @var string $name Nom du groupement d'activités sportives. */
    public $name = '';

    /** @var int $parent Entier représentant l'identifiant du groupement d'activité. */
    public $parent = '';

    /** @var string $url Page web décrivant le groupement d'activités sportives. */
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
     * Recherche et instancie des objets depuis la base de données.
     *
     * @see Se référer à la documentation de la méthode get_records() de la variable globale $DB.
     * @param array|null $conditions Critères de sélection des objets.
     * @param string     $sort       Champs par lesquels s'effectue le tri.
     * @param string     $fields     Liste des champs retournés.
     * @param int        $limitfrom  Retourne les enregistrements à partir de n+$limitfrom.
     * @param int        $limitnum   Nombre total d'enregistrements retournés.
     *
     * @return array Un tableau d'objets instanciés.
     */
    public static function get_records(?array $conditions = null, string $sort = '', string $fields = '*',
                                       int $limitfrom = 0, int $limitnum = 0) {
        global $DB;

        if ($conditions !== null) {
            throw new coding_exception('You cannot define your own value for $conditions argument.');
        }

        if ($fields !== '*') {
            throw new coding_exception('You cannot define your own value for $fields argument.');
        }

        if ($limitfrom !== 0) {
            throw new coding_exception('You cannot define your own value for $limitfrom argument.');
        }

        if ($limitnum !== 0) {
            throw new coding_exception('You cannot define your own value for $limitnum argument.');
        }

        $records = [];

        $sql = "SELECT cc.id, cc.name, cc.parent, acg.url
                  FROM {course_categories} cc
                  JOIN {apsolu_courses_groupings} acg ON acg.id = cc.id";
        if ($sort !== '') {
            $sql .= " ORDER BY ".$sort;
        }

        foreach ($DB->get_records_sql($sql) as $data) {
            $record = new grouping();
            $record->set_vars($data);
            $records[$record->id] = $record;
        }

        return $records;
    }

    /**
     * Charge un objet à partir de son identifiant.
     *
     * @param int|string $recordid Identifiant de l'objet à charger.
     * @param bool       $required Si true, lève une exception lorsque l'objet n'existe pas.
     *                             Valeur par défaut: false (pas d'exception levée).
     *
     * @return void
     */
    public function load($recordid, bool $required = false) {
        global $DB;

        $strictness = IGNORE_MISSING;
        if ($required) {
            $strictness = MUST_EXIST;
        }

        $sql = "SELECT cc.id, cc.name, cc.parent, acg.url".
            " FROM {course_categories} cc".
            " JOIN {apsolu_courses_groupings} acg ON acg.id = cc.id".
            " WHERE acg.id = :recordid";
        $record = $DB->get_record_sql($sql, ['recordid' => $recordid], $strictness);

        if ($record === false) {
            return;
        }

        $this->set_vars($record);
    }

    /**
     * Enregistre un objet en base de données.
     *
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

        // Démarre une transaction, si ce n'est pas déjà fait.
        if ($DB->is_transaction_started() === false) {
            $transaction = $DB->start_delegated_transaction();
        }

        if (empty($this->id) === true) {
            $this->parent = 0;
            $coursecat = core_course_category::create($this);

            $this->id = $coursecat->id;

            // Note: insert_record() exige l'absence d'un id.
            $sql = "INSERT INTO {apsolu_courses_groupings} (id, url) VALUES(:id, :url)";
            $DB->execute($sql, ['id' => $this->id, 'url' => $this->url]);
        } else {
            $DB->update_record(self::TABLENAME, $this);

            $category = $DB->get_record('course_categories', ['id' => $this->id]);
            if ($category !== false) {
                $category->name = $this->name;
                $category->parent = 0;
                $DB->update_record('course_categories', $category);
            }
        }

        // Trie la catégorie parent.
        $category = core_course_category::get((int) $this->parent);
        if ($category->can_resort_subcategories()) {
            \core_course\management\helper::action_category_resort_subcategories($category, $sort = 'name');
        }

        // Valide la transaction en cours.
        if (isset($transaction) === true) {
            $transaction->allow_commit();
        }
    }
}
