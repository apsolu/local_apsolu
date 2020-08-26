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
 * Teste la classe local_apsolu\core\holiday
 *
 * @package   local_apsolu
 * @category  test
 * @copyright 2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Classe de tests pour local_apsolu\core\holiday
 *
 * @package   local_apsolu
 * @category  test
 * @copyright 2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_core_holiday_testcase extends advanced_testcase {
    protected function setUp() {
        parent::setUp();

        $this->resetAfterTest();
    }

    public function test_delete() {
        global $DB;

        $holiday = new local_apsolu\core\holiday();

        // Supprime un objet inexistant.
        $result = $holiday->delete(1);
        $this->assertTrue($result);

        // Supprime un objet existant.
        $holiday->day = time();
        $holiday->save();

        $count_records = $DB->count_records($holiday::TABLENAME);
        $this->assertSame(1, $count_records);

        $result = $holiday->delete();
        $this->assertTrue($result);

        $count_records = $DB->count_records($holiday::TABLENAME);
        $this->assertSame(0, $count_records);
    }

    public function test_get_records() {
        global $DB;

        $holiday = new local_apsolu\core\holiday();

        $count_records = $DB->count_records($holiday::TABLENAME);
        $this->assertSame(0, $count_records);

        // Enregistre un nouvel objet.
        $holiday->day = time();
        $holiday->save();

        $count_records = $DB->count_records($holiday::TABLENAME);
        $this->assertSame(1, $count_records);

        // Enregistre un nouvel objet.
        $holiday->id = 0;
        $holiday->day = time() + (24 * 60 * 60);
        $holiday->save();

        $count_records = $DB->count_records($holiday::TABLENAME);
        $this->assertSame(2, $count_records);
    }

    public function test_get_holidays() {
        // Teste les jours fériés de l'année 2019.
        $holidays2019 = array(
            '2019-01-01',
            '2019-04-22',
            '2019-05-01',
            '2019-05-08',
            '2019-05-30',
            '2019-06-10',
            '2019-07-14',
            '2019-08-15',
            '2019-11-01',
            '2019-11-11',
            '2019-12-25',
            );

        $holidays = local_apsolu\core\holiday::get_holidays(2019);

        $this->assertEquals(11, count($holidays));
        foreach ($holidays as $holiday) {
            $this->assertContains(strftime('%F', $holiday), $holidays2019);
        }

        // Teste les jours fériés de l'année 2020.
        $holidays2020 = array(
            '2020-01-01',
            '2020-04-13',
            '2020-05-01',
            '2020-05-08',
            '2020-05-21',
            '2020-06-01',
            '2020-07-14',
            '2020-08-15',
            '2020-11-01',
            '2020-11-11',
            '2020-12-25',
            );

        $holidays = local_apsolu\core\holiday::get_holidays(2020);

        $this->assertEquals(11, count($holidays));
        foreach ($holidays as $holiday) {
            $this->assertContains(strftime('%F', $holiday), $holidays2020);
        }
    }

    public function test_regenerate_sessions() {
        // Génère une période.
        $data = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_period_data('p1');
        $period1 = new local_apsolu\core\period();
        $period1->save($data);

        // Génère un cours.
        $data = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_course_data();
        $course = new local_apsolu\core\course();
        $data->periodid = $period1->id;
        $course->save($data);

        // Ajoute un jour férié sur la prochaine session de cours.
        $holiday = new local_apsolu\core\holiday();
        $data->day = strtotime('next '.$course->weekday) + WEEKSECS;
        $holiday->save($data);

        // Vérifie qu'il y a toujours 2 sessions.
        $sessions = $course->get_sessions();
        $this->assertSame(2, count($sessions));

        // Vérifie que la session existe toujours.
        $first_session = current($sessions);
        $this->assertGreaterThanOrEqual($data->day, $first_session->sessiontime);
        $this->assertLessThanOrEqual($data->day + DAYSECS, $first_session->sessiontime);

        // Vérifie que la session sur le jour férié est supprimée.
        $holiday->regenerate_sessions();
        foreach ($course->get_sessions() as $session) {
            $this->assertNotEquals($session->sessiontime, $first_session->sessiontime);
        }
    }

    public function test_save() {
        global $DB;

        $holiday = new local_apsolu\core\holiday();

        $initial_count = $DB->count_records($holiday::TABLENAME);

        // Enregistre un objet.
        $data = new stdClass();
        $data->day = time();

        $holiday->save($data);
        $count_records = $DB->count_records($holiday::TABLENAME);

        // Vérifie l'objet inséré.
        $this->assertEquals($data->day, $holiday->day);
        $this->assertSame($count_records, $initial_count + 1);

        // Mets à jour l'objet.
        $data->day = time() + (24 * 60 * 60);

        $holiday->save($data);
        $count_records = $DB->count_records($holiday::TABLENAME);

        // Vérifie l'objet mis à jour.
        $this->assertEquals($data->day, $holiday->day);
        $this->assertSame($count_records, $initial_count + 1);

        // Ajoute un nouvel objet (sans argument).
        $holiday->id = 0;
        $holiday->day = time() + (48 * 60 * 60);

        $holiday->save();
        $count_records = $DB->count_records($holiday::TABLENAME);

        // Vérifie l'objet ajouté.
        $this->assertSame($count_records, $initial_count + 2);

        // Teste la contrainte d'unicité.
        $data->id = 0;
        $data->day = $holiday->day;

        try {
            $holiday->save($data);
            $this->fail('dml_write_exception expected for unique constraint violation.');
        } catch (dml_write_exception $exception) {
            $this->assertInstanceOf('dml_write_exception', $exception);
        }
    }
}
