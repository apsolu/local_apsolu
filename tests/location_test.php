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
 * Teste la classe local_apsolu\core\location
 *
 * @package    local_apsolu
 * @category   test
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Classe de tests pour local_apsolu\core\location
 *
 * @package    local_apsolu
 * @category   test
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_core_location_testcase extends advanced_testcase {
    protected function setUp() : void {
        parent::setUp();

        $this->resetAfterTest();
    }

    public function test_delete() {
        global $DB;

        $location = new local_apsolu\core\location();

        // Supprime un objet inexistant.
        $result = $location->delete(1);
        $this->assertTrue($result);

        // Supprime un objet existant.
        $location->name = 'location 1';
        $location->save();

        $count_records = $DB->count_records($location::TABLENAME);
        $this->assertSame(1, $count_records);

        $result = $location->delete();
        $this->assertTrue($result);

        $count_records = $DB->count_records($location::TABLENAME);
        $this->assertSame(0, $count_records);
    }

    public function test_get_records() {
        global $DB;

        $location = new local_apsolu\core\location();

        $count_records = $DB->count_records($location::TABLENAME);
        $this->assertSame(0, $count_records);

        // Enregistre un nouvel objet.
        $location->name = 'location 1';
        $location->save();

        $count_records = $DB->count_records($location::TABLENAME);
        $this->assertSame(1, $count_records);

        // Enregistre un nouvel objet.
        $location->id = 0;
        $location->name = 'location 2';
        $location->save();

        $count_records = $DB->count_records($location::TABLENAME);
        $this->assertSame(2, $count_records);
    }

    public function test_load() {
        // Charge un objet inexistant.
        $location = new local_apsolu\core\location();
        $location->load(1);

        $this->assertSame(0, $location->id);
        $this->assertSame('', $location->name);

        // Charge un objet existant.
        $location->name = 'location';
        $location->save();

        $test = new local_apsolu\core\location();
        $test->load($location->id);

        $this->assertEquals($location->id, $test->id);
        $this->assertSame($location->name, $test->name);
    }

    public function test_save() {
        global $DB;

        $location = new local_apsolu\core\location();

        $initial_count = $DB->count_records($location::TABLENAME);

        // Enregistre un objet.
        $data = new stdClass();
        $data->name = 'location 1';

        $location->save($data);
        $count_records = $DB->count_records($location::TABLENAME);

        // Vérifie l'objet inséré.
        $this->assertSame($data->name, $location->name);
        $this->assertSame($count_records, $initial_count + 1);

        // Mets à jour l'objet.
        $data->name = 'location 1';

        $location->save($data);
        $count_records = $DB->count_records($location::TABLENAME);

        // Vérifie l'objet mis à jour.
        $this->assertSame($data->name, $location->name);
        $this->assertSame($count_records, $initial_count + 1);

        // Ajoute un nouvel objet (sans argument).
        $location->id = 0;
        $location->name = 'location 2';

        $location->save();
        $count_records = $DB->count_records($location::TABLENAME);

        // Vérifie l'objet ajouté.
        $this->assertSame($count_records, $initial_count + 2);

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
