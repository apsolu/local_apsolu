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
 * Page permettant de baisser la priorité d'un type de présence.
 *
 * @package    local_apsolu
 * @copyright  2023 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\notification as Notification;
use local_apsolu\core\attendance\status as Status;

defined('MOODLE_INTERNAL') || die();

$statusid = required_param('statusid', PARAM_INT);

$status = new Status();
$status->load($statusid, $required = true);
$count = $DB->count_records('apsolu_attendance_statuses');

if ($status->sortorder == $count) {
    // Vérifie que le type de présence n'est pas le dernier élément.
    Notification::error(get_string('the_attendance_status_X_is_already_the_last_element', 'local_apsolu', $status->longlabel));
} else {
    try {
        $order = $status->sortorder + 1;
        $transaction = $DB->start_delegated_transaction();

        // Positionne l'ordre de tri de notre type de présence à -1, pour éviter les problèmes de contraintes d'unicité.
        $sql = "UPDATE {apsolu_attendance_statuses} SET sortorder = -1 WHERE id = :id";
        $DB->execute($sql, ['id' => $status->id]);

        // Met à jour la position du type de présence situé après notre type de présence édité.
        $sql = "UPDATE {apsolu_attendance_statuses} SET sortorder = sortorder - 1 WHERE sortorder = :sortorder";
        $DB->execute($sql, ['sortorder' => $order]);

        // Modifie la position de notre type de présence.
        $sql = "UPDATE {apsolu_attendance_statuses} SET sortorder = :sortorder WHERE id = :id";
        $DB->execute($sql, ['id' => $status->id, 'sortorder' => $order]);

        $params = ['label' => $status->longlabel, 'order' => $order];
        Notification::success(get_string('the_attendance_status_X_has_been_moved_to_Y_position', 'local_apsolu', $params));

        $transaction->allow_commit();
    } catch (Exception $exception) {
        $transaction->rollback($exception);

        Notification::error(get_string('error', 'error'));
    }
}

require __DIR__.'/attendance_statuses_view.php';
