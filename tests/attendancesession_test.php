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

namespace local_apsolu;

use local_apsolu\core\attendancesession;
use local_apsolu\core\course;
use stdClass;

/**
 * Classe de tests pour local_apsolu\core\attendancesession
 *
 * @package    local_apsolu
 * @copyright  2020 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(attendancesession::class)]
final class attendancesession_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();

        $this->resetAfterTest();
    }

    /**
     * Teste delete().
     *
     * @return void
     */
    public function test_delete(): void {
        global $DB;

        // Génère un nouveau cours.
        $data = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_course_data();
        $course = new course();
        $course->save($data);

        // Génère une session de cours.
        $attendancesession = new attendancesession();
        $attendancesession->courseid = $course->id;

        // Supprime un objet inexistant.
        $result = $attendancesession->delete(1);
        $this->assertTrue($result);

        // Supprime un objet existant.
        $attendancesession->name = 'attendancesession 1';
        $attendancesession->save();

        $countrecords = $DB->count_records($attendancesession::TABLENAME);
        $this->assertSame(1, $countrecords);

        $result = $attendancesession->delete();
        $this->assertTrue($result);

        $countrecords = $DB->count_records($attendancesession::TABLENAME);
        $this->assertSame(0, $countrecords);
    }

    /**
     * Teste get_records().
     *
     * @return void
     */
    public function test_get_records(): void {
        global $DB;

        // Génère un nouveau cours.
        $data = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_course_data();
        $course = new course();
        $course->save($data);

        // Génère une session de cours.
        $attendancesession = new attendancesession();
        $attendancesession->courseid = $course->id;

        $countrecords = $DB->count_records($attendancesession::TABLENAME);
        $this->assertSame(0, $countrecords);

        // Enregistre un nouvel objet.
        $attendancesession->name = 'attendancesession 1';
        $attendancesession->save();

        $countrecords = $DB->count_records($attendancesession::TABLENAME);
        $this->assertSame(1, $countrecords);

        // Enregistre un nouvel objet.
        $attendancesession->id = 0;
        $attendancesession->name = 'attendancesession 2';
        $attendancesession->save();

        $countrecords = $DB->count_records($attendancesession::TABLENAME);
        $this->assertSame(2, $countrecords);
    }

    /**
     * Teste has_expired().
     *
     * @return void
     */
    public function test_has_expired(): void {
        // Génère un nouveau cours dont les sessions durent 1 heure.
        $data = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_course_data();
        $data->starttime = '12:00';
        $data->endtime = '13:00';
        $course = new course();
        $course->save($data);

        // Génère une session de cours.
        $attendancesession = new attendancesession();
        $attendancesession->courseid = $course->id;

        $attendancesession->sessiontime = time() - DAYSECS; // Teste une session qui a commencé la veille.
        $this->assertTrue($attendancesession->has_expired());

        $attendancesession->sessiontime = time() - HOURSECS - MINSECS; // Teste une session qui s'est terminée depuis 1 minute.
        $this->assertTrue($attendancesession->has_expired());

        $attendancesession->sessiontime = time(); // Teste une session qui commence à l'instant.
        $this->assertFalse($attendancesession->has_expired());

        $attendancesession->sessiontime = time() - HOURSECS + MINSECS; // Teste une session qui se termine dans 1 minute.
        $this->assertFalse($attendancesession->has_expired());

        $attendancesession->sessiontime = time() + DAYSECS; // Teste une session qui commence le lendemain.
        $this->assertFalse($attendancesession->has_expired());
    }

    /**
     * Teste has_started().
     *
     * @return void
     */
    public function test_has_started(): void {
        // Génère un nouveau cours dont les sessions durent 1 heure.
        $data = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_course_data();
        $data->starttime = '12:00';
        $data->endtime = '13:00';
        $course = new course();
        $course->save($data);

        // Génère une session de cours.
        $attendancesession = new attendancesession();
        $attendancesession->courseid = $course->id;

        $attendancesession->sessiontime = time() - DAYSECS; // Teste une session qui a commencé la veille.
        $this->assertTrue($attendancesession->has_started());

        $attendancesession->sessiontime = time() - MINSECS; // Teste une session qui a commencé depuis 1 minute.
        $this->assertTrue($attendancesession->has_started());

        $attendancesession->sessiontime = time(); // Teste une session qui commence à l'instant.
        $this->assertTrue($attendancesession->has_started());

        $attendancesession->sessiontime = time() + MINSECS; // Teste une session qui commence dans 1 minute.
        $this->assertFalse($attendancesession->has_started());

        $attendancesession->sessiontime = time() + DAYSECS; // Teste une session qui commence le lendemain.
        $this->assertFalse($attendancesession->has_started());
    }

    /**
     * Teste is_in_progress().
     *
     * @return void
     */
    public function test_is_in_progress(): void {
        // Génère un nouveau cours dont les sessions durent 1 heure.
        $data = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_course_data();
        $data->starttime = '12:00';
        $data->endtime = '13:00';
        $course = new course();
        $course->save($data);

        // Génère une session de cours.
        $attendancesession = new attendancesession();
        $attendancesession->courseid = $course->id;

        $attendancesession->sessiontime = time() - DAYSECS; // Teste une session qui a commencé la veille.
        $this->assertFalse($attendancesession->is_in_progress());

        $attendancesession->sessiontime = time() + MINSECS; // Teste une session qui commence dans 1 minute.
        $this->assertFalse($attendancesession->is_in_progress());

        $attendancesession->sessiontime = time(); // Teste une session qui commence à l'instant.
        $this->assertTrue($attendancesession->is_in_progress());

        $attendancesession->sessiontime = time() - MINSECS; // Teste une session qui a commencé depuis 1 minute.
        $this->assertTrue($attendancesession->is_in_progress());

        $attendancesession->sessiontime = time() - HOURSECS + MINSECS; // Teste une session qui se termine dans 1 minute.
        $this->assertTrue($attendancesession->is_in_progress());

        $attendancesession->sessiontime = time() - HOURSECS - MINSECS; // Teste une session qui s'est terminée depuis 1 minute.
        $this->assertFalse($attendancesession->is_in_progress());

        $attendancesession->sessiontime = time() + DAYSECS; // Teste une session qui commence le lendemain.
        $this->assertFalse($attendancesession->is_in_progress());
    }

    /**
     * Teste load().
     *
     * @return void
     */
    public function test_load(): void {
        // Génère un nouveau cours.
        $data = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_course_data();
        $course = new course();
        $course->save($data);

        // Charge un objet inexistant.
        $attendancesession = new attendancesession();
        $attendancesession->load(1);

        $this->assertSame(0, $attendancesession->id);
        $this->assertSame('', $attendancesession->name);

        // Charge un objet existant.
        $attendancesession->name = 'attendancesession';
        $attendancesession->courseid = $course->id;
        $attendancesession->save();

        $test = new attendancesession();
        $test->load($attendancesession->id);

        $this->assertEquals($attendancesession->id, $test->id);
        $this->assertSame($attendancesession->name, $test->name);
    }

    /**
     * Teste save().
     *
     * @return void
     */
    public function test_save(): void {
        global $DB;

        // Génère un nouveau cours.
        $data = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_course_data();
        $course = new course();
        $course->save($data);

        // Génère une session de cours.
        $attendancesession = new attendancesession();
        $attendancesession->courseid = $course->id;

        $initialcount = $DB->count_records($attendancesession::TABLENAME);

        // Enregistre un objet.
        $data = new stdClass();
        $data->name = 'attendancesession 1';

        $attendancesession->save($data);
        $countrecords = $DB->count_records($attendancesession::TABLENAME);

        // Vérifie l'objet inséré.
        $this->assertSame($data->name, $attendancesession->name);
        $this->assertSame($countrecords, $initialcount + 1);

        // Mets à jour l'objet.
        $data->name = 'attendancesession 1';

        $attendancesession->save($data);
        $countrecords = $DB->count_records($attendancesession::TABLENAME);

        // Vérifie l'objet mis à jour.
        $this->assertSame($data->name, $attendancesession->name);
        $this->assertSame($countrecords, $initialcount + 1);

        // Ajoute un nouvel objet (sans argument).
        $attendancesession->id = 0;
        $attendancesession->name = 'attendancesession 2';

        $attendancesession->save();
        $countrecords = $DB->count_records($attendancesession::TABLENAME);

        // Vérifie l'objet ajouté.
        $this->assertSame($countrecords, $initialcount + 2);
    }
}
