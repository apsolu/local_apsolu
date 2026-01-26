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
use local_apsolu\core\city;
use stdClass;

/**
 * Classe de tests pour local_apsolu\core\city
 *
 * @package    local_apsolu
 * @copyright  2020 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \local_apsolu\core\city
 */
final class city_test extends \advanced_testcase {
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

        $city = new city();

        // Supprime un objet inexistant.
        $result = $city->delete(1);
        $this->assertTrue($result);

        // Supprime un objet existant.
        $city->name = 'city 1';
        $city->save();

        $countrecords = $DB->count_records($city::TABLENAME);
        $this->assertSame(1, $countrecords);

        $result = $city->delete();
        $this->assertTrue($result);

        $countrecords = $DB->count_records($city::TABLENAME);
        $this->assertSame(0, $countrecords);
    }

    /**
     * Teste get_records().
     *
     * @covers ::get_records()
     */
    public function test_get_records(): void {
        global $DB;

        $city = new city();

        $countrecords = $DB->count_records($city::TABLENAME);
        $this->assertSame(0, $countrecords);

        // Enregistre un nouvel objet.
        $city->name = 'city 1';
        $city->save();

        $countrecords = $DB->count_records($city::TABLENAME);
        $this->assertSame(1, $countrecords);

        // Enregistre un nouvel objet.
        $city->id = 0;
        $city->name = 'city 2';
        $city->save();

        $countrecords = $DB->count_records($city::TABLENAME);
        $this->assertSame(2, $countrecords);
    }

    /**
     * Teste load().
     *
     * @covers ::load()
     */
    public function test_load(): void {
        // Charge un objet inexistant.
        $city = new city();
        $city->load(1);

        $this->assertSame(0, $city->id);
        $this->assertSame('', $city->name);

        // Charge un objet existant.
        $city->name = 'city';
        $city->save();

        $test = new city();
        $test->load($city->id);

        $this->assertEquals($city->id, $test->id);
        $this->assertSame($city->name, $test->name);
    }

    /**
     * Teste save().
     *
     * @covers ::save()
     */
    public function test_save(): void {
        global $DB;

        $city = new city();

        $initialcount = $DB->count_records($city::TABLENAME);

        // Enregistre un objet.
        $data = new stdClass();
        $data->name = 'city 1';

        $city->save($data);
        $countrecords = $DB->count_records($city::TABLENAME);

        // Vérifie l'objet inséré.
        $this->assertSame($data->name, $city->name);
        $this->assertSame($countrecords, $initialcount + 1);

        // Mets à jour l'objet.
        $data->name = 'city 1';

        $city->save($data);
        $countrecords = $DB->count_records($city::TABLENAME);

        // Vérifie l'objet mis à jour.
        $this->assertSame($data->name, $city->name);
        $this->assertSame($countrecords, $initialcount + 1);

        // Ajoute un nouvel objet (sans argument).
        $city->id = 0;
        $city->name = 'city 2';

        $city->save();
        $countrecords = $DB->count_records($city::TABLENAME);

        // Vérifie l'objet ajouté.
        $this->assertSame($countrecords, $initialcount + 2);

        // Teste la contrainte d'unicité.
        $data->id = 0;
        $data->name = 'city 2';

        try {
            $city->save($data);
            $this->fail('dml_write_exception expected for unique constraint violation.');
        } catch (dml_write_exception $exception) {
            $this->assertInstanceOf('dml_write_exception', $exception);
        }
    }
}
