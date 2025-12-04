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

use local_apsolu\attendance\qrcode as attendanceqrcode;
use local_apsolu\core\attendancesession;
use local_apsolu\event\qrcode_created;
use local_apsolu\event\qrcode_deleted;
use local_apsolu\event\qrcode_updated;
use local_apsolu\event\session_deleted;
use local_apsolu\event\session_updated;
use local_apsolu\task\record_missing_attendance;

/**
 * Classe permettant d'écouter les évènements diffusés par Moodle.
 *
 * @package   local_apsolu
 * @copyright 2025 Université Rennes 2
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qrcode {
    /**
     * Génère une tâche adhoc pour enregistrer les présences manquantes.
     *
     * @param qrcode_created|qrcode_updated|session_updated $event Évènement diffusé par Moodle.
     *
     * @return void
     */
    public static function create_adhoc_task(qrcode_created|qrcode_updated|session_updated $event): void {
        $qrcode = attendanceqrcode::get_record(['id' => $event->objectid]);

        if ($qrcode === false) {
            // Le QR code n'existe plus.
            return;
        }

        $settings = json_decode($qrcode->settings);
        if (empty($settings->automark) === true) {
            // Le marquage automatique n'est pas activé.
            return;
        }

        $session = attendancesession::get_record(['id' => $qrcode->sessionid]);
        if ($session === false) {
            // La session n'existe plus.
            return;
        }

        // Insère la tâche.
        $customdata = (object) ['sessionid' => $qrcode->sessionid, 'statusid' => $settings->automarkstatus];
        $nextruntime = $session->sessiontime + $session->get_duration() + $settings->automarktime;

        $task = new record_missing_attendance();
        $task->set_next_run_time($nextruntime);
        $task->set_custom_data($customdata);
        $task->set_userid($event->userid);

        \core\task\manager::queue_adhoc_task($task);
    }

    /**
     * Écoute l'évènement qrcode_created.
     *
     * @param qrcode_created $event Évènement diffusé par Moodle.
     *
     * @return void
     */
    public static function created(qrcode_created $event): void {
        self::create_adhoc_task($event);
    }

    /**
     * Supprime la tâche adhoc enregistrée pour ce QR code.
     *
     * @param qrcode_deleted|qrcode_updated|session_deleted|session_updated $event Évènement diffusé par Moodle.
     *
     * @return void
     */
    public static function delete_adhoc_task(qrcode_deleted|qrcode_updated|session_deleted|session_updated $event): void {
        global $DB;

        $qrcode = attendanceqrcode::get_record(['id' => $event->objectid]);
        if ($qrcode === false) {
            // Le QR code n'existe plus.
            return;
        }

        $params = ['component' => 'local_apsolu', 'classname' => '\local_apsolu\task\record_missing_attendance'];
        foreach ($DB->get_records('task_adhoc', $params) as $task) {
            $customdata = json_decode($task->customdata);

            if (isset($customdata->sessionid) === false) {
                continue;
            }

            if ($customdata->sessionid != $qrcode->sessionid) {
                continue;
            }

            $DB->delete_records('task_adhoc', ['id' => $task->id]);
            break;
        }
    }

    /**
     * Écoute l'évènement qrcode_deleted.
     *
     * @param qrcode_deleted|session_deleted $event Évènement diffusé par Moodle.
     *
     * @return void
     */
    public static function deleted(qrcode_deleted|session_deleted $event): void {
        self::delete_adhoc_task($event);
    }

    /**
     * Écoute l'évènement qrcode_updated.
     *
     * @param qrcode_updated|session_updated $event Évènement diffusé par Moodle.
     *
     * @return void
     */
    public static function updated(qrcode_updated|session_updated $event): void {
        self::delete_adhoc_task($event);
        self::create_adhoc_task($event);
    }
}
