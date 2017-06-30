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
 * Post installation hook for adding data.
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade procedure.
 */
function xmldb_local_apsolu_upgrade($oldversion = 0) {
    global $CFG;

    $result = true;

    if ($result && $oldversion < 2016082400) {
        $cachedir = $CFG->dataroot.'/apsolu/local_apsolu/cache/homepage';

        $result = mkdir($cachedir, $CFG->directorypermissions, $recursive = true);
    }

    return $result;
}
