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
 * Classe gérant les types de présences.
 *
 * @package    local_apsolu
 * @copyright  2023 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_apsolu\core\attendance;

use local_apsolu\core\record;

/**
 * Classe gérant les types de présences.
 *
 * @package    local_apsolu
 * @copyright  2023 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class status extends record {
    /**
     * Nom de la table de référence en base de données.
     */
    const TABLENAME = 'apsolu_attendance_statuses';

    /** @var int|string Identifiant numérique du type de présence. */
    public $id = 0;

    /** @var string $shortlabel Libellé court. */
    public $shortlabel = '';

    /** @var string $longlabel Libellé long. */
    public $longlabel = '';

    /** @var string $sumlabel Libellé des totaux. */
    public $sumlabel = '';

    /** @var string $color Identifiant de couleur Boostrap (valeurs possibles : success, warning, info et danger). */
    public $color = '';

    /** @var int|string $sortorder Index de tri. */
    public $sortorder = 0;

    /**
     * Affiche une représentation textuelle de l'objet.
     *
     * @return string
     */
    public function __toString() {
        return $this->longlabel;
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

        // Démarre une transaction, si ce n'est pas déjà fait.
        if ($DB->is_transaction_started() === false) {
            $transaction = $DB->start_delegated_transaction();
        }

        // Supprime toutes les présences utilisant ce statut.
        $sql = "DELETE FROM {apsolu_attendance_presences} WHERE statusid = :statusid";
        $DB->execute($sql, ['statusid' => $this->id]);

        // Supprime l'objet en base de données.
        $DB->delete_records(self::TABLENAME, ['id' => $this->id]);

        // Trie les champs.
        $sql = "UPDATE {apsolu_attendance_statuses} SET sortorder = sortorder - 1 WHERE sortorder > :sortorder";
        $DB->execute($sql, ['sortorder' => $this->sortorder]);

        // Valide la transaction en cours.
        if (isset($transaction) === true) {
            $transaction->allow_commit();
        }

        return true;
    }

    /**
     * Génère les motifs de présence par défaut (présent, en retard, dispensé et absent).
     *
     * @return void
     */
    public static function generate_default_values() {
        global $DB;

        $existingrecords = $DB->get_records('apsolu_attendance_statuses', $conditions = null, $sort = '', $fields = 'shortlabel');

        $statuses = [];
        $statuses['attendance_present'] = 'success';
        $statuses['attendance_late'] = 'warning';
        $statuses['attendance_excused'] = 'info';
        $statuses['attendance_absent'] = 'danger';

        $sortorder = 1;
        foreach ($statuses as $code => $color) {
            $data = [];
            $data['shortlabel'] = get_string(sprintf('%s_short', $code), 'local_apsolu');
            $data['longlabel'] = get_string($code, 'local_apsolu');
            $data['sumlabel'] = get_string(sprintf('%s_total', $code), 'local_apsolu');
            $data['color'] = $color;
            $data['sortorder'] = $sortorder;

            if (isset($existingrecords[$data['shortlabel']]) === true) {
                // Empêche la création de doublons.
                continue;
            }

            $sql = "INSERT INTO {apsolu_attendance_statuses} (shortlabel, longlabel, sumlabel, color, sortorder)" .
                " VALUES(:shortlabel, :longlabel, :sumlabel, :color, :sortorder)";
            $DB->execute($sql, $data);

            $sortorder++;
        }
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

        // TODO: implémenter le tri via l'interface web.
        if (empty($this->sortorder) === true) {
            $countrecords = $DB->count_records(self::TABLENAME);
            $this->sortorder = $countrecords + 1;
        }

        parent::save($data, $mform);
    }
}
