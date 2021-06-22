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
 * Classe pour le formulaire permettant de prendre les présences.
 *
 * @package    local_apsolu
 * @copyright  2017 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');

/**
 * Classe pour le formulaire permettant de prendre les présences.
 *
 * @package    local_apsolu
 * @copyright  2017 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit_enrolment_form extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    function definition() {
        $mform = $this->_form;

        list($data, $statuses, $roles) = $this->_customdata;

        // Statuses.
        $mform->addElement('select', 'statusid', get_string('list', 'enrol_select'), $statuses);
        $mform->setType('statusid', PARAM_INT);

        // Roles.
        $mform->addElement('select', 'roleid', get_string('role'), $roles);
        $mform->setType('roleid', PARAM_INT);

        // Userid.
        $mform->addElement('hidden', 'userid', null);
        $mform->setType('userid', PARAM_INT);

        // Courseid.
        $mform->addElement('hidden', 'courseid', null);
        $mform->setType('courseid', PARAM_INT);

        // Enrolid.
        $mform->addElement('hidden', 'enrolid', null);
        $mform->setType('enrolid', PARAM_INT);

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'));

        $url = new moodle_url('/local/apsolu/attendance/edit.php', array('courseid' => $data->courseid));
        $attributes = new stdClass();
        $attributes->href = $url->out(true);
        $attributes->class = 'btn btn-default btn-secondary cancel';
        $buttonarray[] = &$mform->createElement('static', '', '', get_string('close_link', 'local_apsolu', $attributes));

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

        // Finally set the current form data
        $this->set_data($data);
    }
}
