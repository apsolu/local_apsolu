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
 * @copyright  2017 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

class local_apsolu_contacts_form extends moodleform {
    protected function definition() {
        $mform = $this->_form;

        list($defaults) = $this->_customdata;

        // Functional contact.
        $mform->addElement('text', 'functional_contact', get_string('functional_contact', 'local_apsolu'), array('size' => '48'));
        $mform->setType('functional_contact', PARAM_TEXT);
        $mform->addRule('functional_contact', get_string('required'), 'required', null, 'client');

        // Technical contact.
        $mform->addElement('text', 'technical_contact', get_string('technical_contact', 'local_apsolu'), array('size' => '48'));
        $mform->setType('technical_contact', PARAM_TEXT);
        $mform->addRule('technical_contact', get_string('required'), 'required', null, 'client');

        // Submit buttons.
        $attributes = array('class' => 'btn btn-primary');
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'), $attributes);

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

        // Hidden fields.
        $mform->addElement('hidden', 'page', 'contacts');
        $mform->setType('page', PARAM_ALPHANUM);

        // Set default values.
        $this->set_data($defaults);
    }
}
