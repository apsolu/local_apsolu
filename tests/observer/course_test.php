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
 * Teste la classe local_apsolu\observer\course
 *
 * @package    local_apsolu
 * @category   test
 * @copyright  2021 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot.'/course/lib.php');

/**
 * Classe de tests pour local_apsolu\observer\course
 *
 * @package    local_apsolu
 * @category   test
 * @copyright  2021 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_observer_course_testcase extends advanced_testcase {
    protected function setUp() : void {
        parent::setUp();

        $this->setAdminUser();
        $this->getDataGenerator()->get_plugin_generator('local_apsolu')->create_courses();

        $this->resetAfterTest();
    }

    public function test_deleted() {
        global $DB;

        // Teste le bon fonctionnement lors de la suppression d'un cours non APSOLU.
        $count_apsolu_courses = $DB->count_records(\local_apsolu\core\course::TABLENAME);
        $course = $this->getDataGenerator()->create_course();
        delete_course($course, $showfeedback = false);
        $this->assertSame($count_apsolu_courses, $DB->count_records(\local_apsolu\core\course::TABLENAME));

        // Teste la suppression d'un cours via l'API de Moodle.
        $sql = "SELECT c.* FROM {course} c JOIN {apsolu_courses} ac ON c.id = ac.id";
        $courses = $DB->get_records_sql($sql);
        $course = current($courses);
        $this->assertNotSame(false, $course);

        delete_course($course, $showfeedback = false);

        // Contrôle que la table apsolu_courses a été nettoyée.
        $sessions = $DB->get_records('apsolu_attendance_sessions', array('courseid' => $course->id));
        $this->assertEmpty($sessions);

        // Contrôle que la table apsolu_courses a été nettoyée.
        $apsolu_course = $DB->get_record('apsolu_courses', array('id' => $course->id));
        $this->assertFalse($apsolu_course);
    }

    public function test_updated() {
        global $DB;

        $moodle_category1 = $this->getDataGenerator()->create_category();
        $moodle_category2 = $this->getDataGenerator()->create_category();
        $moodle_course = $this->getDataGenerator()->create_course(array(
            'category' => $moodle_category1->id,
            'shortname' => 'Anglais',
            'fullname' => 'Anglais',
        ));

        // Teste le bon fonctionnement lors de la suppression d'un cours non APSOLU.
        $moodle_course->category = $moodle_category2->id;
        update_course($moodle_course);
        $moodle_course = $DB->get_record('course', array('id' => $moodle_course->id));

        $this->assertSame($moodle_category2->id, $moodle_course->category);
        $this->assertSame('Anglais', $moodle_course->shortname);
        $this->assertSame('Anglais', $moodle_course->fullname);

        // Teste la modification d'un cours via l'API de Moodle sans modifier la catégorie.
        $sql = "SELECT c.* FROM {course} c JOIN {apsolu_courses} ac ON c.id = ac.id";
        $apsolu_courses = $DB->get_records_sql($sql);
        $apsolu_course = current($apsolu_courses);
        $this->assertNotSame(false, $apsolu_course);

        $apsolu_shortname = $apsolu_course->shortname;
        $apsolu_fullname = $apsolu_course->fullname;
        $apsolu_category = $apsolu_course->category;

        $apsolu_course->visible = 0;
        update_course($apsolu_course);
        $apsolu_course = $DB->get_record('course', array('id' => $apsolu_course->id));

        $this->assertSame($apsolu_category, $apsolu_course->category);
        $this->assertSame($apsolu_shortname, $apsolu_course->shortname);
        $this->assertSame($apsolu_fullname, $apsolu_course->fullname);

        // Teste la modification d'un cours via l'API update_course de Moodle dans une catégorie non APSOLU.
        $apsolu_course->category = $moodle_category1->id;
        update_course($apsolu_course);
        $apsolu_course = $DB->get_record('course', array('id' => $apsolu_course->id));

        // Le cours ne doit pas avoir été déplacé dans la catégorie Moodle.
        $this->assertNotSame($moodle_category1->id, $apsolu_course->category);
        // Le cours doit avoir été remis dans sa catégorie d'origine.
        $this->assertSame($apsolu_category, $apsolu_course->category);
        // Les noms doivent être inchangés.
        $this->assertSame($apsolu_shortname, $apsolu_course->shortname);
        $this->assertSame($apsolu_fullname, $apsolu_course->fullname);

        // Teste la modification d'un cours via l'API move_courses de Moodle dans une catégorie non APSOLU.
        move_courses(array($apsolu_course->id), $moodle_category1->id);
        $apsolu_course = $DB->get_record('course', array('id' => $apsolu_course->id));

        // Le cours ne doit pas avoir été déplacé dans la catégorie Moodle.
        $this->assertNotSame($moodle_category1->id, $apsolu_course->category);
        // Le cours doit avoir été remis dans sa catégorie d'origine.
        $this->assertSame($apsolu_category, $apsolu_course->category);
        // Les noms doivent être inchangés.
        $this->assertSame($apsolu_shortname, $apsolu_course->shortname);
        $this->assertSame($apsolu_fullname, $apsolu_course->fullname);

        // Teste le recalcule du nom complet et abrégé.
        $sql = "SELECT c.* FROM {course} c JOIN {apsolu_courses} ac ON c.id = ac.id";
        $apsolu_courses = $DB->get_records_sql($sql);
        $apsolu_course = current($apsolu_courses);
        $this->assertNotSame(false, $apsolu_course);

        $sql = "SELECT acc.* FROM {apsolu_courses_categories} acc WHERE acc.id != :id";
        $apsolu_categories = $DB->get_records_sql($sql, array('id' => $apsolu_course->category));
        $apsolu_category = current($apsolu_categories);
        $this->assertNotSame(false, $apsolu_categories);

        $shortname = $apsolu_course->shortname;
        $fullname = $apsolu_course->fullname;

        $apsolu_course->category = $apsolu_category->id;
        update_course($apsolu_course);

        $apsolu_course = $DB->get_record('course', array('id' => $apsolu_course->id));
        $this->assertNotSame($shortname, $apsolu_course->shortname);
        $this->assertNotSame($fullname, $apsolu_course->fullname);
    }
}
