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

use core_date;
use dml_write_exception;
use local_apsolu\core\course;
use local_apsolu\core\holiday;
use local_apsolu\core\period;
use stdClass;

/**
 * Classe de tests pour local_apsolu\core\holiday
 *
 * @package   local_apsolu
 * @category  test
 * @copyright 2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \local_apsolu\core\holiday
 */
final class holiday_test extends \advanced_testcase {
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

        $holiday = new holiday();

        // Supprime un objet inexistant.
        $result = $holiday->delete(1);
        $this->assertTrue($result);

        // Supprime un objet existant.
        $holiday->day = time();
        $holiday->save();

        $countrecords = $DB->count_records($holiday::TABLENAME);
        $this->assertSame(1, $countrecords);

        $result = $holiday->delete();
        $this->assertTrue($result);

        $countrecords = $DB->count_records($holiday::TABLENAME);
        $this->assertSame(0, $countrecords);
    }

    /**
     * Teste get_records().
     *
     * @covers ::get_records()
     */
    public function test_get_records(): void {
        global $DB;

        $holiday = new holiday();

        $countrecords = $DB->count_records($holiday::TABLENAME);
        $this->assertSame(0, $countrecords);

        // Enregistre un nouvel objet.
        $holiday->day = time();
        $holiday->save();

        $countrecords = $DB->count_records($holiday::TABLENAME);
        $this->assertSame(1, $countrecords);

        // Enregistre un nouvel objet.
        $holiday->id = 0;
        $holiday->day = time() + (24 * 60 * 60);
        $holiday->save();

        $countrecords = $DB->count_records($holiday::TABLENAME);
        $this->assertSame(2, $countrecords);
    }

    /**
     * Teste get_holidays().
     *
     * @covers ::get_holidays()
     */
    public function test_get_holidays(): void {
        // Teste les jours fériés de l'année 2019.
        $holidays2019 = [
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
            ];

        $holidays = holiday::get_holidays(2019);

        $this->assertEquals(11, count($holidays));
        foreach ($holidays as $holiday) {
            $this->assertContains(core_date::strftime('%F', $holiday), $holidays2019);
        }

        // Teste les jours fériés de l'année 2020.
        $holidays2020 = [
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
            ];

        $holidays = holiday::get_holidays(2020);

        $this->assertEquals(11, count($holidays));
        foreach ($holidays as $holiday) {
            $this->assertContains(core_date::strftime('%F', $holiday), $holidays2020);
        }
    }

    /**
     * Teste regenerate_sessions().
     *
     * @covers ::regenerate_sessions()
     */
    public function test_regenerate_sessions(): void {
        // Génère une période.
        $data = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_period_data('p1');
        $period1 = new period();
        $period1->save($data);

        // Génère un cours.
        $data = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_course_data();
        $course = new course();
        $data->periodid = $period1->id;
        $course->save($data);

        // Ajoute un jour férié sur la prochaine session de cours.
        $holiday = new holiday();
        $data->day = strtotime('next '.$course->weekday.' this week') + WEEKSECS;
        $holiday->save($data);

        // Vérifie qu'il y a toujours 2 sessions.
        $sessions = $course->get_sessions();
        $this->assertSame(2, count($sessions));

        // Vérifie que la session existe toujours.
        $firstsession = current($sessions);
        $this->assertGreaterThanOrEqual($data->day, $firstsession->sessiontime);
        $this->assertLessThanOrEqual($data->day + DAYSECS, $firstsession->sessiontime);

        // Vérifie que la session sur le jour férié est supprimée.
        $holiday->regenerate_sessions();
        foreach ($course->get_sessions() as $session) {
            $this->assertNotEquals($session->sessiontime, $firstsession->sessiontime);
        }
    }

    /**
     * Teste save().
     *
     * @covers ::save()
     */
    public function test_save(): void {
        global $DB;

        $holiday = new holiday();

        $initialcount = $DB->count_records($holiday::TABLENAME);

        // Enregistre un objet.
        $data = new stdClass();
        $data->day = time();

        $holiday->save($data);
        $countrecords = $DB->count_records($holiday::TABLENAME);

        // Vérifie l'objet inséré.
        $this->assertEquals($data->day, $holiday->day);
        $this->assertSame($countrecords, $initialcount + 1);

        // Mets à jour l'objet.
        $data->day = time() + (24 * 60 * 60);

        $holiday->save($data);
        $countrecords = $DB->count_records($holiday::TABLENAME);

        // Vérifie l'objet mis à jour.
        $this->assertEquals($data->day, $holiday->day);
        $this->assertSame($countrecords, $initialcount + 1);

        // Ajoute un nouvel objet (sans argument).
        $holiday->id = 0;
        $holiday->day = time() + (48 * 60 * 60);

        $holiday->save();
        $countrecords = $DB->count_records($holiday::TABLENAME);

        // Vérifie l'objet ajouté.
        $this->assertSame($countrecords, $initialcount + 2);

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
