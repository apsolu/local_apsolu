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

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

/**
 * Classe pour le formulaire permettant de sélectionner les sessions.
 *
 * @package    local_apsolu
 * @copyright  2017 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit_select_form extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    protected function definition() {
        $mform = $this->_form;

        $data = new stdClass();
        $data->courseid = $this->_customdata['courseid']; // This contains the data of this form.
        $data->sessionid = $this->_customdata['sessionid'];
        $qrcodeid = $this->_customdata['qrcodeid'];

        if (isset($this->_customdata['invalid_enrolments']) == true) {
            $data->invalid_enrolments = $this->_customdata['invalid_enrolments'];
        }

        if (isset($this->_customdata['inactive_enrolments']) == true) {
            $data->inactive_enrolments = $this->_customdata['inactive_enrolments'];
        }

        // Sessions.
        $sessions = [];
        foreach ($this->_customdata['sessions'] as $session) {
            $sessions[$session->id] = $session->name;
        }
        $mform->addElement('select', 'sessionid', get_string('attendance_select_session', 'local_apsolu'), $sessions);
        $mform->setType('sessionid', PARAM_INT);

        // Invalid enrolments.
        $mform->addElement('checkbox', 'invalid_enrolments', get_string('attendance_display_invalid_enrolments', 'local_apsolu'));
        $mform->setType('invalid_enrolments', PARAM_INT);

        // Inactive enrolments.
        $mform->addElement('checkbox', 'inactive_enrolments', get_string('attendance_display_inactive_enrolments', 'local_apsolu'));
        $mform->setType('inactive_enrolments', PARAM_INT);

        // Page.
        $mform->addElement('hidden', 'page', 'edit');
        $mform->setType('page', PARAM_ALPHA);

        // Courseid.
        $mform->addElement('hidden', 'courseid', null);
        $mform->setType('courseid', PARAM_INT);

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('show'));
        $buttonarray[] = &$mform->createElement('submit', 'exportcsv', get_string('export_to_csv_format', 'local_apsolu'));
        $buttonarray[] = &$mform->createElement('submit', 'exportexcel', get_string('export_to_excel_format', 'local_apsolu'));
        if ($qrcodeid !== null) {
            $url = new moodle_url('/local/apsolu/attendance/index.php', [
                'page' => 'qrcode',
                'courseid' => $data->courseid,
                'sessionid' => $data->sessionid,
            ]);

            if ($qrcodeid === 0) {
                // Définit le lien pour générer le QR code.
                $link = html_writer::link($url, get_string('generate_a_qr_code', 'local_apsolu'), ['class' => 'btn btn-warning']);
                $buttonarray[] = &$mform->createElement('html', $link);
            } else if ($qrcodeid === -1) {
                // Affiche un bouton grisé "Générer le QR code".
                $input = html_writer::empty_tag('input', [
                    'class' => 'btn btn-warning',
                    'disabled' => 1,
                    'name' => 'generateqrcode',
                    'type' => 'submit',
                    'value' => get_string('generate_a_qr_code', 'local_apsolu'),
                ]);
                $buttonarray[] = &$mform->createElement('html', $input);
            } else {
                // Définit le lien pour modifier le QR code.
                $printurl = new moodle_url('/local/apsolu/attendance/qrcode.php', ['id' => $qrcodeid, 'print' => 1]);
                $items = [
                    html_writer::link($url, get_string('edit_qr_code', 'local_apsolu'), ['class' => 'dropdown-item']),
                    html_writer::link($printurl, get_string('print', 'local_apsolu'), ['class' => 'dropdown-item']),
                ];
                $list = html_writer::alist($items, ['class' => 'dropdown-menu']);

                // Définit le lien pour afficher le QR code.
                $url = new moodle_url('/local/apsolu/attendance/qrcode.php', ['id' => $qrcodeid]);
                $link = html_writer::link($url, get_string('show_qr_code', 'local_apsolu'), [
                    'class' => 'btn btn-warning',
                ]);

                $dropdown = html_writer::tag('button', html_writer::span('Toggle Dropdown', 'visually-hidden'), [
                    'aria-expanded' => 'false',
                    'class' => 'btn btn-warning dropdown-toggle dropdown-toggle-split',
                    'data-bs-toggle' => 'dropdown',
                    'type' => 'button',
                ]);

                // Affiche les 2 liens sur la page.
                $buttonarray[] = &$mform->createElement('html', html_writer::div($link . $dropdown . $list, 'btn-group dropdown'));
            }
        }
        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);

        // Finally set the current form data.
        $this->set_data($data);
    }
}
