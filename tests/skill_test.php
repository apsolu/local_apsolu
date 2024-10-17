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
use local_apsolu\core\course;
use local_apsolu\core\skill;
use stdClass;

/**
 * Classe de tests pour local_apsolu\core\skill
 *
 * @package    local_apsolu
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \local_apsolu\core\skill
 */
final class skill_test extends \advanced_testcase {
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

        $skill = new skill();

        // Supprime un objet inexistant.
        $result = $skill->delete(1);
        $this->assertTrue($result);

        // Supprime un objet existant.
        $skill->name = 'skill 1';
        $skill->save();

        $countrecords = $DB->count_records($skill::TABLENAME);
        $this->assertSame(1, $countrecords);

        $result = $skill->delete();
        $this->assertTrue($result);

        $countrecords = $DB->count_records($skill::TABLENAME);
        $this->assertSame(0, $countrecords);
    }

    /**
     * Teste get_records().
     *
     * @covers ::get_records()
     */
    public function test_get_records(): void {
        global $DB;

        $skill = new skill();

        $countrecords = $DB->count_records($skill::TABLENAME);
        $this->assertSame(0, $countrecords);

        // Enregistre un nouvel objet.
        $skill->name = 'skill 1';
        $skill->save();

        $countrecords = $DB->count_records($skill::TABLENAME);
        $this->assertSame(1, $countrecords);

        // Enregistre un nouvel objet.
        $skill->id = 0;
        $skill->name = 'skill 2';
        $skill->save();

        $countrecords = $DB->count_records($skill::TABLENAME);
        $this->assertSame(2, $countrecords);
    }

    /**
     * Teste load().
     *
     * @covers ::load()
     */
    public function test_load(): void {
        // Charge un objet inexistant.
        $skill = new skill();
        $skill->load(1);

        $this->assertSame(0, $skill->id);
        $this->assertSame('', $skill->name);

        // Charge un objet existant.
        $skill->name = 'skill';
        $skill->save();

        $test = new skill();
        $test->load($skill->id);

        $this->assertEquals($skill->id, $test->id);
        $this->assertSame($skill->name, $test->name);
    }

    /**
     * Teste save().
     *
     * @covers ::save()
     */
    public function test_save(): void {
        global $DB;

        $skill = new skill();

        $initialcount = $DB->count_records($skill::TABLENAME);

        // Enregistre un objet.
        $data = new stdClass();
        $data->name = 'skill 1';

        $skill->save($data);
        $countrecords = $DB->count_records($skill::TABLENAME);

        // Vérifie l'objet inséré.
        $this->assertSame($data->name, $skill->name);
        $this->assertSame($countrecords, $initialcount + 1);

        // Mets à jour l'objet.
        $data->name = 'skill 1';

        $skill->save($data);
        $countrecords = $DB->count_records($skill::TABLENAME);

        // Vérifie l'objet mis à jour.
        $this->assertSame($data->name, $skill->name);
        $this->assertSame($countrecords, $initialcount + 1);

        // Ajoute un nouvel objet (sans argument).
        $skill->id = 0;
        $skill->name = 'skill 2';

        $skill->save();
        $countrecords = $DB->count_records($skill::TABLENAME);

        // Vérifie l'objet ajouté.
        $this->assertSame($countrecords, $initialcount + 2);

        // Teste la contrainte d'unicité.
        $data->id = 0;
        $data->name = 'skill 2';

        try {
            $skill->save($data);
            $this->fail('dml_write_exception expected for unique constraint violation.');
        } catch (dml_write_exception $exception) {
            $this->assertInstanceOf('dml_write_exception', $exception);
        }

        // Teste la propagation d'un changement de nom pour un créneau.
        $this->setAdminUser();
        $this->getDataGenerator()->get_plugin_generator('local_apsolu')->create_courses();

        $sql = "SELECT c.id
                  FROM {course} c
                 WHERE c.fullname LIKE '%expert%'
                 LIMIT 1";
        $record = $DB->get_record_sql($sql);
        $course = new course();
        $course->load($record->id);
        $oldfullname = $course->fullname;

        $skill->load($course->skillid);
        $data->id = $course->skillid;
        $data->name = 'avancé';
        $skill->save($data);

        $course->load($record->id); // Recharge le cours.

        $this->assertNotEquals($oldfullname, $course->fullname);
        $this->assertStringContainsString($data->name, $course->fullname);
    }
}
