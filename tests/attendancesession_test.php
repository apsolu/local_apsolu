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
 * Teste la classe local_apsolu\core\attendancesession
 *
 * @package    local_apsolu
 * @category   test
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_apsolu\core;

use stdClass;

/**
 * Classe de tests pour local_apsolu\core\attendancesession
 *
 * @package    local_apsolu
 * @category   test
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attendancesession_test extends \advanced_testcase {
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

    public function test_get_records() {
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

    public function test_load() {
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

    public function test_save() {
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
