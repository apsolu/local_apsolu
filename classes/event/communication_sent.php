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

use stdClass;

/**
 * Enregistre une trace des messages envoyés via le module communication.
 *
 * @package    local_apsolu
 * @copyright  2024 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class communication_sent extends \core\event\base {
    /**
     * Initialise l'évènement.
     *
     * @return void
     */
    protected function init() {
        // Values: c (create), r (read), u (update) or d (delete).
        $this->data['crud'] = 'c';

        // Values: LEVEL_TEACHING, LEVEL_PARTICIPATING or LEVEL_OTHER.
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Retourne le nom de l'évènement.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('communication', 'local_apsolu');
    }

    /**
     * Retourne la description de l'évènement.
     *
     * @return string
     */
    public function get_description() {
        global $CFG, $DB;

        $other = $this->other;
        if (is_string($other) === true) {
            // Ligne pour assurer la rétrocompatibilité, lorsqu'on encodait nous même les données other en JSON.
            $other = json_decode($other);
        }

        if (ctype_digit($other->receiver) === true) {
            // Si on a l'identifiant du destinaire, on va chercher son nom dans la table des utilisateurs.
            $user = $DB->get_record('user', ['id' => $other->receiver]);
            $receiver = sprintf('<a href="%s/user/view.php?id=%s">%s</a>', $CFG->wwwroot, $user->id, fullname($user));
        } else {
            // On affiche juste l'adresse mail du contact fonctionnel.
            $receiver = $other->receiver;
        }

        $sender = sprintf('<a href="%s/user/view.php?id=%s">\'%s\'</a>', $CFG->wwwroot, $this->userid, $this->userid);

        $params = new stdClass();
        $params->sender = $sender;
        $params->receiver = $receiver;
        $params->subject = $other->subject;

        return get_string('notification_event_description', 'local_apsolu', $params);
    }
}
