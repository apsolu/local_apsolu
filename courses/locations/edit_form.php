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
 * Classe pour le formulaire permettant de configurer les lieux de pratique.
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

/**
 * Classe pour le formulaire permettant de configurer les lieux de pratique.
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_courses_locations_edit_form extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    protected function definition() {
        global $CFG;

        $mform = $this->_form;
        list($location, $areas, $managers) = $this->_customdata;

        // Name field.
        $mform->addElement('text', 'name', get_string('name'), ['size' => '48']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');

        // Area field.
        $mform->addElement('select', 'areaid', get_string('area', 'local_apsolu'), $areas);
        $mform->setType('areaid', PARAM_INT);
        $mform->addRule('areaid', get_string('required'), 'required', null, 'client');
        // See MDL-53725.
        // Hope to use instead : $mform->addDatalist('area', $areas);.

        // Address field.
        $mform->addElement('textarea', 'address', get_string('address', 'local_apsolu'), ['cols' => '48']);
        $mform->setType('address', PARAM_TEXT);

        // Email field.
        $mform->addElement('text', 'email', get_string('email', 'local_apsolu'), ['size' => '48']);
        $mform->setType('email', PARAM_TEXT);

        // Phone field.
        $mform->addElement('text', 'phone', get_string('phone', 'local_apsolu'), ['size' => '48']);
        $mform->setType('phone', PARAM_TEXT);

        // Longitude field.
        $mform->addElement('text', 'longitude', get_string('longitude', 'local_apsolu'), ['size' => '48']);
        $mform->setType('longitude', PARAM_FLOAT);

        // Latitude field.
        $mform->addElement('text', 'latitude', get_string('latitude', 'local_apsolu'), ['size' => '48']);
        $mform->setType('latitude', PARAM_FLOAT);

        // OpenStreetMap link.
        $mform->addElement('html', '<p id="apsolu-osm-link" class="text-center">
            <a href="http://www.openstreetmap.org/way/81587209#map=13/48.1168/-1.6349" target="_blank">Obtenir les coordonnées d’un
            lieu</a></p>');

        // Wifi access field.
        $mform->addElement('selectyesno', 'wifi_access', get_string('wifi_access', 'local_apsolu'));
        $mform->setType('wifi_access', PARAM_TEXT);

        // Indoor field.
        $mform->addElement('selectyesno', 'indoor', get_string('indoor', 'local_apsolu'));
        $mform->setType('indoor', PARAM_TEXT);

        // Restricted access field.
        $mform->addElement('selectyesno', 'restricted_access', get_string('restricted_access', 'local_apsolu'));
        $mform->setType('restricted_access', PARAM_TEXT);

        // Manager field.
        $mform->addElement('select', 'managerid', get_string('manager', 'local_apsolu'), $managers);
        $mform->setType('managerid', PARAM_INT);
        $mform->addRule('managerid', get_string('required'), 'required', null, 'client');
        // See MDL-53725.
        // Hope to use instead : $mform->addDatalist('manager', $managers);.

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('save', 'admin'));

        $attributes = new stdClass();
        $attributes->href = $CFG->wwwroot.'/local/apsolu/courses/index.php?tab=locations';
        $attributes->class = 'btn btn-default btn-secondary';
        $buttonarray[] = &$mform->createElement('static', '', '', get_string('cancel_link', 'local_apsolu', $attributes));

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);

        // Hidden fields.
        $mform->addElement('hidden', 'tab', 'locations');
        $mform->setType('tab', PARAM_ALPHA);

        $mform->addElement('hidden', 'action', 'edit');
        $mform->setType('action', PARAM_ALPHA);

        $mform->addElement('hidden', 'locationid', $location->id);
        $mform->setType('locationid', PARAM_INT);

        // Set default values.
        $this->set_data($location);
    }
}
