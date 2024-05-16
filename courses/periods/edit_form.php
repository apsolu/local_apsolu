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

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

/**
 * Classe pour le formulaire permettant de configurer les périodes.
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_courses_periods_edit_form extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    protected function definition() {
        global $CFG;

        $mform = $this->_form;
        $instance = $this->_customdata['period'];

        // Year field.
        $year = optional_param('year', null, PARAM_INT);
        if ($year === null) {
            if (empty($instance->id) === false) {
                // On récupère l'année universitaire à partir des semaines déjà saisies.
                list($y, $month, $day) = explode('-', $instance->weeks[0]);
                if ($month > 6) {
                    $year = $y;
                } else {
                    $year = $y - 1;
                }
            } else {
                // On calcule l'année universitaire en cours (à partir de juin, on propose la nouvelle année universitaire).
                if (date('n') >= 6) {
                    $year = date('Y');
                } else {
                    $year = date('Y') - 1;
                }
            }
        }

        // Construit l'url de l'année N-1.
        $lastyearurlparams = ['action' => 'edit', 'periodid' => $instance->id, 'tab' => 'periods', 'year' => $year - 1];
        $lastyearurl = new moodle_url('/local/apsolu/courses/index.php', $lastyearurlparams);

        // Construit l'url de l'année N+1.
        $nextyearurlparams = ['action' => 'edit', 'periodid' => $instance->id, 'tab' => 'periods', 'year' => $year + 1];
        $nextyearurl = new moodle_url('/local/apsolu/courses/index.php', $nextyearurlparams);

        // Contruit l'icône font-awesome pour l'année N-1.
        $str = get_string('previous');
        $previcon = '<i class="fa fa-chevron-circle-left" title="'.s($str).'" aria-hidden="true"></i>
            <span class="sr-only">'.s($str).'</span>';

        // Contruit l'icône font-awesome pour l'année N+1.
        $str = get_string('next');
        $nexticon = '<i class="fa fa-chevron-circle-right" title="'.s($str).'" aria-hidden="true"></i>
            <span class="sr-only">'.s($str).'</span>';

        // Construit le menu permettant de changer d'année universitaire.
        $label = sprintf('<a class="mr-3" href="%s">%s</a> %s-%s <a class="ml-3" href="%s">%s</a>', $lastyearurl, $previcon, $year,
            $year + 1, $nextyearurl, $nexticon);
        $mform->addElement('static', 'yearselector', get_string('year', 'form'), $label);

        // Name field.
        $mform->addElement('text', 'name', get_string('name'), ['style' => 'width: 40em;']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');

        // Generic name field.
        $mform->addElement('text', 'generic_name', get_string('generic_name', 'local_apsolu'), ['style' => 'width: 40em;']);
        $mform->setType('generic_name', PARAM_TEXT);
        $mform->addRule('generic_name', get_string('required'), 'required', null, 'client');

        // Weeks field.
        $start = new DateTime($year.'-08-15T00:00:00');
        $start->sub(new DateInterval('P'.($start->format('N') - 1).'D'));
        $end = new DateTime(($year + 1).'-08-15T00:00:00');
        $end->add(new DateInterval('P'.($end->format('N') - 1).'D'));

        $weeks = [];
        while ($start < $end) {
            $range = 'du lun. '.$start->format('d').' au sam. '.userdate($start->getTimestamp() + 5 * 24 * 60 * 60, '%d %b %Y');
            $weeks[$start->format('Y-m-d')] = 'Sem. '.$start->format('W').' ('.$range.')';
            $start = $start->add(new DateInterval('P7D'));
        }
        $select = $mform->addElement('select', 'weeks', get_string('week'), $weeks, ['size' => 50, 'style' => 'width: 40em;']);
        $mform->setType('weeks', PARAM_TEXT);
        $mform->addRule('weeks', get_string('required'), 'required', null, 'client');
        $select->setMultiple(true);

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('save', 'admin'));

        $attributes = new stdClass();
        $attributes->href = $CFG->wwwroot.'/local/apsolu/courses/index.php?tab=periods';
        $attributes->class = 'btn btn-default btn-secondary';
        $buttonarray[] = &$mform->createElement('static', '', '', get_string('cancel_link', 'local_apsolu', $attributes));

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);

        // Hidden fields.
        $mform->addElement('hidden', 'tab', 'periods');
        $mform->setType('tab', PARAM_ALPHA);

        $mform->addElement('hidden', 'action', 'edit');
        $mform->setType('action', PARAM_ALPHA);

        $mform->addElement('hidden', 'year', $year);
        $mform->setType('year', PARAM_INT);

        $mform->addElement('hidden', 'periodid', $instance->id);
        $mform->setType('periodid', PARAM_INT);

        // Set default values.
        $this->set_data($instance);
    }

    /**
     * Validation.
     *
     * @param array $data
     * @param array $files
     *
     * @return array The errors that were found.
     */
    public function validation($data, $files) {
        global $DB;

        $errors = [];
        $errors = parent::validation($data, $files);

        // Is unique ?
        $period = $DB->get_record('apsolu_periods', ['name' => $data['name']]);
        if ($period && $period->id != $data['periodid']) {
            $errors['name'] = get_string('shortnametaken', '', $data['name']);
        }

        return $errors;
    }
}
