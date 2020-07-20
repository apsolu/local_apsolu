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

defined('MOODLE_INTERNAL') || die();

/**
 * Classe de tests pour local_apsolu\core\attendancesession
 *
 * @package    local_apsolu
 * @category   test
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_core_attendancesession_testcase extends advanced_testcase {
    protected function setUp() {
        parent::setUp();

        $this->resetAfterTest();
    }

    public function test_delete() {
        global $DB;

        $attendancesession = new local_apsolu\core\attendancesession();

        // Supprime un objet inexistant.
        $result = $attendancesession->delete(1);
        $this->assertTrue($result);

        // Supprime un objet existant.
        $attendancesession->name = 'attendancesession 1';
        $attendancesession->save();

        $count_records = $DB->count_records($attendancesession::TABLENAME);
        $this->assertSame(1, $count_records);

        $result = $attendancesession->delete();
        $this->assertTrue($result);

        $count_records = $DB->count_records($attendancesession::TABLENAME);
        $this->assertSame(0, $count_records);
    }

    public function test_get_records() {
        global $DB;

        $attendancesession = new local_apsolu\core\attendancesession();

        $count_records = $DB->count_records($attendancesession::TABLENAME);
        $this->assertSame(0, $count_records);

        // Enregistre un nouvel objet.
        $attendancesession->name = 'attendancesession 1';
        $attendancesession->save();

        $count_records = $DB->count_records($attendancesession::TABLENAME);
        $this->assertSame(1, $count_records);

        // Enregistre un nouvel objet.
        $attendancesession->id = 0;
        $attendancesession->name = 'attendancesession 2';
        $attendancesession->save();

        $count_records = $DB->count_records($attendancesession::TABLENAME);
        $this->assertSame(2, $count_records);
    }

    public function test_load() {
        // Charge un objet inexistant.
        $attendancesession = new local_apsolu\core\attendancesession();
        $attendancesession->load(1);

        $this->assertSame(0, $attendancesession->id);
        $this->assertSame('', $attendancesession->name);

        // Charge un objet existant.
        $attendancesession->name = 'attendancesession';
        $attendancesession->save();

        $test = new local_apsolu\core\attendancesession();
        $test->load($attendancesession->id);

        $this->assertEquals($attendancesession->id, $test->id);
        $this->assertSame($attendancesession->name, $test->name);
    }

    public function test_save() {
        global $DB;

        $attendancesession = new local_apsolu\core\attendancesession();

        $initial_count = $DB->count_records($attendancesession::TABLENAME);

        // Enregistre un objet.
        $data = new stdClass();
        $data->name = 'attendancesession 1';

        $attendancesession->save($data);
        $count_records = $DB->count_records($attendancesession::TABLENAME);

        // Vérifie l'objet inséré.
        $this->assertSame($data->name, $attendancesession->name);
        $this->assertSame($count_records, $initial_count + 1);

        // Mets à jour l'objet.
        $data->name = 'attendancesession 1';

        $attendancesession->save($data);
        $count_records = $DB->count_records($attendancesession::TABLENAME);

        // Vérifie l'objet mis à jour.
        $this->assertSame($data->name, $attendancesession->name);
        $this->assertSame($count_records, $initial_count + 1);

        // Ajoute un nouvel objet (sans argument).
        $attendancesession->id = 0;
        $attendancesession->name = 'attendancesession 2';

        $attendancesession->save();
        $count_records = $DB->count_records($attendancesession::TABLENAME);

        // Vérifie l'objet ajouté.
        $this->assertSame($count_records, $initial_count + 2);
    }
}
