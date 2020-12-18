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
 * Classe gérant les éléments d'évaluation.
 *
 * @package    local_apsolu
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_apsolu\core;

/**
 * Classe gérant les éléments d'évaluation.
 *
 * @package    local_apsolu
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gradeitem extends record {
    /**
     * Nom de la table de référence en base de données.
     */
    const TABLENAME = 'apsolu_grade_items';

    /** @var int|string $id Identifiant numérique du paramètre de notation. */
    public $id = 0;

    /** @var string $name Libellé de l'élément de notation. */
    public $name = '';

    /** @var int|string $roleid Identifiant numérique du rôle. */
    public $roleid = '';

    /** @var int|string $teacherid Identifiant numérique du calendrier. */
    public $calendarid = '';

    /** @var int|string $timecreated Timestamp Unix de création du paramètre de notation. */
    public $timecreated = '';

    /** @var int|string $timemodified Timestamp Unix de modification du paramètre de notation. */
    public $timemodified = '';

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

        // On récupère tous les cours utilisant ce calendrier.
        $sql = "SELECT DISTINCT e.courseid FROM {enrol} e WHERE e.enrol = 'select' AND e.customchar1 = :calendarid";
        foreach ($DB->get_records_sql($sql, array('calendarid' => $this->calendarid)) as $enrol) {
            $course = new course();
            $course->load($enrol->courseid, $required = false);

            if ($course->id === 0) {
                continue;
            }

            $course->set_gradebook();
        }

        // Supprime l'objet en base de données.
        $DB->delete_records(get_called_class()::TABLENAME, array('id' => $this->id));

        // Valide la transaction en cours.
        if (isset($transaction) === true) {
            $transaction->allow_commit();
        }

        return true;
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
    public function save(object $data = null, object $mform = null) {
        global $DB;

        if ($data !== null) {
            $this->set_vars($data);
        }

        // Démarre une transaction, si ce n'est pas déjà fait.
        if ($DB->is_transaction_started() === false) {
            $transaction = $DB->start_delegated_transaction();
        }

        // On enregistre l'élément de notation APSOLU.
        if (empty($this->id) === true) {
            $this->id = $DB->insert_record(get_called_class()::TABLENAME, $this);
        } else {
            $DB->update_record(get_called_class()::TABLENAME, $this);
        }

        // On récupère tous les cours utilisant ce calendrier.
        $sql = "SELECT DISTINCT e.courseid FROM {enrol} e WHERE e.enrol = 'select' AND e.customchar1 = :calendarid";
        foreach ($DB->get_records_sql($sql, array('calendarid' => $this->calendarid)) as $enrol) {
            $course = new course();
            $course->load($enrol->courseid, $required = false);

            if ($course->id === 0) {
                continue;
            }

            $course->set_gradebook();
        }

        // Valide la transaction en cours.
        if (isset($transaction) === true) {
            $transaction->allow_commit();
        }
    }
}
