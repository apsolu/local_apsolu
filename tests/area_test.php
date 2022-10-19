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
 * Teste la classe local_apsolu\core\area
 *
 * @package    local_apsolu
 * @category   test
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_apsolu\core;

use dml_write_exception;
use stdClass;

/**
 * Classe de tests pour local_apsolu\core\area
 *
 * @package    local_apsolu
 * @category   test
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class area_test extends \advanced_testcase {
    protected function setUp() : void {
        parent::setUp();

        $this->resetAfterTest();
    }

    public function test_delete() {
        global $DB;

        $area = new area();

        // Supprime un objet inexistant.
        $result = $area->delete(1);
        $this->assertTrue($result);

        // Supprime un objet existant.
        $area->name = 'area 1';
        $area->cityid = '1';
        $area->save();

        $count_records = $DB->count_records($area::TABLENAME);
        $this->assertSame(1, $count_records);

        $result = $area->delete();
        $this->assertTrue($result);

        $count_records = $DB->count_records($area::TABLENAME);
        $this->assertSame(0, $count_records);
    }

    public function test_get_records() {
        global $DB;

        $area = new area();

        $count_records = $DB->count_records($area::TABLENAME);
        $this->assertSame(0, $count_records);

        // Enregistre un nouvel objet.
        $area->name = 'area 1';
        $area->cityid = '1';
        $area->save();

        $count_records = $DB->count_records($area::TABLENAME);
        $this->assertSame(1, $count_records);

        // Enregistre un nouvel objet.
        $area->id = 0;
        $area->name = 'area 2';
        $area->cityid = '2';
        $area->save();

        $count_records = $DB->count_records($area::TABLENAME);
        $this->assertSame(2, $count_records);
    }

    public function test_load() {
        // Charge un objet inexistant.
        $area = new area();
        $area->load(1);

        $this->assertSame(0, $area->id);
        $this->assertSame('', $area->name);
        $this->assertSame('', $area->cityid);

        // Charge un objet existant.
        $area->name = 'area';
        $area->cityid = '1';
        $area->save();

        $test = new area();
        $test->load($area->id);

        $this->assertEquals($area->id, $test->id);
        $this->assertSame($area->name, $test->name);
        $this->assertSame($area->cityid, $test->cityid);
    }

    public function test_save() {
        global $DB;

        $area = new area();

        $initial_count = $DB->count_records($area::TABLENAME);

        // Enregistre un objet.
        $data = new stdClass();
        $data->name = 'area 1';
        $data->cityid = '1';

        $area->save($data);
        $count_records = $DB->count_records($area::TABLENAME);

        // Vérifie l'objet inséré.
        $this->assertSame($data->name, $area->name);
        $this->assertSame($data->cityid, $area->cityid);
        $this->assertSame($count_records, $initial_count + 1);

        // Mets à jour l'objet.
        $data->name = 'area 1';
        $data->cityid = '2';

        $area->save($data);
        $count_records = $DB->count_records($area::TABLENAME);

        // Vérifie l'objet mis à jour.
        $this->assertSame($data->name, $area->name);
        $this->assertSame($data->cityid, $area->cityid);
        $this->assertSame($count_records, $initial_count + 1);

        // Ajoute un nouvel objet (sans argument).
        $area->id = 0;
        $area->name = 'area 2';
        $area->cityid = '1';

        $area->save();
        $count_records = $DB->count_records($area::TABLENAME);

        // Vérifie l'objet ajouté.
        $this->assertSame($count_records, $initial_count + 2);

        // Teste la contrainte d'unicité.
        $data->id = 0;
        $data->name = 'area 2';

        try {
            $area->save($data);
            $this->fail('dml_write_exception expected for unique constraint violation.');
        } catch (dml_write_exception $exception) {
            $this->assertInstanceOf('dml_write_exception', $exception);
        }
    }
}
