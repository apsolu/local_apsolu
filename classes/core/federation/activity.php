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
 * Classe gérant la correspondance entre le nom des activités FFSU et le nom des activités APSOLU.
 *
 * @package    local_apsolu
 * @copyright  2022 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_apsolu\core\federation;

use local_apsolu\core\record;

/**
 * Classe gérant la correspondance entre le nom des activités FFSU et le nom des activités APSOLU.
 *
 * @package    local_apsolu
 * @copyright  2022 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity extends record {
    /**
     * Nom de la table de référence en base de données.
     */
    const TABLENAME = 'apsolu_federation_activities';

    /** @var int|string $id Identifiant numérique de la correspondance d'activités. */
    public $id = 0;

    /** @var string $name Nom officiel de l'activité sportive côté FFSU. */
    public $name = '';

    /** @var int|string $mainsport Indique si le sport peut-être sélectionné en tant que sport principal (1: oui, 0: non). */
    public $mainsport = '';

    /** @var int|string $restriction Indique si le sport est à contrainte (1: oui, 0: non). */
    public $restriction = '';

    /** @var int|string|null $categoryid Identifiant numérique de la catégorie APSOLU décrivant une activité sportive (table {apsolu_categories}). */
    public $categoryid = null;

    /**
     * Retourne la liste des noms officiels des activités FFSU.
     *
     * @return array
     */
    public static function get_activity_data() {
        $data = array();
        $data[] = ['id' => 2, 'name' => 'Athlétisme - Courses hors stade', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 3, 'name' => 'Aviron (en ligne,longue distance, de mer, indoor)', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 4, 'name' => 'Badminton', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 5, 'name' => 'Baseball - Softball', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 6, 'name' => 'Basket - Basket 3x3', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 7, 'name' => 'Biathlon', 'mainsport' => 1, 'restriction' => 1];
        $data[] = ['id' => 8, 'name' => 'Bowling', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 9, 'name' => 'Boxe éducative Assaut', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 10, 'name' => 'Boxe(s) Combat, plein contact (Anglaise, Kick Boxing K1 rules, Savate BF)', 'mainsport' => 1, 'restriction' => 1];
        $data[] = ['id' => 11, 'name' => 'Bridge', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 12, 'name' => 'Canoë-kayak', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 13, 'name' => 'Cheerleading', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 14, 'name' => 'Course d\'orientation', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 15, 'name' => 'Cyclisme - VTT', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 16, 'name' => 'Danse (toutes formes)', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 17, 'name' => 'Echecs', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 18, 'name' => 'Equitation', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 19, 'name' => 'Escalade', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 20, 'name' => 'Escrime', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 21, 'name' => 'Fitness', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 22, 'name' => 'Football - Futsal', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 23, 'name' => 'Football américain', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 24, 'name' => 'Force Athlétique', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 25, 'name' => 'Golf', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 26, 'name' => 'Gymnastiques : Artistique, GR, Team Gym, Trampoline, Parkour Gym', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 27, 'name' => 'Haltérophilie - Musculation', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 28, 'name' => 'Handball – Beach Handball', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 29, 'name' => 'Hockey', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 30, 'name' => 'Judo - Ju-Jitsu - Ne Waza', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 31, 'name' => 'Karaté', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 32, 'name' => 'Karting', 'mainsport' => 1, 'restriction' => 1];
        $data[] = ['id' => 33, 'name' => 'Kick Boxing- Muay-Thaï Light et Pré combat', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 34, 'name' => 'Lutte- Sambo sportif – Beach Wrestling', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 35, 'name' => 'Nage avec palmes', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 36, 'name' => 'Natation - Natation synchronisée – Natation en eau libre', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 37, 'name' => 'Patinage artistique et de vitesse', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 38, 'name' => 'Pelote basque', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 39, 'name' => 'Pentathlon', 'mainsport' => 1, 'restriction' => 1];
        $data[] = ['id' => 40, 'name' => 'Pétanque', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 41, 'name' => 'Roller hockey', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 42, 'name' => 'Rugby(s) (XV, X, 7, XIII)', 'mainsport' => 1, 'restriction' => 1];
        $data[] = ['id' => 43, 'name' => 'Sauvetage sportif', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 44, 'name' => 'Savate Boxe Française en assaut', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 45, 'name' => 'Skateboard', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 46, 'name' => 'Ski - Snowboard (toutes formes)', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 47, 'name' => 'Squash', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 48, 'name' => 'Surf - Stand Up Paddle', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 49, 'name' => 'Taekwondo', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 50, 'name' => 'Tennis de table', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 51, 'name' => 'Tennis – Padel – Beach Tennis', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 52, 'name' => 'Tir à l\'arc', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 53, 'name' => 'Tir sportif', 'mainsport' => 1, 'restriction' => 1];
        $data[] = ['id' => 54, 'name' => 'Triathlon et Disciplines enchainées : [Bike & Run, Duathlon, Raids multisports, Swimrun]', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 55, 'name' => 'Ultimate – Beach Ultimate', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 56, 'name' => 'Voile - Kite Surf', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 57, 'name' => 'Volley - Beach Volley', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 58, 'name' => 'Water-polo', 'mainsport' => 1, 'restriction' => 0];
        $data[] = ['id' => 59, 'name' => 'Multisports', 'mainsport' => 0, 'restriction' => 0];
        $data[] = ['id' => 60, 'name' => 'E-sport', 'mainsport' => 1, 'restriction' => 0];

        return $data;
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

        // Démarre une transaction, si ce n'est pas déjà fait.
        if ($DB->is_transaction_started() === false) {
            $transaction = $DB->start_delegated_transaction();
        }

        // Réinitialise le champ license de la table apsolu_courses si l'activité FFSU n'est plus associée à une catégorie APSOLU.
        if (empty($this->categoryid) === false) {
            if (isset($data->categoryid) === true && $data->categoryid !== $this->categoryid) {
                $sql = "UPDATE {apsolu_courses} SET license = 0 WHERE id IN (SELECT id FROM {course} WHERE category = :categoryid)";
                $DB->execute($sql, array('categoryid' => $this->categoryid));
            }
        }

        // Enregistre l'objet en base de données.
        parent::save($data, $mform);

        // Valide la transaction en cours.
        if (isset($transaction) === true) {
            $transaction->allow_commit();
        }
    }
}
