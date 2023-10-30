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
 * Page listant la configuration des paiements.
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$configurations = [];

$requiredvariables = ['paybox_servers_incoming', 'paybox_servers_outgoing', 'paybox_log_success_path', 'paybox_log_error_path'];
foreach ($requiredvariables as $variable) {
    $configuration = $DB->get_record('config_plugins', ['name' => $variable, 'plugin' => 'local_apsolu']);

    if ($configuration === false) {
        $configuration = new stdClass();
        $configuration->plugin = 'local_apsolu';
        $configuration->name = $variable;
        $configuration->value = '';
        $id = $DB->insert_record('config_plugins', $configuration);

        $configuration->id = $id;
    }

    $configurations[] = $configuration;
}

$data = new stdClass();
$data->wwwroot = $CFG->wwwroot;
$data->configurations = $configurations;
$data->information = markdown_to_html(get_string('paybox_administration_description', 'local_apsolu', $CFG->wwwroot));

if (isset($notificationform) === true) {
    $data->notification = $notificationform;
}

echo $OUTPUT->render_from_template('local_apsolu/payment_configurations', $data);
