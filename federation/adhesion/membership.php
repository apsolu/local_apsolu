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
 * Page listant les activités FFSU.
 *
 * @package    local_apsolu
 * @copyright  2022 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\federation\activity;
use local_apsolu\core\federation\adhesion;

defined('MOODLE_INTERNAL') || die();

require(__DIR__ . '/membership_form.php');

$PAGE->requires->js_call_amd('local_apsolu/federation_adhesion_membership', 'initialise');

// Initialise le formulaire.
$readonly = ($adhesion->can_edit() === false);
$customdata = [$adhesion, $readonly];
$mform = new local_apsolu_federation_membership(null, $customdata);

// Traite les données renvoyées.
if ($data = $mform->get_data()) {
    $message = '';
    $delay = null;
    $messagetype = \core\output\notification::NOTIFY_INFO;

    try {
        $adhesion->save($data);

        $nextstep = APSOLU_PAGE_MEDICAL_CERTIFICATE;
        if ($adhesion->have_to_upload_parental_authorization() === true) {
            $nextstep = APSOLU_PAGE_PARENTAL_AUTHORIZATION;
        }

        $returnurl = new moodle_url('/local/apsolu/federation/adhesion/index.php', ['step' => $nextstep]);
    } catch (dml_exception $exception) {
        // Erreur d'écriture en base de données.
        $message = get_string('an_error_occurred_while_saving_data', 'local_apsolu');
        $returnurl = new moodle_url('/local/apsolu/federation/adhesion/index.php', ['step' => APSOLU_PAGE_MEMBERSHIP]);
        $messagetype = \core\output\notification::NOTIFY_ERROR;
    } catch (Exception $exception) {
        // L'adhesion ne peut plus être modifiée.
        $message = implode(' ', $adhesion::get_contacts());
        $returnurl = new moodle_url('/local/apsolu/federation/adhesion/index.php', ['step' => APSOLU_PAGE_MEMBERSHIP]);
        $messagetype = \core\output\notification::NOTIFY_ERROR;
    }

    redirect($returnurl, $message, $delay, $messagetype);
}

// Affiche le formulaire.
$mform->display();
