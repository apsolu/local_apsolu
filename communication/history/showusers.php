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
 * Liste les utilisateurs notifiés pour une communication donnée.
 *
 * @package    local_apsolu
 * @copyright  2024 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use logstore_standard\log\store as logstore;

defined('MOODLE_INTERNAL') || die;

$id = required_param('id', PARAM_ALPHANUM);

$PAGE->navbar->add(get_string('list_of_receivers', 'local_apsolu'));

$admin = get_admin();
$users = $DB->get_records('user', ['deleted' => 0], $sort = 'lastname,firstname', $fields = 'id,lastname,firstname,email,idnumber');
if (isset($users[$admin->id]) === true) {
    // Hook pour gérer le cas des copies à l'adresse de contact fonctionnel.
    $functionalcontact = get_config('local_apsolu', 'functional_contact');
    if (empty($functionalcontact) === false) {
        $users[$admin->id]->firstname = '';
        $users[$admin->id]->lastname = '';
        $users[$admin->id]->email = $functionalcontact;
    } else {
        unset($users[$admin->id]);
    }
}

$receivers = [];
$countreceivers = 0;

// Note: il n'y a pas d'index sur le champ eventname. Il faut faire le traitement dans la boucle PHP.
$recordset = $DB->get_recordset('logstore_standard_log', ['component' => 'local_apsolu', 'contextlevel' => CONTEXT_SYSTEM]);
foreach ($recordset as $record) {
    if ($record->eventname !== '\local_apsolu\event\communication_sent') {
        continue;
    }

    $other = logstore::decode_other($record->other);
    if (is_string($other) === true) {
        // Ligne pour assurer la rétrocompatibilité, lorsqu'on encodait nous même les données other en JSON.
        $other = json_decode($other);
    }

    if (empty($other) === true) {
        continue;
    }

    if ($id !== $other->communicationid) {
        continue;
    }

    $countreceivers++;
    if (isset($users[$other->receiver]) === false) {
        continue;
    }

    $receivers[] = $users[$other->receiver];
    $countreceivers++;
}
$recordset->close();

$data = new stdClass();
$data->wwwroot = $CFG->wwwroot;
$data->receivers = $receivers;
$data->count_receivers = $countreceivers;

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('list_of_receivers', 'local_apsolu'));
echo $OUTPUT->tabtree($tabtree, $page);
echo $OUTPUT->render_from_template('local_apsolu/communication_history_users', $data);
echo $OUTPUT->footer();
