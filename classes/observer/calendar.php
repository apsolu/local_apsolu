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

namespace local_apsolu\observer;

use local_apsolu\core\gradeitem;
use local_apsolu\event\calendar_deleted;

/**
 * Classe permettant d'écouter les évènements diffusés par Moodle.
 *
 * @package   local_apsolu
 * @copyright 2024 Université Rennes 2
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class calendar {
    /**
     * Écoute l'évènement calendar_deleted.
     *
     * @param calendar_deleted $event Évènement diffusé par Moodle.
     *
     * @return void
     */
    public static function deleted(calendar_deleted $event) {
        // Supprime les éléments de notations.
        $gradeitems = gradeitem::get_records(['calendarid' => $event->objectid]);
        foreach ($gradeitems as $gradeitem) {
            $gradeitem->delete();
        }
    }
}
