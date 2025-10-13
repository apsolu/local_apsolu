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
 * Enregistre un nouveau type d'élément de formulaire.
 *
 * @package    local_apsolu
 * @copyright  2021 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:ignore MoodleQuickForm::registerElementType('time', __DIR__.'/classes/time_form_element.php', 'local_apsolu_time_form_element');

/**
 * Fonction spéciale gérée par Moodle, permettant d'étendre un menu dans un cours.
 *
 * @see https://docs.moodle.org/dev/Local_plugins#Adding_an_element_to_the_settings_menu
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course to object for the tool
 * @param context $context The context of the course
 *
 * @return void|null return null if we don't want to display the node.
 */
function local_apsolu_extend_navigation_course($navigation, $course, $context) {
    global $PAGE;

    // Only add this settings item on non-site course pages.
    if (!$PAGE->course || $PAGE->course->id == SITEID) {
        return null;
    }

    $url = new moodle_url('/course/view.php');
    if ($PAGE->url->compare($url, URL_MATCH_BASE) === true) {
        // Surcharge la page d'accueil d'un cours.
        return local_apsolu_override_course_page($course);
    }
}

/**
 * Fonction spéciale permettant de surcharger la page d'accueil d'un cours avec du javascript.
 *
 * @param stdClass $course The course to object for the tool
 *
 * @return void
 */
function local_apsolu_override_course_page($course) {
    global $PAGE;

    if (has_capability('moodle/course:update', context_course::instance($course->id, MUST_EXIST)) === true) {
        // Affiche les boutons de prise de présences et de gestion des étudiants en haut de la page.
        $PAGE->requires->js_call_amd('local_apsolu/attendance', 'setupcourse');
    }
}

/**
 * Gère les contrôles d'accès pour la diffusion des fichiers du module local_apsolu.
 *
 * @param stdClass $course        Course object.
 * @param stdClass $cm            Course module object.
 * @param stdClass $context       Context object.
 * @param string   $filearea      File area.
 * @param array    $args          Extra arguments.
 * @param bool     $forcedownload Whether or not force download.
 * @param array    $options       Additional options affecting the file serving.
 *
 * @return void|bool Retourne False en cas d'erreur.
 */
function local_apsolu_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $USER;

    if ($context->contextlevel != CONTEXT_COURSE) {
        debugging('Wrong contextlevel: ' . $context->contextlevel, DEBUG_DEVELOPER);
        return false;
    }

    if (in_array($filearea, ['information', 'medicalcertificate', 'parentalauthorization'], $strict = true) === false) {
        debugging('Wrong filearea: ' . $filearea, DEBUG_DEVELOPER);
        return false;
    }

    $itemid = (int)array_shift($args);

    $fs = get_file_storage();

    $filename = array_pop($args);
    if (empty($args) === true) {
        $filepath = '/';
    } else {
        $filepath = '/' . implode('/', $args) . '/';
    }

    $file = $fs->get_file($context->id, 'local_apsolu', $filearea, $itemid, $filepath, $filename);
    if ($file === false) {
        debugging(get_string('filenotfound', 'error'), DEBUG_DEVELOPER);
        return false;
    }

    switch ($filearea) {
        case 'information':
            // Fichier public, visible sans droit particulier.
            break;
        case 'medicalcertificate':
        case 'parentalauthorization':
            // Fichier visible uniquement par le propriétaire du fichier ou un gestionnaire.
            if (
                $file->get_userid() !== $USER->id &&
                has_capability('local/apsolu:viewallmedicalcertificates', context_system::instance()) === false
            ) {
                return false;
            }
    }

    // Finally send the file.
    send_stored_file($file, 0, 0, true, $options); // Download MUST be forced - security!
}

/**
 * Return a list of all the user preferences used by local_apsolu.
 *
 * @return array
 */
function local_apsolu_user_preferences() {
    $preferences = [];
    $preferences['apsolu_maskable_config'] = [
        'type' => PARAM_RAW,
        'null' => NULL_NOT_ALLOWED,
        'default' => '{}',
    ];

    return $preferences;
}
