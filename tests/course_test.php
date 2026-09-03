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

use coding_exception;
use local_apsolu\core\attendancesession;
use local_apsolu\core\category;
use local_apsolu\core\course;
use local_apsolu\core\location;
use local_apsolu\core\period;
use local_apsolu\customfields\course as CustomfieldsCourse;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/course/lib.php');

/**
 * Classe de tests pour local_apsolu\core\course
 *
 * @package    local_apsolu
 * @copyright  2020 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \local_apsolu\core\course
 */
final class course_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();

        $this->setAdminUser();

        $this->resetAfterTest();
    }

    /**
     * Teste delete().
     *
     * @covers ::delete()
     */
    public function test_delete(): void {
        global $DB;

        $course = new course();

        // Supprime un objet inexistant.
        try {
            $result = $course->delete(1);
            $this->fail('moodle_exception expected on non-existing record.');
        } catch (moodle_exception $exception) {
            $this->assertInstanceOf('moodle_exception', $exception);
        }

        $countcourses = $DB->count_records($course::TABLENAME);

        // Supprime un objet existant.
        $data = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_course_data();
        $course->save($data);

        $countrecords = $DB->count_records($course::TABLENAME);
        $this->assertSame($countcourses + 1, $countrecords);

        $result = $course->delete();
        $this->assertTrue($result);

        $countrecords = $DB->count_records($course::TABLENAME);
        $this->assertSame($countcourses, $countrecords);
    }

    /**
     * Teste get_session_offset().
     *
     * @covers ::get_session_offset()
     */
    public function test_get_session_offset(): void {
        $data = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_course_data();
        $data->customfield_weekday = 3; // Mercredi.
        $data->customfield_timerange = ['start' => ['hour' => '16', 'minute' => '35'], 'end' => ['hour' => '18', 'minute' => '00']];

        $course = new course();
        $course->save($data);

        $startweek = make_timestamp(2020, 7, 6, 0, 0, 0); // Début de semaine.
        $expected = make_timestamp(2020, 7, 8, 16, 35, 0);

        $this->assertSame($expected, $startweek + $course->get_session_offset());

        // Test une exception.
        try {
            $timerange = ['start' => ['hour' => '99', 'minute' => '99'], 'end' => ['hour' => '99', 'minute' => '99']];
            $data->customfield_timerange = $timerange;
            $course->save($data);
            $offset = $course->get_session_offset();
            $this->fail('moodle_exception expected on invalid starttime value.');
        } catch (moodle_exception $exception) {
            $this->assertInstanceOf('moodle_exception', $exception);
        }
    }

    /**
     * Teste get_records().
     *
     * @covers ::get_records()
     */
    public function test_get_records(): void {
        global $DB;

        $course = new course();
        $countrecords = $DB->count_records($course::TABLENAME);

        // Enregistre un nouvel objet.
        $data = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_course_data();
        $course->save($data);
        $this->assertSame($countrecords + 1, $DB->count_records($course::TABLENAME));

        // Enregistre un nouvel objet.
        $data->id = 0;
        $data->customfield_category['additionalstr'] = 'event 2';
        $course->save($data);
        $this->assertSame($countrecords + 2, $DB->count_records($course::TABLENAME));
    }

    /**
     * Teste load().
     *
     * @covers ::load()
     */
    public function test_load(): void {
        // Charge un objet inexistant.
        $course = new course();
        $course->load(-1);

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

    /**
     * Teste save().
     *
     * @covers ::save()
     */
    public function test_save(): void {
        global $DB;

        $course = new course();
        $initialcount = $DB->count_records($course::TABLENAME);

        $customfields = CustomfieldsCourse::get_apsolu_courses_custom_fields();

        // Enregistre un objet.
        $data = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_course_data();
        $course->save($data);
        $countrecords = $DB->count_records($course::TABLENAME);

        $sessions = $course->get_sessions();
        $countsessions = count($sessions);

        // Vérifie l'objet inséré.
        $categoryid = $course->customfields['category']->get('intvalue');
        $category = $DB->get_record('course_categories', ['id' => $categoryid], '*', MUST_EXIST);
        $skillid = $course->customfields['skill']->get_value();
        $skill = $DB->get_record('apsolu_skills', ['id' => $skillid], '*', MUST_EXIST);

        $type = $DB->get_record('apsolu_courses_types', ['id' => $course->customfields['type']->get_value()], '*', MUST_EXIST);
        $type->fullnametemplate = sprintf(
            '%%%02d: %%%02d (%%%02d)',
            $customfields['type']->id,
            $customfields['category']->id,
            $customfields['skill']->id,
        );
        $DB->update_record('apsolu_courses_types', $type);

        $type = $DB->get_record('apsolu_courses_types', ['id' => $course->customfields['type']->get_value()], '*', MUST_EXIST);
        $course->save($data);
        $this->assertSame(sprintf('%s: %s (%s)', $type->name, $category->name, $skill->name), $course->fullname);
        $this->assertSame($countrecords, $initialcount + 1);

        // Met à jour l'objet.
        $data->visible = 0;
        $course->save($data);
        $countrecords = $DB->count_records($course::TABLENAME);

        // Vérifie que les sessions n'ont pas été modifiées.
        $this->assertSame($countsessions, count($course->get_sessions()));
        $this->assertSame($sessions, $course->get_sessions());

        // Vérifie l'objet mis à jour.
        $this->assertSame(sprintf('%s: %s (%s)', $type->name, $category->name, $skill->name), $course->fullname);
        $this->assertSame($countrecords, $initialcount + 1);

        // Vérifie qu'un nom abrégé est regénéré en cas de doublon.
        $course = new course();
        $data->id = 0;
        $course->save($data);
        $this->assertSame(sprintf('%s: %s (%s)', $type->name, $category->name, $skill->name), $course->fullname);

        // Modifie la catégorie du créneau.
        [$catdata, $mform] = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_category_data();
        $category = new category();
        $category->save($catdata, $mform);

        $oldcontext = $DB->get_record('context', ['instanceid' => $course->id, 'contextlevel' => CONTEXT_COURSE]);

        $data->customfield_category['categoryid'] = $category->id;
        $course->save($data);

        $newcontext = $DB->get_record('context', ['instanceid' => $course->id, 'contextlevel' => CONTEXT_COURSE]);

        $this->assertNotEquals($oldcontext->path, $newcontext->path);

        // Teste la création des sessions.
        $data->customfield_timerange = ['start' => ['hour' => '21', 'minute' => '00'], 'end' => ['hour' => '22', 'minute' => '00']];
        foreach ($sessions as $session) {
                $session->delete();
        }
        $this->assertSame(0, count($course->get_sessions()));

        $course->save($data);

        $this->assertSame($countsessions, count($course->get_sessions()));

        // Teste la saisie d'un numéro d'identification (insertion et mise à jour).
        $course = new course();
        $data->idnumber = 'ABC123';
        $course->save($data);

        $idnumber = $DB->get_record('course', ['id' => $course->id]);
        $this->assertSame($data->idnumber, $idnumber->idnumber);

        $data->idnumber = 'ABCD12';
        $course->save($data);

        $idnumber = $DB->get_record('course', ['id' => $course->id]);
        $this->assertSame($data->idnumber, $idnumber->idnumber);
    }

    /**
     * Teste set_sessions().
     *
     * @covers ::set_sessions()
     */
    public function test_set_sessions(): void {
        // TODO: tester que les sessions crées à la main en dehors de la période ne sont pas supprimées lors
        // d'un changement de période.

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

        // Génère un nouveau cours.
        $location = new location();
        $location->save();

        $otherlocation = new location();
        $otherlocation->name = 'otherlocation';
        $otherlocation->save();

        $data = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_course_data();
        $data->customfield_location = $location->id;
        $data->customfield_period = $period1->id;
        $data->customfield_timerange = ['start' => ['hour' => '16', 'minute' => '00'], 'end' => ['hour' => '18', 'minute' => '00']];
        $course = new course();
        $course->save($data);

        // La période p1 a été associée au cours. Il devrait y avoir 2 sessions à venir.
        $sessions = $course->get_sessions();
        $sessionkeys = array_keys($sessions);
        $this->assertEquals(2, count($sessions));

        // Associe la période p2. Il devrait y avoir 4 sessions à venir.
        $data->customfield_period = $period2->id;
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
        $data->customfield_period = $period3->id;
        $course->save($data);
        $course->set_sessions();
        $this->assertEquals(0, count($course->get_sessions()));

        // Ajoute une session non prévue à une date passée.
        $pastsessiontime = '123456';
        $pastsessionlocation = $course->customfields['location']->get_value();
        $session = new attendancesession();
        $session->name = 'Test past session';
        $session->sessiontime = $pastsessiontime;
        $session->courseid = $course->id;
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
        $session->locationid = $course->customfields['location']->get_value();
        $session->save();
        $futuresessionid = $session->id;
        $this->assertEquals(2, count($course->get_sessions()));

        // Associe la période p2. Il devrait y avoir 4 sessions à venir et 1 session passée.
        $data->customfield_period = $period2->id;
        $course->save($data);
        $course->set_sessions();
        $sessions = $course->get_sessions();
        $this->assertEquals(5, count($sessions));
        // La session passée non prévue doit être conservée et ne doit pas être renommée.
        $this->assertArrayHasKey($pastsessionid, $sessions);
        $this->assertEquals('Test past session', $sessions[$pastsessionid]->name);
        // La session future non prévue doit être supprimée.
        $this->assertArrayNotHasKey($futuresessionid, $sessions);

        // Change le lieu de pratique du cours.
        $data->customfield_location = $otherlocation->id;
        $course->save($data);
        $course->set_sessions();
        $sessions = $course->get_sessions();
        // La session passée non prévue doit conserver son lieu de pratique.
        $this->assertEquals($location->id, $sessions[$pastsessionid]->locationid);
        unset($sessions[$pastsessionid]);

        // Les futures sessions doivent être associées au nouveau lieu de pratique.
        foreach ($sessions as $session) {
            $this->assertEquals($course->customfields['location']->get_value(), $session->locationid);
        }
    }

    /**
     * Teste toggle_visibility().
     *
     * @covers ::toggle_visibility()
     */
    public function test_toggle_visibility(): void {
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
