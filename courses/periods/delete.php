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
 * Gère la page de suppression d'une période de cours.
 *
 * @package    local_apsolu
 * @copyright  2020 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

use local_apsolu\core\period;

$periodid = required_param('periodid', PARAM_INT);
$delete = optional_param('delete', '', PARAM_ALPHANUM); // Confirmation hash.

$period = new Period();
$period->load($periodid, $required = true);

$deletehash = md5($period->id);
$returnurl = new moodle_url('/local/apsolu/courses/index.php', ['tab' => 'periods']);

if ($delete === $deletehash) {
    // Effectue les actions de suppression.
    require_sesskey();

    $period->delete();

    $message = get_string('period_has_been_deleted', 'local_apsolu');
    redirect($returnurl, $message, $delay = null, \core\output\notification::NOTIFY_SUCCESS);
}

// Vérifie si cette période n'est pas associée à un cours.
$sql = "SELECT c.fullname" .
    " FROM {course} c" .
    " JOIN {apsolu_courses} cc ON c.id = cc.id" .
    " WHERE cc.periodid = :periodid" .
    " ORDER BY c.fullname";
$courses = $DB->get_records_sql($sql, ['periodid' => $period->id]);
if (count($courses) !== 0) {
    $datatemplate = [];
    $datatemplate['message'] = get_string('period_cannot_be_deleted', 'local_apsolu', $period->name);
    $datatemplate['dependences'] = [];
    foreach ($courses as $course) {
        $datatemplate['dependences'][] = $course->fullname;
    }
    $message = $OUTPUT->render_from_template('local_apsolu/courses_form_undeletable_message', $datatemplate);

    redirect($returnurl, $message, $delay = null, \core\output\notification::NOTIFY_WARNING);
}

// Affiche un message de confirmation.
$datatemplate = [];
$datatemplate['message'] = get_string('do_you_want_to_delete_period', 'local_apsolu', $period->name);
$message = $OUTPUT->render_from_template('local_apsolu/courses_form_delete_message', $datatemplate);

// Bouton de validation.
$urlarguments = ['tab' => 'periods', 'action' => 'delete', 'periodid' => $period->id, 'delete' => $deletehash];
$confirmurl = new moodle_url('/local/apsolu/courses/index.php', $urlarguments);
$confirmbutton = new single_button($confirmurl, get_string('delete'), 'post');

// Bouton d'annulation.
$cancelurl = new moodle_url('/local/apsolu/courses/index.php', ['tab' => 'periods']);

echo $OUTPUT->confirm($message, $confirmbutton, $cancelurl);
