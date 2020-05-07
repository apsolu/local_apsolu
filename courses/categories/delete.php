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
 * Gère la page de suppression d'un groupement d'activités.
 *
 * @package    local_apsolu
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

use local_apsolu\core\category as Category;

$categoryid = required_param('categoryid', PARAM_INT);
$delete = optional_param('delete', '', PARAM_ALPHANUM); // Confirmation hash.

$category = new Category();
$category->load($categoryid, $required = true);

$deletehash = md5($category->id);
$returnurl = new moodle_url('/local/apsolu/courses/index.php', array('tab' => 'categories'));

if ($delete === $deletehash) {
    // Effectue les actions de suppression.
    require_sesskey();

    $category->delete();

    $message = get_string('category_has_been_deleted', 'local_apsolu');
    redirect($returnurl, $message, $delay = null, \core\output\notification::NOTIFY_SUCCESS);
}

// Vérifie si cette activité n'est pas associée à des cours.
$courses = $DB->get_records('course', $conditions = array('category' => $category->id), $sort = 'fullname');
if (count($courses) !== 0) {
    $datatemplate = array();
    $datatemplate['message'] = get_string('category_cannot_be_deleted', 'local_apsolu', $category->name);
    $datatemplate['dependences'] = array();
    foreach ($courses as $course) {
        $datatemplate['dependences'][] = $course->fullname;
    }
    $message = $OUTPUT->render_from_template('local_apsolu/courses_form_undeletable_message', $datatemplate);

    redirect($returnurl, $message, $delay = null, \core\output\notification::NOTIFY_WARNING);
}

// Affiche un message de confirmation.
$datatemplate = array();
$datatemplate['message'] = get_string('do_you_want_to_delete_category', 'local_apsolu', $category->name);
$message = $OUTPUT->render_from_template('local_apsolu/courses_form_delete_message', $datatemplate);

// Bouton de validation.
$urlarguments = array('tab' => 'categories', 'action' => 'delete', 'categoryid' => $category->id, 'delete' => $deletehash);
$confirmurl = new moodle_url('/local/apsolu/courses/index.php', $urlarguments);
$confirmbutton = new single_button($confirmurl, get_string('delete'), 'post');

// Bouton d'annulation.
$cancelurl = new moodle_url('/local/apsolu/courses/index.php', array('tab' => 'categories'));

echo $OUTPUT->confirm($message, $confirmbutton, $cancelurl);
