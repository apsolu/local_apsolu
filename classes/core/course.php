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
 * Classe gérant les cours Apsolu.
 *
 * @package    local_apsolu
 * @copyright  2019 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_apsolu\core;

class course {
    /**
     * Retourne la durée d'un cours en secondes à partir de son heure de début et son heure de fin.
     *
     * @param string $starttime Date de début du cours au format hh:mm.
     * @param string $endtime   Date de fin du cours au format hh:mm.
     *
     * @return int|false Durée en secondes du cours, ou false si une erreur est détectée.
     */
    public static function getDuration(string $starttime, string $endtime) {
        $times = array();
        $times['starttime'] = explode(':', $starttime);
        $times['endtime'] = explode(':', $endtime);

        foreach ($times as $key => $values) {
            if (count($values) !== 2) {
                debugging(__METHOD__.': 2 valeurs attendues pour la variable $'.$key);

                return false;
            }

            if (ctype_digit($values[0]) === false || ctype_digit($values[1]) === false) {
                debugging(__METHOD__.': 2 entiers attendus pour la variable $'.$key);

                return false;
            }

            $times[$key] = $values[0] * 60 * 60 + $values[1] * 60;
        }

        $duration = $times['endtime'] - $times['starttime'];

        if ($duration <= 0) {
            debugging(__METHOD__.': valeur nulle ou négative pour la variable $duration');

            return false;
        }

        return $duration;
    }
}
