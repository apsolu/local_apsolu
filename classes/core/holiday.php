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
 * Classe gérant les jours fériés.
 *
 * @package   local_apsolu
 * @copyright 2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_apsolu\core;

/**
 * Classe gérant les jours fériés.
 *
 * @package   local_apsolu
 * @copyright 2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class holiday extends record {
    /**
     * Nom de la table de référence en base de données.
     */
    const TABLENAME = 'apsolu_holidays';

    /** @var int|string Identifiant numérique du jour férié. */
    public $id = 0;

    /** @var string $day Représentation du jour férié au format timestamp Unix. */
    public $day = '';

    /**
     * Affiche une représentation textuelle de l'objet.
     *
     * @return string.
     */
    public function __tostring() {
        return userdate($this->day, get_string('strftimedaydate'));
    }

    /**
     * Retourne la liste des jours fériés pour l'année donnée en argument.
     *
     * @param int|null $year Année souhaitée pour le calcul des jours fériés. Si null, l'année en cours est donnée.
     *
     * @return array Retourne un tableau de timestampp Unix.
     */
    public static function get_holidays(int $year = null) {
        if ($year === null) {
            // Récupère l'année courante.
            $year = strftime('%Y');
        }

        // Récupère la date du dimanche de Pâques.
        $easter = strftime('%d-%m', easter_date($year));
        list($easterday, $eastermonth) = explode('-', $easter);

        $holidays = [];
        $holidays[] = make_timestamp($year, 1, 1); // 1er janvier.
        $holidays[] = make_timestamp($year, $eastermonth, $easterday + 1);  // Lundi de pâques.
        $holidays[] = make_timestamp($year, 5, 1); // Fête du travail.
        $holidays[] = make_timestamp($year, 5, 8); // Victoire des alliés.
        $holidays[] = make_timestamp($year, $eastermonth, $easterday + 39); // Ascension.
        $holidays[] = make_timestamp($year, $eastermonth, $easterday + 50); // Pentecôte.
        $holidays[] = make_timestamp($year, 7, 14); // Fête nationale.
        $holidays[] = make_timestamp($year, 8, 15); // Assomption.
        $holidays[] = make_timestamp($year, 11, 1); // Toussaint.
        $holidays[] = make_timestamp($year, 11, 11); // Armistice.
        $holidays[] = make_timestamp($year, 12, 25); // Noel.

        return $holidays;
    }

    /**
     * Régénère les sessions des cours dont une session est planifiée sur ce jour férié.
     *
     * @return void
     */
    public function regenerate_sessions() {
        global $DB;

        $sql = "SELECT DISTINCT courseid FROM {".attendancesession::TABLENAME."} WHERE sessiontime BETWEEN :startdate AND :enddate";
        $params = ['startdate' => $this->day, 'enddate' => $this->day + 24 * 60 * 60 - 1];
        $sessions = $DB->get_records_sql($sql, $params);

        foreach ($sessions as $session) {
            $course = new course();
            try {
                $course->load($session->courseid, $required = true);
                $course->set_sessions();
            } catch (Exception $exception) {
                debugging(__METHOD__.': le cours '.$session->courseid.' n\'existe pas.', $level = DEBUG_DEVELOPER);
            }
        }
    }
}
