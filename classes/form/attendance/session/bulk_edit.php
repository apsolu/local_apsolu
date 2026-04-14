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

namespace local_apsolu\form\attendance\session;

use moodleform;
use moodle_url;
use stdClass;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

/**
 * Classe pour le formulaire permettant d'ajouter une série de sessions.
 *
 * @package    local_apsolu
 * @copyright  2026 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bulk_edit extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    protected function definition() {
        global $CFG;

        $mform = $this->_form;
        $default = $this->_customdata['default'];
        $weekdays = $this->_customdata['weekdays'];
        $locations = $this->_customdata['locations'];

        // Champ "À partir de".
        $mform->addElement('date_selector', 'startdate', get_string('from', 'local_apsolu'));

        // Champ "Jusqu'au".
        $mform->addElement('date_selector', 'enddate', get_string('until', 'local_apsolu'), ['optional' => true]);

        // Champ "Jours".
        $checkboxes = [];
        foreach ($weekdays as $numday => $day) {
            $checkboxes[] = $mform->createElement('advcheckbox', sprintf('weekdays[%s]', $day), '', get_string($day, 'calendar'));
            if ($numday < 5) {
                $mform->setDefault($day, true);
            }
        }
        $mform->addGroup($checkboxes, 'weekdaygroup', get_string('weekdays', 'local_apsolu'), null, false);

        // Champ "Exclure les jours fériés".
        $mform->addElement('checkbox', 'excludeholidays', get_string('exclude_holidays', 'local_apsolu'));

        // Champ "Heure de début".
        $hours = [];
        foreach (range(0, 23) as $i) {
            $hours[$i] = sprintf("%02d", $i);
        }

        $minutes = [];
        foreach (range(0, 59, 5) as $i) {
            $minutes[$i] = sprintf("%02d", $i);
        }

        $timegroup = [];
        $timegroup[] = $mform->createElement('select', 'starthour', get_string('hour', 'form'), $hours);
        $timegroup[] = $mform->createElement('select', 'startminute', get_string('minute', 'form'), $minutes);
        $mform->addGroup($timegroup, 'starttime', get_string('starttime', 'local_apsolu'), null, false);

        // Champ "Durée de la session".
        $durationoptions = ['units' => [MINSECS, HOURSECS]];
        $mform->addElement('duration', 'duration', get_string('duration', 'local_apsolu'), $durationoptions);
        $mform->setType('duration', PARAM_INT);

        // Champ "Nombre de sessions par jour".
        $mform->addElement('text', 'count', 'Nombre de sessions par jour');
        $mform->setType('count', PARAM_INT);

        // Champ "Pause entre les sessions" (si le nombre de créneau est supérieur à 1).
        $mform->addElement('duration', 'breakduration', 'Durée de pause entre 2 sessions', $durationoptions);
        $mform->setType('breakduration', PARAM_INT);
        $mform->disabledIf('breakduration', 'count', 'in', [0, 1]);

        // Champ "Lieu".
        $mform->addElement('select', 'locationid', get_string('location', 'local_apsolu'), $locations);
        $mform->setType('locationid', PARAM_INT);
        $mform->addRule('locationid', get_string('required'), 'required', null, 'client');

        // Notifier le contact fonctionnel.
        $functionalcontact = get_config('local_apsolu', 'functional_contact');
        if (empty($functionalcontact) === false) {
            $label = get_string('notify_functional_contact', 'local_apsolu', $functionalcontact);
            $checkbox = $mform->addElement('checkbox', 'notify_functional_contact', $label);
            $mform->setType('notify_functional_contact', PARAM_INT);

            // Force la notification auprès de l'adresse de contact fonctionnel.
            $mform->setDefault('notify_functional_contact', 1);
            $checkbox->freeze();
        }

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'previewbutton', get_string('preview', 'local_apsolu'));
        if ($default->submitted === null) {
            $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('save', 'admin'), ['disabled' => 1]);
        } else {
            $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('save', 'admin'));
        }

        $attributes = new stdClass();
        $attributes->href = new moodle_url(
            '/local/apsolu/attendance/index.php',
            ['page' => 'sessions', 'courseid' => $default->courseid]
        );
        $attributes->class = 'btn btn-default btn-secondary';
        $buttonarray[] = &$mform->createElement('static', '', '', get_string('cancel_link', 'local_apsolu', $attributes));

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);

        // Set default values.
        $this->set_data($default);
    }

    /**
     * Validation.
     *
     * @param array $data
     * @param array $files
     *
     * @return array the errors that were found
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Contrôle que la date "à partir de" est antérieure à la date "jusqu'au".
        if ($data['enddate'] !== 0 && $data['startdate'] >= $data['enddate']) {
            $errors['enddate'] = get_string(
                'the_date_in_the_field_X_must_be_later_than_the_date_in_the_Y_field',
                'local_apsolu',
                ['field1' => get_string('until', 'local_apsolu'), 'field2' => get_string('from', 'local_apsolu')]
            );
        }

        // Contrôle qu'au moins un jour ait été sélectionné.
        $errors['weekdaygroup'] = get_string('you_must_select_at_least_one_value', 'local_apsolu');
        foreach ($data['weekdays'] as $day => $value) {
            if (empty($value) === true) {
                continue;
            }

            unset($errors['weekdaygroup']);
            break;
        }

        // Contrôle que la durée est supérieure ou égale à 1.
        if (empty($data['duration']) === true) {
            $errors['duration'] = get_string('the_value_must_be_greater_than_or_equal_to_X', 'local_apsolu', 1);
        }

        return $errors;
    }
}
