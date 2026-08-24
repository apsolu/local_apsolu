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

use local_apsolu\core\course;
use local_apsolu\customfields\course as CustomfieldsCourse;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/course/lib.php');

/**
 * Classe de tests pour local_apsolu\observer\course
 *
 * @package    local_apsolu
 * @category   test
 * @copyright  2021 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \local_apsolu\observer\course
 */
final class course_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();

        $this->setAdminUser();
        $this->getDataGenerator()->get_plugin_generator('local_apsolu')->create_courses();

        $this->resetAfterTest();
    }

    /**
     * Teste deleted().
     *
     * @covers ::deleted()
     */
    public function test_deleted(): void {
        global $DB;

        // Initialise la sous-requête pour récupérer les cours de type APSOLU.
        $coursecustomfields = CustomfieldsCourse::get_apsolu_courses_custom_fields();
        $subsql = "SELECT cd.instanceid
                     FROM {customfield_data} cd
                    WHERE cd.component = 'core_course'
                      AND cd.value != 0
                      AND cd.fieldid = :customfieldtypeid";
        $params = ['customfieldtypeid' => $coursecustomfields['type']->id];

        // Teste le bon fonctionnement lors de la suppression d'un cours non APSOLU.
        $countapsolucourses = $DB->count_records(course::TABLENAME);
        $course = $this->getDataGenerator()->create_course();
        delete_course($course, $showfeedback = false);
        $this->assertSame($countapsolucourses, $DB->count_records(course::TABLENAME));

        // Teste la suppression d'un cours via l'API de Moodle.
        $sql = "SELECT c.* FROM {course} c WHERE c.id IN (" . $subsql . ")";
        $courses = $DB->get_records_sql($sql, $params);
        $course = current($courses);
        $this->assertNotSame(false, $course);

        delete_course($course, $showfeedback = false);

        // Contrôle que la table apsolu_courses a été nettoyée.
        $sessions = $DB->get_records('apsolu_attendance_sessions', ['courseid' => $course->id]);
        $this->assertEmpty($sessions);

        // Contrôle que la table apsolu_courses a été nettoyée.
        $apsolucourse = Course::get_record(['id' => $course->id]);
        $this->assertFalse($apsolucourse);
    }

    /**
     * Teste updated().
     *
     * @covers ::updated()
     */
    public function test_updated(): void {
        global $DB;

        // Initialise la sous-requête pour récupérer les cours de type APSOLU.
        $coursecustomfields = CustomfieldsCourse::get_apsolu_courses_custom_fields();
        $subsql = "SELECT cd.instanceid
                     FROM {customfield_data} cd
                    WHERE cd.component = 'core_course'
                      AND cd.value != 0
                      AND cd.fieldid = :customfieldtypeid";
        $params = ['customfieldtypeid' => $coursecustomfields['type']->id];

        $moodlecategory1 = $this->getDataGenerator()->create_category();
        $moodlecategory2 = $this->getDataGenerator()->create_category();
        $moodlecourse = $this->getDataGenerator()->create_course([
            'category' => $moodlecategory1->id,
            'shortname' => 'Anglais',
            'fullname' => 'Anglais',
        ]);

        // Teste le bon fonctionnement lors de la suppression d'un cours non APSOLU.
        $moodlecourse->category = $moodlecategory2->id;
        update_course($moodlecourse);
        $moodlecourse = $DB->get_record('course', ['id' => $moodlecourse->id]);

        $this->assertSame($moodlecategory2->id, $moodlecourse->category);
        $this->assertSame('Anglais', $moodlecourse->shortname);
        $this->assertSame('Anglais', $moodlecourse->fullname);

        // Teste la modification d'un cours via l'API de Moodle sans modifier la catégorie.
        $sql = "SELECT c.* FROM {course} c WHERE c.id IN (" . $subsql . ")";
        $apsolucourses = $DB->get_records_sql($sql, $params);
        $apsolucourse = current($apsolucourses);
        $this->assertNotSame(false, $apsolucourse);

        $apsolushortname = $apsolucourse->shortname;
        $apsolufullname = $apsolucourse->fullname;
        $apsolucategory = $apsolucourse->category;

        $apsolucourse->visible = 0;
        update_course($apsolucourse);
        $apsolucourse = $DB->get_record('course', ['id' => $apsolucourse->id]);

        $this->assertSame($apsolucategory, $apsolucourse->category);
        $this->assertSame($apsolushortname, $apsolucourse->shortname);
        $this->assertSame($apsolufullname, $apsolucourse->fullname);

        // Teste la modification d'un cours via l'API update_course de Moodle dans une catégorie non APSOLU.
        $apsolucourse->category = $moodlecategory1->id;
        update_course($apsolucourse);
        $apsolucourse = $DB->get_record('course', ['id' => $apsolucourse->id]);

        // Le cours ne doit pas avoir été déplacé dans la catégorie Moodle.
        $this->assertNotSame($moodlecategory1->id, $apsolucourse->category);
        // Le cours doit avoir été remis dans sa catégorie d'origine.
        $this->assertSame($apsolucategory, $apsolucourse->category);
        // Les noms doivent être inchangés.
        $this->assertSame($apsolushortname, $apsolucourse->shortname);
        $this->assertSame($apsolufullname, $apsolucourse->fullname);

        // Teste la modification d'un cours via l'API move_courses de Moodle dans une catégorie non APSOLU.
        move_courses([$apsolucourse->id], $moodlecategory1->id);
        $apsolucourse = $DB->get_record('course', ['id' => $apsolucourse->id]);

        // Le cours ne doit pas avoir été déplacé dans la catégorie Moodle.
        $this->assertNotSame($moodlecategory1->id, $apsolucourse->category);
        // Le cours doit avoir été remis dans sa catégorie d'origine.
        $this->assertSame($apsolucategory, $apsolucourse->category);
        // Les noms doivent être inchangés.
        $this->assertSame($apsolushortname, $apsolucourse->shortname);
        $this->assertSame($apsolufullname, $apsolucourse->fullname);

        // Teste le recalcule du nom complet et abrégé.
        $sql = "SELECT c.* FROM {course} c WHERE c.id IN (" . $subsql . ")";
        $apsolucourses = $DB->get_records_sql($sql, $params);
        $apsolucourse = current($apsolucourses);
        $this->assertNotSame(false, $apsolucourse);

        $sql = "SELECT acc.* FROM {apsolu_courses_categories} acc WHERE acc.id != :id";
        $apsolucategories = $DB->get_records_sql($sql, ['id' => $apsolucourse->category]);
        $apsolucategory = current($apsolucategories);
        $this->assertNotSame(false, $apsolucategories);

        $shortname = $apsolucourse->shortname;
        $fullname = $apsolucourse->fullname;

        $apsolucourse->category = $apsolucategory->id;
        update_course($apsolucourse);

        $apsolucourse = $DB->get_record('course', ['id' => $apsolucourse->id]);
        $this->assertNotSame($shortname, $apsolucourse->shortname);
        $this->assertNotSame($fullname, $apsolucourse->fullname);

        // Teste le recalcule du nom complet et abrégé (sans changer de catégorie) depuis l'API APSOLU.
        $sql = "SELECT c.* FROM {course} c WHERE c.id IN (" . $subsql . ")";
        $records = $DB->get_records_sql($sql, $params);
        $record = current($records);
        $this->assertNotSame(false, $record);

        $course = new course();
        $course->load($record->id);

        $event = 'changed';
        $data = $course->get_course_data();
        $data->customfield_category['additionalstr'] = $event;
        $course->save($data);
        // Recharge les données, car l'observateur a normalement modifié les noms du cours.
        $course->load($record->id);

        $this->assertStringContainsString($event, $course->shortname);
        $this->assertStringContainsString($event, $course->fullname);
    }
}
