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

use local_apsolu\core\role;
use stdClass;

/**
 * Classe de tests pour local_apsolu\core\role
 *
 * @package   local_apsolu
 * @category  test
 * @copyright 2023 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \local_apsolu\core\role
 */
final class role_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();

        $this->resetAfterTest();
    }

    /**
     * Teste delete().
     *
     * @covers ::delete()
     */
    public function test_save(): void {
        global $DB;

        $role = new role();

        $initialcount = $DB->count_records($role::TABLENAME);
        $this->assertSame(0, $initialcount);

        // Ajoute un objet.
        $data = new stdClass();
        $data->id = 1;
        $data->color = '#a12345';
        $data->fontawesomeid = 'star';

        $role->save($data);
        $countrecords = $DB->count_records($role::TABLENAME);

        // Vérifie l'objet inséré.
        $this->assertEquals($data->id, $role->id);
        $this->assertSame($countrecords, $initialcount + 1);

        // Mets à jour l'objet.
        $data->color = '#9141ac';

        $role->save($data);
        $countrecords = $DB->count_records($role::TABLENAME);

        // Vérifie l'objet mis à jour.
        $this->assertEquals($data->color, $role->color);
        $this->assertSame($countrecords, $initialcount + 1);
    }
}
