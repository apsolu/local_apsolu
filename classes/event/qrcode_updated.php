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

namespace local_apsolu\event;

use moodle_url;

/**
 * Enregistre la mise à jour d'un qrcode de validation de présence.
 *
 * @package   local_apsolu
 * @copyright 2025 Université Rennes 2
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qrcode_updated extends \core\event\base {
    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        // Values: c (create), r (read), u (update) or d (delete).
        $this->data['crud'] = 'u';

        // Values: LEVEL_TEACHING, LEVEL_PARTICIPATING or LEVEL_OTHER.
        $this->data['edulevel'] = self::LEVEL_OTHER;

        $this->data['objecttable'] = 'apsolu_attendance_qrcodes';
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('qr_code_updated', 'local_apsolu');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return 'The user with id ' . $this->userid . ' updated the qrcode' .
           ' with id ' . $this->objectid . ' in the course with the id ' . $this->courseid . '.';
    }

    /**
     * Returns relevant URL.
     *
     * @return moodle_url
     */
    public function get_url() {
        return new moodle_url('/local/apsolu/attendance/qrcode.php', ['id' => $this->objectid]);
    }
}
