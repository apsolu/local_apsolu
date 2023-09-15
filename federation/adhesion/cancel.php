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
 * Gère la page d'annulation et de désinscription à la FFSU.
 *
 * @package    local_apsolu
 * @copyright  2023 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\federation\course as FederationCourse;

require_once(__DIR__.'/../../../../config.php');
require_once($CFG->dirroot.'/enrol/select/lib.php');

$confirm = optional_param('confirm', '', PARAM_ALPHANUM); // Confirmation hash.

$federationcourse = new FederationCourse();
if ($federationcourse->get_course() === false) {
    // Le cours FFSU n'est pas configuré.
    print_error('federation_module_is_not_configured', 'local_apsolu');
}

$context = context_course::instance($federationcourse->get_courseid(), MUST_EXIST);
$PAGE->set_context($context);
$PAGE->set_pagelayout('base');
$PAGE->set_url('/local/apsolu/federation/form/cancel.php');
$PAGE->set_title(get_string('membership_of_the_sports_association', 'local_apsolu'));

require_login($courseorid = null, $autologinguest = false);

// Vérifie que l'utilsateur est bien inscrit au cours.
if (is_enrolled($context, $user = null, $withcapability = '', $onlyactive = true) === false) {
    throw new moodle_exception('you_are_not_enrolled_in_this_course', 'local_apsolu');
}

// Teste qu'une demande de numéro FFSU n'a pas déjà été envoyée.
$adhesion = $DB->get_record('apsolu_federation_adhesions', array('userid' => $USER->id));
if ($adhesion !== false && empty($adhesion->federationnumberrequestdate) === false) {
    throw new moodle_exception('a_license_number_request_is_being_processed', 'local_apsolu');
}

// Navigation.
$url = new moodle_url('/course/view.php', array('id' => $federationcourse->get_courseid()));
$PAGE->navbar->add($federationcourse->get_course()->shortname, $url);
$PAGE->navbar->add(get_string('membership_of_the_sports_association', 'local_apsolu'));

$confirmhash = md5($federationcourse->get_courseid());
$returnurl = new moodle_url('/my');

if ($confirm === $confirmhash) {
    try {
        // Effectue les actions de déinscription.
        require_sesskey();

        // Supprime l'inscription au cours.
        $conditions = array('enrol' => 'select', 'status' => 0, 'courseid' => $federationcourse->get_courseid());
        $instance = $DB->get_record('enrol', $conditions, '*', MUST_EXIST);

        $enrolselectplugin = new enrol_select_plugin();
        $enrolselectplugin->unenrol_user($instance, $USER->id);

        // Supprime les données de l'adhésion FFSU.
        $DB->delete_records('apsolu_federation_adhesions', array('userid' => $USER->id));

        // Supprime les fichiers déposés.
        $context = context_course::instance($federationcourse->get_courseid(), MUST_EXIST);

        $where = "contextid = :contextid
                AND component = :component
                AND filearea = :filearea
                AND userid = :userid";
        $params['contextid'] = $context->id;
        $params['component'] = 'local_apsolu';
        $params['userid'] = $USER->id;

        $fs = get_file_storage();
        foreach (array('parentalauthorization', 'medicalcertificate') as $filearea) {
            $params['filearea'] = $filearea;

            $filerecords = $DB->get_recordset_select('files', $where, $params);
            foreach ($filerecords as $filerecord) {
                $fs->get_file_instance($filerecord)->delete();
            }
            $filerecords->close();
        }
    } catch (Exception $exception) {
        debugging($exception->getMessage(), $level = DEBUG_DEVELOPER);

        throw new moodle_exception('you_are_not_enrolled_in_this_course', 'local_apsolu');
    }

    $message = get_string('the_unenrolment_has_been_done', 'local_apsolu');
    redirect($returnurl, $message, $delay = null, \core\output\notification::NOTIFY_SUCCESS);
}

// Affiche un message de confirmation.
$datatemplate = array();
$datatemplate['message'] = get_string('are_you_sure_you_want_to_cancel_your_federation_registration', 'local_apsolu');
$message = $OUTPUT->render_from_template('local_apsolu/courses_form_delete_message', $datatemplate);

// Bouton de validation.
$confirmurl = new moodle_url('/local/apsolu/federation/adhesion/cancel.php', array('confirm' => $confirmhash));
$confirmbutton = new single_button($confirmurl, get_string('confirm'), 'post');

// Bouton d'annulation.
$cancelurl = new moodle_url('/local/apsolu/federation/adhesion/index.php');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('membership_of_the_sports_association', 'local_apsolu'));
echo $OUTPUT->confirm($message, $confirmbutton, $cancelurl);
echo $OUTPUT->footer();
