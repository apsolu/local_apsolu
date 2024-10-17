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

use local_apsolu\core\category;
use local_apsolu\core\course;
use local_apsolu\core\grouping;
use local_apsolu\core\skill;

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
     * Fonction pour générer :
     * - des groupements d'activités sportives
     * - des activités sportives
     * - des niveaux de pratique
     * - des créneaux
     *
     * @return void
     */
    public function create_courses() {
        // Données pour générer les niveaux de pratiques.
        $records = [];
        $records[] = 'débutant';
        $records[] = 'intermédiaire';
        $records[] = 'expert';

        $skills = [];
        foreach ($records as $skillname) {
            $skill = new skill();
            $skill->name = $skillname;
            $skill->shortname = $skillname;
            $skill->save();

            $skills[] = $skill;
        }

        // Données pour générer les groupements d'activités sportives, les activités sportives et les créneaux.
        $records = [];
        $records[] = ['Pratiques artistiques', 'Arts du cirque'];
        $records[] = ['Pratiques artistiques', 'Danse salsa'];
        $records[] = ['Pratiques artistiques', 'Danse swing'];
        $records[] = ['Pratiques gymniques', 'Trampoline'];
        $records[] = ['Pratiques gymniques', 'Freestyle'];
        $records[] = ['Sports aquatiques', 'Apnée'];
        $records[] = ['Sports aquatiques', 'Aquagym'];
        $records[] = ['Sports aquatiques', 'Natation'];
        $records[] = ['Sports de plein air', 'Aviron'];
        $records[] = ['Sports de plein air', 'Cyclisme sur route'];
        $records[] = ['Sports de plein air', 'Escalade'];

        $groupings = [];
        foreach ($records as $record) {
            list($groupingname, $categoryname) = $record;

            if (isset($groupings[$groupingname]) === false) {
                $grouping = new grouping();
                $grouping->name = $groupingname;
                $grouping->save();

                $groupings[$groupingname] = $grouping;
            }

            list($catdata, $mform) = $this->get_category_data();
            $catdata->name = $categoryname;
            $catdata->parent = $groupings[$groupingname]->id;

            $category = new category();
            $category->save($catdata, $mform);

            for ($i = 0; $i < 3; $i++) {
                $data = $this->get_course_data();
                $data->category = $category->id;
                $data->str_category = $categoryname;
                $data->skillid = $skills[$i]->id;
                $data->str_skill = $skills[$i]->name;

                $course = new course();
                $course->save($data);
            }
        }
    }

    /**
     * Function to create dummy data category.
     *
     * @return stdClass Course object.
     */
    public function get_category_data() {
        // Crée un groupement d'activités.
        $data = new stdClass();
        $data->name = 'grouping';

        $grouping = new grouping();
        $grouping->save($data);

        // Data.
        $category = new stdClass();
        $category->id = 0;
        $category->name = 'category';
        $category->parent = $grouping->id;
        $category->description = '';
        $category->descriptionformat = 0;
        $category->url = '';

        // Form.
        $groupings = [$grouping->id => $grouping->name];
        $context = context_system::instance();
        $itemid = 0;

        $customdata = ['category' => $category, 'groupings' => $groupings, 'context' => $context, 'itemid' => $itemid];
        $mform = new local_apsolu_courses_categories_edit_form(null, $customdata);

        $editor = file_prepare_standard_editor($category, 'description', $mform->get_description_editor_options(), $context,
            'coursecat', 'description', $itemid);
        $mform->set_data($editor);

        return [$category, $mform];
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
        $data->showpolicy = 0;
        $data->category = 1;
        $data->str_category = 'category';
        $data->periodid = 1;
        $data->locationid = 1;
        $data->skillid = 1;
        $data->str_skill = 'skill';

        return $data;
    }

    /**
     * Function to create dummy data period.
     *
     * @param string      $name
     * @param null|string $type
     *
     * @return stdClass period object
     */
    public function get_period_data(string $name, ?string $type = null) {
        $monday = strtotime('monday this week');

        $weeks = [];

        switch ($type) {
            case 'past':
                $weeks[] = core_date::strftime('%F', $monday - (4 * WEEKSECS));
                $weeks[] = core_date::strftime('%F', $monday - (3 * WEEKSECS));
                $weeks[] = core_date::strftime('%F', $monday - (2 * WEEKSECS));
                $weeks[] = core_date::strftime('%F', $monday - WEEKSECS);
                break;
            case 'mixed':
                $weeks[] = core_date::strftime('%F', $monday - (2 * WEEKSECS));
                $weeks[] = core_date::strftime('%F', $monday - WEEKSECS);
                $weeks[] = core_date::strftime('%F', $monday + WEEKSECS);
                $weeks[] = core_date::strftime('%F', $monday + (2 * WEEKSECS));
                break;
            case 'future':
                $weeks[] = core_date::strftime('%F', $monday + (3 * WEEKSECS));
                $weeks[] = core_date::strftime('%F', $monday + (4 * WEEKSECS));
                $weeks[] = core_date::strftime('%F', $monday + (5 * WEEKSECS));
                $weeks[] = core_date::strftime('%F', $monday + (6 * WEEKSECS));
                break;
            default:
                $weeks[] = core_date::strftime('%F', $monday + WEEKSECS);
                $weeks[] = core_date::strftime('%F', $monday + (2 * WEEKSECS));
        }

        $data = new stdClass();
        $data->name = $name;
        $data->generic_name = $name;
        $data->weeks = implode(',', $weeks);

        return $data;
    }
}
