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
 * Classe pour le formulaire permettant de configurer les rôles.
 *
 * @package    local_apsolu
 * @copyright  2023 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

/**
 * Classe pour le formulaire permettant de configurer les rôles.
 *
 * @package    local_apsolu
 * @copyright  2023 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_roles_edit_form extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    protected function definition() {
        global $CFG;

        $mform = $this->_form;

        list($defaults, $role) = $this->_customdata;

        // Role field.
        $mform->addElement('static', 'description', get_string('role'), $role);

        // Color field.
        $attributes = array('data-apsolu' => 'colorpicker');
        $mform->addElement('text', 'color', get_string('color', 'local_apsolu'), $attributes);
        $mform->setType('color', PARAM_TEXT);

        // Font Awesome field.
        $icons = array();
        $icons['adjust'] = '&#xf042; adjust';
        $icons['asterisk'] = '&#xf069; asterisk';
        $icons['bookmark-o'] = '&#xf097; bookmark-o';
        $icons['bookmark'] = '&#xf02e; bookmark';
        $icons['bullseye'] = '&#xf140; bullseye';
        $icons['certificate'] = '&#xf03a; certificate';
        $icons['check-circle-o'] = '&#xf05d; check-circle-o';
        $icons['check-circle'] = '&#xf058; check-circle';
        $icons['check-square-o'] = '&#xf046; check-square-o';
        $icons['check-square'] = '&#xf14a; check-square';
        $icons['check'] = '&#xf00c; check';
        $icons['circle-o'] = '&#xf10c; circle-o';
        $icons['circle-thin'] = '&#xf1db; circle-thin';
        $icons['circle'] = '&#xf111; circle';
        $icons['dot-circle-o'] = '&#xf192; dot-circle-o';
        $icons['minus-square-o'] = '&#xf147; minus-square-o';
        $icons['minus-square'] = '&#xf146; minus-square';
        $icons['plus-square-o'] = '&#xf196; plus-square-o';
        $icons['plus-square'] = '&#xf0fe; plus-square';
        $icons['shield'] = '&#xf132; shield';
        $icons['square-o'] = '&#xf096; square-o';
        $icons['square'] = '&#xf0c8; square';
        $icons['star-half-o'] = '&#xf123; star-half-o';
        $icons['star-half'] = '&#xf089; star-half';
        $icons['star-o'] = '&#xf006; star-o';
        $icons['star'] = '&#xf005; star';
        $icons['tag'] = '&#xf2c0; tag';
        $icons['user-circle-o'] = '&#xf2be; user-circle-o';
        $icons['user-circle'] = '&#xf2bd; user-circle';
        $icons['user-o'] = '&#xf2c0; user-o';
        $icons['users'] = '&#xf0c0; users';
        $icons['user'] = '&#xf007; user';

        $attributes = array('data-apsolu' => 'fontawesome', 'size' => count($icons));
        $mform->addElement('select', 'fontawesomeid', get_string('icon'), $icons, $attributes);
        $mform->setType('fontawesomeid', PARAM_TEXT);

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'));

        $attributes = new stdClass();
        $attributes->href = $CFG->wwwroot.'/local/apsolu/configuration/index.php?page=roles';
        $attributes->class = 'btn btn-default btn-secondary';
        $buttonarray[] = &$mform->createElement('static', '', '', get_string('cancel_link', 'local_apsolu', $attributes));

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

        // Hidden fields.
        $mform->addElement('hidden', 'action', 'edit');
        $mform->setType('action', PARAM_ALPHANUM);

        $mform->addElement('hidden', 'page', 'roles');
        $mform->setType('page', PARAM_ALPHANUM);

        $mform->addElement('hidden', 'roleid', $defaults->id);
        $mform->setType('roleid', PARAM_INT);

        // Set default values.
        $this->set_data($defaults);
    }
}
