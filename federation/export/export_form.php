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
 * Classe pour le formulaire permettant d'exporter les licences FFSU.
 *
 * @package    local_apsolu
 * @copyright  2022 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once $CFG->libdir.'/formslib.php';

/**
 * Classe pour le formulaire permettant d'exporter les licences FFSU.
 *
 * @package    local_apsolu
 * @copyright  2022 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_federation_export_licenses extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    function definition () {
        $mform = $this->_form;
        $numbers = $this->_customdata['numbers'];
        $payments = $this->_customdata['payments'];
        $certificates = $this->_customdata['certificates'];
        $licenses = $this->_customdata['licenses'];
        $activities = $this->_customdata['activities'];

        // Nom de l'étudiant.
        $mform->addElement('text', 'fullnameuser', get_string('fullnameuser'));
        $mform->setType('fullnameuser', PARAM_TEXT);

        // Numéro de l'étudiant.
        $mform->addElement('text', 'idnumber', get_string('idnumber'));
        $mform->setType('idnumber', PARAM_TEXT);

        // Numéro de l'association.
        $attributes = array('size' => 10, 'style' => 'width:6em');
        $select = $mform->addElement('select', 'numbers', get_string('association_number_prefix', 'local_apsolu'), $numbers, $attributes);
        $select->setMultiple(true);
        $mform->setType('numbers', PARAM_INT);

        // État du paiement.
        $mform->addElement('select', 'payment', get_string('license_payment_status', 'local_apsolu'), $payments);
        $mform->setType('payment', PARAM_INT);
        $mform->setDefault('payment', APSOLU_SELECT_YES);

        // État du certificat médical.
        $mform->addElement('select', 'medical', get_string('medical_certificate_status', 'local_apsolu'), $certificates);
        $mform->setType('medical', PARAM_INT);
        $mform->setDefault('medical', APSOLU_SELECT_YES);

        // État du numéro de licence.
        $mform->addElement('select', 'license', get_string('license_number_status', 'local_apsolu'), $licenses);
        $mform->setType('license', PARAM_INT);
        $mform->setDefault('license', APSOLU_SELECT_NO);

        // Activité.
        $mform->addElement('select', 'activity', get_string('activities', 'local_apsolu'), $activities);
        $mform->setType('activity', PARAM_INT);

        // Submit buttons.
        $attributes = array('class' => 'btn btn-default');
        $buttonarray[] = &$mform->createElement('submit', 'previewbutton', get_string('show'), $attributes);

        $buttonarray[] = &$mform->createElement('submit', 'exportbutton', get_string('export', 'local_apsolu'));

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

        // Hidden fields.
        $mform->addElement('hidden', 'page', 'export');
        $mform->setType('page', PARAM_ALPHA);
    }
}
