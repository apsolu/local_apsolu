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
use stdClass;

/**
 * Classe gérant la correspondance entre le nom des activités FFSU et le nom des activités APSOLU.
 *
 * @package    local_apsolu
 * @copyright  2022 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class questionnaire {
    /**
     * Retourne la liste des noms officiels des activités FFSU.
     *
     * @return array
     */
    public static function get_questions() {
        $questions = [];

        $category = new stdClass();
        $category->name = 'Durant les 12 derniers mois';
        $category->questions = [];
        $category->questions[] = (object) ['id' => 'q1', 'label' => 'Un membre de votre famille est-il décédé subitement
            d’une cause cardiaque ou inexpliquée ?'];
        $category->questions[] = (object) ['id' => 'q2', 'label' => 'Avez-vous ressenti une douleur dans la poitrine,
            des palpitations, un essoufflement inhabituel ou un malaise ?'];
        $category->questions[] = (object) ['id' => 'q3', 'label' => 'Avez-vous ressenti des douleurs au niveau de la colonne
            vertébrale (cervicales, dorsales, lombaires) ?'];
        $category->questions[] = (object) ['id' => 'q4', 'label' => 'Avez-vous eu un épisode de respiration sifflante (asthme) ?'];
        $category->questions[] = (object) ['id' => 'q5', 'label' => 'Avez-vous eu une perte de connaissance ?'];
        $category->questions[] = (object) ['id' => 'q6', 'label' => 'Si vous avez arrêté le sport pendant 30 jours ou plus
            pour des raisons de santé, avez-vous repris sans l’accord d’un médecin ?'];
        $category->questions[] = (object) ['id' => 'q7', 'label' => 'Avez-vous été arrêté pour suspicion de commotion cérébrale ou
            pensez-vous avoir subi une commotion cérébrale ?'];
        $category->questions[] = (object) ['id' => 'q8', 'label' => 'Avez-vous débuté un traitement médical de longue durée
            (hors contraception et désensibilisation aux allergies) ?'];
        $questions[] = $category;

        $category = new stdClass();
        $category->name = 'À ce jour';
        $category->questions = [];
        $category->questions[] = (object) ['id' => 'q9', 'label' => 'Un membre de votre famille a-t-il présenté une maladie
            cardiaque grave avant l’âge de 50 ans ? (Arrêt cardiaque, infarctus, stent, pontage, pose défibrillateur, pacemaker).'];
        $category->questions[] = (object) ['id' => 'q10', 'label' => 'Présentez-vous une myopie supérieure à 6 dioptries, une
            fragilisation acquise du globe oculaire ou un œil unique ?'];
        $category->questions[] = (object) ['id' => 'q11', 'label' => 'Ressentez-vous une douleur, un manque de force ou une raideur
            suite à un problème osseux, articulaire ou musculaire (fracture, entorse, luxation, déchirure, tendinite, etc...)
            survenu durant les 12 derniers mois ?'];
        $category->questions[] = (object) ['id' => 'q12', 'label' => 'Votre pratique sportive est-elle interrompue
            pour des raisons de santé ?'];
        $category->questions[] = (object) ['id' => 'q13', 'label' => 'Pensez-vous avoir besoin d’un avis médical pour poursuivre
            votre pratique sportive ?'];
        $questions[] = $category;

        return $questions;
    }
}
