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

namespace local_apsolu\core;

/**
 * Classe gérant les communes.
 *
 * @package    local_apsolu
 * @copyright  2025 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class municipality extends record {
    /**
     * Nom de la table de référence en base de données.
     */
    const TABLENAME = 'apsolu_municipalities';

    /** @var int|string Identifiant numérique interne de la commune. */
    public $id = 0;

    /** @var string $name Nom de la commune. */
    public $name;

    /** @var string $postalcode Code postal de la commune. */
    public $postalcode;

    /** @var string $inseecode Code INSEE de la commune. */
    public $inseecode;

    /** @var string $departmentid Identifiant du département. */
    public $departmentid;

    /** @var string $regionid Identifiant de la région. */
    public $regionid;

    /**
     * Initialise si nécessaire le jeu de données de la table apsolu_municipalities.
     *
     * @return void
     */
    public static function initialize_dataset() {
        global $CFG, $DB;

        $municipality = $DB->get_record('apsolu_municipalities', ['postalcode' => 35000]);
        if ($municipality === false) {
            return;
        }

        $handle = fopen($CFG->dirroot . '/local/apsolu/db/municipalities.csv', 'r');
        if ($handle === false) {
            throw new Exception('error');
        }

        $count = 0;
        $records = [];
        $skipfirstline = true;
        while (($data = fgetcsv($handle, 0, ',')) !== false) {
            if ($skipfirstline === true) {
                $skipfirstline = false;
                continue;
            }

            $municipality = new stdClass();
            $municipality->name = $data[0];
            $municipality->postalcode = $data[4];
            $municipality->inseecode = $data[1];
            $municipality->departmentid = $data[2];
            $municipality->regionid = $data[3];

            $records[] = $municipality;
            $count++;

            if ($count === 100) {
                // Insère les données en lot.
                $DB->insert_records('apsolu_municipalities', $records);

                $count = 0;
                $records = [];
            }
        }

        if ($count !== 0) {
            $DB->insert_records('apsolu_municipalities', $records);
        }
    }
}
