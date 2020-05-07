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
 * Teste la classe local_apsolu\core\city
 *
 * @package    local_apsolu
 * @category   phpunit
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Classe de tests pour local_apsolu\core\city
 */
class local_apsolu_core_city_testcase extends advanced_testcase {
    protected function setUp() {
        parent::setUp();

        $this->resetAfterTest();
    }

    public function test_delete() {
        global $DB;

        $city = new local_apsolu\core\city();

        // Supprime un objet inexistant.
        $result = $city->delete(1);
        $this->assertTrue($result);

        // Supprime un objet existant.
        $city->name = 'city 1';
        $city->save();

        $count_records = $DB->count_records($city::TABLENAME);
        $this->assertSame(1, $count_records);

        $result = $city->delete();
        $this->assertTrue($result);

        $count_records = $DB->count_records($city::TABLENAME);
        $this->assertSame(0, $count_records);
    }

    public function test_get_records() {
        global $DB;

        $city = new local_apsolu\core\city();

        $count_records = $DB->count_records($city::TABLENAME);
        $this->assertSame(0, $count_records);

        // Enregistre un nouvel objet.
        $city->name = 'city 1';
        $city->save();

        $count_records = $DB->count_records($city::TABLENAME);
        $this->assertSame(1, $count_records);

        // Enregistre un nouvel objet.
        $city->id = 0;
        $city->name = 'city 2';
        $city->save();

        $count_records = $DB->count_records($city::TABLENAME);
        $this->assertSame(2, $count_records);
    }

    public function test_load() {
        // Charge un objet inexistant.
        $city = new local_apsolu\core\city();
        $city->load(1);

        $this->assertSame(0, $city->id);
        $this->assertSame('', $city->name);

        // Charge un objet existant.
        $city->name = 'city';
        $city->save();

        $test = new local_apsolu\core\city();
        $test->load($city->id);

        $this->assertEquals($city->id, $test->id);
        $this->assertSame($city->name, $test->name);
    }

    public function test_save() {
        global $DB;

        $city = new local_apsolu\core\city();

        $initial_count = $DB->count_records($city::TABLENAME);

        // Enregistre un objet.
        $data = new stdClass();
        $data->name = 'city 1';

        $city->save($data);
        $count_records = $DB->count_records($city::TABLENAME);

        // Vérifie l'objet inséré.
        $this->assertSame($data->name, $city->name);
        $this->assertSame($count_records, $initial_count + 1);

        // Mets à jour l'objet.
        $data->name = 'city 1';

        $city->save($data);
        $count_records = $DB->count_records($city::TABLENAME);

        // Vérifie l'objet mis à jour.
        $this->assertSame($data->name, $city->name);
        $this->assertSame($count_records, $initial_count + 1);

        // Ajoute un nouvel objet (sans argument).
        $city->id = 0;
        $city->name = 'city 2';

        $city->save();
        $count_records = $DB->count_records($city::TABLENAME);

        // Vérifie l'objet ajouté.
        $this->assertSame($count_records, $initial_count + 2);

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
