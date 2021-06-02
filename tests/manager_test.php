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
 * Teste la classe local_apsolu\core\manager
 *
 * @package    local_apsolu
 * @category   test
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Classe de tests pour local_apsolu\core\manager
 *
 * @package    local_apsolu
 * @category   test
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_core_manager_testcase extends advanced_testcase {
    protected function setUp() : void {
        parent::setUp();

        $this->resetAfterTest();
    }

    public function test_delete() {
        global $DB;

        $manager = new local_apsolu\core\manager();

        // Supprime un objet inexistant.
        $result = $manager->delete(1);
        $this->assertTrue($result);

        // Supprime un objet existant.
        $manager->name = 'manager 1';
        $manager->save();

        $count_records = $DB->count_records($manager::TABLENAME);
        $this->assertSame(1, $count_records);

        $result = $manager->delete();
        $this->assertTrue($result);

        $count_records = $DB->count_records($manager::TABLENAME);
        $this->assertSame(0, $count_records);
    }

    public function test_get_records() {
        global $DB;

        $manager = new local_apsolu\core\manager();

        $count_records = $DB->count_records($manager::TABLENAME);
        $this->assertSame(0, $count_records);

        // Enregistre un nouvel objet.
        $manager->name = 'manager 1';
        $manager->save();

        $count_records = $DB->count_records($manager::TABLENAME);
        $this->assertSame(1, $count_records);

        // Enregistre un nouvel objet.
        $manager->id = 0;
        $manager->name = 'manager 2';
        $manager->save();

        $count_records = $DB->count_records($manager::TABLENAME);
        $this->assertSame(2, $count_records);
    }

    public function test_load() {
        // Charge un objet inexistant.
        $manager = new local_apsolu\core\manager();
        $manager->load(1);

        $this->assertSame(0, $manager->id);
        $this->assertSame('', $manager->name);

        // Charge un objet existant.
        $manager->name = 'manager';
        $manager->save();

        $test = new local_apsolu\core\manager();
        $test->load($manager->id);

        $this->assertEquals($manager->id, $test->id);
        $this->assertSame($manager->name, $test->name);
    }

    public function test_save() {
        global $DB;

        $manager = new local_apsolu\core\manager();

        $initial_count = $DB->count_records($manager::TABLENAME);

        // Enregistre un objet.
        $data = new stdClass();
        $data->name = 'manager 1';

        $manager->save($data);
        $count_records = $DB->count_records($manager::TABLENAME);

        // Vérifie l'objet inséré.
        $this->assertSame($data->name, $manager->name);
        $this->assertSame($count_records, $initial_count + 1);

        // Mets à jour l'objet.
        $data->name = 'manager 1';

        $manager->save($data);
        $count_records = $DB->count_records($manager::TABLENAME);

        // Vérifie l'objet mis à jour.
        $this->assertSame($data->name, $manager->name);
        $this->assertSame($count_records, $initial_count + 1);

        // Ajoute un nouvel objet (sans argument).
        $manager->id = 0;
        $manager->name = 'manager 2';

        $manager->save();
        $count_records = $DB->count_records($manager::TABLENAME);

        // Vérifie l'objet ajouté.
        $this->assertSame($count_records, $initial_count + 2);

        // Teste la contrainte d'unicité.
        $data->id = 0;
        $data->name = 'manager 2';

        try {
            $manager->save($data);
            $this->fail('dml_write_exception expected for unique constraint violation.');
        } catch (dml_write_exception $exception) {
            $this->assertInstanceOf('dml_write_exception', $exception);
        }
    }
}
