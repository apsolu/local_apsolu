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

defined('MOODLE_INTERNAL') || die();

// MoodleQuickForm::registerElementType('time', __DIR__.'/classes/time_form_element.php', 'local_apsolu_time_form_element');

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
function local_apsolu_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {

    if ($context->contextlevel != CONTEXT_COURSE) {
        debugging('Wrong contextlevel: '.$context->contextlevel, DEBUG_DEVELOPER);
        return false;
    }

    if ($filearea !== 'information') {
        debugging('Wrong filearea: '.$filearea, DEBUG_DEVELOPER);
        return false;
    }

    $itemid = (int)array_shift($args);

    $fs = get_file_storage();

    $filename = array_pop($args);
    if (empty($args) === true) {
        $filepath = '/';
    } else {
        $filepath = '/'.implode('/', $args).'/';
    }

    $file = $fs->get_file($context->id, 'local_apsolu', $filearea, $itemid, $filepath, $filename);
    if ($file === false) {
        debugging(get_string('filenotfound', 'error'), DEBUG_DEVELOPER);
        return false;
    }

    // Finally send the file.
    send_stored_file($file, 0, 0, true, $options); // Download MUST be forced - security!
}
