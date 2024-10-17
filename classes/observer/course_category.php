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

use stdClass;

/**
 * Classe permettant d'écouter les évènements diffusés par Moodle.
 *
 * @package   local_apsolu
 * @copyright 2021 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_category {
    /**
     * Écoute l'évènement \core\event\course_category_deleted.
     *
     * @param \core\event\course_category_deleted $event Évènement diffusé par Moodle.
     *
     * @return void
     */
    public static function deleted(\core\event\course_category_deleted $event) {
        global $DB;

        $context = $event->get_context();

        $category = $DB->get_record(\local_apsolu\core\category::TABLENAME, ['id' => $context->instanceid]);
        if ($category !== false) {
            // C'est une catégorie d'activité sportive APSOLU.
            $DB->delete_records(\local_apsolu\core\category::TABLENAME, ['id' => $category->id]);
            return;
        }

        $category = $DB->get_record(\local_apsolu\core\grouping::TABLENAME, ['id' => $context->instanceid]);
        if ($category !== false) {
            // C'est une catégorie de groupement d'activités sportives APSOLU.
            $DB->delete_records(\local_apsolu\core\grouping::TABLENAME, ['id' => $category->id]);
            return;
        }
    }

    /**
     * Écoute l'évènement \core\event\course_category_updated.
     *
     * @param \core\event\course_category_updated $event Évènement diffusé par Moodle.
     *
     * @return void
     */
    public static function updated(\core\event\course_category_updated $event) {
        global $DB;

        $context = $event->get_context();

        $sql = "SELECT cc.*".
            " FROM {course_categories} cc".
            " JOIN {apsolu_courses_categories} acc ON cc.id = acc.id".
            " WHERE cc.id = :categoryid";
        $category = $DB->get_record_sql($sql, ['categoryid' => $context->instanceid]);
        if ($category !== false) {
            // C'est une catégorie d'activité sportive APSOLU.
            // Le parent doit être une catégorie de groupement d'activités sportives APSOLU.
            $parent = $DB->get_record(\local_apsolu\core\grouping::TABLENAME, ['id' => $category->parent]);
            if ($parent === false) {
                $message = get_string('category_must_be_parent_of_a_grouping_of_sports_activities',
                    'local_apsolu', $category->name);
                \core\notification::add($message, \core\notification::WARNING);

                $sql = "SELECT cc.*".
                    " FROM {course_categories} cc".
                    " JOIN {apsolu_courses_groupings} acc ON cc.id = acc.id";
                $categories = $DB->get_records_sql($sql);
                $parent = current($categories);

                $params = new stdClass();
                $params->name = $category->name;
                $params->parentname = $parent->name;
                $message = get_string('category_has_been_moved_into', 'local_apsolu', $params);
                \core\notification::add($message, \core\notification::WARNING);

                $category->parent = $parent->id;
                $DB->update_record('course_categories', $category);
            }

            return;
        }

        $sql = "SELECT cc.*".
            " FROM {course_categories} cc".
            " JOIN {apsolu_courses_groupings} acg ON cc.id = acg.id".
            " WHERE cc.id = :categoryid";
        $category = $DB->get_record_sql($sql, ['categoryid' => $context->instanceid]);
        if ($category !== false) {
            // C'est une catégorie de groupement d'activités sportives APSOLU. Le parent ne peut pas être changé.
            if (empty($category->parent) === false) {
                $message = get_string('category_cannot_be_moved', 'local_apsolu', $category->name);
                \core\notification::add($message, \core\notification::ERROR);

                $category->parent = 0;
                $DB->update_record('course_categories', $category);
            }

            return;
        }
    }
}
