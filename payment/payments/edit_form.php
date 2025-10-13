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
 * Classe pour le formulaire permettant l'édition d'un paiement.
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

/**
 * Classe pour le formulaire permettant l'édition d'un paiement.
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_payment_payments_edit_form extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    protected function definition() {
        global $CFG;

        $mform = $this->_form;
        $payment = $this->_customdata['payment'];
        $methods = $this->_customdata['methods'];
        $sources = $this->_customdata['sources'];
        $statuses = $this->_customdata['statuses'];
        $centers = $this->_customdata['centers'];
        $cards = $this->_customdata['cards'];

        // Method field.
        $mform->addElement('select', 'method', get_string('method', 'local_apsolu'), $methods);
        $mform->setType('method', PARAM_ALPHA);
        $mform->addRule('method', get_string('required'), 'required', null, 'client');

        // Source field.
        $mform->addElement('select', 'source', get_string('source', 'local_apsolu'), $sources);
        $mform->setType('source', PARAM_ALPHA);
        $mform->addRule('source', get_string('required'), 'required', null, 'client');

        // Status field.
        $mform->addElement('select', 'status', get_string('status', 'local_apsolu'), $statuses);
        $mform->setType('status', PARAM_INT);
        $mform->addRule('status', get_string('required'), 'required', null, 'client');

        // Centers field.
        $mform->addElement('select', 'center', get_string('centers', 'local_apsolu'), $centers);
        $mform->setType('center', PARAM_INT);
        $mform->addRule('center', get_string('required'), 'required', null, 'client');

        // Courses field.
        foreach ($cards as $cardid => $cardname) {
            $mform->addElement('checkbox', 'card' . $cardid, $cardname);
            $mform->setType('card' . $cardid, PARAM_INT);
        }

        // TODO: disable les checkboxes en fonction des centres de paiement.

        // Amount field.
        $mform->addElement('text', 'amount', get_string('amount', 'local_apsolu'));
        $mform->setType('amount', PARAM_LOCALISEDFLOAT);
        $mform->addRule('amount', get_string('required'), 'required', null, 'client');

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('save', 'admin'));

        $attributes = new stdClass();
        $attributes->href = $CFG->wwwroot . '/local/apsolu/payment/admin.php?tab=payments&userid=' . $payment->userid;
        $attributes->class = 'btn btn-default btn-secondary';
        $buttonarray[] = &$mform->createElement('static', '', '', get_string('cancel_link', 'local_apsolu', $attributes));

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);

        // Hidden fields.
        $mform->addElement('hidden', 'tab', 'payments');
        $mform->setType('tab', PARAM_ALPHA);

        $mform->addElement('hidden', 'action', 'edit');
        $mform->setType('action', PARAM_ALPHA);

        $mform->addElement('hidden', 'paymentid', $payment->id);
        $mform->setType('paymentid', PARAM_INT);

        $mform->addElement('hidden', 'userid', $payment->userid);
        $mform->setType('userid', PARAM_INT);

        // Set default values.
        $this->set_data($payment);
    }
}
