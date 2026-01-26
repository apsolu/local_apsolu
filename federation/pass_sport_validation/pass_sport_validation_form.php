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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Classe pour le formulaire permettant de valider les paiements par Pass Sport.
 *
 * @package    local_apsolu
 * @copyright  2025 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_federation_pass_sport_validation extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    protected function definition() {
        $mform = $this->_form;
        $passsport = $this->_customdata['pass_sport'];

        // Nom de l'étudiant.
        $mform->addElement('text', 'fullnameuser', get_string('fullnameuser'));
        $mform->setType('fullnameuser', PARAM_TEXT);

        // Numéro de l'étudiant.
        $mform->addElement('text', 'idnumber', get_string('idnumber'));
        $mform->setType('idnumber', PARAM_TEXT);

        // Etat du certificat médical.
        $mform->addElement('select', 'pass_sport_status', get_string('pass_sport_status', 'local_apsolu'), $passsport);
        $mform->setType('pass_sport_status', PARAM_INT);
        $mform->setDefault('pass_sport_status', APSOLU_SELECT_NO);

        // Submit buttons.
        $attributes = ['class' => 'btn btn-default'];
        $buttonarray[] = &$mform->createElement('submit', 'showbutton', get_string('show'), $attributes);

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);

        // Hidden fields.
        $mform->addElement('hidden', 'page', 'pass_sport_validation');
        $mform->setType('page', PARAM_ALPHAEXT);
    }
}
