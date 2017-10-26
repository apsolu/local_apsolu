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
 * Bulk user upload forms
 *
 * @package    tool
 * @subpackage uploaduser
 * @copyright  2007 Dan Poltawski
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once $CFG->libdir.'/formslib.php';
require_once($CFG->dirroot . '/user/editlib.php');

/**
 * Upload a file CVS file with user information.
 *
 * @copyright  2007 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_users_merge extends moodleform {
    function definition () {
        $mform = $this->_form;

        $mform->addElement('header', 'upload', get_string('upload'));

        $mform->addElement('filepicker', 'userfile', get_string('file'));
        $mform->addRule('userfile', null, 'required');

        $mform->addElement('header', 'settings', get_string('settings'));
        $mform->setExpanded('settings', $expanded = false);

        // Submit buttons.
        if (optional_param('previewbutton', null, PARAM_ALPHA) === null) {
            $attributes = array('class' => 'btn btn-primary');
            $buttonarray[] = &$mform->createElement('submit', 'previewbutton', get_string('users_preview', 'local_apsolu'), $attributes);

            $attributes = array('class' => 'btn btn-default', 'disabled' => 'disabled');
            $buttonarray[] = &$mform->createElement('submit', 'mergebutton', get_string('users_merge', 'local_apsolu'), $attributes);
        } else {
            $attributes = array('class' => 'btn btn-primary');
            $buttonarray[] = &$mform->createElement('submit', 'mergebutton', get_string('users_merge', 'local_apsolu'), $attributes);

            $attributes = array('class' => 'btn btn-default');
            $buttonarray[] = &$mform->createElement('submit', 'previewbutton', get_string('users_preview', 'local_apsolu'), $attributes);
        }

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }
}
