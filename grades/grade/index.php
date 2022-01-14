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
 * Page de notation des étudiants pour les enseignants.
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\customfields;
use local_apsolu\core\gradebook as Gradebook;

require_once(__DIR__.'/../../../../config.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('base');
$PAGE->set_url('/local/apsolu/grades/index.php');
$PAGE->set_title(get_string('gradebook', 'grades'));

// Navigation.
$PAGE->navbar->add(get_string('gradebook', 'grades'));

require_login($courseorid = null, $autologinguest = false);

require_once($CFG->dirroot.'/local/apsolu/grades/grade/gradebook.php');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('gradebook', 'grades'));

$data = new stdClass();

$data->display_table = isset($gradebook);
if ($data->display_table === true) {
    $data->headers = $gradebook->headers;
    $data->users = $gradebook->users;
    $data->count_grades = count($gradebook->users);
}

if (isset($notification) === true) {
    $data->notification = $notification;
}

$data->filtersdata = base64_encode(json_encode($filtersdata));
$data->filtersform = $mform->render();

echo $OUTPUT->render_from_template('local_apsolu/grade_table', $data);

echo $OUTPUT->footer();
