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

use context_course;
use local_apsolu\attendance\qrcode as attendanceqrcode;
use local_apsolu\core\course;
use local_apsolu\core\attendancesession;
use local_apsolu\event\qrcode_updated;

/**
 * Classe de tests pour local_apsolu\observer\qrcode
 *
 * @package    local_apsolu
 * @category   test
 * @copyright  2025 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(qrcode::class)]
final class qrcode_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();

        $this->setAdminUser();

        $this->resetAfterTest();
    }

    /**
     * Teste create_adhoc_task().
     *
     * @return void
     */
    public function test_create_adhoc_task(): void {
        global $DB;

        $params = ['component' => 'local_apsolu', 'classname' => '\local_apsolu\task\record_missing_attendance'];

        // Génère un nouveau cours.
        $data = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_course_data();
        $course = new course();
        $course->save($data);

        // Génère une session de cours.
        $session = new attendancesession();
        $session->courseid = $course->id;
        $session->name = 'attendancesession 1';
        $session->save();

        // Génère un QR code.
        $settings = attendanceqrcode::get_default_settings();
        $settings->automark = 0;

        $qrcode = new attendanceqrcode();
        $qrcode->keycode = attendanceqrcode::generate_keycode();
        $qrcode->settings = $settings;
        $qrcode->sessionid = $session->id;
        $qrcode->save();

        $record = $DB->get_record(attendanceqrcode::TABLENAME, ['id' => $qrcode->id], $fields = '*', MUST_EXIST);

        // Contrôle qu'il n'y a aucune tâche en base de données.
        $this->assertSame(0, $DB->count_records('task_adhoc', $params));

        // Teste la création de tâche sur un QR code qui n'existe pas.
        $event = qrcode_updated::create([
            'objectid' => 0,
            'context' => context_course::instance($course->id),
        ]);

        qrcode::create_adhoc_task($event);
        $this->assertSame(0, $DB->count_records('task_adhoc', $params));

        // Teste la création de tâche lorsque l'option automark n'est pas activée.
        $record->settings = json_encode($settings);
        $DB->update_record(attendanceqrcode::TABLENAME, $record); // On passe par l'objet DB pour éviter de déclancher les events.

        $event = qrcode_updated::create([
            'objectid' => $qrcode->id,
            'context' => context_course::instance($course->id),
        ]);
        qrcode::create_adhoc_task($event);
        $this->assertSame(0, $DB->count_records('task_adhoc', $params));

        // Teste la création de tâche lorsque la session n'existe plus.
        $settings->automark = 1;
        $record->settings = json_encode($settings);
        $record->sessionid = 0;
        $DB->update_record(attendanceqrcode::TABLENAME, $record); // On passe par l'objet DB pour éviter de déclancher les events.

        qrcode::create_adhoc_task($event);
        $this->assertSame(0, $DB->count_records('task_adhoc', $params));

        // Valide l'insertion de la tâche.
        $record->sessionid = $session->id;
        $DB->update_record(attendanceqrcode::TABLENAME, $record); // On passe par l'objet DB pour éviter de déclancher les events.

        qrcode::create_adhoc_task($event);
        $this->assertSame(1, $DB->count_records('task_adhoc', $params));
    }

    /**
     * Teste delete_adhoc_task().
     *
     * @return void
     */
    public function test_delete_adhoc_task(): void {
        global $DB;

        $params = ['component' => 'local_apsolu', 'classname' => '\local_apsolu\task\record_missing_attendance'];

        // Génère un nouveau cours.
        $data = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_course_data();
        $course = new course();
        $course->save($data);

        // Génère une session de cours.
        $session = new attendancesession();
        $session->courseid = $course->id;
        $session->name = 'attendancesession 1';
        $session->save();

        // Génère un QR code.
        $qrcode = new attendanceqrcode();
        $qrcode->keycode = attendanceqrcode::generate_keycode();
        $qrcode->settings = attendanceqrcode::get_default_settings();
        $qrcode->settings->automark = 1;
        $qrcode->sessionid = $session->id;
        $qrcode->save();

        // Contrôle qu'il y a une tâche en base de données.
        $this->assertSame(1, $DB->count_records('task_adhoc', $params));

        // Teste la suppression d'une tâche sur un QR code qui n'existe pas.
        $event = qrcode_updated::create([
            'objectid' => 0,
            'context' => context_course::instance($course->id),
        ]);

        qrcode::delete_adhoc_task($event);
        $this->assertSame(1, $DB->count_records('task_adhoc', $params));

        // Valide la suppression d'une tâche.
        $event = qrcode_updated::create([
            'objectid' => $qrcode->id,
            'context' => context_course::instance($course->id),
        ]);

        qrcode::delete_adhoc_task($event);
        $this->assertSame(0, $DB->count_records('task_adhoc', $params));
    }
}
