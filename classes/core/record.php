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
 * Classe faisant le lien entre les classes PHP apsolu et la base de données Moodle.
 *
 * Elle regroupe les méthodes de base communes à tous les objets apsolu.
 *
 * @package    local_apsolu
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_apsolu\core;

/**
 * Classe faisant le lien entre les classes PHP apsolu et la base de données Moodle.
 *
 * Elle regroupe les méthodes de base communes à tous les objets apsolu.
 *
 * @package    local_apsolu
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class record {
    /**
     * Affiche une représentation textuelle de l'objet.
     *
     * @return string
     */
    public function __tostring() {
        return $this->name;
    }

    /**
     * Supprime un objet en base de données.
     *
     * @throws dml_exception A DML specific exception is thrown for any errors.
     *
     * @return bool true.
     */
    public function delete() {
        global $DB;

        // Supprime l'objet en base de données.
        $DB->delete_records(get_called_class()::TABLENAME, array('id' => $this->id));

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
    public static function get_records(array $conditions = null, string $sort = '', string $fields = '*', int $limitfrom = 0, int $limitnum = 0) {
        global $DB;

        $classname = get_called_class();

        $records = array();

        foreach ($DB->get_records($classname::TABLENAME, $conditions, $sort, $fields, $limitfrom, $limitnum) as $data) {
            $record = new $classname();
            $record->set_vars($data);
            $records[$record->id] = $record;
        }

        return $records;
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

        $record = $DB->get_record(get_called_class()::TABLENAME, array('id' => $recordid), $fields = '*', $strictness);

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
    public function save(object $data = null, object $mform = null) {
        global $DB;

        if ($data !== null) {
            $this->set_vars($data);
        }

        if (empty($this->id) === true) {
            $this->id = $DB->insert_record(get_called_class()::TABLENAME, $this);
        } else {
            $DB->update_record(get_called_class()::TABLENAME, $this);
        }
    }

    /**
     * Définis les propriétés de la classe à partir d'un objet.
     *
     * @param object $data stdClass représentant l'objet.
     *
     * @return void
     */
    protected function set_vars(object $data) {
        foreach (get_class_vars(get_called_class()) as $var => $value) {
            if (isset($data->{$var}) === false) {
                continue;
            }

            $this->{$var} = trim($data->{$var});
        }
    }
}
