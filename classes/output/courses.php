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

namespace local_apsolu\output;

use core_course\customfield\course_handler;
use local_apsolu\core\course;
use stdClass;

/**
 * Classe pour formater les données des cours à afficher sur la page des inscriptions ou l'administration.
 *
 * @package    local_apsolu
 * @copyright  2026 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class courses {
    /**
     * Affiche les champs personnalisés sur n'importe quel type de page.
     */
    const int VISIBLE_ANY = 0;

    /**
     * Affiche les champs personnalisés seulement sur les pages publiques.
     */
    const int VISIBLE_ONLY_PUBLIC = 1;

    /**
     * Affiche les champs personnalisés seulement sur les pages d'administration.
     */
    const int VISIBLE_ONLY_ADMINISTRATION = 2;

    /**
     * Récupère les entêtes à afficher pour le tableau des cours sur la page des inscriptions ou l'administration.
     *
     * @param int|string $coursetypeid Id du type de cours.
     * @param int $visibilityscope Visibilité des champs.
     *
     * @return array
     */
    public static function get_headers($coursetypeid, int $visibilityscope = self::VISIBLE_ANY): array {
        global $DB;

        $customfields = $DB->get_records(
            'apsolu_courses_fields',
            ['coursetypeid' => $coursetypeid],
            $sort = null,
            $fields = 'customfieldid, showinadministration, showonpublicpages'
        );

        $i = 0;
        $locationindex = null;
        $headers = [];

        // Récupère la liste des champs personnalisés pour ce type de format de cours.
        $category = false;
        $handler = course_handler::create();
        foreach ($handler->get_categories_with_fields() as $record) {
            if ($record->get('name') !== 'APSOLU') {
                continue;
            }

            $category = $record;
            break;
        }

        if ($category === false) {
            throw new moodle_exception('La catégorie de champs personnalisés de cours APSOLU n\'existe pas.');
        }

        foreach ($category->get_fields() as $customfield) {
            if (
                $visibilityscope === self::VISIBLE_ONLY_PUBLIC &&
                empty($customfields[$customfield->get('id')]->showonpublicpages) === true
            ) {
                // Ignore les champs non affichés dans l'administration.
                continue;
            }

            if (
                $visibilityscope === self::VISIBLE_ONLY_ADMINISTRATION &&
                empty($customfields[$customfield->get('id')]->showinadministration) === true
            ) {
                // Ignore les champs non affichés dans l'administration.
                continue;
            }

            $shortname = $customfield->get('shortname');

            if (in_array($shortname, ['category', 'event', 'type'], $strict = true) === true) {
                // Le champ "Activité" (category) est toujours affiché.
                // Les champs "Libellé complémentaire" et "Type" ne sont jamais affichés.
                continue;
            }

            if (in_array($customfield->get('type'), ['textarea'], $strict = true) === true) {
                // Les champs de type "zone de texte" ne sont jamais affichés.
                continue;
            }

            if ($shortname === 'location') {
                $locationindex = $i;
            }

            $i++;
            $headers[$shortname] = $customfield->get('name');
        }

        return $headers;
    }

    /**
     * Formate les données à afficher.
     *
     * @param array $courses Tableau de cours.
     * @param array $headers Tableau lisant les entêtes à utiliser.
     *
     * @return array
     */
    public static function get_data($courses, $headers): array {
        global $CFG, $DB;

        $records = [];

        $currentactivity = null;
        $currentaltclass = 'odd';

        $categories = $DB->get_records('course_categories');
        Course::get_contacts($courses);

        foreach ($courses as $course) {
            if ($currentactivity !== $course->category) {
                $currentactivity = $course->category;

                if ($currentaltclass === 'odd') {
                    $currentaltclass = 'even';
                } else {
                    $currentaltclass = 'odd';
                }
            }
            $altclass = $currentaltclass;

            $teachers = [];
            foreach ($course->managers as $manager) {
                $teachers[] = fullname($manager);
            }
            sort($teachers);

            $fields = [];
            foreach ($headers as $key => $string) {
                $value = '';
                if (isset($course->customfields[$key]) === true) {
                    $value = $course->customfields[$key]->export_value();
                }
                $fields[] = $value;
            }

            $parent = $categories[$course->category]->parent;

            $category = json_decode($course->customfields['category']->get_value(), $associative = true);

            $data = new stdClass();
            $data->id = $course->id;
            $data->idnumber = $course->idnumber;
            $data->fullname = $course->customfields['category']->export_value();
            $data->sport = $categories[$course->category]->name;
            $data->event = $category['additionalstr'];
            $data->grouping = $categories[$parent]->name;
            $data->fields = $fields;
            $data->alt_class = $altclass;
            $data->visible = $course->visible;
            $data->teachers = $teachers;

            $records[] = $data;
        }

        return $records;
    }
}
