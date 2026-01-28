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

use context_course;
use local_apsolu\event\federation_number_created;
use local_apsolu\event\federation_number_updated;

/**
 * Classe de tests pour local_apsolu\observer\federation_number
 *
 * @package    local_apsolu
 * @category   test
 * @copyright  2026 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(federation_number::class)]
final class federation_number_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();

        $this->resetAfterTest();
    }

    /**
     * Teste create_adhoc_task().
     *
     * @return void
     */
    public function test_set_federation_number(): void {
        global $DB;

        $shortname = 'apsolufederationnumber';
        $context = context_course::instance(1, MUST_EXIST);

        // Teste la création d'un numéro de licence.
        $event = federation_number_created::create([
            'objectid' => 1,
            'context' => $context,
            'relateduserid' => 2,
            'other' => ['federationnumber' => 'AAA'],
            ]);
        $event->trigger();

        $this->assertSame(1, $DB->count_records('user_info_field', ['shortname' => $shortname]));

        $field = $DB->get_record('user_info_field', ['shortname' => $shortname], $fields = '*', MUST_EXIST);

        $info = $DB->get_record('user_info_data', ['fieldid' => $field->id, 'userid' => 2], $fields = '*', MUST_EXIST);
        $this->assertSame('AAA', $info->data);

        // Teste la mise à jour d'un numéro de licence.
        $event = federation_number_updated::create([
            'objectid' => 1,
            'context' => $context,
            'relateduserid' => 2,
            'other' => ['federationnumber' => 'BBB', 'oldfederationnumber' => 'AAA'],
            ]);
        $event->trigger();

        $this->assertSame(1, $DB->count_records('user_info_field', ['shortname' => $shortname]));

        $field = $DB->get_record('user_info_field', ['shortname' => $shortname], $fields = '*', MUST_EXIST);

        $info = $DB->get_record('user_info_data', ['fieldid' => $field->id, 'userid' => 2], $fields = '*', MUST_EXIST);
        $this->assertSame('BBB', $info->data);
    }
}
