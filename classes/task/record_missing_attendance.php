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

namespace local_apsolu\task;

use Exception;
use local_apsolu\core\attendance\status as attendancestatus;
use local_apsolu\core\attendancepresence;
use local_apsolu\core\attendancesession;
use local_apsolu\enrolment;

/**
 * Classe représentant la tâche permettant d'enregistrer les présences des étudiants sans motif de présence.
 *
 * Elle est utilisée par la prise de présences par QR code.
 *
 * @package   local_apsolu
 * @copyright 2025 Université Rennes 2
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class record_missing_attendance extends \core\task\adhoc_task {
    /**
     * Retourne le nom de la tâche.
     *
     * @return string
     */
    public function get_name(): string {
        // Shown in admin screens.
        return get_string('record_missing_attendance', 'local_apsolu');
    }

    /**
     * Execute la tâche.
     *
     * @return void
     */
    public function execute(): void {
        try {
            $now = time();
            $customdata = $this->get_custom_data();

            // Contrôle l'existance de la session.
            $session = attendancesession::get_record(['id' => $customdata->sessionid]);
            if ($session === false) {
                return;
            }

            // Contrôle l'existance du statut de présence.
            $status = attendancestatus::get_record(['id' => $customdata->statusid]);
            if ($status === false) {
                return;
            }

            // Récupère la liste des inscrits.
            $startedbefore = $session->sessiontime + $session->get_duration(); // Inscriptions commencées avant la fin du cours.
            $endedafter = $session->sessiontime; // Inscriptions terminées après le début du cours.
            $students = enrolment::get_enrolled_users($session->courseid, 0, $startedbefore, $endedafter);

            // Récupère la liste des présences.
            $presences = [];
            foreach (attendancepresence::get_records(['sessionid' => $session->id]) as $record) {
                $presences[$record->studentid] = $record->studentid;
            }

            // Enregistre les présences manquantes.
            foreach ($students as $student) {
                if (isset($presences[$student->id]) === true) {
                    // Cet étudiant a déjà une présence.
                    continue;
                }

                $presence = new attendancepresence();
                $presence->studentid = $student->id;
                $presence->teacherid = $student->id;
                $presence->statusid = $status->id;
                $presence->timecreated = $now;
                $presence->timemodified = $now;
                $presence->sessionid = $session->id;

                $presence->save();
            }
        } catch (Exception $exception) {
            mtrace($exception->getMessage());
        }
    }
}
