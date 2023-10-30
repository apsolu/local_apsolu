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
 * Teste la classe local_apsolu\core\course
 *
 * @package    local_apsolu
 * @category   test
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_apsolu\core;

use coding_exception;
use moodle_exception;

global $CFG;

require_once($CFG->dirroot.'/course/lib.php');

/**
 * Classe de tests pour local_apsolu\core\course
 *
 * @package    local_apsolu
 * @category   test
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_test extends \advanced_testcase {
    protected function setUp() : void {
        parent::setUp();

        $this->setAdminUser();

        $this->resetAfterTest();
    }

    public function test_delete() {
        global $DB;

        $course = new course();

        // Supprime un objet inexistant.
        try {
            $result = $course->delete(1);
            $this->fail('moodle_exception expected on non-existing record.');
        } catch (moodle_exception $exception) {
            $this->assertInstanceOf('moodle_exception', $exception);
        }

        // Supprime un objet existant.
        $data = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_course_data();
        $course->save($data);

        $countrecords = $DB->count_records($course::TABLENAME);
        $this->assertSame(1, $countrecords);

        $result = $course->delete();
        $this->assertTrue($result);

        $countrecords = $DB->count_records($course::TABLENAME);
        $this->assertSame(0, $countrecords);
    }

    public function test_get_session_offset() {
        $course = new course();
        $course->numweekday = 3; // Mercredi.
        $course->starttime = '16:35';

        $startweek = make_timestamp(2020, 7, 6, 0, 0, 0); // Début de semaine.
        $expected = make_timestamp(2020, 7, 8, 16, 35, 0);

        $this->assertSame($expected, $startweek + $course->get_session_offset());

        // Test une exception.
        try {
            $course->starttime = '16h35';
            $offset = $course->get_session_offset();
            $this->fail('codding_exception expected on invalid starttime value.');
        } catch (coding_exception $exception) {
            $this->assertInstanceOf('coding_exception', $exception);
        }
    }

    public function test_get_records() {
        global $DB;

        $course = new course();

        $countrecords = $DB->count_records($course::TABLENAME);
        $this->assertSame(0, $countrecords);

        // Enregistre un nouvel objet.
        $data = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_course_data();
        $course->save($data);

        $countrecords = $DB->count_records($course::TABLENAME);
        $this->assertSame(1, $countrecords);

        // Enregistre un nouvel objet.
        $course->id = 0;
        $data->event = 'event 2';
        $course->save($data);

        $countrecords = $DB->count_records($course::TABLENAME);
        $this->assertSame(2, $countrecords);
    }

    public function test_load() {
        // Charge un objet inexistant.
        $course = new course();
        $course->load(1);

        $this->assertSame(0, $course->id);
        $this->assertSame('', $course->fullname);

        // Charge un objet existant.
        $data = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_course_data();
        $course->save($data);

        $test = new course();
        $test->load($course->id);

        $this->assertEquals($course->id, $test->id);
        $this->assertSame($course->fullname, $test->fullname);
    }

    public function test_save() {
        global $DB;

        $course = new course();
        $category = new category();

        $initialcount = $DB->count_records($course::TABLENAME);

        // Enregistre un objet.
        $data = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_course_data();
        $course->save($data);
        $countrecords = $DB->count_records($course::TABLENAME);

        $sessions = $course->get_sessions();
        $countsessions = count($sessions);

        // Vérifie l'objet inséré.
        $strtime = get_string($data->weekday, 'calendar').' '.$data->starttime.' '.$data->endtime;
        $this->assertSame(sprintf('%s %s %s %s', $data->str_category, $data->event, $strtime, $data->str_skill), $course->fullname);
        $this->assertSame($countrecords, $initialcount + 1);

        // Mets à jour l'objet.
        $data->event = '';
        $course->save($data);
        $countrecords = $DB->count_records($course::TABLENAME);

        // Vérifie que les sessions n'ont pas été modifiées.
        $this->assertSame($countsessions, count($course->get_sessions()));
        $this->assertSame($sessions, $course->get_sessions());

        // Vérifie l'objet mis à jour.
        $this->assertSame(sprintf('%s %s %s', $data->str_category, $strtime, $data->str_skill), $course->fullname);
        $this->assertSame($countrecords, $initialcount + 1);

        // Vérifie qu'un nom abrégé est regénéré en cas de doublon.
        $course = new course();
        $course->save($data);
        $this->assertSame(sprintf('%s %s %s.', $data->str_category, $strtime, $data->str_skill), $course->shortname);

        // Modifie la catégorie du créneau.
        list($catdata, $mform) = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_category_data();
        $category->save($catdata, $mform);

        $oldcontext = $DB->get_record('context', ['instanceid' => $course->id, 'contextlevel' => CONTEXT_COURSE]);

        $data->category = $category->id;
        $course->save($data);

        $newcontext = $DB->get_record('context', ['instanceid' => $course->id, 'contextlevel' => CONTEXT_COURSE]);

        $this->assertNotEquals($oldcontext->path, $newcontext->path);

        // Teste la création des sessions.
        $data->startime = '21:00';
        foreach ($sessions as $session) {
                $session->delete();
        }
        $this->assertSame(0, count($course->get_sessions()));

        $course->save($data);

        $this->assertSame($countsessions, count($course->get_sessions()));
    }

    public function test_set_sessions() {
        // TODO: tester que les sessions crées à la main en dehors de la période ne sont pas supprimées lors d'un changement de période.

        // Période incluant les 2 prochaines semaines à venir.
        $data = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_period_data('p1');
        $period1 = new period();
        $period1->save($data);

        // Période incluant les 3, 4, 5 et 6 prochaines semaines à venir.
        $data = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_period_data('p2', 'future');
        $period2 = new period();
        $period2->save($data);

        // Période incluant les 4 semaines passées.
        $data = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_period_data('p3', 'past');
        $period3 = new period();
        $period3->save($data);

        $data = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_course_data();
        $course = new course();
        $data->periodid = $period1->id;
        $course->save($data);

        // La période p1 a été associée au cours. Il devrait y avoir 2 sessions à venir.
        $sessions = $course->get_sessions();
        $sessionkeys = array_keys($sessions);
        $this->assertEquals(2, count($sessions));

        // Associe la période p2. Il devrait y avoir 4 sessions à venir.
        $data->periodid = $period2->id;
        $course->save($data);
        $sessions = $course->get_sessions();
        $this->assertEquals(4, count($sessions));

        // Vérifie les anciennes sessions p1 ont été supprimées.
        foreach ($sessionkeys as $key) {
            $this->assertArrayNotHasKey($key, $sessions);
        }

        // Vérifie que les sessions déjà existantes sont conservées.
        $sessionkeys = array_keys($sessions);
        $course->set_sessions();
        $sessions = $course->get_sessions();
        $this->assertEquals(array_keys($sessions), $sessionkeys);

        // Vérifie qu'en modification, les sessions obsolètes/passées ne sont pas ajoutées.
        $course->periodid = $period3->id;
        $course->set_sessions();
        $this->assertEquals(0, count($course->get_sessions()));

        // Ajoute une session non prévue à une date passée.
        $pastsessiontime = '123456';
        $pastsessionlocation = $course->locationid;
        $session = new attendancesession();
        $session->name = 'Test past session';
        $session->sessiontime = $pastsessiontime;
        $session->courseid = $course->id;
        $session->activityid = $course->category;
        $session->locationid = $pastsessionlocation;
        $session->save();
        $pastsessionid = $session->id;
        $this->assertEquals(1, count($course->get_sessions()));

        // Ajoute une session non prévue à une date future.
        $futuresessiontime = time() + WEEKSECS;
        $session = new attendancesession();
        $session->name = 'Test future session';
        $session->sessiontime = $futuresessiontime;
        $session->courseid = $course->id;
        $session->activityid = $course->category;
        $session->locationid = $course->locationid;
        $session->save();
        $futuresessionid = $session->id;
        $this->assertEquals(2, count($course->get_sessions()));

        // Associe la période p2. Il devrait y avoir 4 sessions à venir et 1 session passée.
        $course->periodid = $period2->id;
        $course->set_sessions();
        $sessions = $course->get_sessions();
        $this->assertEquals(5, count($sessions));
        // La session passée non prévue doit être conservée et ne doit pas être renommée.
        $this->assertArrayHasKey($pastsessionid, $sessions);
        $this->assertEquals('Test past session', $sessions[$pastsessionid]->name);
        // La session future non prévue doit être supprimée.
        $this->assertArrayNotHasKey($futuresessionid, $sessions);

        // Change le lieu de pratique du cours.
        $course->locationid = (string) ($pastsessionlocation + 1);
        $course->set_sessions();
        $sessions = $course->get_sessions();
        // La session passée non prévue doit conserver son lieu de pratique.
        $this->assertEquals($pastsessionlocation, $sessions[$pastsessionid]->locationid);
        unset($sessions[$pastsessionid]);

        // Les futures sessions doivent être associées au nouveau lieu de pratique.
        foreach ($sessions as $session) {
            $this->assertEquals($course->locationid, $session->locationid);
        }
    }

    public function test_toggle_visibility() {
        global $DB;

        $this->setAdminUser();

        // Génère un cours.
        $data = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_course_data();

        $course = new course();
        $course->save($data);

        // Récupère la visibilité du cours.
        $visibility = $DB->get_record('course', ['id' => $course->id]);
        $visible = intval($visibility->visible);

        // La visibilité du cours doit changer.
        $this->assertNotSame($visible, $course::toggle_visibility($course->id));

        // La visibilité du cours doit revenir à son état initial.
        $this->assertSame($visible, $course::toggle_visibility($course->id));
    }
}
