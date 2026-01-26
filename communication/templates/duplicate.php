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
 * Gère la page de duplication d'un modèle de message.
 *
 * @package    local_apsolu
 * @copyright  2024 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$templateid = required_param('id', PARAM_INT);
$duplicate = optional_param('duplicate', '', PARAM_ALPHANUM); // Confirmation hash.

$template = $DB->get_record('apsolu_communication_templates', ['id' => $templateid, 'hidden' => 0], $fields = '*', MUST_EXIST);

$duplicatehash = md5($template->id);
$returnurl = new moodle_url('/local/apsolu/communication/index.php', ['page' => 'templates']);

if ($duplicate === $duplicatehash) {
    // Effectue les actions de duplication.
    require_sesskey();

    unset($template->id);
    $template->id = $DB->insert_record('apsolu_communication_templates', $template);

    // Ajoute une trace des changements dans les logs.
    $other = ['newid' => $template->id];

    $event = \local_apsolu\event\template_duplicated::create([
        'objectid' => $templateid,
        'context' => context_system::instance(),
        'other' => $other,
    ]);

    $event->trigger();

    $message = get_string('template_has_been_duplicated', 'local_apsolu');
    redirect($returnurl, $message, $delay = null, \core\output\notification::NOTIFY_SUCCESS);
}

// Affiche un message de confirmation.
$datatemplate = [];
$datatemplate['message'] = get_string('do_you_want_to_duplicate_template', 'local_apsolu', $template->subject);
$message = $OUTPUT->render_from_template('local_apsolu/courses_form_delete_message', $datatemplate);

// Bouton de validation.
$urlarguments = ['page' => 'templates', 'action' => 'duplicate', 'id' => $template->id, 'duplicate' => $duplicatehash];
$confirmurl = new moodle_url('/local/apsolu/communication/index.php', $urlarguments);
$confirmbutton = new single_button($confirmurl, get_string('duplicate'), 'post');

// Bouton d'annulation.
$cancelurl = new moodle_url('/local/apsolu/communication/index.php', ['page' => 'templates']);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('templates', 'local_apsolu'));
echo $OUTPUT->tabtree($tabtree, $page);
echo $OUTPUT->confirm($message, $confirmbutton, $cancelurl);
echo $OUTPUT->footer();
