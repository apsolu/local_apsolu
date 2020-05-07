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
 * Classe gérant les lieux de pratique.
 *
 * @package    local_apsolu
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_apsolu\core;

class location extends record {
    /** @const Nom de la table en base de données. */
    const TABLENAME = 'apsolu_locations';

    /** @var int|string Identifiant numérique du lieu. */
    public $id = 0;

    /** @var string $name Nom du lieu. */
    public $name = '';

    /** @var string $address Adresse postale du lieu. */
    public $address = '';

    /** @var string $email Adresse email. */
    public $email = '';

    /** @var string $phone Numéro de téléphone. */
    public $phone = '';

    /** @var string $longitude Longitude du lieu. */
    public $longitude = '';

    /** @var string $latitude du lieu. */
    public $latitude = '';

    /** @var boolean $wifi_access Témoin indiquant la présence du WiFi sur le lieu de pratique. */
    public $wifi_access = '';

    /** @var boolean $indoor Témoin indiquant si le lieu est couvert. */
    public $indoor = '';

    /** @var boolean $restricted_access Témoin indiquant si le lieu est à accès restreint. */
    public $restricted_access = '';

    /** @var int|string $areaid Identifiant numérique de la zone géographique. */
    public $areaid = '';

    /** @var int|string $managerid Identifiant numérique du gestionnaire de lieu. */
    public $managerid = '';
}
