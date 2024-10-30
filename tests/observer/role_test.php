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

namespace local_apsolu\observer;

/**
 * Classe de tests pour local_apsolu\observer\role
 *
 * @package    local_apsolu
 * @category   test
 * @copyright  2024 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \local_apsolu\observer\role
 */
final class role_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();

        $this->setAdminUser();

        $this->resetAfterTest();
    }

    /**
     * Teste deleted().
     *
     * @covers ::deleted()
     */
    public function test_deleted(): void {
        global $DB;

        $countrecords1 = $DB->count_records('apsolu_grade_items');
        $countrecords2 = $DB->count_records('apsolu_payments_cards_roles');

        // Crée un rôle.
        $roleid = create_role('role1', 'shortname', 'description', $archetype = '');

        // Crée un enregistrement inscription/role.
        $sql = "INSERT INTO {apsolu_grade_items} (name, roleid, calendarid, timecreated, grademax, publicationdate)
                                          VALUES ('test', :roleid, 1, 1, 1, 1)";
        $DB->execute($sql, ['roleid' => $roleid]);

        $sql = "INSERT INTO {apsolu_payments_cards_roles} (cardid, roleid) VALUES (1, :roleid)";
        $DB->execute($sql, ['roleid' => $roleid]);

        $this->assertSame($countrecords1 + 1, $DB->count_records('apsolu_grade_items'));
        $this->assertSame($countrecords2 + 1, $DB->count_records('apsolu_payments_cards_roles'));

        // Teste le bon appel à la classe local_apsolu\observer\role.
        delete_role($roleid);
        $this->assertSame($countrecords1, $DB->count_records('apsolu_grade_items'));
        $this->assertSame($countrecords2, $DB->count_records('apsolu_payments_cards_roles'));
    }
}
