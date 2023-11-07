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
 * Classe pour le formulaire permettant d'importer les licences FFSU.
 *
 * @package    local_apsolu
 * @copyright  2017 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/user/editlib.php');

/**
 * Classe pour le formulaire permettant d'importer les licences FFSU.
 *
 * @package    local_apsolu
 * @copyright  2017 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_federation_import_licences extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    function definition () {
        $preview = optional_param('previewbutton', null, PARAM_ALPHA);
        $federationnumbercolumn = optional_param('federationnumbercolumn', null, PARAM_INT);
        $emailcolumn = optional_param('emailcolumn', null, PARAM_INT);

        $mform = $this->_form;
        list($columns, $previewtable) = $this->_customdata;

        $mform->addElement('header', 'upload', get_string('upload'));

        $mform->addElement('filepicker', 'userfile', get_string('file'));
        $mform->addRule('userfile', null, 'required');

        $mform->addElement('header', 'settings', get_string('settings'));
        $mform->setExpanded('settings', $expanded = ($preview === null));

        $choices = csv_import_reader::get_delimiter_list();
        $mform->addElement('select', 'delimiter_name', get_string('csvdelimiter', 'tool_uploaduser'), $choices);
        if (array_key_exists('cfg', $choices)) {
            $mform->setDefault('delimiter_name', 'cfg');
        } else if (get_string('listsep', 'langconfig') == ';') {
            $mform->setDefault('delimiter_name', 'semicolon');
        } else {
            $mform->setDefault('delimiter_name', 'comma');
        }

        $choices = core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('encoding', 'tool_uploaduser'), $choices);
        $mform->setDefault('encoding', 'UTF-8');

        $choices = ['5' => '5', '10' => 10, '20' => 20, '100' => 100, '1000' => 1000, '100000' => 100000];
        $mform->addElement('select', 'previewrows', get_string('rowpreviewnum', 'tool_uploaduser'), $choices);
        $mform->setType('previewrows', PARAM_INT);

        if ($preview === null) {
            // Hack pour gérer la gestion l'association des colonnes.
            $mform->addElement('hidden', 'federationnumbercolumn', $federationnumbercolumn);
            $mform->setType('federationnumbercolumn', PARAM_INT);

            $mform->addElement('hidden', 'emailcolumn', $emailcolumn);
            $mform->setType('emailcolumn', PARAM_INT);

            // Seul le bouton aperçu est disponible.
            $buttonarray[] = &$mform->createElement('submit', 'previewbutton', get_string('federation_preview', 'local_apsolu'));

            $attributes = ['class' => 'btn btn-default', 'disabled' => 'disabled'];
            $buttonarray[] = &$mform->createElement('submit', 'importbutton',
                get_string('federation_import', 'local_apsolu'), $attributes);
        } else {
            // Aperçu du fichier csv.
            $mform->addElement('header', 'preview', get_string('federation_preview', 'local_apsolu'));
            $mform->setExpanded('preview', $expanded = true);
            $mform->addElement('html', $previewtable);

            // Association des colonnes.
            $mform->addElement('header', 'mapping', get_string('mapping_of_columns', 'local_apsolu'));
            $mform->setExpanded('mapping', $expanded = true);

            $mform->addElement('select', 'federationnumbercolumn', get_string('federation_number', 'local_apsolu'), $columns);
            $mform->setType('federationnumbercolumn', PARAM_INT);

            $mform->addElement('select', 'emailcolumn', get_string('email', 'local_apsolu'), $columns);
            $mform->setType('emailcolumn', PARAM_INT);

            // Les boutons aperçu et importer sont disponibles.
            $attributes = ['class' => 'btn btn-default'];
            $buttonarray[] = &$mform->createElement('submit', 'previewbutton',
                get_string('federation_preview', 'local_apsolu'), $attributes);

            $buttonarray[] = &$mform->createElement('submit', 'importbutton', get_string('federation_import', 'local_apsolu'));
        }

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
        $mform->closeHeaderBefore('buttonar');

        // Hidden fields.
        $mform->addElement('hidden', 'page', 'import');
        $mform->setType('page', PARAM_ALPHA);
    }
}
