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
 * Classe pour le formulaire permettant la configuration des paiements.
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

/**
 * Classe pour le formulaire permettant la configuration des paiements.
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_payment_configurations_edit_form extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    protected function definition() {
        global $CFG;

        $mform = $this->_form;
        $configuration = $this->_customdata['configuration'];

        // Name field.
        $mform->addElement('text', 'name', get_string('name'), ['size' => '48', 'readonly' => '1']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');

        // Value field.
        $mform->addElement('text', 'value', get_string('value', 'local_apsolu'), ['size' => '48']);
        $mform->setType('value', PARAM_TEXT);
        $mform->addRule('value', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('value', $configuration->name, 'local_apsolu');

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('save', 'admin'));

        $attributes = new stdClass();
        $attributes->href = $CFG->wwwroot.'/local/apsolu/payment/admin.php?tab=configurations';
        $attributes->class = 'btn btn-default btn-secondary';
        $buttonarray[] = &$mform->createElement('static', '', '', get_string('cancel_link', 'local_apsolu', $attributes));

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);

        // Hidden fields.
        $mform->addElement('hidden', 'tab', 'configurations');
        $mform->setType('tab', PARAM_ALPHA);

        $mform->addElement('hidden', 'action', 'edit');
        $mform->setType('action', PARAM_ALPHA);

        $mform->addElement('hidden', 'configurationid', $configuration->id);
        $mform->setType('configurationid', PARAM_INT);

        // Set default values.
        $this->set_data($configuration);
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

        $errors = [];
        $errors = parent::validation($data, $files);

        if (empty($data['value']) === true) {
            return $errors;
        }

        switch ($data['name']) {
            case 'paybox_servers_incoming':
                foreach (explode(',', $data['value']) as $ip) {
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
                        continue;
                    }

                    $errors['value'] = get_string('this_ip_address_X_is_invalid', 'local_apsolu', $ip);
                    break;
                }
                break;
            case 'paybox_servers_outgoing':
                foreach (explode(',', $data['value']) as $domain) {
                    if (filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false) {
                        continue;
                    }

                    $errors['value'] = get_string('this_domain_name_X_is_invalid', 'local_apsolu', $domain);
                    break;
                }
                break;
            case 'paybox_log_success_path':
            case 'paybox_log_error_path':
                if (is_dir($data['value']) === true) {
                    $errors['value'] = get_string('the_path_X_is_a_directory', 'local_apsolu', $data['value']);
                } else {
                    $dir = dirname($data['value']);
                    if (is_writable($dir) === false) {
                        $errors['value'] = get_string('the_directory_X_is_not_writable', 'local_apsolu', $dir);
                    }
                }
                break;
        }

        return $errors;
    }
}
