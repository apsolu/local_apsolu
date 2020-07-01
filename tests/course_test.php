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

defined('MOODLE_INTERNAL') || die();

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
class local_apsolu_core_course_testcase extends advanced_testcase {
    protected function setUp() {
        parent::setUp();

        $this->setAdminUser();

        $this->resetAfterTest();
    }

    public function test_delete() {
        global $DB;

        $course = new local_apsolu\core\course();

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

        $count_records = $DB->count_records($course::TABLENAME);
        $this->assertSame(1, $count_records);

        $result = $course->delete();
        $this->assertTrue($result);

        $count_records = $DB->count_records($course::TABLENAME);
        $this->assertSame(0, $count_records);
    }

    public function test_get_records() {
        global $DB;

        $course = new local_apsolu\core\course();

        $count_records = $DB->count_records($course::TABLENAME);
        $this->assertSame(0, $count_records);

        // Enregistre un nouvel objet.
        $data = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_course_data();
        $course->save($data);

        $count_records = $DB->count_records($course::TABLENAME);
        $this->assertSame(1, $count_records);

        // Enregistre un nouvel objet.
        $course->id = 0;
        $data->event = 'event 2';
        $course->save($data);

        $count_records = $DB->count_records($course::TABLENAME);
        $this->assertSame(2, $count_records);
    }

    public function test_load() {
        // Charge un objet inexistant.
        $course = new local_apsolu\core\course();
        $course->load(1);

        $this->assertSame(0, $course->id);
        $this->assertSame('', $course->fullname);

        // Charge un objet existant.
        $data = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_course_data();
        $course->save($data);

        $test = new local_apsolu\core\course();
        $test->load($course->id);

        $this->assertEquals($course->id, $test->id);
        $this->assertSame($course->fullname, $test->fullname);
    }

    public function test_save() {
        global $DB;

        $course = new local_apsolu\core\course();
        $category = new local_apsolu\core\category();

        $initial_count = $DB->count_records($course::TABLENAME);

        // Enregistre un objet.
        $data = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_course_data();
        $course->save($data);
        $count_records = $DB->count_records($course::TABLENAME);

        // Vérifie l'objet inséré.
        $str_time = get_string($data->weekday, 'calendar').' '.$data->starttime.' '.$data->endtime;
        $this->assertSame(sprintf('%s %s %s %s', $data->str_category, $data->event, $str_time, $data->str_skill), $course->fullname);
        $this->assertSame($count_records, $initial_count + 1);

        // Mets à jour l'objet.
        $data->event = '';
        $course->save($data);
        $count_records = $DB->count_records($course::TABLENAME);

        // Vérifie l'objet mis à jour.
        $this->assertSame(sprintf('%s %s %s', $data->str_category, $str_time, $data->str_skill), $course->fullname);
        $this->assertSame($count_records, $initial_count + 1);

        // Modifie la catégorie du créneau.
        list($catdata, $mform) = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_category_data();
        $category->save($catdata, $mform);

        $oldcontext = $DB->get_record('context', array('instanceid' => $course->id, 'contextlevel' => CONTEXT_COURSE));

        $data->category = $category->id;
        $course->save($data);

        $newcontext = $DB->get_record('context', array('instanceid' => $course->id, 'contextlevel' => CONTEXT_COURSE));

        $this->assertNotEquals($oldcontext->path, $newcontext->path);
    }

    public function test_toggle_visibility() {
        global $DB;

        $this->setAdminUser();

        // Génère un cours.
        $data = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_course_data();

        $course = new local_apsolu\core\course();
        $course->save($data);

        // Récupère la visibilité du cours.
        $visibility = $DB->get_record('course', array('id' => $course->id));
        $visible = intval($visibility->visible);

        // La visibilité du cours doit changer.
        $this->assertNotSame($visible, $course::toggle_visibility($course->id));

        // La visibilité du cours doit revenir à son état initial.
        $this->assertSame($visible, $course::toggle_visibility($course->id));
    }
}
