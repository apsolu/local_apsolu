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

class local_apsolu_header_form extends moodleform {
    protected function definition() {
        $mform = $this->_form;

        list($defaults) = $this->_customdata;

        // Active.
        $mform->addElement('checkbox', 'apsoluheaderactive', 'Afficher le message');
        $mform->setType('apsoluheaderactive', PARAM_INT);

        // Message.
        $mform->addElement('editor', 'apsoluheadercontent', get_string('message', 'local_apsolu'));
        $mform->setType('apsoluheadercontent', PARAM_RAW);

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'));

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');

        // Hidden fields.
        $mform->addElement('hidden', 'page', 'header');
        $mform->setType('page', PARAM_ALPHANUM);

        // Set default values.
        $this->set_data($defaults);
    }
}
