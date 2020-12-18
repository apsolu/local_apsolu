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
 * Page pour gérer l'édition d'un élément d'évaluation.
 *
 * @package    local_apsolu
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\gradeitem as Gradeitem;
use local_apsolu\core\gradebook as Gradebook;

defined('MOODLE_INTERNAL') || die;

require_once(__DIR__.'/edit_form.php');
require_once($CFG->libdir.'/gradelib.php');

// Get gradeitem id.
$gradeitemid = optional_param('gradeitemid', 0, PARAM_INT);

// Generate object.
$gradeitem = new Gradeitem();
if ($gradeitemid !== 0) {
    $gradeitem->load($gradeitemid);
}

// Roles.
$roles = array();
foreach (Gradebook::get_gradable_roles() as $role) {
    $roles[$role->id] = $role->localname;
}

// Calendriers.
$calendars = array();
foreach ($DB->get_records('apsolu_calendars') as $calendar) {
    $calendars[$calendar->id] = $calendar->name;
}

// Build form.
$customdata = array($gradeitem, $roles, $calendars);
$mform = new local_apsolu_grades_gradeitems_edit_form(null, $customdata);

if ($data = $mform->get_data()) {
    // Message à afficher à la fin de l'enregistrement.
    $message = get_string('gradeitem_updated', 'local_apsolu');
    if (empty($gradeitem->id) === true) {
        $message = get_string('gradeitem_saved', 'local_apsolu');
    }

    // Save data.
    $gradeitem->save($data);

    // Redirige vers la page générale.
    $returnurl = new moodle_url('/local/apsolu/grades/admin/index.php', array('tab' => 'gradeitems'));
    redirect($returnurl, $message, $delay = null, \core\output\notification::NOTIFY_SUCCESS);
}

// Display form.
$heading = get_string('edit_gradeitem', 'local_apsolu');
if (empty($gradeitem->id) === true) {
    $heading = get_string('add_gradeitem', 'local_apsolu');
}

echo $OUTPUT->heading($heading);
$mform->display();
