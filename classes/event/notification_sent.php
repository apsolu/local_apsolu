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
 * Event to log user notifications.
 *
 * @package    local_apsolu
 * @copyright  2021 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_apsolu\event;

use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Event to log user notifications.
 *
 * @package    local_apsolu
 * @copyright  2021 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notification_sent extends \core\event\base {
    /**
     * Initialise l'évènement.
     *
     * @return void
     */
    protected function init() {
        // Values: c(reate), r(ead), u(pdate) or d(elete).
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
        return get_string('notification', 'local_apsolu');
    }

    /**
     * Retourne la description de l'évènement.
     *
     * @return string
     */
    public function get_description() {
        global $CFG, $DB;

        $other = json_decode($this->other);
        if (ctype_digit($other->receiver) === true) {
            // Si on a l'identifiant du destinaire, on va chercher son nom dans la table des utilisateurs.
            $user = $DB->get_record('user', array('id' => $other->receiver));
            if ($this->contextlevel == CONTEXT_COURSE) {
                $receiver = sprintf('<a href="%s/user/view.php?course=%s&id=%s">%s</a>', $CFG->wwwroot, $this->contextinstanceid, $user->id, fullname($user));
            } else {
                $receiver = sprintf('<a href="%s/user/view.php?id=%s">%s</a>', $CFG->wwwroot, $user->id, fullname($user));
            }
        } else {
            // On affiche juste l'adresse mail du contact fonctionnel.
            $receiver = $other->receiver;
        }

        if ($this->contextlevel == CONTEXT_COURSE) {
            $sender = sprintf('<a href="%s/user/view.php?course=%s&id=%s">\'%s\'</a>', $CFG->wwwroot, $this->contextinstanceid,
                $this->userid, $this->userid);
        } else {
            $sender = sprintf('<a href="%s/user/view.php?id=%s">\'%s\'</a>', $CFG->wwwroot, $this->userid, $this->userid);
        }

        $params = new stdClass();
        $params->sender = $sender;
        $params->receiver = $receiver;
        $params->subject = $other->subject;

        return get_string('notification_event_description', 'local_apsolu', $params);
    }
}
