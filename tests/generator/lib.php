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
 * local_apsolu data generator
 *
 * @package    local_apsolu
 * @category   test
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot.'/local/apsolu/courses/categories/edit_form.php');

/**
 * Data generator class
 *
 * @package    local_apsolu
 * @category   test
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_generator extends testing_module_generator {
    /**
     * Function to create dummy data category.
     *
     * @return stdClass Course object.
     */
    public function get_category_data() {
        // Data.
        $category = new stdClass();
        $category->id = 0;
        $category->name = 'category';
        $category->parent = 0;
        $category->description = '';
        $category->descriptionformat = 0;
        $category->url = '';
        $category->federation = 0;

        // Form.
        $groupings = array(1 => 'grouping');
        $context = context_system::instance();
        $itemid = 0;

        $customdata = array('category' => $category, 'groupings' => $groupings, 'context' => $context, 'itemid' => $itemid);
        $mform = new local_apsolu_courses_categories_edit_form(null, $customdata);

        $editor = file_prepare_standard_editor($category, 'description', $mform->get_description_editor_options(), $context, 'coursecat', 'description', $itemid);
        $mform->set_data($editor);

        return array($category, $mform);
    }

    /**
     * Function to create dummy data course.
     *
     * @param string $event
     *
     * @return stdClass course object
     */
    public function get_course_data(string $event = 'event 1') {
        $data = new stdClass();
        $data->event = $event;
        $data->weekday = 'friday';
        $data->starttime = '12:00';
        $data->endtime = '13:00';
        $data->license = 1;
        $data->on_homepage = 1;
        $data->category = 1;
        $data->str_category = 'category';
        $data->periodid = 1;
        $data->locationid = 1;
        $data->skillid = 1;
        $data->str_skill = 'skill';

        return $data;
    }
}
