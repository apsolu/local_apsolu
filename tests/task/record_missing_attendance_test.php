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

use enrol_select_plugin;
use local_apsolu\attendance\qrcode;
use local_apsolu\core\attendancepresence;
use local_apsolu\core\attendancesession;
use local_apsolu\core\course;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/course/lib.php');

/**
 * Classe de tests pour local_apsolu\task\record_missing_attendance
 *
 * @package    local_apsolu
 * @category   test
 * @copyright  2025 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(record_missing_attendance::class)]
final class record_missing_attendance_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();

        $this->setAdminUser();

        $this->resetAfterTest();
    }

    /**
     * Teste execute().
     *
     * @return void
     */
    public function test_execute(): void {
        global $DB, $USER;

        // Génère une instance enrol_select.
        $generator = $this->getDataGenerator();
        $numberofusers = [enrol_select_plugin::ACCEPTED => 5];
        [$plugin, $enrol, $users] = $generator->get_plugin_generator('enrol_select')->create_enrol_instance($numberofusers);
        $users = $users[enrol_select_plugin::ACCEPTED];

        // Génère une session de cours.
        $session = new attendancesession();
        $session->courseid = $enrol->courseid;
        $session->name = 'attendancesession 1';
        $session->save();

        // Génère un QR code.
        $qrcode = new qrcode();
        $qrcode->keycode = qrcode::generate_keycode();
        $qrcode->settings = qrcode::get_default_settings();
        $qrcode->sessionid = $session->id;
        $qrcode->save();

        // Prend 3 présences.
        $i = 0;
        foreach ($users as $user) {
            if ($i === 3) {
                break;
            }

            $presence = new attendancepresence();
            $presence->studentid = $user->id;
            $presence->teacherid = $USER->id;
            $presence->statusid = 1;
            $presence->timecreated = time();
            $presence->timemodified = time();
            $presence->sessionid = $session->id;
            $presence->save();

            $i++;
        }

        $this->assertSame($i, $DB->count_records(attendancepresence::TABLENAME));

        // Génère une tâche adhoc.
        $customdata = (object) ['sessionid' => 0, 'statusid' => 0];

        $task = new record_missing_attendance();
        $task->set_custom_data($customdata);

        // Teste l'exécution d'une tâche adhoc avec une session qui n'existe pas.
        $task->execute();
        $this->assertSame($i, $DB->count_records(attendancepresence::TABLENAME));

        // Teste l'exécution d'une tâche adhoc avec un statut qui n'existe pas.
        $customdata->sessionid = $session->id;
        $task->set_custom_data($customdata);

        $task->execute();
        $this->assertSame($i, $DB->count_records(attendancepresence::TABLENAME));

        // Valide l'exécution d'une tâche adhoc.
        $customdata->statusid = 1;
        $task->set_custom_data($customdata);

        $task->execute();
        $this->assertSame(count($users), $DB->count_records(attendancepresence::TABLENAME));
    }
}
