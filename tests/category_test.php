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
 * Teste la classe local_apsolu\core\category
 *
 * @package    local_apsolu
 * @category   test
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Classe de tests pour local_apsolu\core\category
 *
 * @package    local_apsolu
 * @category   test
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_core_category_testcase extends advanced_testcase {
    protected function setUp() {
        parent::setUp();

        $this->setAdminUser();

        $this->resetAfterTest();
    }

    public function test_delete() {
        global $DB;

        $category = new local_apsolu\core\category();

        // Supprime un objet inexistant.
        try {
            $result = $category->delete(1);
            $this->fail('moodle_exception expected on non-existing record.');
        } catch (moodle_exception $exception) {
            $this->assertInstanceOf('moodle_exception', $exception);
        }

        // Ajoute un objet.
        list($data, $mform) = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_category_data();
        $category->save($data, $mform);

        // Supprime un objet existant.
        $count_records = $DB->count_records($category::TABLENAME);
        $this->assertSame(1, $count_records);

        set_config('defaultrequestcategory', 1);
        $result = $category->delete();
        $this->assertTrue($result);

        $count_records = $DB->count_records($category::TABLENAME);
        $this->assertSame(0, $count_records);
    }

    public function test_get_records() {
        global $DB;

        $category = new local_apsolu\core\category();

        $count_records = $DB->count_records($category::TABLENAME);
        $this->assertSame(0, $count_records);

        // Enregistre un nouvel objet.
        list($data, $mform) = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_category_data();
        $category->save($data, $mform);

        $count_records = $DB->count_records($category::TABLENAME);
        $this->assertSame(1, $count_records);

        // Enregistre un nouvel objet.
        $category->id = 0;
        list($data, $mform) = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_category_data();
        $category->save($data, $mform);

        $count_records = $DB->count_records($category::TABLENAME);
        $this->assertSame(2, $count_records);
    }

    public function test_load() {
        // Charge un objet inexistant.
        $category = new local_apsolu\core\category();
        $category->load(1);

        $this->assertSame(0, $category->id);
        $this->assertSame('', $category->name);

        // Charge un objet existant.
        list($data, $mform) = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_category_data();
        $category->save($data, $mform);

        $test = new local_apsolu\core\category();
        $test->load($category->id);

        $this->assertEquals($category->id, $test->id);
        $this->assertSame($category->name, $test->name);
    }

    public function test_save() {
        global $DB;

        $category = new local_apsolu\core\category();

        $initial_count = $DB->count_records($category::TABLENAME);

        // Enregistre un objet.
        list($data, $mform) = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_category_data();
        $category->save($data, $mform);
        $count_records = $DB->count_records($category::TABLENAME);

        // Vérifie l'objet inséré.
        $this->assertSame($data->name, $category->name);
        $this->assertSame($count_records, $initial_count + 1);

        // Mets à jour l'objet.
        $category->name = 'category';
        $category->save($category, $mform);
        $count_records = $DB->count_records($category::TABLENAME);

        // Vérifie l'objet mis à jour.
        $this->assertSame($data->name, $category->name);
        $this->assertSame($count_records, $initial_count + 1);
    }
}
