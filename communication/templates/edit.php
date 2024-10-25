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
 * Page pour gérer l'édition d'un modèle de message.
 *
 * @package    local_apsolu
 * @copyright  2024 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\form\communication\template as form;

defined('MOODLE_INTERNAL') || die;

$templateid = required_param('id', PARAM_INT);

$template = $DB->get_record('apsolu_communication_templates', ['id' => $templateid, 'hidden' => 0], $fields = '*', MUST_EXIST);

$url = new moodle_url('/local/apsolu/communication/index.php', ['page' => 'templates', 'action' => 'edit', 'id' => $template->id]);

// Build form.
$defaultdata = new stdClass();
$defaultdata->template = $templateid;
$defaultdata->subject = $template->subject;
$defaultdata->message = ['format' => FORMAT_HTML, 'text' => $template->body];
$defaultdata->carboncopy = $template->carboncopy;
$defaultdata->notify_functional_contact = $template->functionalcontact;
$filters = json_decode($template->filters);
if ($filters !== false) {
    foreach ($filters as $key => $value) {
        $defaultdata->{$key} = $value;
    }
}
$recipients = [];
$redirecturl = null;
$mform = new form($url, [$defaultdata, $recipients, $redirecturl]);

if ($data = $mform->get_data()) {
    $filters = ['groupings', 'categories', 'courses', 'teachers', 'enrollists', 'calendars', 'roles', 'cohorts', 'locations'];

    $oldtemplate = clone $template;

    $template->subject = $data->subject;
    $template->body = $data->message['text'];
    $template->carboncopy = intval(isset($data->carboncopy));
    $template->functionalcontact = intval(isset($data->notify_functional_contact));
    $template->filters = [];
    $template->hidden = 0;
    foreach ($filters as $filter) {
        $template->filters[$filter] = '';

        if (isset($data->{$filter}) === true) {
            $template->filters[$filter] = $data->{$filter};
        }
    }

    $template->filters = json_encode($template->filters);

    $DB->update_record('apsolu_communication_templates', $template);

    // Ajoute une trace des changements dans les logs.
    $other = [];
    foreach (get_object_vars($oldtemplate) as $property => $value) {
        if ((string) $template->{$property} === $value) {
            continue;
        }

        $other[$property] = ['old' => $value, 'new' => (string) $template->{$property}];
    }

    $event = \local_apsolu\event\template_updated::create([
        'objectid' => $template->id,
        'context' => context_system::instance(),
        'other' => $other,
    ]);
    $event->trigger();

    // Redirige vers la page générale.
    $message = get_string('template_updated', 'local_apsolu');
    $returnurl = new moodle_url('/local/apsolu/communication/index.php', ['page' => 'templates']);
    redirect($returnurl, $message, $delay = null, \core\output\notification::NOTIFY_SUCCESS);
}

// Display form.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('templates', 'local_apsolu'));
echo $OUTPUT->tabtree($tabtree, $page);
echo $mform->render();
echo $OUTPUT->footer();
