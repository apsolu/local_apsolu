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
 * @package    local_apsolu
 * @copyright  2017 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/local/apsolu/configuration/calendar_form.php');

// Build form.
$attributes = array(
    'semester1_enrol_startdate',
    'semester1_enrol_enddate',
    'semester1_startdate',
    'semester1_enddate',
    'semester1_reenrol_startdate',
    'semester1_reenrol_enddate',
    'semester2_enrol_startdate',
    'semester2_enrol_enddate',
    'semester2_startdate',
    'semester2_enddate',
    'payments_startdate',
    'payments_enddate',
    'semester1_grading_deadline',
    'semester2_grading_deadline',
    );

$defaults = new stdClass();
foreach ($attributes as $attribute) {
    $defaults->{$attribute} = get_config('local_apsolu', $attribute);
}

$customdata = array($defaults);

$mform = new local_apsolu_calendar_form(null, $customdata);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('settings_configuration_calendar', 'local_apsolu'));

if ($data = $mform->get_data()) {
    foreach ($attributes as $attribute) {
        if ($defaults->{$attribute} != $data->{$attribute}) {
            // Mets à jour les méthodes d'inscription enrol_select.
            switch ($attribute) {
                case 'semester1_enrol_startdate':
                case 'semester2_enrol_startdate':
                    $field = 'enrolstartdate';
                    break;
                case 'semester1_enrol_enddate':
                case 'semester2_enrol_enddate':
                    $field = 'enrolenddate';
                    break;
                case 'semester1_startdate':
                case 'semester2_startdate':
                    $field = 'customint7';
                    break;
                case 'semester1_enddate':
                case 'semester2_enddate':
                    $field = 'customint8';
                    break;
                case 'semester1_reenrol_startdate':
                    $field = 'customint4';
                    break;
                case 'semester1_reenrol_enddate':
                    $field = 'customint5';
                    break;
            }

            if (substr($attribute, 0, 9) === 'semester1') {
                $customchar1 = 's1';
            } else {
                $customchar1 = 's2';
            }

            $sql = "UPDATE {enrol} SET ".$field." = :".$field." WHERE enrol = 'select' AND customchar1 = :customchar1";
            $DB->execute($sql, array($field => $data->{$attribute}, 'customchar1' => $customchar1));

            if (in_array($field, array('customint7', 'customint8'), $strict = true) === true) {
                // Mets à jour les dates d'accès au cours des étudiants.
                if ($field === 'customint7') {
                    $sql = "UPDATE {user_enrolments} SET timestart = :value WHERE enrolid IN (SELECT id FROM {enrol} WHERE enrol = 'select' AND customchar1 = :customchar1)";
                } else {
                    $sql = "UPDATE {user_enrolments} SET timeend = :value WHERE enrolid IN (SELECT id FROM {enrol} WHERE enrol = 'select' AND customchar1 = :customchar1)";
                }
                $DB->execute($sql, array('value' => $data->{$attribute}, 'customchar1' => $customchar1));
            }
        }

        set_config($attribute, $data->{$attribute}, 'local_apsolu');
    }

    echo $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');
}

$mform->display();
echo $OUTPUT->footer();
