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
 * Page d'édition de la configuration des paiements.
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require(__DIR__.'/edit_form.php');

// Get configuration id.
$configurationid = optional_param('configurationid', 0, PARAM_INT);

// Generate object.
$configuration = $DB->get_record('config_plugins', ['id' => $configurationid, 'plugin' => 'local_apsolu'], '*', MUST_EXIST);

// Build form.
$customdata = ['configuration' => $configuration];
$mform = new local_apsolu_payment_configurations_edit_form(null, $customdata);

if ($data = $mform->get_data()) {
    // Save data.
    $configuration = new stdClass();
    $configuration->id = $configurationid;
    $configuration->value = trim($data->value);

    $DB->update_record('config_plugins', $configuration);

    // Display notification and display elements list.
    $notificationform = $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');

    require(__DIR__.'/view.php');
} else {
    // Display form.
    echo '<h1>'.get_string('configuration_edit', 'local_apsolu').'</h1>';

    $mform->display();
}
