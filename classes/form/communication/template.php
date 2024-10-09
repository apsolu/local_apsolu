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

namespace local_apsolu\form\communication;

use stdClass;

/**
 * Classe pour le formulaire permettant de saisir un modèle de message.
 *
 * @package    local_apsolu
 * @copyright  2024 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class template extends notify {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    protected function definition() {
        global $CFG;

        parent::definition();

        $mform = $this->_form;

        $mform->removeElement('sender');
        $mform->removeElement('buttonar');
        $mform->setExpanded('filters', true);

        foreach ($mform->_elements as $element) {
            if (in_array($element->getName(), ['subject', 'message'], true) === false) {
                continue;
            }

            // Déverrouille les éléments subject et message.
            $element->unfreeze();
        }

        // Boutons de validation du formulaire.
        $buttonarray[] = &$mform->createElement('submit', 'save', get_string('save'));

        $attributes = new stdClass();
        $attributes->href = $CFG->wwwroot.'/local/apsolu/communication/index.php?page=templates';
        $attributes->class = 'btn btn-default btn-secondary';
        $buttonarray[] = &$mform->createElement('static', '', '', get_string('cancel_link', 'local_apsolu', $attributes));

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
    }
}
