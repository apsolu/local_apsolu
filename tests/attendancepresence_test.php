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
 * Teste la classe local_apsolu\core\attendancepresence
 *
 * @package    local_apsolu
 * @category   test
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Classe de tests pour local_apsolu\core\attendancepresence
 *
 * @package    local_apsolu
 * @category   test
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_core_attendancepresence_testcase extends advanced_testcase {
    protected function setUp() : void {
        parent::setUp();

        $this->resetAfterTest();
    }

    public function test_delete() {
        global $DB;

        $attendancepresence = new local_apsolu\core\attendancepresence();

        // Supprime un objet inexistant.
        $result = $attendancepresence->delete(1);
        $this->assertTrue($result);

        // Supprime un objet existant.
        $attendancepresence->name = 'attendancepresence 1';
        $attendancepresence->save();

        $count_records = $DB->count_records($attendancepresence::TABLENAME);
        $this->assertSame(1, $count_records);

        $result = $attendancepresence->delete();
        $this->assertTrue($result);

        $count_records = $DB->count_records($attendancepresence::TABLENAME);
        $this->assertSame(0, $count_records);
    }

    public function test_get_records() {
        global $DB;

        $attendancepresence = new local_apsolu\core\attendancepresence();

        $count_records = $DB->count_records($attendancepresence::TABLENAME);
        $this->assertSame(0, $count_records);

        // Enregistre un nouvel objet.
        $attendancepresence->studentid = 1;
        $attendancepresence->sessionid = 1;
        $attendancepresence->save();

        $count_records = $DB->count_records($attendancepresence::TABLENAME);
        $this->assertSame(1, $count_records);

        // Enregistre un nouvel objet.
        $attendancepresence->id = 0;
        $attendancepresence->studentid = 1;
        $attendancepresence->sessionid = 2;
        $attendancepresence->save();

        $count_records = $DB->count_records($attendancepresence::TABLENAME);
        $this->assertSame(2, $count_records);
    }

    public function test_load() {
        // Charge un objet inexistant.
        $attendancepresence = new local_apsolu\core\attendancepresence();
        $attendancepresence->load(1);

        $this->assertSame(0, $attendancepresence->id);

        // Charge un objet existant.
        $attendancepresence->name = 'attendancepresence';
        $attendancepresence->save();

        $test = new local_apsolu\core\attendancepresence();
        $test->load($attendancepresence->id);

        $this->assertEquals($attendancepresence->id, $test->id);
    }

    public function test_save() {
        global $DB;

        $attendancepresence = new local_apsolu\core\attendancepresence();

        $initial_count = $DB->count_records($attendancepresence::TABLENAME);

        // Enregistre un objet.
        $data = new stdClass();
        $data->studentid = 1;
        $data->sessionid = 1;

        $attendancepresence->save($data);
        $count_records = $DB->count_records($attendancepresence::TABLENAME);

        // Vérifie l'objet inséré.
        $this->assertSame($count_records, $initial_count + 1);

        // Mets à jour l'objet.
        $data->studentid = 2;

        $attendancepresence->save($data);
        $count_records = $DB->count_records($attendancepresence::TABLENAME);

        // Vérifie l'objet mis à jour.
        $this->assertSame($count_records, $initial_count + 1);

        // Ajoute un nouvel objet (sans argument).
        $attendancepresence->id = 0;
        $attendancepresence->studentid = 3;
        $attendancepresence->sessionid = 3;

        $attendancepresence->save();
        $count_records = $DB->count_records($attendancepresence::TABLENAME);

        // Vérifie l'objet ajouté.
        $this->assertSame($count_records, $initial_count + 2);

        // Teste la contrainte d'unicité.
        try {
            $attendancepresence->id = 0;
            $attendancepresence->save($data);
            $this->fail('dml_write_exception expected for unique constraint violation.');
        } catch (dml_write_exception $exception) {
            $this->assertInstanceOf('dml_write_exception', $exception);
        }
    }
}
