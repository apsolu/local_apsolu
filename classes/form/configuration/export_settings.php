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
 * Classe pour le formulaire permettant de configurer les paramètres d'exportation.
 *
 * @package    local_apsolu
 * @copyright  2025 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class export_settings extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    protected function definition() {
        global $CFG;

        $mform = $this->_form;

        [$defaults, $fields] = $this->_customdata;

        $displayselect = $mform->addElement(
            'select',
            'additionaldisplayfields',
            get_string('additional_fields_to_display', 'local_apsolu'),
            $fields,
            ['size' => 10, 'style' => 'width: 40em;']
        );
        $mform->addHelpButton('additionaldisplayfields', 'additional_fields_to_display', 'local_apsolu');
        $displayselect->setMultiple(true);

        $exporselect = $mform->addElement(
            'select',
            'additionalexportfields',
            get_string('additional_fields_to_export', 'local_apsolu'),
            $fields,
            ['size' => 10, 'style' => 'width: 40em;']
        );
        $mform->addHelpButton('additionalexportfields', 'additional_fields_to_export', 'local_apsolu');
        $exporselect->setMultiple(true);

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'));

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);

        // Set default values.
        $this->set_data($defaults);
    }
}
