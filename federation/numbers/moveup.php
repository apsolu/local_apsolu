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
 * Page permettant de monter la priorité d'un numéro d'association.
 *
 * @package    local_apsolu
 * @copyright  2023 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\notification;
use local_apsolu\core\federation\number;

defined('MOODLE_INTERNAL') || die();

$numberid = required_param('numberid', PARAM_INT);

$number = new Number();
$number->load($numberid, $required = true);
$count = $DB->count_records('apsolu_federation_numbers');

if ($number->sortorder === '0') {
    // Vérifie que le numéro d'association n'est pas le premier élément.
    Notification::error(get_string('the_association_number_X_is_already_the_first_element', 'local_apsolu', $number->number));
} else {
    try {
        $order = $number->sortorder - 1;
        $transaction = $DB->start_delegated_transaction();

        // Positionne l'ordre de tri de notre numéro d'association à -1, pour éviter les problèmes de contraintes d'unicité.
        $sql = "UPDATE {apsolu_federation_numbers} SET sortorder = -1 WHERE id = :id";
        $DB->execute($sql, ['id' => $number->id]);

        // Met à jour la position du numéro d'association situé avant notre numéro d'association édité.
        $sql = "UPDATE {apsolu_federation_numbers} SET sortorder = sortorder + 1 WHERE sortorder = :sortorder";
        $DB->execute($sql, ['sortorder' => $order]);

        // Modifie la position de notre numéro d'association.
        $sql = "UPDATE {apsolu_federation_numbers} SET sortorder = :sortorder WHERE id = :id";
        $DB->execute($sql, ['id' => $number->id, 'sortorder' => $order]);

        $params = ['number' => $number->number, 'order' => $order + 1];
        Notification::success(get_string('the_association_number_X_has_been_moved_to_Y_position', 'local_apsolu', $params));

        $transaction->allow_commit();
    } catch (Exception $exception) {
        $transaction->rollback($exception);

        Notification::error(get_string('error', 'error'));
    }
}

require(__DIR__ . '/view.php');
