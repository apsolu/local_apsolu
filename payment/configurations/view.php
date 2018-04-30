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

$configurations = array();

$requiredvariables = array('paybox_servers_incoming', 'paybox_servers_outgoing');
foreach ($requiredvariables as $variable) {
    $configuration = $DB->get_record('config_plugins', array('name' => $variable, 'plugin' => 'local_apsolu'));

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

if (isset($notificationform) === true) {
    $data->notification = $notificationform;
}

echo $OUTPUT->render_from_template('local_apsolu/payment_configurations', $data);
