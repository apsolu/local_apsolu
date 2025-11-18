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
 * Contrôleur pour l'administration des présences.
 *
 * @package    local_apsolu
 * @copyright  2025 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$tab = optional_param('tab', 'qrcode', PARAM_ALPHA);
$action = optional_param('action', 'view', PARAM_ALPHA);

// Set tabs.
$tabslist = [];
$tabslist['qrcode'] = get_string('qr_code', 'local_apsolu');
$tabslist['statuses'] = get_string('attendance_statuses', 'local_apsolu');

$tabsbar = [];
foreach ($tabslist as $tabname => $tablabel) {
    $url = new moodle_url('/local/apsolu/attendance/admin/index.php', ['tab' => $tabname]);
    $tabsbar[] = new tabobject($tabname, $url, $tablabel);
}

// Set default tabs.
if (isset($tabslist[$tab]) === false) {
    $tab = $tabslist['qrcode'];
}

// Setup admin access requirement.
admin_externalpage_setup('local_apsolu_attendances_' . $tab);

// Display page.
ob_start();
require(__DIR__ . '/' . $tab . '.php');
$content = ob_get_contents();
ob_end_clean();

echo $OUTPUT->header();
echo $OUTPUT->tabtree($tabsbar, $tab);
echo $content;
echo $OUTPUT->footer();
