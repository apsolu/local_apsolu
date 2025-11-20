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

namespace local_apsolu\attendance;

use local_apsolu\attendance\qrcode;
use local_apsolu\core\attendancepresence;
use local_apsolu\core\attendancesession;
use local_apsolu\core\attendance\status;
use local_apsolu\core\course;

/**
 * Classe de tests pour local_apsolu\attendance\qrcode
 *
 * @package    local_apsolu
 * @category   test
 * @copyright  2025 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \local_apsolu\attendance\qrcode
 */
final class qrcode_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();

        $this->resetAfterTest();
    }

    /**
     * Teste delete().
     *
     * @covers ::delete()
     */
    public function test_delete(): void {
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
        $qrcode = new qrcode();
        $qrcode->keycode = qrcode::generate_keycode();
        $qrcode->settings = qrcode::get_default_settings();
        $qrcode->sessionid = $session->id;
        $qrcode->save();

        $countrecords = count(qrcode::get_records());
        $this->assertSame(1, $countrecords);

        // Supprime le QR code.
        $result = $qrcode->delete();
        $this->assertTrue($result);

        $countrecords = count(qrcode::get_records());
        $this->assertSame(0, $countrecords);
    }

    /**
     * Teste generate_keycode().
     *
     * @covers ::generate_keycode()
     */
    public function test_generate_keycode(): void {
        $this->assertIsString(qrcode::generate_keycode());
    }

    /**
     * Teste get_default_settings().
     *
     * @covers ::get_default_settings()
     */
    public function test_get_default_settings(): void {
        $this->assertIsObject(qrcode::get_default_settings());
        $this->assertCount(10, (array) qrcode::get_default_settings());
    }

    /**
     * Teste save().
     *
     * @covers ::save()
     */
    public function test_save(): void {
        // Génère un nouveau cours.
        $data = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_course_data();
        $course = new course();
        $course->save($data);

        // Génère une session de cours.
        $session1 = new attendancesession();
        $session1->courseid = $course->id;
        $session1->name = 'attendancesession 1';
        $session1->save();

        $session2 = new attendancesession();
        $session2->courseid = $course->id;
        $session2->name = 'attendancesession 2';
        $session2->save();

        $countrecords = count(qrcode::get_records());
        $this->assertSame(0, $countrecords);

        // Génère un QR code.
        $qrcode = new qrcode();
        $qrcode->keycode = qrcode::generate_keycode();
        $qrcode->settings = qrcode::get_default_settings();
        $qrcode->sessionid = $session1->id;
        $qrcode->save();
        $firstqrcodeid = $qrcode->id;

        $this->assertIsInt($qrcode->id);
        $countrecords = count(qrcode::get_records());
        $this->assertSame(1, $countrecords);

        // Génère un QR code pour une nouvelle session.
        $qrcode = new qrcode();
        $qrcode->keycode = qrcode::generate_keycode();
        $qrcode->settings = qrcode::get_default_settings();
        $qrcode->sessionid = $session2->id;
        $qrcode->id = $qrcode->save();

        $countrecords = count(qrcode::get_records());
        $this->assertSame(2, $countrecords);

        // Génère un QR code pour une session déjà associée à un autre QR code. À la fin, il ne doit pas avoir 3 QR codes en base.
        $qrcode = new qrcode();
        $qrcode->keycode = qrcode::generate_keycode();
        $qrcode->settings = qrcode::get_default_settings();
        $qrcode->sessionid = $session1->id;
        $qrcode->id = $qrcode->save();

        $this->assertNotEquals($qrcode->id, $firstqrcodeid);
        $countrecords = count(qrcode::get_records());
        $this->assertSame(2, $countrecords);

        // Met à jour le QR code.
        $qrcode->keycode = qrcode::generate_keycode();
        $qrcode->save();
        $updatedqrcodeid = $qrcode->id;

        $this->assertEquals($qrcode->id, $updatedqrcodeid);
        $countrecords = count(qrcode::get_records());
        $this->assertSame(2, $countrecords);

        // Tester avec de la data.
    }

    /**
     * Teste set_default_settings().
     *
     * @covers ::set_default_settings()
     */
    public function test_set_default_settings(): void {
        // TODO.
    }

    /**
     * Teste sign().
     *
     * @covers ::sign()
     */
    public function test_sign(): void {
        // TODO.
    }
}
