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
 * @copyright  2022 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\federation\activity as Activity;
use local_apsolu\core\federation\adhesion as Adhesion;

defined('MOODLE_INTERNAL') || die();

require __DIR__.'/membership_form.php';

// Prépare les données du formulaire.
$sexes = array('' => '');
foreach (Adhesion::get_sexes() as $id => $label) {
    $sexes[$id] = $label;
}

$disciplines = array('' => '');
foreach (Adhesion::get_disciplines() as $id => $label) {
    $disciplines[$id] = $label;
}

$managertypes = Adhesion::get_manager_types();

$mainsports = array();
foreach (Activity::get_records(array('mainsport' => 1), $sort = 'name') as $record) {
    $mainsports[$record->id] = $record->name;
}

$sportswithconstraints = array();
foreach (Activity::get_records(array('restriction' => 1), $sort = 'name') as $record) {
    $sportswithconstraints[$record->id] = $record->name;
}

if (empty($adhesion->birthday) === true) {
    // Affiche une année proche de la date de naissance de l'étudiant, plutôt que la date du jour.
    $adhesion->birthday = mktime(0, 0, 0, 1, 1, date('Y') - 18);
}

// Initialise le formulaire.
$readonly = ($adhesion->can_edit() === false);
$customdata = array($adhesion, $sexes, $disciplines, $mainsports, $managertypes, $sportswithconstraints, $readonly);
$mform = new local_apsolu_federation_membership(null, $customdata);

// Traite les données renvoyées.
if ($data = $mform->get_data()) {
    $message = '';
    $delay = null;
    $messagetype = \core\output\notification::NOTIFY_INFO;

    try {
        $adhesion->save($data);

        $returnurl = new moodle_url('/local/apsolu/federation/adhesion/index.php', array('step' => '2'));
    } catch (dml_exception $exception) {
        // Erreur d'écriture en base de données.
        $message = get_string('an_error_occurred_while_saving_data', 'local_apsolu');
        $returnurl = new moodle_url('/local/apsolu/federation/adhesion/index.php', array('step' => '1'));
        $messagetype = \core\output\notification::NOTIFY_ERROR;
    } catch (Exception $exception) {
        // L'adhesion ne peut plus être modifiée.
        $message = implode(' ', $adhesion::get_contacts());
        $returnurl = new moodle_url('/local/apsolu/federation/adhesion/index.php', array('step' => '1'));
        $messagetype = \core\output\notification::NOTIFY_ERROR;
    }

    redirect($returnurl, $message, $delay, $messagetype);
}

// Affiche le formulaire.
echo $mform->display();
