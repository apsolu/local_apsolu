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

use core_cache\cache;
use core_customfield\event\field_created;
use core_customfield\event\field_deleted;
use core_customfield\event\field_updated;

/**
 * Classe permettant d'écouter les évènements diffusés par Moodle.
 *
 * @package   local_apsolu
 * @copyright 2026 Université Rennes 2
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class customfield {
    /**
     * Écoute l'évènement field_created.
     *
     * @param field_created $event Évènement diffusé par Moodle.
     *
     * @return void
     */
    public static function created(field_created $event): void {
        // Invalide le cache.
        self::invalidate_cache();
    }

    /**
     * Écoute l'évènement field_deleted.
     *
     * @param field_deleted $event Évènement diffusé par Moodle.
     *
     * @return void
     */
    public static function deleted(field_deleted $event): void {
        global $DB;

        // Supprime les références du champ personnalisé supprimé dans la table apsolu_courses_fields.
        $DB->delete_records('apsolu_courses_fields', ['customfieldid' => $event->objectid]);

        // Invalide le cache.
        self::invalidate_cache();
    }

    /**
     * Fonction interne pour invalider le cache des coursecustomfields.
     *
     * @return void
     */
    private static function invalidate_cache() {
         // Invalide le cache des champs de cours personnalisés.
        $cache = cache::make('local_apsolu', 'coursecustomfields');
        $cache->purge();
    }

    /**
     * Écoute l'évènement field_updated.
     *
     * @param field_updated $event Évènement diffusé par Moodle.
     *
     * @return void
     */
    public static function updated(field_updated $event): void {
        // Invalide le cache.
        self::invalidate_cache();
    }
}
