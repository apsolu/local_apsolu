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
 * Affiche le message envoyé aux utilisateurs.
 *
 * @package    local_apsolu
 * @copyright  2024 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\form\communication\template as form;
use logstore_standard\log\store as logstore;

defined('MOODLE_INTERNAL') || die;

$templateid = required_param('templateid', PARAM_INT);
$timestamp = required_param('timestamp', PARAM_INT);

$PAGE->navbar->add(get_string('message_sent', 'local_apsolu'));

$template = $DB->get_record('apsolu_communication_templates', ['id' => $templateid], '*', MUST_EXIST);
$template->template = $templateid; // Pour ne pas avoir le champ 'enregistrer comme un nouveau modèle'.
$template->message = ['format' => FORMAT_HTML, 'text' => $template->body];
$template->notify_functional_contact = $template->functionalcontact;
$filters = json_decode($template->filters);
if ($filters !== false) {
    foreach ($filters as $key => $value) {
        $template->{$key} = $value;
    }
}
unset($template->filters);

// Note: il n'y a pas d'index sur le champ eventname. Il faut faire le traitement dans la boucle PHP.
$params = ['component' => 'local_apsolu', 'contextlevel' => CONTEXT_SYSTEM];
$recordset = $DB->get_recordset('logstore_standard_log', $params, $sort = 'timecreated');
foreach ($recordset as $record) {
    if (in_array($record->eventname, ['\local_apsolu\event\template_created', '\local_apsolu\event\template_updated']) === false) {
        continue;
    }

    if ($record->objectid !== $template->id) {
        // L'évènement ne concerne pas notre template.
        continue;
    }

    if ($record->timecreated > $timestamp) {
        // L'évènement a été créé après l'envoi de notre message. Il n'est plus nécessaire de refaire l'historique du template.
        break;
    }

    // Récupère les anciennes données du template.
    $other = logstore::decode_other($record->other);
    if (is_string($other) === true) {
        // Ligne pour assurer la rétrocompatibilité, lorsqu'on encodait nous même les données other en JSON.
        $other = json_decode($other);
    }

    if (empty($other) === true) {
        continue;
    }

    if (isset($other->subject->new) === true) {
        $template->subject = $other->subject->new;
    }

    if (isset($other->body->new) === true) {
        $template->message = ['format' => FORMAT_HTML, 'text' => $other->body->new];
    }

    if (isset($other->carboncopy->new) === true) {
        $template->carboncopy = $other->carboncopy->new;
    }

    if (isset($other->functionalcontact->new) === true) {
        $template->notify_functional_contact = $other->functionalcontact->new;
    }

    if (isset($other->filters->new) === true) {
        foreach ($other->filters->new as $key => $value) {
            $template->{$key} = $value;
        }
    }
}
$recordset->close();

$recipients = [];
$redirecturl = null;
$mform = new form(null, [$template, $recipients, $redirecturl]);
$mform->freeze_for_review();

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('message_sent', 'local_apsolu'));
echo $OUTPUT->tabtree($tabtree, $page);
$mform->display();
echo $OUTPUT->footer();
