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
 * Classe gérant les présences des sessions de cours.
 *
 * @package    local_apsolu
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_apsolu\core;

/**
 * Classe gérant les présences des sessions de cours.
 *
 * @package    local_apsolu
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attendancepresence extends record {
    /**
     * Nom de la table de référence en base de données.
     */
    const TABLENAME = 'apsolu_attendance_presences';

    /** @var int|string $id Identifiant numérique de la présence. */
    public $id = 0;

    /** @var int|string $studentid Identifiant numérique de l'utilisateur présent. */
    public $studentid = '';

    /** @var int|string $teacherid Identifiant numérique de l'utilisateur ayant pris la présence. */
    public $teacherid = '';

    /** @var int|string $statusid Identifiant numérique du statut de la présence. */
    public $statusid = '';

    /** @var string $description Commentaire autour de la présence. */
    public $description = '';

    /** @var int|string $timecreated Timestamp Unix de création de la présence. */
    public $timecreated = '';

    /** @var int|string $timemodified Timestamp Unix de modification de la présence. */
    public $timemodified = '';

    /** @var int|string $sessionid Identifiant numérique de la session lié à cette présence. */
    public $sessionid = '';
}
