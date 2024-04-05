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
 * Classe gérant les rôles.
 *
 * @package   local_apsolu
 * @copyright 2023 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_apsolu\core;

use stdClass;

/**
 * Classe gérant les rôles.
 *
 * @package   local_apsolu
 * @copyright 2023 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class role extends record {
    /**
     * Nom de la table de référence en base de données.
     */
    const TABLENAME = 'apsolu_roles';

    /** @var int|string Identifiant numérique du rôle. */
    public $id = 0;

    /** @var string $name Nom du rôle. */
    public $name;

    /** @var string $shortname Nom abrégé du rôle. */
    public $shortname;

    /** @var string $description Description du rôle. */
    public $description;

    /** @var int|string $sortorder Ordre de tri du rôle. */
    public $sortorder;

    /** @var string $archetype Modèle du rôle. */
    public $archetype;

    /** @var string $color Couleur de l'icône représentant le rôle. */
    public $color = 'gray';

    /** @var string $fontawesomeid Identifiant font awesome de l'icône représentant le rôle. */
    public $fontawesomeid = 'check';

    /** @var string $icon Code HTML représentant l'icône associée au rôle. */
    public $icon;

    /**
     * Retourne le code HTML pour afficher l'icône utilisée pour le rôle.
     *
     * @return string
     */
    public function get_icon() {
        global $OUTPUT;

        $data = new stdClass();
        $data->fontawesomeid = $this->fontawesomeid;
        $data->color = $this->color;
        $data->name = $this->name;

        return $OUTPUT->render_from_template('local_apsolu/role_pix', $data);
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
    public static function get_records(array $conditions = null, string $sort = '', string $fields = '*',
        int $limitfrom = 0, int $limitnum = 0) {
        global $DB;

        $sql = "SELECT r.id, r.name, r.shortname, r.description, r.sortorder, r.archetype, ar.color, ar.fontawesomeid
                  FROM {role} r
             LEFT JOIN {apsolu_roles} ar ON r.id = ar.id
                 WHERE r.archetype = 'student'
                   AND r.id != 5
              ORDER BY sortorder";
        $roles = role_fix_names($DB->get_records_sql($sql));

        $records = [];

        foreach ($roles as $data) {
            $record = new role();
            $record->set_vars($data);
            $record->set_icon();
            $records[$record->id] = $record;
        }

        return $records;
    }

    /**
     * Charge un objet à partir de son identifiant.
     *
     * @param int|string $recordid Identifiant de l'objet à charger.
     * @param bool       $required Si true, lève une exception lorsque l'objet n'existe pas. Aucune exception levée par défaut.
     *
     * @return void
     */
    public function load($recordid, bool $required = false) {
        global $DB;

        $strictness = IGNORE_MISSING;
        if ($required) {
            $strictness = MUST_EXIST;
        }

        $sql = "SELECT r.id, r.name, r.shortname, r.description, r.sortorder, r.archetype, ar.color, ar.fontawesomeid
                  FROM {role} r
             LEFT JOIN {apsolu_roles} ar ON r.id = ar.id
                 WHERE r.archetype = 'student'
                   AND r.id = :roleid";
        $roles = role_fix_names($DB->get_records_sql($sql, ['roleid' => $recordid], $strictness));

        if (count($roles) === 0) {
            return;
        }

        $record = current($roles);
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

        if ($DB->get_record(self::TABLENAME, ['id' => $this->id]) === false) {
            $sql = "INSERT INTO {".self::TABLENAME."} (id, color, fontawesomeid)".
                " VALUES(:id, :color, :fontawesomeid)";
            $parameters = ['id' => $this->id, 'color' => $this->color, 'fontawesomeid' => $this->fontawesomeid];
            $DB->execute($sql, $parameters);
        } else {
            $DB->update_record(self::TABLENAME, $this);
        }
    }

    /**
     * Définit la propriété "icon".
     *
     * @return void
     */
    public function set_icon() {
        $this->icon = $this->get_icon();
    }
}
