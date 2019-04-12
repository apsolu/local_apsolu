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
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require(__DIR__.'/edit_form.php');

$courseid = optional_param('courseid', 0, PARAM_INT);

$url = new moodle_url('local/apsolu/courses/courses/edit.php', array('tab' => $tab, 'action' => 'edit', 'courseid' => $courseid));

if (empty($courseid)) {
    $course = false;
} else {
    $sql = "SELECT c.id, c.category, ac.event, ac.skillid, ac.locationid,".
        " ac.numweekday, ac.weekday, ac.starttime, ac.endtime, ac.periodid, ac.paymentcenterid, ac.license, ac.on_homepage".
        " FROM {course} c".
        " JOIN {apsolu_courses} ac ON ac.id=c.id".
        " WHERE c.id = ?";
    $course = $DB->get_record_sql($sql, array($courseid));
}

if ($course === false) {
    $course = new stdClass();
    $course->id = 0;
    $course->category = 0;
    $course->event = '';
    $course->skillid = 0;
    $course->locationid = 0;
    $course->numweekday = '';
    $course->weekday = '';
    $course->starttime = '';
    $course->endtime = '';
    $course->periodid = 0;
    $course->paymentcenterid = 1;
    $course->license = 0;
    $course->on_homepage = 1;
}

// Load categories.
$sql = "SELECT *".
    " FROM {apsolu_courses_categories} s, {course_categories} cc".
    " WHERE s.id=cc.id".
    " ORDER BY cc.name";
$categories = array();
foreach ($DB->get_records_sql($sql) as $category) {
    $categories[$category->id] = $category->name;
}

if ($categories === array()) {
    print_error('error_no_category', 'local_apsolu', $CFG->wwwroot.'/local/apsolu/courses/index.php?tab=categories');
}

// Load skills.
$skills = array();
foreach ($DB->get_records('apsolu_skills', $conditions = null, $sort = 'name') as $skill) {
    $skills[$skill->id] = $skill->name;
}

if ($skills === array()) {
    print_error('error_no_skill', 'local_apsolu', $CFG->wwwroot.'/local/apsolu/courses/index.php?tab=skills');
}

// Load locations.
$locations = array();
foreach ($DB->get_records('apsolu_locations', $conditions = null, $sort = 'name') as $location) {
    $locations[$location->id] = $location->name;
}

if ($locations === array()) {
    print_error('error_no_location', 'local_apsolu', $CFG->wwwroot.'/local/apsolu/courses/index.php?tab=locations');
}

// Load periods.
$periods = array();
foreach ($DB->get_records('apsolu_periods', $conditions = null, $sort = 'name') as $period) {
    $periods[$period->id] = $period->name;
}

if ($periods === array()) {
    print_error('error_no_period', 'local_apsolu', $CFG->wwwroot.'/local/apsolu/courses/index.php?tab=periods');
}

// Load weekdays.
$weekdays = array(
    'monday' => get_string('monday', 'calendar'),
    'tuesday' => get_string('tuesday', 'calendar'),
    'wednesday' => get_string('wednesday', 'calendar'),
    'thursday' => get_string('thursday', 'calendar'),
    'friday' => get_string('friday', 'calendar'),
    'saturday' => get_string('saturday', 'calendar'),
    'sunday' => get_string('sunday', 'calendar')
    );

// Load payment centers.
$centers = array();
foreach ($DB->get_records('apsolu_payments_centers', $conditions = null, $sort = 'name') as $center) {
    $centers[$center->id] = $center->name;
}

if ($centers === array()) {
    print_error('error_no_center', 'local_apsolu', $CFG->wwwroot.'/local/apsolu/payment/admin.php?tab=centers');
}

// Build form.
$customdata = array($course, $categories, $skills, $locations, $periods, $weekdays, $centers);
$mform = new local_apsolu_courses_courses_edit_form(null, $customdata);

if ($data = $mform->get_data()) {
    // Save data.
    $course = new stdClass();
    $course->id = $data->courseid;
    $course->category = $data->category;
    $course->event = $data->event;
    $course->skillid = $data->skillid;
    $course->locationid = $data->locationid;
    $course->weekday = $data->weekday;
    $course->numweekday = array_search($data->weekday, array_keys($weekdays)) + 1;
    $course->starttime = $data->starttime;
    $course->endtime = $data->endtime;
    $course->periodid = $data->periodid;
    $course->license = $data->license;
    $course->on_homepage = $data->on_homepage;
    $course->paymentcenterid = $data->paymentcenterid;

    // Set fullname and shortname.
    $formattime = get_string($course->weekday, 'calendar').' '.$data->starttime.' '.$data->endtime;
    if (empty($course->event)) {
        $course->fullname = $categories[$data->category].' '.$formattime.' '.$skills[$data->skillid];
    } else {
        $course->fullname = $categories[$data->category].' '.$data->event.' '.$formattime.' '.$skills[$data->skillid];
    }
    $course->shortname = $course->fullname;

    if ($course->id == 0) {
        // Créé le cours.
        $newcourse = create_course($course);
        $course->id = $newcourse->id;

        // Créé l'instance apsolu_courses.
        $sql = "INSERT INTO {apsolu_courses} (id, event, skillid, locationid, weekday, numweekday, starttime, endtime, periodid, paymentcenterid, license, on_homepage)".
            " VALUES(?,?,?,?,?,?,?,?,?,?,?,?)";
        $params = array($course->id, $course->event, $course->skillid, $course->locationid, $course->weekday,
            $course->numweekday, $course->starttime, $course->endtime, $course->periodid, $course->paymentcenterid, $course->license, $course->on_homepage);
        $DB->execute($sql, $params);

        // Ajoute une méthode d'inscription manuelle.
        $instance = $DB->get_record('enrol', array('enrol' => 'manual', 'courseid' => $course->id));
        if ($instance === false) {
            $plugin = enrol_get_plugin('manual');

            $fields = $plugin->get_instance_defaults();

            $instance = new stdClass();
            $instance->id = $plugin->add_instance($course, $fields);
        }

        // Ajoute le bloc apsolu_course.
        try {
            $blocktype = 'apsolu_course';
            $context = context_course::instance($course->id, MUST_EXIST);

            $blockinstance = new stdClass();
            $blockinstance->blockname = $blocktype;
            $blockinstance->parentcontextid = $context->id;
            $blockinstance->showinsubcontexts = 0;
            $blockinstance->pagetypepattern = 'course-view-*';
            $blockinstance->subpagepattern = NULL;
            $blockinstance->defaultregion = 'side-pre'; // Dans la colonne de gauche.
            $blockinstance->defaultweight = -1; // Avant le bloc "Paramètres du cours".
            $blockinstance->configdata = '';
            $blockinstance->timecreated = time();
            $blockinstance->timemodified = time();
            $blockinstance->id = $DB->insert_record('block_instances', $blockinstance);

            // Ensure the block context is created.
            context_block::instance($blockinstance->id);

            // If the new instance was created, allow it to do additional setup
            if ($block = block_instance($blocktype, $blockinstance)) {
                $block->instance_create();
            }
        } catch (Exception $exception) {
            debugging($exception->getMessage(), DEBUG_DEVELOPER);
        }
    } else {
        $DB->update_record('course', $course);
        $DB->update_record('apsolu_courses', $course);
    }

    // Display notification and display elements list.
    $notificationform = $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');

    require(__DIR__.'/view.php');
} else {
    // Display form.
    echo '<h1>'.get_string('course_add', 'local_apsolu').'</h1>';

    if (empty($course->id) === false) {
        echo '<ul class="list-inline text-right">'.
            '<li><a class="btn btn-primary" href="'.$CFG->wwwroot.'/enrol/users.php?id='.$course->id.'">Inscrire un utilisateur</a></li>'.
            '<li><a class="btn btn-primary" href="'.$CFG->wwwroot.'/enrol/instances.php?id='.$course->id.'">Méthode d\'inscription</a></li>'.
            '</ul>';
    }

    $mform->display();
}
