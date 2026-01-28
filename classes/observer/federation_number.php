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

use local_apsolu\event\federation_number_created;
use local_apsolu\event\federation_number_updated;

/**
 * Classe permettant d'écouter les évènements diffusés par Moodle.
 *
 * @package   local_apsolu
 * @copyright 2026 Université Rennes 2
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class federation_number {
    /**
     * Écoute l'évènement federation_number_created.
     *
     * @param federation_number_created $event Évènement diffusé par Moodle.
     *
     * @return void
     */
    public static function created(federation_number_created $event): void {
        self::set_federation_number($event);
    }

    /**
     *
     */
    public static function set_federation_number(federation_number_created|federation_number_updated $event): void {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/user/profile/definelib.php');
        require_once($CFG->dirroot . '/user/profile/lib.php');

        $shortname = 'apsolufederationnumber';

        // Enregistre le numéro de licence dans le profil de l'utilisateur.
        $other = (array) $event->other;
        $infodata = (object) ['id' => $event->relateduserid, 'profile_field_' . $shortname => $event->other['federationnumber']];
        $errors = profile_validation($infodata, $files = []);
        if (count($errors) > 0) {
            throw new moodle_exception(json_encode($errors));
        }

        profile_save_data($infodata);
    }

    /**
     * Écoute l'évènement federation_number_updated.
     *
     * @param federation_number_updated $event Évènement diffusé par Moodle.
     *
     * @return void
     */
    public static function updated(federation_number_updated $event): void {
        self::set_federation_number($event);
    }
}
