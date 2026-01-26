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
use local_apsolu\core\location;
use stdClass;

/**
 * Classe de tests pour local_apsolu\core\location
 *
 * @package    local_apsolu
 * @copyright  2020 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \local_apsolu\core\location
 */
final class location_test extends \advanced_testcase {
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

        $location = new location();

        // Supprime un objet inexistant.
        $result = $location->delete(1);
        $this->assertTrue($result);

        // Supprime un objet existant.
        $location->name = 'location 1';
        $location->save();

        $countrecords = $DB->count_records($location::TABLENAME);
        $this->assertSame(1, $countrecords);

        $result = $location->delete();
        $this->assertTrue($result);

        $countrecords = $DB->count_records($location::TABLENAME);
        $this->assertSame(0, $countrecords);
    }

    /**
     * Teste get_records().
     *
     * @covers ::get_records()
     */
    public function test_get_records(): void {
        global $DB;

        $location = new location();

        $countrecords = $DB->count_records($location::TABLENAME);
        $this->assertSame(0, $countrecords);

        // Enregistre un nouvel objet.
        $location->name = 'location 1';
        $location->save();

        $countrecords = $DB->count_records($location::TABLENAME);
        $this->assertSame(1, $countrecords);

        // Enregistre un nouvel objet.
        $location->id = 0;
        $location->name = 'location 2';
        $location->save();

        $countrecords = $DB->count_records($location::TABLENAME);
        $this->assertSame(2, $countrecords);
    }

    /**
     * Teste load().
     *
     * @covers ::load()
     */
    public function test_load(): void {
        // Charge un objet inexistant.
        $location = new location();
        $location->load(1);

        $this->assertSame(0, $location->id);
        $this->assertSame('', $location->name);

        // Charge un objet existant.
        $location->name = 'location';
        $location->save();

        $test = new location();
        $test->load($location->id);

        $this->assertEquals($location->id, $test->id);
        $this->assertSame($location->name, $test->name);
    }

    /**
     * Teste save().
     *
     * @covers ::save()
     */
    public function test_save(): void {
        global $DB;

        $location = new location();

        $initialcount = $DB->count_records($location::TABLENAME);

        // Enregistre un objet.
        $data = new stdClass();
        $data->name = 'location 1';

        $location->save($data);
        $countrecords = $DB->count_records($location::TABLENAME);

        // Vérifie l'objet inséré.
        $this->assertSame($data->name, $location->name);
        $this->assertSame($countrecords, $initialcount + 1);

        // Mets à jour l'objet.
        $data->name = 'location 1';

        $location->save($data);
        $countrecords = $DB->count_records($location::TABLENAME);

        // Vérifie l'objet mis à jour.
        $this->assertSame($data->name, $location->name);
        $this->assertSame($countrecords, $initialcount + 1);

        // Ajoute un nouvel objet (sans argument).
        $location->id = 0;
        $location->name = 'location 2';

        $location->save();
        $countrecords = $DB->count_records($location::TABLENAME);

        // Vérifie l'objet ajouté.
        $this->assertSame($countrecords, $initialcount + 2);

        // Teste la contrainte d'unicité.
        $data->id = 0;
        $data->name = 'location 2';

        try {
            $location->save($data);
            $this->fail('dml_write_exception expected for unique constraint violation.');
        } catch (dml_write_exception $exception) {
            $this->assertInstanceOf('dml_write_exception', $exception);
        }
    }
}
