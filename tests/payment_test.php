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
 * Teste la classe local_apsolu\apsolu\payment
 *
 * @package    local_apsolu
 * @category   test
 * @copyright  2022 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_apsolu\core;

use stdClass;
use UniversiteRennes2\Apsolu\Payment;

require_once __DIR__.'/../classes/apsolu/payment.php';

/**
 * Classe de tests pour local_apsolu\apsolu\payment
 *
 * @package    local_apsolu
 * @category   test
 * @copyright  2022 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class payment_test extends \advanced_testcase {

    protected function setUp() : void {
        global $DB;

        parent::setUp();

        $this->resetAfterTest();
    }

    public function test_is_open() {
        $year = date('Y');

        // Les paiements n'ont pas commencé.
        set_config('payments_startdate', mktime(0, 0, 0, 1, 1, $year - 2), 'local_apsolu');
        set_config('payments_enddate', mktime(0, 0, 0, 1, 1, $year - 1), 'local_apsolu');

        $this->assertFalse(Payment::is_open());

        // Les paiements sont terminés.
        set_config('payments_startdate', mktime(0, 0, 0, 1, 1, $year + 1), 'local_apsolu');
        set_config('payments_enddate', mktime(0, 0, 0, 1, 1, $year + 2), 'local_apsolu');

        $this->assertFalse(Payment::is_open());

        // Les paiements sont ouverts.
        set_config('payments_startdate', mktime(0, 0, 0, 1, 1, $year - 1), 'local_apsolu');
        set_config('payments_enddate', mktime(0, 0, 0, 1, 1, $year + 1), 'local_apsolu');

        $this->assertTrue(Payment::is_open());
    }
}
