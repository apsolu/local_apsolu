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
 * Classe pour le formulaire permettant de sélectionner les options pour l'export des sessions.
 *
 * @package    local_apsolu
 * @copyright  2021 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

/**
 * Classe pour le formulaire permettant de sélectionner les options pour l'export des sessions.
 *
 * @package    local_apsolu
 * @copyright  2021 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_attendance_export_form extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    protected function definition() {
        $mform = $this->_form;

        $courseid = $this->_customdata['courseid'];
        $options = ['optional' => true];

        // Info.
        $label = get_string('enable_dates_if_you_do_not_want_to_export_the_entire_data', 'local_apsolu');
        $mform->addElement('html', '<p class="alert alert-info">' . $label . '</p>');

        // Date.
        $mform->addElement('date_selector', 'startdate', get_string('start_date', 'local_apsolu'), $options);
        $mform->addElement('date_selector', 'enddate', get_string('end_date', 'local_apsolu'), $options);

        // Format.
        $formats = [];
        $formats['csv'] = get_string('csv_format', 'local_apsolu');
        $formats['excel'] = get_string('excel_format', 'local_apsolu');
        $mform->addElement('select', 'format', get_string('export_format', 'local_apsolu'), $formats);

        // Courseid.
        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);

        // Submit buttons.
        $mform->addElement('submit', 'submitbutton', get_string('export', 'local_apsolu'));
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
        $errors = parent::validation($data, $files);

        if (empty($data['startdate']) === true) {
            return $errors;
        }

        if (empty($data['enddate']) === true) {
            return $errors;
        }

        if ($data['startdate'] < $data['enddate']) {
            return $errors;
        }

        $errors['enddate'] = get_string('enddatebeforestartdate', 'error');

        return $errors;
    }
}
