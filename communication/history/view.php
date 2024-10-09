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
 * Liste les messages envoyés par le module communication.
 *
 * @package    local_apsolu
 * @copyright  2024 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$templates = $DB->get_records('apsolu_communication_templates');

$messages = [];
$countmessages = 0;
$params = ['component' => 'local_apsolu', 'eventname' => '\local_apsolu\event\communication_sent'];
foreach ($DB->get_records('logstore_standard_log', $params, $sort = 'timecreated DESC') as $record) {
    $other = json_decode(unserialize($record->other));
    if (empty($other) === true) {
        continue;
    }

    $communicationid = $other->communicationid;

    if (isset($messages[$communicationid]) === false) {
        $messages[$communicationid] = new stdClass();
        $messages[$communicationid]->id = $other->communicationid;
        $messages[$communicationid]->subject = '';
        if (isset($templates[$other->template]) === true) {
            $messages[$communicationid]->subject = $templates[$other->template]->subject;
        }
        $messages[$communicationid]->date = userdate($record->timecreated, get_string('strftimedaydatetime'));
        $messages[$communicationid]->countmessages = 0;
    }

    $messages[$communicationid]->countmessages++;
    $countmessages++;
}

$data = new stdClass();
$data->wwwroot = $CFG->wwwroot;
$data->messages = array_values($messages);
$data->count_messages = $countmessages;

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('history', 'local_apsolu'));
echo $OUTPUT->tabtree($tabtree, $page);
echo $OUTPUT->render_from_template('local_apsolu/communication_history', $data);
echo $OUTPUT->footer();