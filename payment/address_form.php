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

use local_apsolu\core\country;
use local_apsolu\core\paybox;

require_once($CFG->libdir . '/formslib.php');

/**
 * Classe pour le formulaire permettant de saisir les coordonnées d'un porteur de carte de paiement.
 *
 * @package    local_apsolu
 * @copyright  2022 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_payment_address_form extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    protected function definition() {
        global $CFG;

        $mform = $this->_form;
        [$address] = $this->_customdata;

        $attributes = ['maxlength' => 48, 'size' => 48];

        // Lastname field.
        $attributes['maxlength'] = paybox::MAX_LENGTH_LASTNAME;

        $mform->addElement('text', 'lastname', get_string('cardholder_lastname', 'local_apsolu'), $attributes);
        $mform->setType('lastname', PARAM_TEXT);
        $errormessage = get_string('maximumchars', '', paybox::MAX_LENGTH_LASTNAME);
        $mform->addRule('lastname', $errormessage, 'maxlength', paybox::MAX_LENGTH_LASTNAME, 'server');
        $mform->addRule('lastname', get_string('required'), 'required', null, 'client');

        // Firstname field.
        $attributes['maxlength'] = paybox::MAX_LENGTH_FIRSTNAME;

        $mform->addElement('text', 'firstname', get_string('cardholder_firstname', 'local_apsolu'), $attributes);
        $mform->setType('firstname', PARAM_TEXT);
        $errormessage = get_string('maximumchars', '', paybox::MAX_LENGTH_FIRSTNAME);
        $mform->addRule('firstname', $errormessage, 'maxlength', paybox::MAX_LENGTH_FIRSTNAME, 'server');
        $mform->addRule('firstname', get_string('required'), 'required', null, 'client');

        // Address1 field.
        $attributes['maxlength'] = paybox::MAX_LENGTH_ADDRESS1;

        $mform->addElement('text', 'address1', get_string('cardholder_postal_address', 'local_apsolu'), $attributes);
        $mform->setType('address1', PARAM_TEXT);
        $errormessage = get_string('maximumchars', '', paybox::MAX_LENGTH_ADDRESS1);
        $mform->addRule('address1', $errormessage, 'maxlength', paybox::MAX_LENGTH_ADDRESS1, 'server');
        $mform->addRule('address1', get_string('required'), 'required', null, 'client');

        // Address2 field.
        $attributes['maxlength'] = paybox::MAX_LENGTH_ADDRESS2;

        $label = get_string('cardholder_additional_postal_address_optional', 'local_apsolu');
        $mform->addElement('text', 'address2', $label, $attributes);
        $errormessage = get_string('maximumchars', '', paybox::MAX_LENGTH_ADDRESS2);
        $mform->addRule('address2', $errormessage, 'maxlength', paybox::MAX_LENGTH_ADDRESS2, 'server');
        $mform->setType('address2', PARAM_TEXT);

        // Zipcode field.
        $attributes['maxlength'] = paybox::MAX_LENGTH_ZIPCODE;

        $mform->addElement('text', 'zipcode', get_string('cardholder_zipcode', 'local_apsolu'), $attributes);
        $errormessage = get_string('maximumchars', '', paybox::MAX_LENGTH_ZIPCODE);
        $mform->addRule('zipcode', $errormessage, 'maxlength', paybox::MAX_LENGTH_ZIPCODE, 'server');
        $mform->setType('zipcode', PARAM_TEXT);
        $mform->addRule('zipcode', get_string('required'), 'required', null, 'client');

        // City field.
        $attributes['maxlength'] = paybox::MAX_LENGTH_CITY;

        $mform->addElement('text', 'city', get_string('cardholder_city', 'local_apsolu'), $attributes);
        $mform->setType('city', PARAM_TEXT);
        $errormessage = get_string('maximumchars', '', paybox::MAX_LENGTH_CITY);
        $mform->addRule('city', $errormessage, 'maxlength', paybox::MAX_LENGTH_CITY, 'server');
        $mform->addRule('city', get_string('required'), 'required', null, 'client');

        // CountryCode field.
        $multiple = ['multiple' => false];
        $label = get_string('cardholder_country', 'local_apsolu');
        $mform->addElement('autocomplete', 'countrycode', $label, country::get_iso_3166_3(), $multiple);
        $mform->setType('countrycode', PARAM_INT);
        $mform->addRule('countrycode', get_string('required'), 'required', null, 'client');

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('continue'));

        $attributes = new stdClass();
        $attributes->href = $CFG->wwwroot . '/my/';
        $attributes->class = 'btn btn-default btn-secondary';
        $buttonarray[] = &$mform->createElement('static', '', '', get_string('cancel_link', 'local_apsolu', $attributes));

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);

        // Set default values.
        $this->set_data($address);
    }
}
