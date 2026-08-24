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
use local_apsolu\core\coursetype;
use stdClass;

/**
 * Classe de tests pour local_apsolu\core\coursetype
 *
 * @package    local_apsolu
 * @copyright  2026 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \local_apsolu\core\coursetype
 */
final class coursetype_test extends \advanced_testcase {
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

        $coursetype = new coursetype();
        $countrecords = $DB->count_records($coursetype::TABLENAME);

        // Supprime un objet inexistant.
        $result = $coursetype->delete(-9);
        $this->assertTrue($result);

        // Supprime un objet existant.
        $coursetype->name = 'coursetype 1';
        $coursetype->shortname = 'coursetype1';
        $coursetype->fullnametemplate = 'coursetype';
        $coursetype->color = '#f66151';
        $data = new stdClass();
        $data->fields = ['type' => ['fieldid' => 1], 'category' => ['fieldid' => 2]];
        $coursetype->save($data);

        $this->assertSame($countrecords + 1, $DB->count_records($coursetype::TABLENAME));

        $result = $coursetype->delete();
        $this->assertTrue($result);

        $this->assertSame($countrecords, $DB->count_records($coursetype::TABLENAME));
    }

    /**
     * Teste get_records().
     *
     * @covers ::get_records()
     */
    public function test_get_records(): void {
        global $DB;

        $coursetype = new coursetype();

        $countrecords = $DB->count_records($coursetype::TABLENAME);
        $this->assertSame(1, $countrecords);

        // Enregistre un nouvel objet.
        $coursetype->name = 'coursetype 1';
        $coursetype->shortname = 'coursetype1';
        $coursetype->fullnametemplate = 'coursetype';
        $coursetype->color = '#f66151';
        $data = new stdClass();
        $data->fields = ['type' => ['fieldid' => 1], 'category' => ['fieldid' => 2]];
        $coursetype->save($data);

        $countrecords = $DB->count_records($coursetype::TABLENAME);
        $this->assertSame(2, $countrecords);

        // Enregistre un nouvel objet.
        $coursetype->id = 0;
        $coursetype->name = 'coursetype 2';
        $coursetype->shortname = 'coursetype2';
        $coursetype->fullnametemplate = 'coursetype';
        $coursetype->color = '#f66151';
        $data = new stdClass();
        $data->fields = ['type' => ['fieldid' => 1], 'category' => ['fieldid' => 2]];
        $coursetype->save($data);

        $countrecords = $DB->count_records($coursetype::TABLENAME);
        $this->assertSame(3, $countrecords);
    }

    /**
     * Teste load().
     *
     * @covers ::load()
     */
    public function test_load(): void {
        // Charge un objet inexistant.
        $coursetype = new coursetype();
        $coursetype->load(-1);

        $this->assertSame(0, $coursetype->id);
        $this->assertSame('', $coursetype->name);
        $this->assertSame('', $coursetype->shortname);

        // Charge un objet existant.
        $coursetype->name = 'coursetype';
        $coursetype->shortname = 'coursetype';
        $data = new stdClass();
        $data->fields = ['type' => ['fieldid' => 1], 'category' => ['fieldid' => 2]];
        $coursetype->save($data);

        $test = new coursetype();
        $test->load($coursetype->id);

        $this->assertEquals($coursetype->id, $test->id);
        $this->assertSame($coursetype->name, $test->name);
        $this->assertSame($coursetype->shortname, $test->shortname);
    }

    /**
     * Teste save().
     *
     * @covers ::save()
     */
    public function test_save(): void {
        global $DB;

        $coursetype = new coursetype();

        $initialcount = $DB->count_records($coursetype::TABLENAME);

        // Enregistre un objet.
        $data = new stdClass();
        $data->name = 'coursetype 1';
        $data->shortname = 'coursetype1';
        $data->fields = ['type' => ['fieldid' => 1], 'category' => ['fieldid' => 2]];

        $coursetype->save($data);
        $countrecords = $DB->count_records($coursetype::TABLENAME);

        // Vérifie l'objet inséré.
        $this->assertSame($data->name, $coursetype->name);
        $this->assertSame($data->shortname, $coursetype->shortname);
        $this->assertSame($countrecords, $initialcount + 1);

        // Met à jour l'objet.
        $data->name = 'coursetype 1';
        $data->shortname = 'coursetype';

        $coursetype->save($data);
        $countrecords = $DB->count_records($coursetype::TABLENAME);

        // Vérifie l'objet mis à jour.
        $this->assertSame($data->name, $coursetype->name);
        $this->assertSame($data->shortname, $coursetype->shortname);
        $this->assertSame($countrecords, $initialcount + 1);
    }
}
