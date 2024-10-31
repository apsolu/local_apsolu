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
 * Page pour gérer la suppression d'un calendrier.
 *
 * @package    local_apsolu
 * @copyright  2019 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;


$calendarid = required_param('calendarid', PARAM_INT);
$confirm = optional_param('confirm', '', PARAM_ALPHANUM); // Confirmation hash.

$url = new moodle_url('/local/apsolu/configuration/index.php', ['page' => 'calendars',
    'action' => 'delete', 'calendarid' => $calendarid]);

$calendar = $DB->get_record('apsolu_calendars', ['id' => $calendarid], $fields = '*', MUST_EXIST);

$returnurl = new moodle_url('/local/apsolu/configuration/index.php', ['page' => 'calendars']);
$deletehash = md5($calendar->id);

if ($confirm === $deletehash) {
    // We do - time to delete the course.
    require_sesskey();

    // This might take a while. Raise the execution time limit.
    core_php_time_limit::raise();

    try {
        $transaction = $DB->start_delegated_transaction();

        $DB->delete_records('apsolu_calendars', ['id' => $calendar->id]);

        // Ajoute une trace des changements dans les logs.
        $event = \local_apsolu\event\calendar_deleted::create([
            'objectid' => $calendar->id,
            'context' => context_system::instance(),
        ]);
        $event->trigger();

        $transaction->allow_commit();
    } catch (Exception $exception) {
        // Exécuter $transaction->rollback($exception), implique une redirection vers la homepage de Moodle.
        redirect($returnurl, get_string('an_error_occurred_while_deleting_record', 'local_apsolu'),
            null, \core\output\notification::NOTIFY_ERROR);
    }

    redirect($returnurl, get_string('calendar_deleted', 'local_apsolu'), null, \core\output\notification::NOTIFY_SUCCESS);
} else {
    $sql = "SELECT DISTINCT c.id, c.fullname".
        " FROM {course} c".
        " JOIN {enrol} e ON c.id = e.courseid".
        " WHERE e.enrol = 'select'".
        " AND e.customchar1 = :calendarid".
        " ORDER BY c.fullname";
    $courses = $DB->get_records_sql($sql, ['calendarid' => $calendar->id]);

    $data = new stdClass();
    $data->wwwroot = $CFG->wwwroot;
    $data->calendarname = $calendar->name;
    $data->courses = array_values($courses);
    $data->count_courses = count($courses);

    $message = $OUTPUT->render_from_template('local_apsolu/configuration_calendars_delete', $data);

    $urlarguments = ['page' => 'calendars', 'action' => 'delete', 'calendarid' => $calendarid, 'confirm' => $deletehash];
    $confirmurl = new moodle_url('/local/apsolu/configuration/index.php', $urlarguments);
    $confirmbutton = new single_button($confirmurl, get_string('delete'), 'post');

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('calendars_types', 'local_apsolu'));

    echo $OUTPUT->confirm($message, $confirmbutton, $returnurl);
    echo $OUTPUT->footer();
}
