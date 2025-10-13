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
 * Classe pour le formulaire permettant de gérer la charte.
 *
 * @package    local_apsolu
 * @copyright  2023 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\federation\adhesion;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Classe pour le formulaire permettant de gérer la charte.
 *
 * @package    local_apsolu
 * @copyright  2023 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_federation_agreement extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    protected function definition() {
        global $USER;

        $mform = $this->_form;
        [$adhesion, $readonly] = $this->_customdata;

        if ($readonly === true) {
            $messages = $adhesion::get_contacts();
            $mform->addElement('html', sprintf('<div class="alert alert-info">%s</div>', implode(' ', $messages)));
            $mform->hardFreeze();
        }

        // Charte.
        $agreement = get_config('local_apsolu', 'ffsu_agreement');
        $html = '<div class="bg-light card mx-auto my-3 w-75"><div class="card-body">%s</div></div>';
        $mform->addElement('html', sprintf($html, $agreement));

        // Validation.
        $label = get_string('by_checking_the_box_i_declare_to_accept_the_agreement_above', 'local_apsolu');
        $mform->addElement('checkbox', 'agreementaccepted', $label);

        // Champs cachés.
        $mform->addElement('hidden', 'step', APSOLU_PAGE_AGREEMENT);
        $mform->setType('step', PARAM_INT);

        // Submit buttons.
        $attributes = ['class' => 'btn btn-default'];
        $buttonarray[] = &$mform->createElement('submit', 'save', get_string('save'), $attributes);

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);

        // Set default values.
        $this->set_data($adhesion);
    }
}
