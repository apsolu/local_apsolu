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
 * Classe pour le formulaire d'enregistrer les autorisations parentales.
 *
 * @package    local_apsolu
 * @copyright  2023 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_form\filetypes_util;

defined('MOODLE_INTERNAL') || die();

require_once $CFG->libdir.'/formslib.php';

/**
 * Classe pour le formulaire d'enregistrer les autorisations parentales.
 *
 * @package    local_apsolu
 * @copyright  2023 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_federation_parental_authorization extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    function definition () {
        global $USER;

        $mform = $this->_form;

        list($adhesion, $course, $context, $freeze) = $this->_customdata;

        // Texte de présentation pour l'autorisation parentale.
        $description = get_config('local_apsolu', 'parental_authorization_description');
        $html = '<div class="bg-light card mx-auto my-3 w-75"><div class="card-body">%s</div></div>';
        $mform->addElement('html', sprintf($html, $description));

        // Autorisation parentale.
        $label = get_string('parental_authorization', 'local_apsolu');
        if ($freeze === true) {
            $mform->freeze();

            $fs = get_file_storage();
            $context = context_course::instance($course->id, MUST_EXIST);
            list($component, $filearea, $itemid) = array('local_apsolu', 'parentalauthorization', $USER->id);
            $sort = 'itemid, filepath, filename';
            $files = $fs->get_area_files($context->id, $component, $filearea, $itemid, $sort, $includedirs = false);
            $items = array();
            foreach ($files as $file) {
                $url = moodle_url::make_pluginfile_url($context->id, $component, $filearea, $itemid, '/', $file->get_filename(), $forcedownload = false, $includetoken = false);
                $items[] = html_writer::link($url, $file->get_filename());
            }

            if (empty($items) === true) {
                $mform->addElement('static', 'parentalauthorization', $label, get_string('no_files', 'local_apsolu'));
            } else {
                $attributes = array('class' => 'list-unstyled');
                $mform->addElement('static', 'parentalauthorization', $label, html_writer::alist($items, $attributes));
            }
        } else {
            $attributes = null;
            $options = self::get_filemanager_options($course, $context);

            $mform->addElement('filemanager', 'parentalauthorization_filemanager', $label, $attributes, $options);
        }

        // Champs cachés.
        $mform->addElement('hidden', 'step', APSOLU_PAGE_PARENTAL_AUTHORIZATION);
        $mform->setType('step', PARAM_INT);

        // Submit buttons.
        $attributes = array('class' => 'btn btn-default');
        $buttonarray[] = &$mform->createElement('submit', 'save', get_string('save'), $attributes);

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

        // Set default values.
        $this->set_data($adhesion);
    }

    /**
     * Retourne les options passées aux éléments du formulaire de type filemanager.
     *
     * @param stdClass $course  Objet représentant un cours.
     * @param context  $context Objet représentant un contexte.
     *
     * @return array
     */
    public static function get_filemanager_options($course, $context) {
        global $CFG;

        $maxbytes = get_user_max_upload_file_size($context, $CFG->maxbytes, $course->maxbytes);

        $options = array();
        $options['areamaxbytes'] = $maxbytes;
        $options['context'] = $context;
        $options['maxbytes'] = $maxbytes;
        $options['maxfiles'] = get_config('local_apsolu', 'ffsu_maxfiles');;
        $options['subdirs'] = 0;

        $acceptedfiles = (string) get_config('local_apsolu', 'ffsu_acceptedfiles');
        $util = new filetypes_util();
        $options['accepted_types'] = $util->normalize_file_types($acceptedfiles);

        return $options;
    }
}
