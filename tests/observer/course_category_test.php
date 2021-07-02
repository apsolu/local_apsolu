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
 * Teste la classe local_apsolu\observer\course_category
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
 * Classe de tests pour local_apsolu\observer\course_category
 *
 * @package    local_apsolu
 * @category   test
 * @copyright  2021 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_observer_course_category_testcase extends advanced_testcase {
    protected function setUp() : void {
        parent::setUp();

        $this->setAdminUser();
        $this->getDataGenerator()->get_plugin_generator('local_apsolu')->create_courses();

        $this->resetAfterTest();
    }

    public function test_deleted() {
        global $DB;

        // Prépare les données.
        $category1 = $this->getDataGenerator()->create_category();

        $category2 = new \local_apsolu\core\grouping();
        $category2->save((object) array('name' => 'grouping'));

        list($data, $mform) = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_category_data();
        $category3 = new \local_apsolu\core\category();
        $category3->save($data, $mform);

        list($data, $mform) = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_category_data();
        $category4 = new \local_apsolu\core\category();
        $category4->save($data, $mform);

        list($data, $mform) = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_category_data();
        $category5 = new \local_apsolu\core\category();
        $category5->save($data, $mform);

        // Enregistre l'état initial.
        $count_apsolu_categories = $DB->count_records(\local_apsolu\core\category::TABLENAME);
        $count_apsolu_groupings = $DB->count_records(\local_apsolu\core\grouping::TABLENAME);
        $count_categories = $DB->count_records('course_categories');

        // Teste la suppression du catégorie non APSOLU.
        $coursecat = core_course_category::get($category1->id);
        $coursecat->delete_full($showfeedback = false);

        $this->assertSame(--$count_categories, $DB->count_records('course_categories'));
        $this->assertSame($count_apsolu_groupings, $DB->count_records(\local_apsolu\core\grouping::TABLENAME));
        $this->assertSame($count_apsolu_categories, $DB->count_records(\local_apsolu\core\category::TABLENAME));

        // Teste la suppression du catégorie de groupement d'activités APSOLU.
        $coursecat = core_course_category::get($category2->id);
        $coursecat->delete_full($showfeedback = false);

        $this->assertSame(--$count_categories, $DB->count_records('course_categories'));
        $this->assertSame(--$count_apsolu_groupings, $DB->count_records(\local_apsolu\core\grouping::TABLENAME));
        $this->assertSame($count_apsolu_categories, $DB->count_records(\local_apsolu\core\category::TABLENAME));

        // Teste la suppression du catégorie d'activité APSOLU.
        $coursecat = core_course_category::get($category3->id);
        $coursecat->delete_full($showfeedback = false);

        $this->assertSame(--$count_categories, $DB->count_records('course_categories'));
        $this->assertSame($count_apsolu_groupings, $DB->count_records(\local_apsolu\core\grouping::TABLENAME));
        $this->assertSame(--$count_apsolu_categories, $DB->count_records(\local_apsolu\core\category::TABLENAME));

        // Teste la suppression du catégorie de groupement d'activités APSOLU contenant une catégorie d'activité.
        $coursecat = core_course_category::get($category4->parent);
        $coursecat->delete_full($showfeedback = false);

        $count_categories = $count_categories - 2;
        $this->assertSame($count_categories, $DB->count_records('course_categories'));
        $this->assertSame(--$count_apsolu_groupings, $DB->count_records(\local_apsolu\core\grouping::TABLENAME));
        $this->assertSame(--$count_apsolu_categories, $DB->count_records(\local_apsolu\core\category::TABLENAME));

        // Teste la suppression du catégorie de groupement d'activités APSOLU contenant une catégorie d'activité, en déplaçant la catégorie d'activité.
        $coursecat = core_course_category::get($category5->parent);
        $coursecat->delete_move($newparentid = 1, $showfeedback = false);

        $this->assertSame(--$count_categories, $DB->count_records('course_categories'));
        $this->assertSame(--$count_apsolu_groupings, $DB->count_records(\local_apsolu\core\grouping::TABLENAME));
        $this->assertSame($count_apsolu_categories, $DB->count_records(\local_apsolu\core\category::TABLENAME));
    }

    public function test_updated() {
        global $DB;

        $parent = $this->getDataGenerator()->create_category();

        // Teste la mise à jour d'une catégorie non APSOLU.
        $category = $this->getDataGenerator()->create_category();

        $coursecat = core_course_category::get($category->id);
        $coursecat->update(array('parent' => $parent->id));

        $category = $DB->get_record('course_categories', array('id' => $category->id));
        $this->assertSame($parent->id, $category->parent);

        // Teste la mise à jour d'une catégorie de groupement d'activités APSOLU.
        $category = new \local_apsolu\core\grouping();
        $category->save((object) array('name' => 'grouping'));
        $grouping = clone $category;

        $coursecat = core_course_category::get($category->id);
        $coursecat->update(array('parent' => $parent->id));

        $category = $DB->get_record('course_categories', array('id' => $category->id));
        $this->assertNotSame($parent->id, $category->parent);

        // Teste la mise à jour d'une catégorie d'activité APSOLU dans une catégorie Moodle.
        list($data, $mform) = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_category_data();
        $category = new \local_apsolu\core\category();
        $category->save($data, $mform);

        $coursecat = core_course_category::get($category->id);
        $coursecat->update(array('parent' => $parent->id));

        $category = $DB->get_record('course_categories', array('id' => $category->id));
        $this->assertNotSame($parent->id, $category->parent);

        // Teste la mise à jour d'une catégorie d'activité APSOLU dans une catégorie de groupement d'activités APSOLU.
        $coursecat = core_course_category::get($category->id);
        $coursecat->update(array('parent' => $grouping->id));

        $category = $DB->get_record('course_categories', array('id' => $category->id));
        $this->assertSame($grouping->id, $category->parent);
    }
}
