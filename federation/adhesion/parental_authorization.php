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
 * Page permettant le dépôt de l'autorisation parentale pour la FFSU.
 *
 * @package    local_apsolu
 * @copyright  2023 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\federation\activity as Activity;
use local_apsolu\core\federation\adhesion as Adhesion;

defined('MOODLE_INTERNAL') || die();

require __DIR__.'/parental_authorization_form.php';

// Initialise le formulaire.
$readonly = ($adhesion->can_edit() === false);
$customdata = [$adhesion, $course, $context, $readonly];
$mform = new local_apsolu_federation_parental_authorization(null, $customdata);

// Charge les fichiers éventuellement déposés précédemment.
$mdata = new stdClass();
$filemanageroptions = $mform::get_filemanager_options($course, $context);
$fieldname = 'parentalauthorization';
$component = 'local_apsolu';
$filearea = 'parentalauthorization';
$itemid = $USER->id;
file_prepare_standard_filemanager($mdata, $fieldname, $filemanageroptions, $context, $component, $filearea, $itemid);
$mform->set_data($mdata);

// Traite les données renvoyées.
if ($data = $mform->get_data()) {
    $message = '';
    $delay = null;
    $messagetype = \core\output\notification::NOTIFY_INFO;

    try {
        // Enregistre les fichiers en base de données.
        file_postupdate_standard_filemanager($mdata, $fieldname, $filemanageroptions, $context, $component, $filearea, $itemid);

        $adhesion->save($data);

        $returnurl = new moodle_url('/local/apsolu/federation/adhesion/index.php', ['step' => APSOLU_PAGE_MEDICAL_CERTIFICATE]);
    } catch (dml_exception $exception) {
        // Erreur d'écriture en base de données.
        $message = get_string('an_error_occurred_while_saving_data', 'local_apsolu');
        $returnurl = new moodle_url('/local/apsolu/federation/adhesion/index.php', ['step' => APSOLU_PAGE_PARENTAL_AUTHORIZATION]);
        $messagetype = \core\output\notification::NOTIFY_ERROR;
    } catch (Exception $exception) {
        // L'adhesion ne peut plus être modifiée.
        $message = implode(' ', $adhesion::get_contacts());
        $returnurl = new moodle_url('/local/apsolu/federation/adhesion/index.php', ['step' => APSOLU_PAGE_PARENTAL_AUTHORIZATION]);
        $messagetype = \core\output\notification::NOTIFY_ERROR;
    }

    redirect($returnurl, $message, $delay, $messagetype);
}

// Affiche le formulaire.
echo $mform->display();
