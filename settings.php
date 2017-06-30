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
 * Add page to admin menu.
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(
    'moodle/category:manage',
    'moodle/course:create',
);

if ($hassiteconfig or has_any_capability($capabilities, context_system::instance())) {
    if (empty($ADMIN->locate('apsolu'))) {
        $ADMIN->add('root', new admin_category('apsolu', get_string('settings_root', 'local_apsolu')), 'users');
    }

    // Configuration.
    $ADMIN->add('apsolu', new admin_category('local_apsolu_configuration', get_string('settings_configuration', 'local_apsolu')));

    $str = get_string('settings_configuration_calendar', 'local_apsolu');
    $url = new moodle_url('/local/apsolu/configuration/index.php', array('type' => 'calendar'));
    $ADMIN->add('local_apsolu_configuration', new admin_externalpage('local_apsolu_configuration_calendar', $str, $url, $capabilities));

    // Statistics.
    $ADMIN->add('apsolu', new admin_category('local_apsolu_statistics', get_string('settings_statistics', 'local_apsolu')));

    $str = get_string('settings_statistics_rosters', 'local_apsolu');
    $url = new moodle_url('/local/apsolu/statistics/index.php', array('type' => 'rosters'));
    $ADMIN->add('local_apsolu_statistics', new admin_externalpage('local_apsolu_statistics_rosters', $str, $url, $capabilities));
}
