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
 * Select session
 *
 * @package    local_apsolu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');

class edit_enrolment_form extends moodleform {

    /**
     * Form definition.
     */
    function definition() {
        $mform = $this->_form;

        list($data, $lists, $roles) = $this->_customdata;

        // Lists.
        $mform->addElement('select', 'listid', get_string('list', 'enrol_select'), $lists);
        $mform->setType('listid', PARAM_INT);

        // Roles.
        $mform->addElement('select', 'roleid', get_string('role'), $roles);
        $mform->setType('roleid', PARAM_INT);

        // Userid.
        $mform->addElement('hidden', 'userid', null);
        $mform->setType('userid', PARAM_INT);

        // Courseid.
        $mform->addElement('hidden', 'courseid', null);
        $mform->setType('courseid', PARAM_INT);

        // UEid.
        $mform->addElement('hidden', 'ueid', null);
        $mform->setType('ueid', PARAM_INT);

        // RAid.
        $mform->addElement('hidden', 'raid', null);
        $mform->setType('raid', PARAM_INT);

        // Submit buttons.
        $attributes = array('class' => 'btn btn-primary');
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'), $attributes);

        $url = new moodle_url('/local/apsolu/attendance/edit.php', array('courseid' => $data->courseid));
        $attributes = new stdClass();
        $attributes->href = $url->out(true);
        $attributes->class = 'btn btn-default cancel';
        $buttonarray[] = &$mform->createElement('static', '', '', get_string('close_link', 'local_apsolu', $attributes));

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

        // Finally set the current form data
        $this->set_data($data);
    }
}
