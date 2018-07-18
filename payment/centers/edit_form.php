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
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

/**
 * Form class to create or to edit a category.
 */
class local_apsolu_payment_centers_edit_form extends moodleform {
    protected function definition() {
        global $CFG, $DB;

        $mform = $this->_form;
        $center = $this->_customdata['center'];

        // Name field.
        $mform->addElement('text', 'name', get_string('name'), array('size' => '48'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');

        // Idnumber field.
        $mform->addElement('text', 'idnumber', get_string('paybox_idnumber', 'local_apsolu'), array('size' => '48'));
        $mform->setType('idnumber', PARAM_INT);
        $mform->addRule('idnumber', get_string('required'), 'required', null, 'client');

        // Site number field.
        $mform->addElement('text', 'sitenumber', get_string('paybox_sitenumber', 'local_apsolu'), array('size' => '48'));
        $mform->setType('sitenumber', PARAM_INT);
        $mform->addRule('sitenumber', get_string('required'), 'required', null, 'client');

        // Rank field.
        $mform->addElement('text', 'rank', get_string('paybox_rank', 'local_apsolu'), array('size' => '48'));
        $mform->setType('rank', PARAM_INT);
        $mform->addRule('rank', get_string('required'), 'required', null, 'client');

        // HMAC field.
        $mform->addElement('text', 'hmac', get_string('paybox_hmac', 'local_apsolu'));
        $mform->setType('hmac', PARAM_ALPHANUM);

        // Submit buttons.
        $attributes = array('class' => 'btn btn-primary');
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('save', 'admin'), $attributes);

        $attributes = new stdClass();
        $attributes->href = $CFG->wwwroot.'/local/apsolu/payment/admin.php?tab=centers';
        $attributes->class = 'btn btn-default';
        $buttonarray[] = &$mform->createElement('static', '', '', get_string('cancel_link', 'local_apsolu', $attributes));

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

        // Hidden fields.
        $mform->addElement('hidden', 'tab', 'centers');
        $mform->setType('tab', PARAM_ALPHA);

        $mform->addElement('hidden', 'action', 'edit');
        $mform->setType('action', PARAM_ALPHA);

        $mform->addElement('hidden', 'centerid', $center->id);
        $mform->setType('centerid', PARAM_INT);

        // Set default values.
        $this->set_data($center);
    }
}