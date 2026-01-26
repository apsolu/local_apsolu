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

use context_system;
use stdClass;

/**
 * Classe de tests pour local_apsolu\observer\card
 *
 * @package    local_apsolu
 * @category   test
 * @copyright  2024 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \local_apsolu\observer\card
 */
final class card_test extends \advanced_testcase {
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

        $countrecords1 = $DB->count_records('apsolu_payments_cards');
        $countrecords2 = $DB->count_records('apsolu_payments_cards_cals');
        $countrecords3 = $DB->count_records('apsolu_payments_cards_cohort');
        $countrecords4 = $DB->count_records('apsolu_payments_cards_roles');

        // Crée un tarif.
        $card = new stdClass();
        $card->name = 'test';
        $card->fullname = 'test';
        $card->trial = '';
        $card->price = 10;
        $card->centerid = 1;
        $card->id = $DB->insert_record('apsolu_payments_cards', $card);

        $sql = "INSERT INTO {apsolu_payments_cards_cals} (cardid, calendartypeid, value) VALUES(:cardid, 1, 1)";
        $DB->execute($sql, ['cardid' => $card->id]);

        $sql = "INSERT INTO {apsolu_payments_cards_cohort} (cardid, cohortid) VALUES(:cardid, 1)";
        $DB->execute($sql, ['cardid' => $card->id]);

        $sql = "INSERT INTO {apsolu_payments_cards_roles} (cardid, roleid) VALUES(:cardid, 1)";
        $DB->execute($sql, ['cardid' => $card->id]);

        $this->assertSame($countrecords1 + 1, $DB->count_records('apsolu_payments_cards'));
        $this->assertSame($countrecords2 + 1, $DB->count_records('apsolu_payments_cards_cals'));
        $this->assertSame($countrecords3 + 1, $DB->count_records('apsolu_payments_cards_cohort'));
        $this->assertSame($countrecords4 + 1, $DB->count_records('apsolu_payments_cards_roles'));

        // Teste le bon appel à la classe local_apsolu\observer\card.
        $DB->delete_records('apsolu_payments_cards', ['id' => $card->id]);

        $event = \local_apsolu\event\card_deleted::create([
            'objectid' => $card->id,
            'context' => context_system::instance(),
        ]);
        $event->trigger();

        $this->assertSame($countrecords1, $DB->count_records('apsolu_payments_cards'));
        $this->assertSame($countrecords2, $DB->count_records('apsolu_payments_cards_cals'));
        $this->assertSame($countrecords3, $DB->count_records('apsolu_payments_cards_cohort'));
        $this->assertSame($countrecords4, $DB->count_records('apsolu_payments_cards_roles'));
    }
}
