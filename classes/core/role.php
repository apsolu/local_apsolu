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

    /** @var string $color Couleur de l'icône représentant le rôle. */
    public $color = 'gray';

    /** @var string $fontawesomeid Identifiant font awesome de l'icône représentant le rôle. */
    public $fontawesomeid = 'check';

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

        if ($DB->get_record(self::TABLENAME, array('id' => $this->id)) === false) {
            $sql = "INSERT INTO {".self::TABLENAME."} (id, color, fontawesomeid)".
                " VALUES(:id, :color, :fontawesomeid)";
            $parameters = array('id' => $this->id, 'color' => $this->color, 'fontawesomeid' => $this->fontawesomeid);
            $DB->execute($sql, $parameters);
        } else {
            $DB->update_record(self::TABLENAME, $this);
        }
    }
}
