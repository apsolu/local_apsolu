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
 * Classe pour le formulaire permettant de configurer l'association du nom d'une activité FFSU à une activité APSOLU.
 *
 * @package    local_apsolu
 * @copyright  2022 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

/**
 * Classe pour le formulaire permettant de configurer l'association du nom d'une activité FFSU à une activité APSOLU.
 *
 * @package    local_apsolu
 * @copyright  2022 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_federation_activities_edit_form extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    protected function definition() {
        global $CFG;

        $mform = $this->_form;
        $activity = $this->_customdata['activity'];
        $categories = $this->_customdata['categories'];

        // Champ Nom officiel utilisé par la FFSU.
        $mform->addElement('static', 'name', get_string('name_used_by_federation', 'local_apsolu'));
        $mform->setType('name', PARAM_TEXT);

        // Champ Nom utilisé dans APSOLU.
        $options = array();
        $options['multiple'] = false;
        $mform->addElement('autocomplete', 'categoryid', get_string('name_used_by_apsolu', 'local_apsolu'), $categories, $options);
        $mform->setType('categoryid', PARAM_INT);

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('save', 'admin'));

        $attributes = new stdClass();
        $attributes->href = $CFG->wwwroot.'/local/apsolu/federation/index.php?page=activities';
        $attributes->class = 'btn btn-default btn-secondary';
        $buttonarray[] = &$mform->createElement('static', '', '', get_string('cancel_link', 'local_apsolu', $attributes));

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

        // Hidden fields.
        $mform->addElement('hidden', 'tab', 'categories');
        $mform->setType('tab', PARAM_ALPHA);

        $mform->addElement('hidden', 'action', 'edit');
        $mform->setType('action', PARAM_ALPHA);

        $mform->addElement('hidden', 'activityid', $activity->id);
        $mform->setType('activityid', PARAM_INT);

        // Set default values.
        $this->set_data($activity);
    }

    /**
     * Validation.
     *
     * @param array $data
     * @param array $files
     * @return array the errors that were found
     */
    public function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);

        // Vérifie que l'activité APSOLU n'est pas déjà associée à une autre activité FFSU.
        if (empty($data['categoryid']) === false) {
            $sql = "SELECT afa.id, afa.name".
                " FROM {apsolu_federation_activities} afa".
                " WHERE afa.categoryid = :categoryid".
                " AND afa.id != :id";
            $record = $DB->get_record_sql($sql, array('categoryid' => $data['categoryid'], 'id' => $data['activityid']));
            if ($record !== false) {
                $errors['categoryid'] = get_string('this_activity_is_already_associated_with_activity_X', 'local_apsolu', $record->name);
            }
        }

        return $errors;
    }
}
