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

namespace local_apsolu\form\configuration;

use moodleform;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

/**
 * Classe pour le formulaire pour la configuration des profils utilisateurs.
 *
 * @package    local_apsolu
 * @copyright  2024 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_profile extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    protected function definition() {
        global $CFG;

        $mform = $this->_form;

        [$defaults, $hiddenfields] = $this->_customdata;

        // Champs masqués.
        $label = get_string('information_to_hide_in_user_profile', 'local_apsolu');
        $select = $mform->addElement('select', 'userhiddenfields', $label, $hiddenfields, ['sizzzze' => 50]);
        $select->setMultiple(true);
        $capacity = get_string('apsolu:viewuserhiddendetails', 'local_apsolu');
        $mform->addHelpButton('userhiddenfields', 'information_to_hide_in_user_profile', 'local_apsolu', '', false, $capacity);
        $mform->addRule('userhiddenfields', get_string('required'), 'required', null, 'client');
        $mform->setType('userhiddenfields', PARAM_TEXT);

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'));

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);

        // Hidden fields.
        $mform->addElement('hidden', 'page', 'userprofile');
        $mform->setType('page', PARAM_ALPHANUM);

        // Set default values.
        $this->set_data($defaults);
    }
}
