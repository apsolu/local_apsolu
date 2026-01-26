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

use local_apsolu\core\gradeitem;
use context_system;
use stdClass;

/**
 * Classe de tests pour local_apsolu\observer\calendar
 *
 * @package    local_apsolu
 * @category   test
 * @copyright  2024 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \local_apsolu\observer\calendar
 */
final class calendar_test extends \advanced_testcase {
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

        $countrecords1 = $DB->count_records('apsolu_calendars');
        $countrecords2 = $DB->count_records('apsolu_grade_items');

        // Crée un calendrier.
        $calendar = new stdClass();
        $calendar->name = 'test';

        $calendar->id = $DB->insert_record('apsolu_calendars', $calendar);

        // Crée un élément de notation.
        $gradeitem = new gradeitem();
        $gradeitem->grademax = 10;
        $gradeitem->calendarid = $calendar->id;
        $gradeitem->save();

        $this->assertSame($countrecords1 + 1, $DB->count_records('apsolu_calendars'));
        $this->assertSame($countrecords2 + 1, $DB->count_records('apsolu_grade_items'));

        // Teste le bon appel à la classe local_apsolu\observer\calendar.
        $DB->delete_records('apsolu_calendars', ['id' => $calendar->id]);

        $event = \local_apsolu\event\calendar_deleted::create([
            'objectid' => $calendar->id,
            'context' => context_system::instance(),
        ]);
        $event->trigger();

        $this->assertSame($countrecords1, $DB->count_records('apsolu_calendars'));
        $this->assertSame($countrecords2, $DB->count_records('apsolu_grade_items'));
    }
}
