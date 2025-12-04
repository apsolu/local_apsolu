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

namespace local_apsolu\form\attendance\admin;

use html_writer;
use moodleform;
use stdClass;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

/**
 * Classe pour le formulaire permettant d'éditer le paramétrage des QR codes.
 *
 * @package    local_apsolu
 * @copyright  2025 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qrcode extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    protected function definition() {
        global $CFG;

        $mform = $this->_form;
        [$default, $statuses] = $this->_customdata;

        $borderbottom = ['class' => 'border-bottom'];
        $durationoptions = ['units' => [1, MINSECS, HOURSECS]];

        $mform->addElement('header', 'settings', get_string('default_settings', 'local_apsolu'));

        // Activation.
        $mform->addElement('selectyesno', 'enabled', get_string('enable_attendance_recordings_via_qr_codes', 'local_apsolu'));

        // Avant le début de la session.
        $mform->addElement('html', html_writer::tag(
            'h4',
            get_string('before_the_start_of_the_session', 'local_apsolu'),
            $borderbottom
        ));

        $mform->addElement('duration', 'starttime', get_string('open_attendance_recording', 'local_apsolu'), $durationoptions);
        $mform->addHelpButton('starttime', 'open_attendance_recording', 'local_apsolu');

        $mform->addElement('select', 'presentstatus', get_string('status_present', 'local_apsolu'), $statuses);
        $mform->addHelpButton('presentstatus', 'status_present', 'local_apsolu');

        // Durant la session.
        $mform->addElement('html', html_writer::tag('h4', get_string('during_the_session', 'local_apsolu'), $borderbottom));

        $mform->addElement('selectyesno', 'latetimeenabled', get_string('enable_change_of_attendance_type', 'local_apsolu'));

        $mform->addElement('duration', 'latetime', get_string('change_status', 'local_apsolu'), $durationoptions);
        $mform->addHelpButton('latetime', 'change_status', 'local_apsolu');
        $mform->disabledIf('latetime', 'latetimeenabled', 'eq', '0');

        $mform->addElement('select', 'latestatus', get_string('status_late', 'local_apsolu'), $statuses);
        $mform->addHelpButton('latestatus', 'status_late', 'local_apsolu');
        $mform->disabledIf('latestatus', 'latetimeenabled', 'eq', '0');

        $mform->addElement('selectyesno', 'endtimeenabled', get_string('enable_stop_of_attendance_recordings', 'local_apsolu'));

        $mform->addElement('duration', 'endtime', get_string('stop_taking_attendance', 'local_apsolu'), $durationoptions);
        $mform->addHelpButton('endtime', 'stop_taking_attendance', 'local_apsolu');
        $mform->disabledIf('endtime', 'endtimeenabled', 'eq', '0');

        // Après la session.
        $mform->addElement('html', html_writer::tag('h4', get_string('after_the_session', 'local_apsolu'), $borderbottom));

        $mform->addElement(
            'selectyesno',
            'automarkenabled',
            get_string('assign_status_to_students_without_attendance', 'local_apsolu')
        );
        $mform->addHelpButton('automarkenabled', 'assign_status_to_students_without_attendance', 'local_apsolu');

        $mform->addElement('select', 'automarkstatus', get_string('status_absent', 'local_apsolu'), $statuses);
        $mform->addHelpButton('automarkstatus', 'status_absent', 'local_apsolu');
        $mform->disabledIf('automarkstatus', 'automarkenabled', 'eq', '0');

        $durationoptions['units'][] = DAYSECS;
        $mform->addElement(
            'duration',
            'automarktime',
            get_string('deadline_for_recording_attendance', 'local_apsolu'),
            $durationoptions
        );
        $mform->addHelpButton('automarktime', 'deadline_for_recording_attendance', 'local_apsolu');
        $mform->disabledIf('automarktime', 'automarkenabled', 'eq', '0');

        // Options.
        $mform->addElement('html', html_writer::tag('h4', get_string('options', 'local_apsolu'), $borderbottom));

        $label = get_string('allow_students_who_are_not_enrolled_in_the_course', 'local_apsolu');
        $mform->addElement('selectyesno', 'allowguests', $label);
        $mform->addHelpButton('allowguests', 'allow_students_who_are_not_enrolled_in_the_course', 'local_apsolu');

        $label = get_string('automatically_log_out_of_your_account_once_the_qr_code_is_displayed', 'local_apsolu');
        $mform->addElement('selectyesno', 'autologout', $label);
        $mform->addHelpButton('autologout', 'automatically_log_out_of_your_account_once_the_qr_code_is_displayed', 'local_apsolu');

        $mform->addElement('selectyesno', 'rotate', get_string('rotate_qr_code', 'local_apsolu'));
        $mform->addHelpButton('rotate', 'rotate_qr_code', 'local_apsolu');

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('save', 'admin'));
        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);

        // Set default values.
        $this->set_data($default);
    }

    /**
     * Validation.
     *
     * @param array $data
     * @param array $files
     *
     * @return array The errors that were found.
     */
    public function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);

        if (empty($data['latetimeenabled']) === false && $data['starttime'] === 0 && $data['latetime'] === 0) {
            // Contrôle que la durée de prises de présences initiales et la durée de présence "en retard" ne sont pas égales à zéro.
            $a = new stdClass();
            $a->field1 = get_string('change_status', 'local_apsolu');
            $a->field2 = get_string('open_attendance_recording', 'local_apsolu');

            $errors['latetime'] = get_string(
                'the_duration_of_the_X_field_must_be_greater_than_the_duration_of_the_Y_field',
                'local_apsolu',
                $a
            );
        }

        if (empty($data['endtimeenabled']) === false && $data['starttime'] === 0 && $data['endtime'] === 0) {
            // Contrôle que la durée de prises de présences initiales et l'arrêt de prises de présences ne sont pas égales à zéro.
            $a = new stdClass();
            $a->field1 = get_string('stop_taking_attendance', 'local_apsolu');
            $a->field2 = get_string('open_attendance_recording', 'local_apsolu');

            $errors['endtime'] = get_string(
                'the_duration_of_the_X_field_must_be_greater_than_the_duration_of_the_Y_field',
                'local_apsolu',
                $a
            );
        } else if (
            empty($data['latetimeenabled']) === false &&
            empty($data['endtimeenabled']) === false &&
            $data['latetime'] >= $data['endtime']
        ) {
            // Contrôle que la durée de prises de présences "en retard" n'est pas égale à l'arrêt de prises de présences.
            $a = new stdClass();
            $a->field1 = get_string('stop_taking_attendance', 'local_apsolu');
            $a->field2 = get_string('change_status', 'local_apsolu');

            $errors['endtime'] = get_string(
                'the_duration_of_the_X_field_must_be_greater_than_the_duration_of_the_Y_field',
                'local_apsolu',
                $a
            );
        }

        if ($data['rotate'] === $data['autologout'] && empty($data['rotate']) === false) {
            // Contrôle que le champ "QR code rotatif" et "Se déconnecter à l'affichage du QR code" ne sont pas combinés.
            $a = new stdClass();
            $a->field1 = get_string('rotate_qr_code', 'local_apsolu');
            $a->field2 = get_string('automatically_log_out_of_your_account_once_the_qr_code_is_displayed', 'local_apsolu');

            $errors['rotate'] = get_string('the_field_X_cannot_be_combined_with_the_field_Y', 'local_apsolu', $a);
        }

        return $errors;
    }
}
