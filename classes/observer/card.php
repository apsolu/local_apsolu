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

namespace local_apsolu\observer;

use local_apsolu\event\card_deleted;

/**
 * Classe permettant d'écouter les évènements diffusés par Moodle.
 *
 * @package   local_apsolu
 * @copyright 2024 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class card {
    /**
     * Écoute l'évènement card_deleted.
     *
     * @param card_deleted $event Évènement diffusé par Moodle.
     *
     * @return void
     */
    public static function deleted(card_deleted $event) {
        global $DB;

        // Supprime les références du tarif supprimé dans la table apsolu_payments_cards_cals.
        $DB->delete_records('apsolu_payments_cards_cals', ['cardid' => $event->objectid]);

        // Supprime les références du tarif supprimé dans la table apsolu_payments_cards_cohort.
        $DB->delete_records('apsolu_payments_cards_cohort', ['cardid' => $event->objectid]);

        // Supprime les références du tarif supprimé dans la table apsolu_payments_cards_roles.
        $DB->delete_records('apsolu_payments_cards_roles', ['cardid' => $event->objectid]);
    }
}
