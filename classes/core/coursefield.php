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
 * Classe gérant les champs de cours.
 *
 * @package   local_apsolu
 * @copyright 2026 Université Rennes 2
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class coursefield extends record {
    /**
     * Nom de la table de référence en base de données.
     */
    const TABLENAME = 'apsolu_courses_fields';

    /** @var int|string Identifiant numérique du champ de cours. */
    public $id = 0;

    /** @var int|string $coursetypeid Identifiant du type de cours. */
    public $coursetypeid;

    /** @var int|string $customfieldid Identifiant du champ personnalisé. */
    public $customfieldid;

    /** @var int|string $showinadministration Indique si le champ doit être affiché dans les colonnes de l'administration. */
    public $showinadministration;

    /** @var int|string $showonpublicpages Indique si le champ doit être affiché dans les colonnes des pages publiques. */
    public $showonpublicpages;
}
