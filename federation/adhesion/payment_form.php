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

use core_form\filetypes_util;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Classe pour le formulaire de redirection vers les paiements et/ou de demande de licences.
 *
 * @package    local_apsolu
 * @copyright  2025 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_federation_payment extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    protected function definition() {
        global $USER;

        $mform = $this->_form;

        list($contact, $cards, $requireddocuments, $due, $freeze) = $this->_customdata;

        $countrequireddocuments = count($requireddocuments);

        if ($freeze === true || $countrequireddocuments > 0) {
            $mform->freeze();

            if ($countrequireddocuments > 0) {
                $paragraph = html_writer::tag('p', get_string('before_being_able_to_request_a_license_you_must', 'local_apsolu'));
                $list = html_writer::alist($requireddocuments, $attributes = [], $tag = 'ul');
                $mform->addElement('html', sprintf('<div class="alert alert-warning">%s%s</div>', $paragraph, $list));

                return;
            } else {
                $mform->addElement('html', sprintf('<div class="alert alert-info">%s</div>', $contact));
            }
        }

        if (count($cards) > 0) {
            if ($due === false) {
                $label = get_string('you_have_paid_for_your_membership_in_the_sports_association', 'local_apsolu');
                $mform->addElement('html', sprintf('<div class="alert alert-success">%s</div>', $label));
            }

            $paragraph = html_writer::tag('p', get_string('federation_payments', 'local_apsolu'));
            $list = html_writer::alist($cards, $attributes = ['class' => 'list-unstyled'], $tag = 'ul');
            $mform->addElement('html', sprintf('<div class="px-5 my-2">%s%s</div>', $paragraph, $list));
        }

        // Champs cachés.
        $mform->addElement('hidden', 'step', APSOLU_PAGE_PAYMENT);
        $mform->setType('step', PARAM_INT);

        // Submit buttons.
        if ($due === true) {
            $label = get_string('pay_and_request_a_federation_number', 'local_apsolu');
        } else {
            $label = get_string('request_a_federation_number', 'local_apsolu');
        }
        $attributes = ['class' => 'btn btn-default'];
        $buttonarray[] = &$mform->createElement('submit', 'save', $label, $attributes);
        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
    }
}
