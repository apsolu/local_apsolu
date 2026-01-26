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
 * Classe pour le formulaire permettant de configurer les messages d'entête.
 *
 * @package    local_apsolu
 * @copyright  2017 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_header_form extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    protected function definition() {
        $mform = $this->_form;

        [$defaults] = $this->_customdata;

        // Active.
        $mform->addElement('checkbox', 'apsoluheaderactive', 'Afficher le message');
        $mform->setType('apsoluheaderactive', PARAM_INT);

        // Stylage de l'entete.
        $stylearray = [
            'secondary' => "Normal",
            'primary' => "Primaire",
            'success' => "Succès",
            'danger' => "Alerte",
            'warning' => "Avertissement",
            'info' => "Information",
            'dark' => "Sombre",
            'none' => "Aucun",
        ];

        $select = $mform->addElement('select', 'apsoluheaderstyle', 'Style de l\'entête', $stylearray);
        $mform->setType('apsoluheaderstyle', PARAM_TEXT);

        // Message.
        $mform->addElement('editor', 'apsoluheadercontent', get_string('message', 'local_apsolu'));
        $mform->setType('apsoluheadercontent', PARAM_RAW);

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'));

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
        $mform->closeHeaderBefore('buttonar');

        // Hidden fields.
        $mform->addElement('hidden', 'page', 'headermessage');
        $mform->setType('page', PARAM_ALPHANUM);

        // Set default values.
        $this->set_data($defaults);
    }
}
