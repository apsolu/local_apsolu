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
 * Classe pour le formulaire permettant l'édition des préférences d'affichage de l'offre de formations.
 *
 * @package    local_apsolu
 * @copyright  2020 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_course_offerings_form extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    protected function definition() {
        $mform = $this->_form;

        // 1. Configuration des colonnes.
        $mform->addElement('header', 'show_columns', get_string('show_columns', 'local_apsolu'));
        $mform->setExpanded('show_columns', $expanded = true);

        // Configuration de la colonne : site de pratique.
        $mform->addElement('selectyesno', 'show_city_column', get_string('show_city_column', 'local_apsolu'));
        $mform->addHelpButton('show_city_column', 'show_city_column', 'local_apsolu');
        $mform->setType('show_city_column', PARAM_INT);

        // Configuration de la colonne : groupement d'activités.
        $mform->addElement('selectyesno', 'show_grouping_column', get_string('show_grouping_column', 'local_apsolu'));
        $mform->addHelpButton('show_grouping_column', 'show_grouping_column', 'local_apsolu');
        $mform->setType('show_grouping_column', PARAM_INT);

        // Configuration de la colonne : activité.
        $mform->addElement('selectyesno', 'show_category_column', get_string('show_category_column', 'local_apsolu'));
        $mform->addHelpButton('show_category_column', 'show_category_column', 'local_apsolu');
        $mform->setType('show_category_column', PARAM_INT);

        // Configuration de la colonne : zone géographique.
        $mform->addElement('selectyesno', 'show_area_column', get_string('show_area_column', 'local_apsolu'));
        $mform->addHelpButton('show_area_column', 'show_area_column', 'local_apsolu');
        $mform->setType('show_area_column', PARAM_INT);

        // Configuration de la colonne : période.
        $mform->addElement('selectyesno', 'show_period_column', get_string('show_period_column', 'local_apsolu'));
        $mform->addHelpButton('show_period_column', 'show_period_column', 'local_apsolu');
        $mform->setType('show_period_column', PARAM_INT);

        // Configuration de la colonne : horaires.
        $mform->addElement('selectyesno', 'show_times_column', get_string('show_times_column', 'local_apsolu'));
        $mform->addHelpButton('show_times_column', 'show_times_column', 'local_apsolu');
        $mform->setType('show_times_column', PARAM_INT);

        // Configuration de la colonne : jour de la semaine.
        $mform->addElement('selectyesno', 'show_weekday_column', get_string('show_weekday_column', 'local_apsolu'));
        $mform->addHelpButton('show_weekday_column', 'show_weekday_column', 'local_apsolu');
        $mform->setType('show_weekday_column', PARAM_INT);

        // Configuration de la colonne : lieu de pratique.
        $mform->addElement('selectyesno', 'show_location_column', get_string('show_location_column', 'local_apsolu'));
        $mform->addHelpButton('show_location_column', 'show_location_column', 'local_apsolu');
        $mform->setType('show_location_column', PARAM_INT);

        // Configuration de la colonne : niveau de pratique.
        $mform->addElement('selectyesno', 'show_skill_column', get_string('show_skill_column', 'local_apsolu'));
        $mform->addHelpButton('show_skill_column', 'show_skill_column', 'local_apsolu');
        $mform->setType('show_skill_column', PARAM_INT);

        // Configuration de la colonne : type d'inscription.
        $mform->addElement('selectyesno', 'show_role_column', get_string('show_role_column', 'local_apsolu'));
        $mform->addHelpButton('show_role_column', 'show_role_column', 'local_apsolu');
        $mform->setType('show_role_column', PARAM_INT);

        // Configuration de la colonne : enseignants.
        $mform->addElement('selectyesno', 'show_teachers_column', get_string('show_teachers_column', 'local_apsolu'));
        $mform->addHelpButton('show_teachers_column', 'show_teachers_column', 'local_apsolu');
        $mform->setType('show_teachers_column', PARAM_INT);

        // 2. Configuration des filtres.
        $mform->addElement('header', 'show_filters', get_string('show_filters', 'local_apsolu'));
        $mform->setExpanded('show_filters', $expanded = true);

        // Configuration du filtre : site de pratique.
        $mform->addElement('selectyesno', 'show_city_filter', get_string('show_city_filter', 'local_apsolu'));
        $mform->addHelpButton('show_city_filter', 'show_city_filter', 'local_apsolu');
        $mform->setType('show_city_filter', PARAM_INT);

        // Configuration du filtre : groupement d'activités.
        $mform->addElement('selectyesno', 'show_grouping_filter', get_string('show_grouping_filter', 'local_apsolu'));
        $mform->addHelpButton('show_grouping_filter', 'show_grouping_filter', 'local_apsolu');
        $mform->setType('show_grouping_filter', PARAM_INT);

        // Configuration du filtre : activité.
        $mform->addElement('selectyesno', 'show_category_filter', get_string('show_category_filter', 'local_apsolu'));
        $mform->addHelpButton('show_category_filter', 'show_category_filter', 'local_apsolu');
        $mform->setType('show_category_filter', PARAM_INT);

        // Configuration du filtre : zone géographique.
        $mform->addElement('selectyesno', 'show_area_filter', get_string('show_area_filter', 'local_apsolu'));
        $mform->addHelpButton('show_area_filter', 'show_area_filter', 'local_apsolu');
        $mform->setType('show_area_filter', PARAM_INT);

        // Configuration du filtre : période.
        $mform->addElement('selectyesno', 'show_period_filter', get_string('show_period_filter', 'local_apsolu'));
        $mform->addHelpButton('show_period_filter', 'show_period_filter', 'local_apsolu');
        $mform->setType('show_period_filter', PARAM_INT);

        // Configuration du filtre : horaires.
        $mform->addElement('selectyesno', 'show_times_filter', get_string('show_times_filter', 'local_apsolu'));
        $mform->addHelpButton('show_times_filter', 'show_times_filter', 'local_apsolu');
        $mform->setType('show_times_filter', PARAM_INT);

        // Configuration du filtre : jour de la semaine.
        $mform->addElement('selectyesno', 'show_weekday_filter', get_string('show_weekday_filter', 'local_apsolu'));
        $mform->addHelpButton('show_weekday_filter', 'show_weekday_filter', 'local_apsolu');
        $mform->setType('show_weekday_filter', PARAM_INT);

        // Configuration du filtre : lieu de pratique.
        $mform->addElement('selectyesno', 'show_location_filter', get_string('show_location_filter', 'local_apsolu'));
        $mform->addHelpButton('show_location_filter', 'show_location_filter', 'local_apsolu');
        $mform->setType('show_location_filter', PARAM_INT);

        // Configuration du filtre : niveau de pratique.
        $mform->addElement('selectyesno', 'show_skill_filter', get_string('show_skill_filter', 'local_apsolu'));
        $mform->addHelpButton('show_skill_filter', 'show_skill_filter', 'local_apsolu');
        $mform->setType('show_skill_filter', PARAM_INT);

        // Configuration du filtre : type d'inscription.
        $mform->addElement('selectyesno', 'show_role_filter', get_string('show_role_filter', 'local_apsolu'));
        $mform->addHelpButton('show_role_filter', 'show_role_filter', 'local_apsolu');
        $mform->setType('show_role_filter', PARAM_INT);

        // Configuration du filtre : enseignants.
        $mform->addElement('selectyesno', 'show_teachers_filter', get_string('show_teachers_filter', 'local_apsolu'));
        $mform->addHelpButton('show_teachers_filter', 'show_teachers_filter', 'local_apsolu');
        $mform->setType('show_teachers_filter', PARAM_INT);

        // 3. Plages horaires.
        $mform->addElement('header', 'time_ranges', get_string('time_ranges', 'local_apsolu'));
        $mform->setExpanded('time_ranges', $expanded = true);

        // Configuration de plage horaire 1 (matin).
        $mform->addElement('text', 'range1_end', get_string('end_time_for_range_1', 'local_apsolu'));
        $mform->addHelpButton('range1_end', 'end_time_for_range_1', 'local_apsolu');
        $mform->setType('range1_end', PARAM_TEXT);

        // Configuration de plage horaire 2 (midi).
        $mform->addElement('text', 'range2_start', get_string('start_time_for_range_2', 'local_apsolu'));
        $mform->addHelpButton('range2_start', 'start_time_for_range_2', 'local_apsolu');
        $mform->setType('range2_start', PARAM_TEXT);

        $mform->addElement('text', 'range2_end', get_string('end_time_for_range_2', 'local_apsolu'));
        $mform->addHelpButton('range2_end', 'end_time_for_range_2', 'local_apsolu');
        $mform->setType('range2_end', PARAM_TEXT);

        // Configuration de plage horaire 3 (après-midi).
        $mform->addElement('text', 'range3_start', get_string('start_time_for_range_3', 'local_apsolu'));
        $mform->addHelpButton('range3_start', 'start_time_for_range_3', 'local_apsolu');
        $mform->setType('range3_start', PARAM_TEXT);

        $mform->addElement('text', 'range3_end', get_string('end_time_for_range_3', 'local_apsolu'));
        $mform->addHelpButton('range3_end', 'end_time_for_range_3', 'local_apsolu');
        $mform->setType('range3_end', PARAM_TEXT);

        // Configuration de plage horaire 4 (soir).
        $mform->addElement('text', 'range4_start', get_string('start_time_for_range_4', 'local_apsolu'));
        $mform->addHelpButton('range4_start', 'start_time_for_range_4', 'local_apsolu');
        $mform->setType('range4_start', PARAM_TEXT);

        // 4. Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'));

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);

        // Hidden fields.
        $mform->addElement('hidden', 'page', 'courseofferings');
        $mform->setType('page', PARAM_ALPHANUM);
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

        $errors = parent::validation($data, $files);

        $ranges = [];
        $ranges['range1'] = ['end'];
        $ranges['range2'] = ['start', 'end'];
        $ranges['range3'] = ['start', 'end'];
        $ranges['range4'] = ['start'];

        foreach ($ranges as $name => $types) {
            // Vérifie que les données sont bien au format HH:MM.
            $badformat = false;
            foreach ($types as $type) {
                $fieldname = $name . '_' . $type;

                if (preg_match('/^[0-9]?[0-9]:[0-9][0-9]$/', $data[$fieldname]) !== 1) {
                    $badformat = true;
                    $errors[$fieldname] = get_string('expected_time_format', 'local_apsolu');
                    continue;
                }

                [$hour, $minute] = explode(':', $data[$fieldname]);
                if ($hour > 24) {
                    $badformat = true;
                    $errors[$fieldname] = get_string('expected_time_format', 'local_apsolu');
                    continue;
                }

                if ($minute > 59) {
                    $badformat = true;
                    $errors[$fieldname] = get_string('expected_time_format', 'local_apsolu');
                    continue;
                }

                if (strlen($data[$fieldname]) === 4) {
                    $data[$fieldname] = '0' . $data[$fieldname];
                }
            }

            if ($badformat === true) {
                continue;
            }

            if (isset($types[1]) === false) {
                continue;
            }

            // Vérifie que l'heure de début n'est pas postérieure à la date de fin.
            if ($data[$name . '_start'] >= $data[$name . '_end']) {
                $parameters = new stdClass();
                $parameters->start = $data[$name . '_start'];
                $parameters->end = $data[$name . '_end'];

                $errors[$name . '_start'] = get_string('this_time_cannot_be_older_than_this_time', 'local_apsolu', $parameters);
            }
        }

        return $errors;
    }
}
