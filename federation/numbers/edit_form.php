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
 * Classe pour le formulaire permettant d'éditer les numéros d'association.
 *
 * @package    local_apsolu
 * @copyright  2022 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_federation_numbers_edit_form extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    protected function definition() {
        global $CFG;

        $mform = $this->_form;
        $number = $this->_customdata['number'];
        $fields = $this->_customdata['fields'];

        // Champ Numéro d'association.
        $mform->addElement('text', 'number', get_string('association_number', 'local_apsolu'), ['maxlength' => 4]);
        $mform->setType('number', PARAM_TEXT);
        $mform->addRule('number', get_string('required'), 'required', null, 'client');

        // Champ "Champ".
        $options = [];
        $options['multiple'] = false;
        $mform->addElement('select', 'field', get_string('field', 'local_apsolu'), $fields, $options);
        $mform->setType('field', PARAM_TEXT);
        $mform->addRule('field', get_string('required'), 'required', null, 'client');

        // Champ Valeur.
        $mform->addElement('text', 'value', get_string('value', 'local_apsolu'));
        $mform->setType('value', PARAM_TEXT);
        $mform->addRule('value', get_string('required'), 'required', null, 'client');

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('save', 'admin'));

        $attributes = new stdClass();
        $attributes->href = $CFG->wwwroot.'/local/apsolu/federation/index.php?page=numbers';
        $attributes->class = 'btn btn-default btn-secondary';
        $buttonarray[] = &$mform->createElement('static', '', '', get_string('cancel_link', 'local_apsolu', $attributes));

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);

        // Hidden fields.
        $mform->addElement('hidden', 'page', 'numbers');
        $mform->setType('page', PARAM_ALPHA);

        $mform->addElement('hidden', 'action', 'edit');
        $mform->setType('action', PARAM_ALPHA);

        $mform->addElement('hidden', 'numberid', $number->id);
        $mform->setType('numberid', PARAM_INT);

        // Set default values.
        $this->set_data($number);
    }
}
