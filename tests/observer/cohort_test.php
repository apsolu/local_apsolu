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

use local_apsolu\observer\cohort;
use stdclass;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/cohort/lib.php');

/**
 * Classe de tests pour local_apsolu\observer\cohort
 *
 * @package    local_apsolu
 * @category   test
 * @copyright  2022 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \local_apsolu\observer\cohort
 */
final class cohort_test extends \advanced_testcase {
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

        $countrecords = $DB->count_records('apsolu_payments_cards_cohort');

        // Crée une cohorte.
        $cohort = new stdClass();
        $cohort->name = 'test';
        $cohort->contextid = 1;
        $cohort->id = cohort_add_cohort($cohort);

        // Crée un enregistrement carte/cohorte.
        $sql = "INSERT INTO {apsolu_payments_cards_cohort} (cardid, cohortid) VALUES(:cardid, :cohortid)";
        $DB->execute($sql, ['cardid' => 1, 'cohortid' => $cohort->id]);

        $this->assertSame($countrecords + 1, $DB->count_records('apsolu_payments_cards_cohort'));

        // Teste le bon appel à la classe local_apsolu\observer\cohort.
        cohort_delete_cohort($cohort);
        $this->assertSame($countrecords, $DB->count_records('apsolu_payments_cards_cohort'));
    }
}
