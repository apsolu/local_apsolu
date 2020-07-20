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
 * Purge les jours fériés.
 *
 * @package   local_apsolu
 * @copyright 2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

use local_apsolu\core\holiday as Holiday;

$holidays = Holiday::get_records();
foreach ($holidays as $holiday) {
    $holiday->delete();
}

$returnurl = new moodle_url('/local/apsolu/courses/index.php', array('tab' => 'holidays'));
$message = get_string('holidays_have_been_purged', 'local_apsolu');
redirect($returnurl, $message, $delay = null, \core\output\notification::NOTIFY_SUCCESS);