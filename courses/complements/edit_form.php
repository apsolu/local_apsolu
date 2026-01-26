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
 * Classe pour le formulaire permettant de configurer les activités complémentaires
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_courses_complements_edit_form extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    protected function definition() {
        global $CFG;

        $mform = $this->_form;
        [$complement, $categories] = $this->_customdata;

        // Category field.
        $mform->addElement('select', 'category', get_string('category', 'local_apsolu'), $categories);
        $mform->setType('category', PARAM_INT);
        $mform->addRule('category', get_string('required'), 'required', null, 'client');

        // Fullname field.
        $mform->addElement('text', 'fullname', get_string('fullnamecourse'), 'maxlength="254" size="50"');
        $mform->setType('fullname', PARAM_TEXT);
        $mform->addRule('fullname', get_string('required'), 'required', null, 'client');

        // Shortname field.
        $mform->addElement('text', 'shortname', get_string('shortnamecourse'), 'maxlength="100" size="20"');
        $mform->setType('shortname', PARAM_TEXT);
        $mform->addRule('shortname', get_string('required'), 'required', null, 'client');

        // Price field.
        $mform->addElement('text', 'price', get_string('price', 'local_apsolu'));
        $mform->setType('price', PARAM_LOCALISEDFLOAT);
        $mform->addRule('price', get_string('required'), 'required', null, 'client');

        // Federation field.
        $mform->addElement('selectyesno', 'federation', get_string('federation', 'local_apsolu'));
        $mform->setType('federation', PARAM_INT);

        // Summary field.
        $mform->addElement('textarea', 'summary', get_string('description'));
        $mform->setType('summary', PARAM_TEXT);

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('save', 'admin'));

        $attributes = new stdClass();
        $attributes->href = $CFG->wwwroot . '/local/apsolu/courses/complements.php';
        $attributes->class = 'btn btn-default btn-secondary';
        $buttonarray[] = &$mform->createElement('static', '', '', get_string('cancel_link', 'local_apsolu', $attributes));

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);

        // Hidden fields.
        $mform->addElement('hidden', 'action', 'edit');
        $mform->setType('action', PARAM_ALPHA);

        $mform->addElement('hidden', 'complementid', $complement->id);
        $mform->setType('complementid', PARAM_INT);

        // Set default values.
        $this->set_data($complement);
    }
}
