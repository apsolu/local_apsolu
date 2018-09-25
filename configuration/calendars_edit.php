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

require_once($CFG->dirroot.'/local/apsolu/configuration/calendars_form.php');

$calendarid = optional_param('calendarid', 0, PARAM_INT);

// Vérifie qu'il existe au moins un type de calendrier.
$calendarstypes = $DB->get_records('apsolu_calendars_types', $conditions = array(), $sort = 'name');
if (count($calendarstypes) === 0) {
    redirect($CFG->wwwroot.'/local/apsolu/configuration/index.php?page=calendarstypes', get_string('needcalendarstypefirst', 'local_apsolu'), null, \core\output\notification::NOTIFY_ERROR);
}

// Définis l'instance.
$instance = false;
if ($calendarid !== 0) {
    $instance = $DB->get_record('apsolu_calendars', array('id' => $calendarid));
}

if ($instance === false) {
    $instance = new stdClass();
    $instance->id = 0;
    $instance->name = '';
    $instance->enrolstartdate = 0;
    $instance->enrolenddate = 0;
    $instance->coursestartdate = 0;
    $instance->courseenddate = 0;
    $instance->reenrolstartdate = 0;
    $instance->reenrolenddate = 0;
    $instance->gradestartdate = 0;
    $instance->gradeenddate = 0;
    $instance->typeid = 0;
}

// Build form.
$customdata = array($instance, $calendarstypes);
$mform = new local_apsolu_calendar_edit_form(null, $customdata);

if ($data = $mform->get_data()) {
    // Save data.
    $instance = new stdClass();
    $instance->id = $data->calendarid;
    $instance->name = trim($data->name);
    $instance->enrolstartdate = $data->enrolstartdate;
    $instance->enrolenddate = $data->enrolenddate;
    $instance->coursestartdate = $data->coursestartdate;
    $instance->courseenddate = $data->courseenddate;
    $instance->reenrolstartdate = $data->reenrolstartdate;
    $instance->reenrolenddate = $data->reenrolenddate;
    $instance->gradestartdate = $data->gradestartdate;
    $instance->gradeenddate = $data->gradeenddate;
    $instance->typeid = $data->typeid;

    if ($instance->id === 0) {
        $DB->insert_record('apsolu_calendars', $instance);
    } else {
        $DB->update_record('apsolu_calendars', $instance);

        // Mets à jour les méthodes d'inscription se basant sur ce calendrier.
        $sql = "UPDATE {enrol} SET enrolstartdate=:enrolstartdate, enrolenddate=:enrolenddate, customint7=:coursestartdate, customint8=:courseenddate, customint4=:reenrolstartdate, customint5=:reenrolenddate".
            " WHERE enrol = 'select' AND customchar1 = :calendarid";
        $params = array();
        $params['enrolstartdate'] = $instance->enrolstartdate;
        $params['enrolenddate'] = $instance->enrolenddate;
        $params['coursestartdate'] = $instance->coursestartdate;
        $params['courseenddate'] = $instance->courseenddate;
        $params['reenrolstartdate'] = $instance->reenrolstartdate;
        $params['reenrolenddate'] = $instance->reenrolenddate;
        $params['calendarid'] = $instance->id;
        $DB->execute($sql, $params);

        // Mets à jour les inscriptions liées à ce calendrier.
        $sql = "UPDATE {user_enrolments} SET timestart = :coursestartdate, timeend = :courseenddate WHERE enrolid IN (SELECT id FROM {enrol} WHERE enrol = 'select' AND customchar1 = :calendarid)";
        $DB->execute($sql, array('coursestartdate' => $instance->coursestartdate, 'courseenddate' => $instance->courseenddate, 'calendarid' => $instance->id));
    }

    // Display notification and display elements list.
    $notificationform = $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');

    require(__DIR__.'/calendars_view.php');
} else {
    // Display form.
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('settings_configuration_calendarstypes', 'local_apsolu'));

    $mform->display();
    echo $OUTPUT->footer();
}
