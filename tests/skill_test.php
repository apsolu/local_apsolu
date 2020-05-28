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
 * Teste la classe local_apsolu\core\skill
 *
 * @package    local_apsolu
 * @category   test
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Classe de tests pour local_apsolu\core\skill
 *
 * @package    local_apsolu
 * @category   test
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_core_skill_testcase extends advanced_testcase {
    protected function setUp() {
        parent::setUp();

        $this->resetAfterTest();
    }

    public function test_delete() {
        global $DB;

        $skill = new local_apsolu\core\skill();

        // Supprime un objet inexistant.
        $result = $skill->delete(1);
        $this->assertTrue($result);

        // Supprime un objet existant.
        $skill->name = 'skill 1';
        $skill->save();

        $count_records = $DB->count_records($skill::TABLENAME);
        $this->assertSame(1, $count_records);

        $result = $skill->delete();
        $this->assertTrue($result);

        $count_records = $DB->count_records($skill::TABLENAME);
        $this->assertSame(0, $count_records);
    }

    public function test_get_records() {
        global $DB;

        $skill = new local_apsolu\core\skill();

        $count_records = $DB->count_records($skill::TABLENAME);
        $this->assertSame(0, $count_records);

        // Enregistre un nouvel objet.
        $skill->name = 'skill 1';
        $skill->save();

        $count_records = $DB->count_records($skill::TABLENAME);
        $this->assertSame(1, $count_records);

        // Enregistre un nouvel objet.
        $skill->id = 0;
        $skill->name = 'skill 2';
        $skill->save();

        $count_records = $DB->count_records($skill::TABLENAME);
        $this->assertSame(2, $count_records);
    }

    public function test_load() {
        // Charge un objet inexistant.
        $skill = new local_apsolu\core\skill();
        $skill->load(1);

        $this->assertSame(0, $skill->id);
        $this->assertSame('', $skill->name);

        // Charge un objet existant.
        $skill->name = 'skill';
        $skill->save();

        $test = new local_apsolu\core\skill();
        $test->load($skill->id);

        $this->assertEquals($skill->id, $test->id);
        $this->assertSame($skill->name, $test->name);
    }

    public function test_save() {
        global $DB;

        $skill = new local_apsolu\core\skill();

        $initial_count = $DB->count_records($skill::TABLENAME);

        // Enregistre un objet.
        $data = new stdClass();
        $data->name = 'skill 1';

        $skill->save($data);
        $count_records = $DB->count_records($skill::TABLENAME);

        // Vérifie l'objet inséré.
        $this->assertSame($data->name, $skill->name);
        $this->assertSame($count_records, $initial_count + 1);

        // Mets à jour l'objet.
        $data->name = 'skill 1';

        $skill->save($data);
        $count_records = $DB->count_records($skill::TABLENAME);

        // Vérifie l'objet mis à jour.
        $this->assertSame($data->name, $skill->name);
        $this->assertSame($count_records, $initial_count + 1);

        // Ajoute un nouvel objet (sans argument).
        $skill->id = 0;
        $skill->name = 'skill 2';

        $skill->save();
        $count_records = $DB->count_records($skill::TABLENAME);

        // Vérifie l'objet ajouté.
        $this->assertSame($count_records, $initial_count + 2);

        // Teste la contrainte d'unicité.
        $data->id = 0;
        $data->name = 'skill 2';

        try {
            $skill->save($data);
            $this->fail('dml_write_exception expected for unique constraint violation.');
        } catch (dml_write_exception $exception) {
            $this->assertInstanceOf('dml_write_exception', $exception);
        }
    }
}
