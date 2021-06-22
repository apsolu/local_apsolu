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
 * Teste la classe local_apsolu\core\grouping
 *
 * @package    local_apsolu
 * @category   test
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Classe de tests pour local_apsolu\core\grouping
 *
 * @package    local_apsolu
 * @category   test
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_core_grouping_testcase extends advanced_testcase {
    protected function setUp() : void {
        parent::setUp();

        $this->resetAfterTest();
    }

    public function test_delete() {
        global $DB;

        $grouping = new local_apsolu\core\grouping();

        // Supprime un objet inexistant.
        try {
            $result = $grouping->delete(1);
            $this->fail('moodle_exception expected on non-existing record.');
        } catch (moodle_exception $exception) {
            $this->assertInstanceOf('moodle_exception', $exception);
        }

        // Supprime un objet existant.
        $grouping->name = 'grouping 1';
        $grouping->save();

        $count_records = $DB->count_records($grouping::TABLENAME);
        $this->assertSame(1, $count_records);

        set_config('defaultrequestcategory', 1);
        $result = $grouping->delete();
        $this->assertTrue($result);

        $count_records = $DB->count_records($grouping::TABLENAME);
        $this->assertSame(0, $count_records);
    }

    public function test_get_records() {
        global $DB;

        $grouping = new local_apsolu\core\grouping();

        $count_records = $DB->count_records($grouping::TABLENAME);
        $this->assertSame(0, $count_records);

        // Enregistre un nouvel objet.
        $grouping->name = 'grouping 1';
        $grouping->save();

        $count_records = $DB->count_records($grouping::TABLENAME);
        $this->assertSame(1, $count_records);

        // Enregistre un nouvel objet.
        $grouping->id = 0;
        $grouping->name = 'grouping 2';
        $grouping->save();

        $count_records = $DB->count_records($grouping::TABLENAME);
        $this->assertSame(2, $count_records);
    }

    public function test_load() {
        // Charge un objet inexistant.
        $grouping = new local_apsolu\core\grouping();
        $grouping->load(1);

        $this->assertSame(0, $grouping->id);
        $this->assertSame('', $grouping->name);

        // Charge un objet existant.
        $grouping->name = 'grouping';
        $grouping->save();

        $test = new local_apsolu\core\grouping();
        $test->load($grouping->id);

        $this->assertEquals($grouping->id, $test->id);
        $this->assertSame($grouping->name, $test->name);
    }

    public function test_save() {
        global $DB;

        $grouping = new local_apsolu\core\grouping();

        $initial_count = $DB->count_records($grouping::TABLENAME);

        // Enregistre un objet.
        $data = new stdClass();
        $data->name = 'grouping 1';

        $grouping->save($data);
        $count_records = $DB->count_records($grouping::TABLENAME);

        // Vérifie l'objet inséré.
        $this->assertSame($data->name, $grouping->name);
        $this->assertSame($count_records, $initial_count + 1);

        // Mets à jour l'objet.
        $data->name = 'grouping 1';

        $grouping->save($data);
        $count_records = $DB->count_records($grouping::TABLENAME);

        // Vérifie l'objet mis à jour.
        $this->assertSame($data->name, $grouping->name);
        $this->assertSame($count_records, $initial_count + 1);

        // Ajoute un nouvel objet (sans argument).
        $grouping->id = 0;
        $grouping->name = 'grouping 2';

        $grouping->save();
        $count_records = $DB->count_records($grouping::TABLENAME);

        // Vérifie l'objet ajouté.
        $this->assertSame($count_records, $initial_count + 2);
    }
}
