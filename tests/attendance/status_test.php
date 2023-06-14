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
 * Teste la classe local_apsolu\core\attendance\status
 *
 * @package    local_apsolu
 * @category   test
 * @copyright  2023 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_apsolu\core\attendance;

use local_apsolu\core\attendancepresence;
use local_apsolu\core\attendancesession;
use local_apsolu\core\course;

/**
 * Classe de tests pour local_apsolu\core\attendance\status
 *
 * @package    local_apsolu
 * @category   test
 * @copyright  2023 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class status_test extends \advanced_testcase {
    protected function setUp() : void {
        parent::setUp();

        $this->resetAfterTest();
    }

    public function test_delete() {
        global $DB;

        // Génère un nouveau cours.
        $data = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_course_data();
        $course = new course();
        $course->save($data);

        // Génère une session de cours.
        $attendancesession = new attendancesession();
        $attendancesession->courseid = $course->id;
        $attendancesession->name = 'attendancesession 1';
        $attendancesession->save();

        // Génère des présences.
        $attendancepresence = new attendancepresence();
        $attendancepresence->studentid = 1;
        $attendancepresence->statusid = 1;
        $attendancepresence->sessionid = $attendancesession->id;
        $attendancepresence->save();

        $attendancepresence = new attendancepresence();
        $attendancepresence->studentid = 2;
        $attendancepresence->statusid = 2;
        $attendancepresence->sessionid = $attendancesession->id;
        $attendancepresence->save();

        $countrecords = $DB->count_records($attendancepresence::TABLENAME);
        $this->assertSame(2, $countrecords);

        $status = new status();
        $status->load(1);
        $result = $status->delete();
        $this->assertTrue($result);

        $countrecords = $DB->count_records($attendancepresence::TABLENAME);
        $this->assertSame(1, $countrecords);

        $status = new status();
        $status->load(2);
        $this->assertEquals(1, $status->sortorder);

        $result = $status->delete();
        $this->assertTrue($result);

        $countrecords = $DB->count_records($attendancepresence::TABLENAME);
        $this->assertSame(0, $countrecords);
    }

    public function test_save() {
        global $DB;

        $status = new status();

        $initialcount = $DB->count_records($status::TABLENAME);

        $status->save();
        $this->assertEquals($initialcount + 1, $status->sortorder);
    }
}
