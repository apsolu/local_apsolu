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

if ($data = $mform->get_data()) {
    foreach ($attributes as $attribute) {
        if ($defaults->{$attribute} !== $data->{$attribute}) {
            // TODO: update enrol_select methods.
        }
        set_config($attribute, $data->{$attribute}, 'local_apsolu');
    }

    echo $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');
}

echo $OUTPUT->notification('Page en construction. Ne pas modifier.', 'notifywarning');

$mform->display();
echo $OUTPUT->footer();
