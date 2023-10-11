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
 * Classe pour le formulaire permettant de configurer les cours spéciaux.
 *
 * @package    local_apsolu
 * @copyright  2022 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

/**
 * Classe pour le formulaire permettant de configurer les cours spéciaux.
 *
 * @package    local_apsolu
 * @copyright  2022 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_special_courses_form extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    protected function definition() {
        global $CFG;

        $mform = $this->_form;

        list($defaults, $collaborative_courses, $federation_courses) = $this->_customdata;

        // Cours collaboratif pour les enseignants du SUAPS.
        $mform->addElement('autocomplete', 'collaborative_course', get_string('internal_collaborative_course', 'local_apsolu'), $collaborative_courses);
        $mform->addHelpButton('collaborative_course', 'internal_collaborative_course', 'local_apsolu');
        $mform->setType('collaborative_course', PARAM_TEXT);

        // Cours de la fédération sportive de sports universitaires.
        $mform->addElement('autocomplete', 'federation_course', get_string('federation_course', 'local_apsolu'), $federation_courses);
        $mform->addHelpButton('federation_course', 'federation_course', 'local_apsolu');
        $mform->setType('federation_course', PARAM_TEXT);

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'));

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

        // Hidden fields.
        $mform->addElement('hidden', 'page', 'specialcourses');
        $mform->setType('page', PARAM_ALPHANUM);

        // Set default values.
        $this->set_data($defaults);
    }
}
