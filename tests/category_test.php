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

use local_apsolu\core\category;
use local_apsolu\core\course;
use moodle_exception;

/**
 * Classe de tests pour local_apsolu\core\category
 *
 * @package    local_apsolu
 * @copyright  2020 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \local_apsolu\core\category
 */
final class category_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();

        $this->setAdminUser();

        $this->resetAfterTest();
    }

    /**
     * Teste delete().
     *
     * @covers ::delete()
     */
    public function test_delete(): void {
        global $DB;

        $category = new category();

        // Supprime un objet inexistant.
        try {
            $result = $category->delete(1);
            $this->fail('moodle_exception expected on non-existing record.');
        } catch (moodle_exception $exception) {
            $this->assertInstanceOf('moodle_exception', $exception);
        }

        // Ajoute un objet.
        [$data, $mform] = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_category_data();
        $category->save($data, $mform);

        // Supprime un objet existant.
        $countrecords = $DB->count_records($category::TABLENAME);
        $this->assertSame(1, $countrecords);

        set_config('defaultrequestcategory', 1);
        $result = $category->delete();
        $this->assertTrue($result);

        $countrecords = $DB->count_records($category::TABLENAME);
        $this->assertSame(0, $countrecords);
    }

    /**
     * Teste get_records().
     *
     * @covers ::get_records()
     */
    public function test_get_records(): void {
        global $DB;

        $category = new category();

        $countrecords = $DB->count_records($category::TABLENAME);
        $this->assertSame(0, $countrecords);

        // Enregistre un nouvel objet.
        [$data, $mform] = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_category_data();
        $category->save($data, $mform);

        $countrecords = $DB->count_records($category::TABLENAME);
        $this->assertSame(1, $countrecords);

        // Enregistre un nouvel objet.
        $category->id = 0;
        [$data, $mform] = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_category_data();
        $category->save($data, $mform);

        $countrecords = $DB->count_records($category::TABLENAME);
        $this->assertSame(2, $countrecords);
    }

    /**
     * Teste delete().
     *
     * @covers ::delete()
     */
    public function test_load(): void {
        // Charge un objet inexistant.
        $category = new category();
        $category->load(1);

        $this->assertSame(0, $category->id);
        $this->assertSame('', $category->name);

        // Charge un objet existant.
        [$data, $mform] = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_category_data();
        $category->save($data, $mform);

        $test = new category();
        $test->load($category->id);

        $this->assertEquals($category->id, $test->id);
        $this->assertSame($category->name, $test->name);
    }

    /**
     * Teste save().
     *
     * @covers ::save()
     */
    public function test_save(): void {
        global $DB;

        $category = new category();

        $initialcount = $DB->count_records($category::TABLENAME);

        // Enregistre un objet.
        [$data, $mform] = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_category_data();
        $category->save($data, $mform);
        $countrecords = $DB->count_records($category::TABLENAME);

        // Vérifie l'objet inséré.
        $this->assertSame($data->name, $category->name);
        $this->assertSame($countrecords, $initialcount + 1);

        // Mets à jour l'objet.
        $category->name = 'category';
        $category->save($category, $mform);
        $countrecords = $DB->count_records($category::TABLENAME);

        // Vérifie l'objet mis à jour.
        $this->assertSame($data->name, $category->name);
        $this->assertSame($countrecords, $initialcount + 1);

        // Teste la propagation d'un changement de nom pour un créneau.
        $this->getDataGenerator()->get_plugin_generator('local_apsolu')->create_courses();

        $sql = "SELECT c.id
                  FROM {course} c
                 WHERE c.fullname LIKE '%Danse salsa%'
                 LIMIT 1";
        $record = $DB->get_record_sql($sql);
        $course = new course();
        $course->load($record->id);
        $oldfullname = $course->fullname;

        $category->load($course->category);
        $data->id = $course->category;
        $data->name = 'Football';
        $category->save($data, $mform);

        $course->load($record->id); // Recharge le cours.

        $this->assertNotEquals($oldfullname, $course->fullname);
        $this->assertStringContainsString($data->name, $course->fullname);
    }
}
