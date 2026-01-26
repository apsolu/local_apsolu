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
 * Classe pour le formulaire permettant de configurer les types de calendrier.
 *
 * @package    local_apsolu
 * @copyright  2017 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_calendars_types_edit_form extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    protected function definition() {
        $mform = $this->_form;

        [$defaults] = $this->_customdata;

        $attributes = ['size' => '20', 'maxlength' => '255'];
        $mform->addElement('text', 'name', get_string('calendartypename', 'local_apsolu'), $attributes);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'server');
        $mform->addRule('name', get_string('required'), 'required', null, 'client');

        $attributes = ['size' => '20', 'maxlength' => '100'];
        $mform->addElement('text', 'shortname', get_string('shortname'), $attributes);
        $mform->setType('shortname', PARAM_TEXT);
        $mform->addRule('shortname', get_string('maximumchars', '', 100), 'maxlength', 100, 'server');
        $mform->addRule('shortname', get_string('required'), 'required', null, 'client');

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'));

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);

        // Hidden fields.
        $mform->addElement('hidden', 'action', 'edit');
        $mform->setType('action', PARAM_ALPHANUM);

        $mform->addElement('hidden', 'page', 'calendarstypes');
        $mform->setType('page', PARAM_ALPHANUM);

        $mform->addElement('hidden', 'typeid', $defaults->id);
        $mform->setType('typeid', PARAM_INT);

        // Set default values.
        $this->set_data($defaults);
    }
}
