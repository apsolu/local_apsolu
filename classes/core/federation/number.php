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

use local_apsolu\core\record;

/**
 * Classe gérant les numéros d'association.
 *
 * @package    local_apsolu
 * @copyright  2022 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class number extends record {
    /**
     * Nom de la table de référence en base de données.
     */
    const TABLENAME = 'apsolu_federation_numbers';

    /** @var int|string $id Identifiant numérique de la correspondance d'activités. */
    public $id = 0;

    /** @var string $number Numéro de l'association. */
    public $number = '';

    /** @var string $field Champ utilisé pour le critère de recherche. */
    public $field = '';

    /** @var string $value Valeur recherchée. */
    public $value = '';

    /** @var int|string $sortorder Identifiant numérique de la catégorie APSOLU décrivant
                                   une activité sportive (table {apsolu_categories}). */
    public $sortorder = '';

    /**
     * Supprime l'objet en base de données.
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
        $DB->delete_records(get_called_class()::TABLENAME, ['id' => $this->id]);

        // Corrige le champ sortorder des autres objets.
        $sql = "UPDATE {" . self::TABLENAME . "} SET sortorder = sortorder -1 WHERE sortorder > :sortorder";
        $DB->execute($sql, ['sortorder' => $this->sortorder]);

        // Valide la transaction en cours.
        if (isset($transaction) === true) {
            $transaction->allow_commit();
        }

        return true;
    }

    /**
     * Retourne la liste des champs permettant de déterminer le numéro de fédération.
     *
     * @return array.
     */
    public static function get_default_fields() {
        $fields = [];
        $fields['apsoluufr'] = get_string('fields_apsoluufr', 'local_apsolu');
        $fields['department'] = get_string('department');
        $fields['institution'] = get_string('institution');

        return $fields;
    }
}
