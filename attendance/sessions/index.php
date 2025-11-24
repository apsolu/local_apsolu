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
 * Contrôleur pour l'administration des sessions de cours.
 *
 * @package    local_apsolu
 * @copyright  2017 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$sessionid = optional_param('sessionid', 0, PARAM_INT); // Session id.
$action = optional_param('action', 'view', PARAM_ALPHANUM);

$notifications = [];
$streditcoursesettings = get_string('attendance_sessions_edit', 'local_apsolu');

$data = new stdClass();
$data->url = $CFG->wwwroot;
$data->courseid = $courseid;

switch ($action) {
    case 'delete':
    case 'edit':
    case 'view':
        require(__DIR__ . '/' . $action . '.php');
        break;
    default:
        require(__DIR__ . '/view.php');
}

// Titre et navigation.
$PAGE->navbar->add($streditcoursesettings);

$pagedesc = $streditcoursesettings;
$title = $streditcoursesettings;
$fullname = $course->fullname;

$PAGE->set_title($title);
$PAGE->set_heading($fullname);

echo $OUTPUT->header();
echo $OUTPUT->tabtree($tabsbar, 'sessions');
echo $OUTPUT->heading($pagedesc);
foreach ($notifications as $notification) {
    echo $notification;
}
echo $OUTPUT->render_from_template($template, $data);
echo $OUTPUT->footer();
