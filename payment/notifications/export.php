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
 * Page permettant de saisir une relance de paiements.
 *
 * @package    local_apsolu
 * @copyright  2021 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

use UniversiteRennes2\Apsolu\Payment;

require_once($CFG->dirroot . '/local/apsolu/classes/apsolu/payment.php');
require_once($CFG->dirroot . '/local/apsolu/locallib.php');
require_once($CFG->dirroot . '/local/apsolu/payment/notifications/export_form.php');
require_once($CFG->libdir . '/csvlib.class.php');

// Generate object.
$cards = $DB->get_records('apsolu_payments_cards');

// Build form.
$customdata = [$cards];
$mform = new local_apsolu_payment_notifications_export_form(null, $customdata);
$notificationform = '';

if ($data = $mform->get_data()) {
    // Définit les entêtes du fichier csv.
    $headers = [];
    $headers[] = get_string('firstname');
    $headers[] = get_string('lastname');
    $headers[] = get_string('idnumber');
    $headers[] = get_string('email');
    $headers[] = get_string('payment_card', 'local_apsolu');

    // Prépare les données pour l'exportation csv.
    $filename = 'presences_de_cours';

    $csvexport = new csv_export_writer();
    $csvexport->set_filename($filename);

    $csvexport->add_data($headers);

    // Récupère les données.
    $row = null;
    foreach ($cards as $card) {
        $field = 'card' . $card->id;
        if (isset($data->{$field}) === false) {
            continue;
        }

        $users = Payment::get_card_users($card->id);
        foreach ($users as $user) {
            $status = Payment::get_user_card_status($card, $user->id);
            if ($status !== Payment::DUE) {
                continue;
            }

            $row = [];
            $row[] = $user->firstname;
            $row[] = $user->lastname;
            $row[] = $user->idnumber;
            $row[] = $user->email;
            $row[] = $card->fullname;

            $csvexport->add_data($row);
        }
    }

    if (isset($row) !== false) {
        $csvexport->download_file();
        exit();
    }

    // Si le script passe par là, c'est qu'il n'y a aucun "impayé".
    $notificationform = $OUTPUT->notification(get_string('no_pending_payments_found', 'local_apsolu'), 'notifymessage');
}

echo $submenu;
echo $notificationform;
echo $mform->render();
