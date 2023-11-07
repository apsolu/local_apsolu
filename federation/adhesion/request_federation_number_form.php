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
 * Classe pour le formulaire permettant de demander un numéro de licence.
 *
 * @package    local_apsolu
 * @copyright  2023 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\federation\adhesion as Adhesion;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Classe pour le formulaire permettant de demander un numéro de licence.
 *
 * @package    local_apsolu
 * @copyright  2023 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_request_federation_number_form extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    protected function definition() {
        global $OUTPUT;

        $mform = $this->_form;

        list($items, $canrequestfederationnumber, $hasrequestedfederationnumber) = $this->_customdata;

        if ($hasrequestedfederationnumber === true) {
            $content = Adhesion::get_contacts();
            $mform->addElement('html', html_writer::div(implode(' ', $content), 'alert alert-info'));
        }

        $crossicon = $OUTPUT->render(new pix_icon('i/grade_incorrect', ''));
        $checkicon = $OUTPUT->render(new pix_icon('i/grade_correct', ''));

        $list = [];
        foreach ($items as $i => $item) {
            if ($item->status === true) {
                $list[] = sprintf('%s %s', $checkicon, $item->label);
            } else {
                $list[] = sprintf('%s %s', $crossicon, $item->label);
            }
        }
        $mform->addElement('static', 'list', '', html_writer::alist($list, $attributes = ['class' => 'list-unstyled mt-3']));

        // Champs cachés.
        $mform->addElement('hidden', 'step', APSOLU_PAGE_SUMMARY);
        $mform->setType('step', PARAM_INT);

        // Submit buttons.
        if ($hasrequestedfederationnumber === true) {
            $label = get_string('your_request_is_being_processed', 'local_apsolu');
        } else {
            $label = get_string('request_a_federation_number', 'local_apsolu');
        }

        $attributes = ['class' => 'btn btn-default'];
        if ($canrequestfederationnumber === false) {
            $attributes['disabled'] = 1;
        }

        $buttonarray[] = &$mform->createElement('submit', 'save', $label, $attributes);

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
    }
}
