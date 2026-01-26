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
 * Page de suppression des tarifs.
 *
 * @package    local_apsolu
 * @copyright  2024 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

// Get card id.
$cardid = required_param('cardid', PARAM_INT);
$delete = optional_param('delete', '', PARAM_ALPHANUM); // Confirmation hash.

$instance = $DB->get_record('apsolu_payments_cards', ['id' => $cardid], $fields = '*', MUST_EXIST);

$deletehash = md5($instance->id);
$returnurl = new moodle_url('/local/apsolu/payment/admin.php', ['tab' => 'prices']);

// Contrôle que le template qu'aucun utilisateur n'a réglé ce tarif.
$countrecords = $DB->count_records('apsolu_payments_items', ['cardid' => $instance->id]);
if ($countrecords > 0) {
    throw new moodle_exception(
        'this_card_cannot_be_deleted_as_it_has_recently_used_in_X_transactions',
        'local_apsolu',
        $returnurl,
        $param = $countrecords
    );
}

if ($delete === $deletehash) {
    // Effectue les actions de suppression.
    require_sesskey();

    $DB->delete_records('apsolu_payments_cards', ['id' => $instance->id]);

    // Ajoute une trace des changements dans les logs.
    $event = \local_apsolu\event\card_deleted::create([
        'objectid' => $instance->id,
        'context' => context_system::instance(),
    ]);
    $event->trigger();

    $message = get_string('card_has_been_deleted', 'local_apsolu');
    redirect($returnurl, $message, $delay = null, \core\output\notification::NOTIFY_SUCCESS);
}

// Affiche un message de confirmation.
$message = '';
$countrecords = $DB->count_records('enrol_select_cards', ['cardid' => $instance->id]);
if ($countrecords > 0) {
    // Ajoute un avertissement si la carte est utilisée dans une méthode d'inscription.
    $message = html_writer::div(
        get_string('warning_this_card_is_currently_used_in_X_enrolments', 'local_apsolu', $countrecords),
        'alert alert-warning'
    );
}

$datatemplate = [];
$datatemplate['message'] = get_string('do_you_want_to_delete_card', 'local_apsolu', $instance->name);
$message .= $OUTPUT->render_from_template('local_apsolu/courses_form_delete_message', $datatemplate);

// Bouton de validation.
$urlarguments = ['tab' => 'prices', 'action' => 'delete', 'cardid' => $instance->id, 'delete' => $deletehash];
$confirmurl = new moodle_url('/local/apsolu/payment/admin.php', $urlarguments);
$confirmbutton = new single_button($confirmurl, get_string('delete'), 'post');

echo $OUTPUT->confirm($message, $confirmbutton, $returnurl);
