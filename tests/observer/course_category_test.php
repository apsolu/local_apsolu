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

use core_course_category;
use local_apsolu\core\category;
use local_apsolu\core\grouping;
use local_apsolu\observer\course_category;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot.'/course/lib.php');

/**
 * Classe de tests pour local_apsolu\observer\course_category
 *
 * @package    local_apsolu
 * @category   test
 * @copyright  2021 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class course_category_test extends \advanced_testcase {
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

        // Prépare les données.
        $category1 = $this->getDataGenerator()->create_category();

        $category2 = new grouping();
        $category2->save((object) ['name' => 'grouping']);

        list($data, $mform) = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_category_data();
        $category3 = new category();
        $category3->save($data, $mform);

        list($data, $mform) = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_category_data();
        $category4 = new category();
        $category4->save($data, $mform);

        list($data, $mform) = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_category_data();
        $category5 = new category();
        $category5->save($data, $mform);

        // Enregistre l'état initial.
        $countapsolucategories = $DB->count_records(category::TABLENAME);
        $countapsolugroupings = $DB->count_records(grouping::TABLENAME);
        $countcategories = $DB->count_records('course_categories');

        // Teste la suppression du catégorie non APSOLU.
        $coursecat = core_course_category::get($category1->id);
        $coursecat->delete_full($showfeedback = false);

        $this->assertSame(--$countcategories, $DB->count_records('course_categories'));
        $this->assertSame($countapsolugroupings, $DB->count_records(grouping::TABLENAME));
        $this->assertSame($countapsolucategories, $DB->count_records(category::TABLENAME));

        // Teste la suppression du catégorie de groupement d'activités APSOLU.
        $coursecat = core_course_category::get($category2->id);
        $coursecat->delete_full($showfeedback = false);

        $this->assertSame(--$countcategories, $DB->count_records('course_categories'));
        $this->assertSame(--$countapsolugroupings, $DB->count_records(grouping::TABLENAME));
        $this->assertSame($countapsolucategories, $DB->count_records(category::TABLENAME));

        // Teste la suppression du catégorie d'activité APSOLU.
        $coursecat = core_course_category::get($category3->id);
        $coursecat->delete_full($showfeedback = false);

        $this->assertSame(--$countcategories, $DB->count_records('course_categories'));
        $this->assertSame($countapsolugroupings, $DB->count_records(grouping::TABLENAME));
        $this->assertSame(--$countapsolucategories, $DB->count_records(category::TABLENAME));

        // Teste la suppression du catégorie de groupement d'activités APSOLU contenant une catégorie d'activité.
        $coursecat = core_course_category::get($category4->parent);
        $coursecat->delete_full($showfeedback = false);

        $countcategories = $countcategories - 2;
        $this->assertSame($countcategories, $DB->count_records('course_categories'));
        $this->assertSame(--$countapsolugroupings, $DB->count_records(grouping::TABLENAME));
        $this->assertSame(--$countapsolucategories, $DB->count_records(category::TABLENAME));

        // Teste la suppression du catégorie de groupement d'activités APSOLU contenant une catégorie d'activité,
        // en déplaçant la catégorie d'activité.
        $coursecat = core_course_category::get($category5->parent);
        $coursecat->delete_move($newparentid = 1, $showfeedback = false);

        $this->assertSame(--$countcategories, $DB->count_records('course_categories'));
        $this->assertSame(--$countapsolugroupings, $DB->count_records(grouping::TABLENAME));
        $this->assertSame($countapsolucategories, $DB->count_records(category::TABLENAME));
    }

    /**
     * Teste updated().
     *
     * @covers ::updated()
     */
    public function test_updated(): void {
        global $DB;

        $parent = $this->getDataGenerator()->create_category();

        // Teste la mise à jour d'une catégorie non APSOLU.
        $category = $this->getDataGenerator()->create_category();

        $coursecat = core_course_category::get($category->id);
        $coursecat->update(['parent' => $parent->id]);

        $category = $DB->get_record('course_categories', ['id' => $category->id]);
        $this->assertSame($parent->id, $category->parent);

        // Teste la mise à jour d'une catégorie de groupement d'activités APSOLU.
        $category = new grouping();
        $category->save((object) ['name' => 'grouping']);
        $grouping = clone $category;

        $coursecat = core_course_category::get($category->id);
        $coursecat->update(['parent' => $parent->id]);

        $category = $DB->get_record('course_categories', ['id' => $category->id]);
        $this->assertNotSame($parent->id, $category->parent);

        // Teste la mise à jour d'une catégorie d'activité APSOLU dans une catégorie Moodle.
        list($data, $mform) = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_category_data();
        $category = new category();
        $category->save($data, $mform);

        $coursecat = core_course_category::get($category->id);
        $coursecat->update(['parent' => $parent->id]);

        $category = $DB->get_record('course_categories', ['id' => $category->id]);
        $this->assertNotSame($parent->id, $category->parent);

        // Teste la mise à jour d'une catégorie d'activité APSOLU dans une catégorie de groupement d'activités APSOLU.
        $coursecat = core_course_category::get($category->id);
        $coursecat->update(['parent' => $grouping->id]);

        $category = $DB->get_record('course_categories', ['id' => $category->id]);
        $this->assertSame($grouping->id, $category->parent);
    }
}
