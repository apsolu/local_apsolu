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
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

/**
 * Classe pour créer le formulaire permettant de créer un message de relance de paiement.
 */
class local_apsolu_payment_notifications_compose_form extends moodleform {
    protected function definition() {
        global $CFG, $DB;

        $mform = $this->_form;
        list($data, $cards) = $this->_customdata;

        // Subject field.
        $mform->addElement('text', 'subject', get_string('subject', 'local_apsolu'), array('size' => '48'));
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', get_string('required'), 'required', null, 'client');

        // Message field.
        $mform->addElement('editor', 'message', get_string('description'), null,  array('maxfiles' => EDITOR_UNLIMITED_FILES));
        $mform->setType('message', PARAM_RAW);
        $mform->addRule('message', get_string('required'), 'required', null, 'client');

        // Cards field.
        foreach ($cards as $cardid => $card) {
            $mform->addElement('checkbox', 'card'.$cardid, $card->fullname);
            $mform->setType('card'.$cardid, PARAM_INT);
        }

        // Submit buttons.
        $mform->addElement('submit', 'submitbutton', get_string('save', 'admin'));

        // Hidden fields.
        $mform->addElement('hidden', 'tab', 'notifications');
        $mform->setType('tab', PARAM_ALPHA);

        $mform->addElement('hidden', 'action', 'compose');
        $mform->setType('action', PARAM_ALPHA);

        // Set default values.
        $this->set_data($data);
    }

    /**
     * Validation.
     *
     * @param array $data
     * @param array $files
     * @return array the errors that were found
     */
    public function validation($data, $files) {
        global $DB;

        $errors = array();
        $errors = parent::validation($data, $files);

        // Vérifie qu'au moins une carte est cochée.
        $nocards = true;
        foreach ((array) $data as $field => $value) {
            if (preg_match('/^card[0-9]+$/', $field) === 1) {
                $nocards = false;
                break;
            }
        }

        if ($nocards === true) {
            $cards = $DB->get_records('apsolu_payments_cards');
            foreach ($cards as $card) {
                $errors['card'.$card->id] = 'Il faut cocher au moins une carte.';
            }
        }

        return $errors;
    }
}
