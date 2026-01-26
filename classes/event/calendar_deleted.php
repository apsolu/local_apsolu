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

/**
 * Enregistre une trace lorsqu'un tarif est supprimé.
 *
 * @package    local_apsolu
 * @copyright  2024 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class calendar_deleted extends \core\event\base {
    /**
     * Initialise l'évènement.
     *
     * @return void
     */
    protected function init() {
        // Values: c (create), r (read), u (update) or d (delete).
        $this->data['crud'] = 'd';

        // Values: LEVEL_TEACHING, LEVEL_PARTICIPATING or LEVEL_OTHER.
        $this->data['edulevel'] = self::LEVEL_OTHER;

        $this->data['objecttable'] = 'apsolu_calendars';
    }

    /**
     * Retourne le nom de l'évènement.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('calendar_deleted', 'local_apsolu');
    }

    /**
     * Retourne la description de l'évènement.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' deleted the calendar with id '$this->objectid'.";
    }
}
