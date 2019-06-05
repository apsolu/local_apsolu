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
 * @copyright  2019 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

class local_apsolu_homepage_form extends moodleform {
    protected function definition() {
        $mform = $this->_form;

        list($defaults) = $this->_customdata;

        // Active.
        $mform->addElement('header', 'homepage_general', get_string('general'));
            $mform->addElement('checkbox', 'homepage_enable', get_string('use_apsolu_homepage', 'local_apsolu'), get_string('enable'));
            $mform->setType('homepage_enable', PARAM_INT);

        // Accueil.
        $mform->addElement('header', 'homepage_section1', get_string('named_section', 'local_apsolu', get_string('home', 'local_apsolu')));
            // Message affiché sur la section 'accueil'.
            $mform->addElement('editor', 'homepage_section1_text', get_string('section_text', 'local_apsolu'));
            $mform->setType('homepage_section1_text', PARAM_RAW);

            // Image de fond sur la section 'accueil'.
            // TODO: $mform->addElement('filepicker', 'homepage_section1_image', get_string('background_image', 'local_apsolu'), null, array('maxfiles' => 1, 'accepted_types' => array('.jpg', '.png', '.svg')));

            // Crédit sur l'image de fond.
            // TODO: $mform->addElement('editor', 'homepage_section1_credit', get_string('author_credit', 'local_apsolu'));
            // TODO: $mform->setType('homepage_section1_credit', PARAM_RAW);

        // Les activités.
        $mform->addElement('header', 'homepage_section2', get_string('named_section', 'local_apsolu', get_string('the_activities', 'local_apsolu')));
            // Texte affiché.
            $mform->addElement('static', 'homepage_section2_text', get_string('section_text', 'local_apsolu'), get_string('section2_text', 'local_apsolu'));

            // Image de fond.
            // TODO: $mform->addElement('filepicker', 'homepage_section2_image', get_string('background_image', 'local_apsolu'), null, array('maxfiles' => 1, 'accepted_types' => array('.jpg', '.png', '.svg')));

            // Crédit.
            // TODO: $mform->addElement('editor', 'homepage_section2_credit', get_string('author_credit', 'local_apsolu'));
            // TODO: $mform->setType('homepage_section2_credit', PARAM_RAW);

        // S'inscrire.
        $mform->addElement('header', 'homepage_section3', get_string('named_section', 'local_apsolu', get_string('signup', 'local_apsolu')));
            // Texte affiché.
            $mform->addElement('editor', 'homepage_section3_text', get_string('section_text', 'local_apsolu'), array('cols' => '48'));
            $mform->setType('homepage_section3_text', PARAM_RAW);

            // Image de fond.
            // TODO: $mform->addElement('filepicker', 'homepage_section3_image', get_string('background_image', 'local_apsolu'), null, array('maxfiles' => 1, 'accepted_types' => array('.jpg', '.png', '.svg')));

            // Crédit.
            // TODO: $mform->addElement('editor', 'homepage_section3_credit', get_string('author_credit', 'local_apsolu'));
            // TODO: $mform->setType('homepage_section3_credit', PARAM_RAW);

        // Se connecter.
        $mform->addElement('header', 'homepage_section4', get_string('named_section', 'local_apsolu', get_string('login', 'local_apsolu')));
            // URL pour s'authentifier avec le compte institutionnel.
            $mform->addElement('text', 'homepage_section4_institutional_account_url', get_string('institutional_account_authentification_url', 'local_apsolu'), array('size' => '100'));
            $mform->setType('homepage_section4_institutional_account_url', PARAM_URL);

            // URL pour s'authentifier avec un compte générique.
            $mform->addElement('text', 'homepage_section4_non_institutional_account_url', get_string('non_institutional_account_authentification_url', 'local_apsolu'), array('size' => '100'));
            $mform->setType('homepage_section4_non_institutional_account_url', PARAM_URL);

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'));

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');

        // Hidden fields.
        $mform->addElement('hidden', 'page', 'homepage');
        $mform->setType('page', PARAM_ALPHANUM);

        // Set default values.
        $this->set_data($defaults);
    }
}
