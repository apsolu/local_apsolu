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
 * Classe pour le formulaire permettant la configuration les tarifs de paiements.
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

/**
 * Classe pour le formulaire permettant la configuration les tarifs de paiements.
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_payment_cards_edit_form extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    protected function definition() {
        global $CFG;

        $mform = $this->_form;
        list($instance, $cohorts, $roles, $centers, $calendarstypes) = $this->_customdata;

        // Libellé du tarif.
        $mform->addElement('text', 'name', get_string('card_shortname', 'local_apsolu'), ['size' => '48']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('name', 'card_shortname', 'local_apsolu'); // Libellé affiché à l'étudiant.

        // Libellé long du tarif.
        $mform->addElement('text', 'fullname', get_string('card_fullname', 'local_apsolu'), ['size' => '48']);
        $mform->setType('fullname', PARAM_TEXT);
        $mform->addRule('fullname', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('fullname', 'card_fullname', 'local_apsolu'); // Libellé affiché aux gestionnaires.

        // Paiments.
        $mform->addElement('header', 'header', get_string('payments', 'local_apsolu'));

        // Centre de paiement.
        $options = [];
        foreach ($centers as $center) {
            $options[$center->id] = $center->name;
        }
        $select = $mform->addElement('select', 'centerid', get_string('center', 'local_apsolu'), $options);

        // Prix.
        $mform->addElement('text', 'price', get_string('price', 'local_apsolu'), ['size' => '48']);
        $mform->setType('price', PARAM_LOCALISEDFLOAT);
        $mform->addRule('price', get_string('required'), 'required', null, 'client');

        // Nombre de sessions offertes.
        $mform->addElement('text', 'trial', get_string('freetrial', 'local_apsolu'), ['size' => '48']);
        $mform->setType('trial', PARAM_INT);
        $mform->addRule('trial', get_string('required'), 'required', null, 'client');

        // Nombre de créneaux offerts.
        foreach ($calendarstypes as $type) {
            $name = 'types['.$type->id.']';
            $mform->addElement('text', $name, get_string('freecourses', 'local_apsolu', $type->name), ['size' => '48']);
            $mform->setType($name, PARAM_TEXT);
            $mform->addRule($name, get_string('required'), 'required', null, 'client');
        }

        // Cohortes.
        $mform->addElement('header', 'header', get_string('cohorts', 'enrol_select'));

        $options = [];
        foreach ($cohorts as $cohort) {
            $options[$cohort->id] = $cohort->name;
        }
        $attributes = ['size' => 10];
        $select = $mform->addElement('select', 'cohorts', get_string('selectcohorts', 'enrol_select'), $options, $attributes);
        $select->setMultiple(true);

        // Rôles.
        $mform->addElement('header', 'header', get_string('roles'));

        $options = [];
        foreach ($roles as $role) {
            $options[$role->id] = $role->localname;
        }
        $select = $mform->addElement('select', 'roles', get_string('registertype', 'enrol_select'), $options, $instance->roles);
        $select->setMultiple(true);

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('save', 'admin'));

        $attributes = new stdClass();
        $attributes->href = $CFG->wwwroot.'/local/apsolu/payment/admin.php?tab=prices';
        $attributes->class = 'btn btn-default btn-secondary';
        $buttonarray[] = &$mform->createElement('static', '', '', get_string('cancel_link', 'local_apsolu', $attributes));

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);

        // Hidden fields.
        $mform->addElement('hidden', 'tab', 'prices');
        $mform->setType('tab', PARAM_ALPHA);

        $mform->addElement('hidden', 'action', 'edit');
        $mform->setType('action', PARAM_ALPHA);

        $mform->addElement('hidden', 'cardid', $instance->id);
        $mform->setType('cardid', PARAM_INT);

        // Set default values.
        $this->set_data($instance);
    }
}
