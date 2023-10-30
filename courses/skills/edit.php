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
 * Page pour gérer l'édition d'un niveau de pratique.
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\skill as Skill;

defined('MOODLE_INTERNAL') || die;

require(__DIR__.'/edit_form.php');

// Get skill id.
$skillid = optional_param('skillid', 0, PARAM_INT);

// Generate object.
$skill = new Skill();
if ($skillid !== 0) {
    $skill->load($skillid);
}

// Build form.
$customdata = ['skill' => $skill];
$mform = new local_apsolu_courses_skills_edit_form(null, $customdata);

if ($data = $mform->get_data()) {
    // Message à afficher à la fin de l'enregistrement.
    $message = get_string('skill_updated', 'local_apsolu');
    if (empty($skill->id) === true) {
        $message = get_string('skill_saved', 'local_apsolu');
    }

    // Save data.
    $skill->save($data);

    // Redirige vers la page générale.
    $returnurl = new moodle_url('/local/apsolu/courses/index.php', ['tab' => 'skills']);
    redirect($returnurl, $message, $delay = null, \core\output\notification::NOTIFY_SUCCESS);
}

// Display form.
$heading = get_string('edit_skill', 'local_apsolu');
if (empty($skill->id) === true) {
    $heading = get_string('add_skill', 'local_apsolu');
}

echo $OUTPUT->heading($heading);
$mform->display();
