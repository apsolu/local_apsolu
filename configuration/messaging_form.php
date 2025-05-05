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
 * Classe pour le formulaire permettant de configurer les paramètres de messagerie.
 *
 * @package    local_apsolu
 * @copyright  2022 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\messaging;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

/**
 * Classe pour le formulaire permettant de configurer les paramètres de messagerie.
 *
 * @package    local_apsolu
 * @copyright  2022 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_messaging_form extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    protected function definition() {
        $mform = $this->_form;

        list($defaults) = $this->_customdata;

        // Functional contact.
        $mform->addElement('text', 'functional_contact', get_string('functional_contact', 'local_apsolu'), ['size' => '48']);
        $mform->addHelpButton('functional_contact', 'functional_contact', 'local_apsolu');
        $mform->setType('functional_contact', PARAM_TEXT);
        $mform->addRule('functional_contact', get_string('required'), 'required', null, 'client');

        // Technical contact.
        $mform->addElement('text', 'technical_contact', get_string('technical_contact', 'local_apsolu'), ['size' => '48']);
        $mform->addHelpButton('technical_contact', 'technical_contact', 'local_apsolu');
        $mform->setType('technical_contact', PARAM_TEXT);
        $mform->addRule('technical_contact', get_string('required'), 'required', null, 'client');

        // Préférence pour la mise en copie de l'adresse de contact fonctionnel.
        $label = get_string('by_default_copy_functional_contact', 'local_apsolu');
        $mform->addElement('select', 'functional_contact_preference', $label, messaging::get_functional_contact_options());
        $mform->addHelpButton('functional_contact_preference', 'functional_contact_preference', 'local_apsolu');
        $mform->setType('functional_contact_preference', PARAM_INT);

        // Préférence pour l'adresse de réponse.
        $label = get_string('replyto_address_preference', 'local_apsolu');
        $mform->addElement('select', 'replytoaddresspreference', $label, messaging::get_replyto_options());
        $mform->addHelpButton('replytoaddresspreference', 'replyto_address_preference', 'local_apsolu');
        $mform->setType('replytoaddresspreference', PARAM_INT);

        // Choix par défaut pour la préférence de l'adresse d'expédition.
        $label = get_string('default_replyto_address', 'local_apsolu');
        $mform->addElement('select', 'defaultreplytoaddresspreference', $label, messaging::get_default_replyto_options());
        $mform->setType('defaultreplytoaddresspreference', PARAM_INT);
        $mform->hideIf('defaultreplytoaddresspreference', 'replytoaddresspreference',
            'neq', messaging::ALLOW_REPLYTO_ADDRESS_CHOICE);

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'));

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);

        // Hidden fields.
        $mform->addElement('hidden', 'page', 'messaging');
        $mform->setType('page', PARAM_ALPHANUM);

        // Set default values.
        $this->set_data($defaults);
    }

    /**
     * Validation.
     *
     * @param array $data
     * @param array $files
     *
     * @return array The errors that were found.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $addresses = [];
        $addresses[] = 'functional_contact';
        $addresses[] = 'technical_contact';

        foreach ($addresses as $fieldname) {
            if ($data[$fieldname] === '') {
                continue;
            }

            if (filter_var($data[$fieldname], FILTER_VALIDATE_EMAIL) !== false) {
                continue;
            }

            $errors[$fieldname] = get_string('invalidemail');
        }

        return $errors;
    }
}
