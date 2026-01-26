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

require_once($CFG->libdir . '/formslib.php');

/**
 * Classe pour le formulaire permettant d'exporter la liste des utilisateurs devant être relancés.
 *
 * @package    local_apsolu
 * @copyright  2021 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_payment_notifications_export_form extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    protected function definition() {
        $mform = $this->_form;
        [$cards] = $this->_customdata;

        $mform->addElement('header', 'header_cards', get_string('cards', 'local_apsolu'));

        // Notice explicative.
        $mform->addElement('html', sprintf(
            '<div class="bg-light card card-body my-4">%s</div>',
            get_string('this_form_allows_you_to_export_list_of_pending_payments', 'local_apsolu')
        ));

        // Liste des cartes.
        foreach ($cards as $cardid => $card) {
            $mform->addElement('checkbox', 'card' . $cardid, $card->fullname);
            $mform->setType('card' . $cardid, PARAM_INT);
        }

        // Submit buttons.
        $mform->addElement('submit', 'submitbutton', get_string('export', 'local_apsolu'));

        // Hidden fields.
        $mform->addElement('hidden', 'tab', 'notifications');
        $mform->setType('tab', PARAM_ALPHA);

        $mform->addElement('hidden', 'action', 'export');
        $mform->setType('action', PARAM_ALPHA);
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
        global $DB;

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
                $errors['card' . $card->id] = 'Il faut cocher au moins une carte.';
            }
        }

        return $errors;
    }
}
