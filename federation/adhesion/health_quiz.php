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

use local_apsolu\core\federation\questionnaire as Questionnaire;

defined('MOODLE_INTERNAL') || die();

$quizstatus = optional_param('quizstatus', null, PARAM_INT);

if ($quizstatus !== null && $quizstatus < 2) {
    $message = '';
    $delay = null;
    $messagetype = \core\output\notification::NOTIFY_INFO;

    $adhesion->questionnairestatus = $quizstatus;

    try {
        $adhesion->save();

        $returnurl = new moodle_url('/local/apsolu/federation/adhesion/index.php', ['step' => APSOLU_PAGE_AGREEMENT]);
    } catch (dml_exception $exception) {
        // Erreur d'écriture en base de données.
        debugging($exception->getMessage(), $level = DEBUG_DEVELOPER);

        $message = get_string('an_error_occurred_while_saving_data', 'local_apsolu');
        $returnurl = new moodle_url('/local/apsolu/federation/adhesion/index.php');
        $messagetype = \core\output\notification::NOTIFY_ERROR;
    } catch (Exception $exception) {
        // L'adhesion ne peut plus être modifiée.
        $message = implode(' ', $adhesion::get_contacts());
        $returnurl = new moodle_url('/local/apsolu/federation/adhesion/index.php');
        $messagetype = \core\output\notification::NOTIFY_ERROR;
    }

    redirect($returnurl, $message, $delay, $messagetype);
}

if ($adhesion->can_edit() === false) {
    $messages = $adhesion::get_contacts();
    echo html_writer::tag('div', implode(' ', $messages), ['class' => 'alert alert-info']);
} else {
    $PAGE->requires->js_call_amd('local_apsolu/federation_adhesion_health_quiz', 'initialise');

    $data = new stdClass();
    $data->categories = Questionnaire::get_questions();
    $data->step = APSOLU_PAGE_HEALTH_QUIZ;
    $data->wwwroot = $CFG->wwwroot;
    $data->behat = defined('BEHAT_SITE_RUNNING') && BEHAT_SITE_RUNNING;
    echo $OUTPUT->render_from_template('local_apsolu/federation_adhesion_health_quiz', $data);
}
