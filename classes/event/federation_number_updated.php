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

defined('MOODLE_INTERNAL') || die();

/**
 * Enregistre la mise à jour du numéro de licence FFSU.
 *
 * @package   local_apsolu
 * @copyright 2023 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class federation_number_updated extends \core\event\base {
    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        // Values: c(reate), r(ead), u(pdate) or d(elete).
        $this->data['crud'] = 'u';

        // Values: LEVEL_TEACHING, LEVEL_PARTICIPATING or LEVEL_OTHER.
        $this->data['edulevel'] = self::LEVEL_OTHER;

        $this->data['objecttable'] = 'apsolu_federation_adhesions';
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('federation_number_updated', 'local_apsolu');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $other = json_decode($this->other);

        return 'The user with id '.$this->userid.' updated the federation number '.$other->oldfederationnumber.
            ' to '.$other->federationnumber.' for adhesion with id '.$this->objectid;
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        $parameters = ['page' => 'import'];
        return new \moodle_url('/local/apsolu/federation/index.php', $parameters);
    }
}
