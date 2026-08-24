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

use local_apsolu\core\category;
use local_apsolu\core\course;
use local_apsolu\core\coursetype;
use local_apsolu\core\customfields;
use local_apsolu\core\grouping;
use local_apsolu\core\skill;
use local_apsolu\customfields\course as CustomfieldsCourse;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/local/apsolu/courses/categories/edit_form.php');

/**
 * Data generator class
 *
 * @package    local_apsolu
 * @category   test
 * @copyright  2020 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_generator extends testing_module_generator {
    /**
     * Méthode pour générer des catégories de cours (activités sportives).
     *
     * @return array Tableau de catégories de cours.
     */
    public function create_categories(): array {
        global $DB;

        // Vérifie que les données n'ont pas déjà été générées.
        $categories = $DB->get_records('apsolu_courses_categories');

        if ($categories !== []) {
            return $categories;
        }

        // Génère les catégories.
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
            [$groupingname, $categoryname] = $record;

            if (isset($groupings[$groupingname]) === false) {
                $grouping = new grouping();
                $grouping->name = $groupingname;
                $grouping->save();

                $groupings[$groupingname] = $grouping;
            }

            [$catdata, $mform] = $this->get_category_data();
            $catdata->name = $categoryname;
            $catdata->parent = $groupings[$groupingname]->id;

            $category = new category();
            $category->save($catdata, $mform);

            $categories[$category->id] = $category;
        }

        return $categories;
    }

    /**
     * Méthode pour générer des types de cours.
     *
     * @return array Tableau de types de cours.
     */
    public function create_course_types(): array {
        global $DB;

        // Vérifie que les données n'ont pas déjà été générées.
        $coursetypes = $DB->get_records('apsolu_courses_types', [], 'shortname, id, name, fullnametemplate, color, sortorder');

        if ($coursetypes !== []) {
            return $coursetypes;
        }

        // Génère les types de cours.
        $customfields = CustomfieldsCourse::get_course_custom_fields();
        $typeid = $customfields['type']->id;
        $categoryid = $customfields['category']->id;
        $skillid = $customfields['skill']->id;

        $records = [];
        $records[] = ['Cours', ['skill', 'period', 'location', 'weekday', 'timerange', 'federation', 'on_homepage', 'information']];
        $records[] = ['Stage', ['skill', 'location', 'daterange']];

        foreach ($records as $record) {
            $data = new stdClass();
            $data->name = $record[0];
            $data->shortname = strtolower($data->name);
            $data->fullnametemplate = sprintf('%%%02d: %%%02d (%%%02d)', $typeid, $categoryid, $skillid);
            $data->color = '#f66151';
            $data->fields = [];
            foreach ($record[1] as $field) {
                $data->fields[$field] = ['fieldid' => $customfields[$field]->id, 'admin' => '1', 'public' => '1'];
            }

            $coursetype = new CourseType();
            $coursetype->save($data);
            $coursetypes[$data->shortname] = $coursetype;
        }

        return $coursetypes;
    }

    /**
     * Fonction pour générer :
     * - des groupements d'activités sportives
     * - des activités sportives
     * - des niveaux de pratique
     * - des créneaux
     *
     * @return array Tableau de cours.
     */
    public function create_courses(): array {
        global $DB;

        // Données pour générer des formats de cours.
        $coursetypes = $this->create_course_types();

        // Données pour générer les niveaux de pratiques.
        $skills = $this->create_skills();

        // Données pour générer les groupements d'activités sportives et les activités sportives.
        $categories = $this->create_categories();

        // Vérifie que les données n'ont pas déjà été générées.
        $courses = $DB->get_records_sql('SELECT * FROM {course} WHERE category != 0');

        if ($courses !== []) {
            return $courses;
        }

        // Données pour générer créneaux horaires.
        foreach ($categories as $category) {
            for ($i = 0; $i < 3; $i++) {
                $values = [];
                $values['customfield_type'] = current($coursetypes)->id;
                $values['customfield_category'] = ['categoryid' => $category->id, 'additionalstr' => ''];
                $data = $this->get_course_data($values);

                $course = new course();
                $course->save($data);

                $courses[$course->id] = $course;
            }
        }

        return $courses;
    }

    /**
     * Méthode pour générer des niveaux de pratique.
     *
     * @return array Tableau de niveaux de pratique.
     */
    public function create_skills(): array {
        global $DB;

        // Vérifie que les données n'ont pas déjà été générées.
        $skills = $DB->get_records('apsolu_skills');

        if ($skills !== []) {
            return $skills;
        }

        // Génère les niveaux de pratique.
        $records = [];
        $records[] = 'débutant';
        $records[] = 'intermédiaire';
        $records[] = 'expert';

        foreach ($records as $skillname) {
            $skill = new skill();
            $skill->name = $skillname;
            $skill->shortname = $skillname;
            $skill->save();

            $skills[$skill->id] = $skill;
        }

        return $skills;
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

        $editor = file_prepare_standard_editor(
            $category,
            'description',
            $mform->get_description_editor_options(),
            $context,
            'coursecat',
            'description',
            $itemid
        );
        $mform->set_data($editor);

        return [$category, $mform];
    }

    /**
     * Function to create dummy data course.
     *
     * @param array $values
     *
     * @return stdClass course object
     */
    public function get_course_data(array $values = []) {
        $type = current($this->create_course_types());
        $category = current($this->create_categories());
        $skill = current($this->create_skills());

        $data = new stdClass();
        $data->id = 0;
        $data->customfield_type = $type->id;
        $data->customfield_category = ['categoryid' => $category->id, 'additionalstr' => ''];
        $data->idnumber = '';
        $data->customfield_timerange = ['start' => ['hour' => 13, 'minute' => 00], 'end' => ['hour' => 14, 'minute' => 30]];
        $data->customfield_skill = $skill->id;
        $data->customfield_weekday = 2;
        $data->customfield_federation = 1;

        foreach ($values as $key => $value) {
            $data->$key = $value;
        }

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
