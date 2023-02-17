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

namespace local_apsolu\observer;

use local_apsolu\core\course;

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
class course_test extends \advanced_testcase {
    protected function setUp() : void {
        parent::setUp();

        $this->setAdminUser();
        $this->getDataGenerator()->get_plugin_generator('local_apsolu')->create_courses();

        $this->resetAfterTest();
    }

    public function test_deleted() {
        global $DB;

        // Teste le bon fonctionnement lors de la suppression d'un cours non APSOLU.
        $countapsolucourses = $DB->count_records(course::TABLENAME);
        $course = $this->getDataGenerator()->create_course();
        delete_course($course, $showfeedback = false);
        $this->assertSame($countapsolucourses, $DB->count_records(course::TABLENAME));

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
        $apsolucourse = $DB->get_record('apsolu_courses', array('id' => $course->id));
        $this->assertFalse($apsolucourse);
    }

    public function test_updated() {
        global $DB;

        $moodlecategory1 = $this->getDataGenerator()->create_category();
        $moodlecategory2 = $this->getDataGenerator()->create_category();
        $moodlecourse = $this->getDataGenerator()->create_course(array(
            'category' => $moodlecategory1->id,
            'shortname' => 'Anglais',
            'fullname' => 'Anglais',
        ));

        // Teste le bon fonctionnement lors de la suppression d'un cours non APSOLU.
        $moodlecourse->category = $moodlecategory2->id;
        update_course($moodlecourse);
        $moodlecourse = $DB->get_record('course', array('id' => $moodlecourse->id));

        $this->assertSame($moodlecategory2->id, $moodlecourse->category);
        $this->assertSame('Anglais', $moodlecourse->shortname);
        $this->assertSame('Anglais', $moodlecourse->fullname);

        // Teste la modification d'un cours via l'API de Moodle sans modifier la catégorie.
        $sql = "SELECT c.* FROM {course} c JOIN {apsolu_courses} ac ON c.id = ac.id";
        $apsolucourses = $DB->get_records_sql($sql);
        $apsolucourse = current($apsolucourses);
        $this->assertNotSame(false, $apsolucourse);

        $apsolushortname = $apsolucourse->shortname;
        $apsolufullname = $apsolucourse->fullname;
        $apsolucategory = $apsolucourse->category;

        $apsolucourse->visible = 0;
        update_course($apsolucourse);
        $apsolucourse = $DB->get_record('course', array('id' => $apsolucourse->id));

        $this->assertSame($apsolucategory, $apsolucourse->category);
        $this->assertSame($apsolushortname, $apsolucourse->shortname);
        $this->assertSame($apsolufullname, $apsolucourse->fullname);

        // Teste la modification d'un cours via l'API update_course de Moodle dans une catégorie non APSOLU.
        $apsolucourse->category = $moodlecategory1->id;
        update_course($apsolucourse);
        $apsolucourse = $DB->get_record('course', array('id' => $apsolucourse->id));

        // Le cours ne doit pas avoir été déplacé dans la catégorie Moodle.
        $this->assertNotSame($moodlecategory1->id, $apsolucourse->category);
        // Le cours doit avoir été remis dans sa catégorie d'origine.
        $this->assertSame($apsolucategory, $apsolucourse->category);
        // Les noms doivent être inchangés.
        $this->assertSame($apsolushortname, $apsolucourse->shortname);
        $this->assertSame($apsolufullname, $apsolucourse->fullname);

        // Teste la modification d'un cours via l'API move_courses de Moodle dans une catégorie non APSOLU.
        move_courses(array($apsolucourse->id), $moodlecategory1->id);
        $apsolucourse = $DB->get_record('course', array('id' => $apsolucourse->id));

        // Le cours ne doit pas avoir été déplacé dans la catégorie Moodle.
        $this->assertNotSame($moodlecategory1->id, $apsolucourse->category);
        // Le cours doit avoir été remis dans sa catégorie d'origine.
        $this->assertSame($apsolucategory, $apsolucourse->category);
        // Les noms doivent être inchangés.
        $this->assertSame($apsolushortname, $apsolucourse->shortname);
        $this->assertSame($apsolufullname, $apsolucourse->fullname);

        // Teste le recalcule du nom complet et abrégé.
        $sql = "SELECT c.* FROM {course} c JOIN {apsolu_courses} ac ON c.id = ac.id";
        $apsolucourses = $DB->get_records_sql($sql);
        $apsolucourse = current($apsolucourses);
        $this->assertNotSame(false, $apsolucourse);

        $sql = "SELECT acc.* FROM {apsolu_courses_categories} acc WHERE acc.id != :id";
        $apsolucategories = $DB->get_records_sql($sql, array('id' => $apsolucourse->category));
        $apsolucategory = current($apsolucategories);
        $this->assertNotSame(false, $apsolucategories);

        $shortname = $apsolucourse->shortname;
        $fullname = $apsolucourse->fullname;

        $apsolucourse->category = $apsolucategory->id;
        update_course($apsolucourse);

        $apsolucourse = $DB->get_record('course', array('id' => $apsolucourse->id));
        $this->assertNotSame($shortname, $apsolucourse->shortname);
        $this->assertNotSame($fullname, $apsolucourse->fullname);
    }
}
