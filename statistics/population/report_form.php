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
 * Classe pour gérer un formulaire Moodle.
 *
 * @package    local_apsolu
 * @copyright  2017 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

/**
 * Classe pour gérer un formulaire Moodle.
 *
 * @package    local_apsolu
 * @copyright  2017 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_apsolu_statistics_report_form extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    protected function definition() {
        $mform = $this->_form;

        list($reports,$reportid) = $this->_customdata;

        $options = array();

        $group = get_string('none');
        $options[$group] = array();
        $options[$group][0] = $group;

        $group = get_string('statistics_enrollments', 'local_apsolu');
        $options[$group] = array();
        foreach($reports as $property => $value) {
            if (property_exists($value, 'group') && $value->group == "statistics_enrollments") {
                $options[$group][$value->id] = $value->label;
            }
        }

        $group = get_string('statistics_enrollees', 'local_apsolu');
        $options[$group] = array();
        foreach($reports as $property => $value) {
            if (property_exists($value, 'group') && $value->group == "statistics_enrollees") {
                $options[$group][$value->id] = $value->label;
            }
        }

        $group = get_string('other');
        $options[$group] = array();
        foreach($reports as $property => $value) {
            if (!property_exists($value, 'group')) {
                $options[$group][$value->id] = $value->label;
            }
        }

        $reportselect = $mform->addElement('selectgroups', 'reportid', get_string('statistics_select_reports', 'local_apsolu'), $options);

        if (!is_null ($reportid)) {
            $reportselect->setSelected($reportid);
        }
    }
}
