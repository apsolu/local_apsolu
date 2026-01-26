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

use dml_write_exception;
use local_apsolu\core\attendancepresence;
use stdClass;

/**
 * Classe de tests pour local_apsolu\core\attendancepresence
 *
 * @package    local_apsolu
 * @copyright  2020 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \local_apsolu\core\attendancepresence
 */
final class attendancepresence_test extends \advanced_testcase {
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
        global $DB;

        $attendancepresence = new attendancepresence();

        // Supprime un objet inexistant.
        $result = $attendancepresence->delete(1);
        $this->assertTrue($result);

        // Supprime un objet existant.
        $attendancepresence->name = 'attendancepresence 1';
        $attendancepresence->save();

        $countrecords = $DB->count_records($attendancepresence::TABLENAME);
        $this->assertSame(1, $countrecords);

        $result = $attendancepresence->delete();
        $this->assertTrue($result);

        $countrecords = $DB->count_records($attendancepresence::TABLENAME);
        $this->assertSame(0, $countrecords);
    }

    /**
     * Teste get_records().
     *
     * @covers ::get_records()
     */
    public function test_get_records(): void {
        global $DB;

        $attendancepresence = new attendancepresence();

        $countrecords = $DB->count_records($attendancepresence::TABLENAME);
        $this->assertSame(0, $countrecords);

        // Enregistre un nouvel objet.
        $attendancepresence->studentid = 1;
        $attendancepresence->sessionid = 1;
        $attendancepresence->save();

        $countrecords = $DB->count_records($attendancepresence::TABLENAME);
        $this->assertSame(1, $countrecords);

        // Enregistre un nouvel objet.
        $attendancepresence->id = 0;
        $attendancepresence->studentid = 1;
        $attendancepresence->sessionid = 2;
        $attendancepresence->save();

        $countrecords = $DB->count_records($attendancepresence::TABLENAME);
        $this->assertSame(2, $countrecords);
    }

    /**
     * Teste load().
     *
     * @covers ::load()
     */
    public function test_load(): void {
        // Charge un objet inexistant.
        $attendancepresence = new attendancepresence();
        $attendancepresence->load(1);

        $this->assertSame(0, $attendancepresence->id);

        // Charge un objet existant.
        $attendancepresence->name = 'attendancepresence';
        $attendancepresence->save();

        $test = new attendancepresence();
        $test->load($attendancepresence->id);

        $this->assertEquals($attendancepresence->id, $test->id);
    }

    /**
     * Teste save().
     *
     * @covers ::save()
     */
    public function test_save(): void {
        global $DB;

        $attendancepresence = new attendancepresence();

        $initialcount = $DB->count_records($attendancepresence::TABLENAME);

        // Enregistre un objet.
        $data = new stdClass();
        $data->studentid = 1;
        $data->sessionid = 1;

        $attendancepresence->save($data);
        $countrecords = $DB->count_records($attendancepresence::TABLENAME);

        // Vérifie l'objet inséré.
        $this->assertSame($countrecords, $initialcount + 1);

        // Mets à jour l'objet.
        $data->studentid = 2;

        $attendancepresence->save($data);
        $countrecords = $DB->count_records($attendancepresence::TABLENAME);

        // Vérifie l'objet mis à jour.
        $this->assertSame($countrecords, $initialcount + 1);

        // Ajoute un nouvel objet (sans argument).
        $attendancepresence->id = 0;
        $attendancepresence->studentid = 3;
        $attendancepresence->sessionid = 3;

        $attendancepresence->save();
        $countrecords = $DB->count_records($attendancepresence::TABLENAME);

        // Vérifie l'objet ajouté.
        $this->assertSame($countrecords, $initialcount + 2);

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
