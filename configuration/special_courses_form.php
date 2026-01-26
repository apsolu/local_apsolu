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
 * Classe pour le formulaire permettant de configurer les cours spéciaux.
 *
 * @package    local_apsolu
 * @copyright  2022 Université Rennes 2
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

        [$defaults, $collaborativecourses, $federationcourses] = $this->_customdata;

        // Cours collaboratif pour les enseignants du SUAPS.
        $label = get_string('internal_collaborative_course', 'local_apsolu');
        $mform->addElement('autocomplete', 'collaborative_course', $label, $collaborativecourses);
        $mform->addHelpButton('collaborative_course', 'internal_collaborative_course', 'local_apsolu');
        $mform->setType('collaborative_course', PARAM_TEXT);

        // Affiche un lien statique vers le cours collaboratif, si il existe.
        if (empty($defaults->collaborative_course) === false && is_numeric($defaults->collaborative_course) === true) {
            $label = get_string('course_link', 'local_apsolu');
            $url = new moodle_url('/course/view.php', ['id' => $defaults->collaborative_course]);
            $mform->addElement('static', 'collaborative_course_link', $label, html_writer::link($url, $url));
        }

        // Cours de la fédération sportive de sports universitaires.
        $label = get_string('federation_course', 'local_apsolu');
        $mform->addElement('autocomplete', 'federation_course', $label, $federationcourses);
        $mform->addHelpButton('federation_course', 'federation_course', 'local_apsolu');
        $mform->setType('federation_course', PARAM_TEXT);

        // Affiche un lien statique vers le cours FFSU, si il existe.
        if (empty($defaults->federation_course) === false && is_numeric($defaults->federation_course) === true) {
            $label = get_string('course_link', 'local_apsolu');
            $url = new moodle_url('/course/view.php', ['id' => $defaults->federation_course]);
            $mform->addElement('static', 'federation_course_link', $label, html_writer::link($url, $url));
        }

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'));

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);

        // Hidden fields.
        $mform->addElement('hidden', 'page', 'specialcourses');
        $mform->setType('page', PARAM_ALPHANUM);

        // Set default values.
        $this->set_data($defaults);
    }
}
