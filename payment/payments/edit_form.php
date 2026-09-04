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

use UniversiteRennes2\Apsolu\Payment;

require_once($CFG->libdir . '/formslib.php');

/**
 * Classe pour le formulaire permettant l'édition d'un paiement.
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2
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
        $atoutsopts = $this->_customdata['atoutsopts'];
        $methods = $this->_customdata['methods'];
        $sources = $this->_customdata['sources'];
        $statuses = $this->_customdata['statuses'];
        $centers = $this->_customdata['centers'];
        $cards = $this->_customdata['cards'];

        // Atouts Normandie.
        if (empty($atoutsopts) === false) {
            $atouts[] = &$mform->createElement('select', 'atoutsopt', '', $atoutsopts);
            $atouts[] = $mform->createElement('float', 'amountatouts', '', ['class' => 'input-sm']);
            $atouts[] = $mform->createElement('html', '<span class="ms-1 fs-6">€</span>');
            $mform->addGroup($atouts, 'atouts', get_string('use_atouts_payment', 'local_apsolu'));

            $mform->hideIf('atouts[amountatouts]', 'atouts[atoutsopt]', '!=', 'partatouts');
        }

        // Method field.
        $mform->addElement('select', 'method', get_string('method', 'local_apsolu'), $methods);
        $mform->setType('method', PARAM_ALPHA);
        $mform->hideIf('method', 'atouts[atoutsopt]', 'eq', 'allatouts');

        // Amount field.
        $mform->addElement('float', 'amount', get_string('amount', 'local_apsolu'), ['class' => 'input-sm']);
        $mform->addRule('amount', get_string('required'), 'required', null, 'client');

        // Source field.
        $mform->addElement('select', 'source', get_string('source', 'local_apsolu'), $sources);
        $mform->setType('source', PARAM_ALPHA);

        // Status field.
        $mform->addElement('select', 'status', get_string('status', 'local_apsolu'), $statuses);
        $mform->setType('status', PARAM_INT);

        // Centers field.
        $mform->addElement('select', 'center', get_string('centers', 'local_apsolu'), $centers);
        $mform->setType('center', PARAM_INT);

        // Cards field.
        $cardopts = [];
        foreach ($cards as $cardid => $cardname) {
            // Carte sélectionnée ? (mode édition).
            $attr = in_array($cardid, $this->_customdata['checkedcards']) ? ['checked' => true] : [];
            $cardopts[] = $mform->createElement('advcheckbox', 'card' . $cardid, '', $cardname, $attr);
        }

        $mform->addGroup($cardopts, 'cards', get_string('card', 'local_apsolu'), [' '], false);
        $mform->setType('cards', PARAM_INT);
        $mform->addRule('cards', get_string('required'), 'required', null, 'client');

        // TODO: disable les checkboxes en fonction des centres de paiement.

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

    /**
     * Validation.
     *
     * @param array $data
     * @param array $files
     *
     * @return array the errors that were found
     */
    public function validation($data, $files): array {
        global $DB;

        $errors = parent::validation($data, $files);

        // Paiement via Atouts Normandie ?
        $atoutspayment = empty($this->_customdata['atoutsopts']) === false && $data['atouts']['atoutsopt'] !== 'noatouts';

        // Contrôle qu'une des cartes a été sélectionnée.
        $cards = $this->_customdata['cards'];
        if (empty($cards == false)) {
            $checked = false;
            foreach ($cards as $id => $card) {
                $name = 'card' . $id;
                if (empty($data[$name]) === false) {
                    $checked = true;
                    break;
                }
            }
            if ($checked == false) {
                $errors['cards'] = get_string('no_payment_card', 'local_apsolu');
            }
        } else {
            // Aucune carte encore à payer : le paiement ne devrait jamais être possible.
            $errors['cards'] = get_string('no_card_due', 'local_apsolu');
        }

        $status = $this->_customdata['statuses'][$data['status']];
        // Si le statut est GIFT.
        if ((int) $data['status'] === Payment::GIFT) {
            // Statut offert non possible si le paiement est fait en entier via Atouts Normandie.
            if ($atoutspayment && $data['atouts']['atoutsopt'] === 'allatouts') {
                $errors['status'] = get_string('invalid_payment_gift_status', 'local_apsolu', $status);
            } else {
                // Le montant doit être égal à 0.
                if ((float) $data['amount'] != 0) {
                    $errors['amount'] = get_string('invalid_free_amount', 'local_apsolu', $status);
                }
                // Le moyen de paiement doit être 'Aucun'.
                if ($data['method'] != $this->_customdata['nopaymentmethod']) {
                    $errors['method'] = get_string('invalid_payment_method', 'local_apsolu', $status);
                }
            }
        } else { // Si le statut est PAID ou DUE.
            // Le montant doit être supérieur à 0.
            if ((float) $data['amount'] <= 0) {
                $errors['amount'] = get_string('invalid_paid_amount', 'local_apsolu', get_string('paymentgift', 'local_apsolu'));
            }
            // Le moyen de paiement doit être différent de 'Aucun' (sauf paiement intégral Atouts Normandie).
            if ($data['method'] == $this->_customdata['nopaymentmethod'] && (!$atoutspayment || $data['atouts']['atoutsopt'] !== 'allatouts')) {
                $errors['method'] = get_string('invalid_payment_method', 'local_apsolu', $status);
            }
        }

        // Paiement partiel via Atouts Normandie.
        if ($atoutspayment && $data['atouts']['atoutsopt'] === 'partatouts' && (float) $data['atouts']['amountatouts'] <= 0) {
            $errors['atouts'] = get_string('invalid_partial_amount', 'local_apsolu');
        }

        return $errors;
    }
}
