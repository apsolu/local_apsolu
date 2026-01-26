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
 * @copyright  2022 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use grade_grade;
use grade_item;
use local_apsolu\core\gradebook;

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/gradelib.php');

$inputname = required_param('inputname', PARAM_ALPHANUMEXT);
$rt = optional_param('rt', 0, PARAM_INT);

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('base');
$PAGE->set_url('/local/apsolu/grades/delete.php');
$PAGE->set_title(get_string('gradebook', 'grades'));

// Navigation.
$PAGE->navbar->add(get_string('gradebook', 'grades'));

require_login($courseorid = null, $autologinguest = false);

// Récupère l'élément de notation.
if (preg_match('/^[0-9]+-[0-9]+-[0-9]+$/', $inputname) !== 1) {
    throw new dml_missing_record_exception('apsolu_grade_items');
}

[$userid, $courseid, $apsolugradeitemid] = explode('-', $inputname);

// Recherche l'élément de notation APSOLU.
$apsolugradeitem = $DB->get_record('apsolu_grade_items', ['id' => $apsolugradeitemid], '*', MUST_EXIST);
$itemname = sprintf('%s-%s', $apsolugradeitem->id, $apsolugradeitem->name);

// Recherche l'élément de notation du cours.
$item = false;
foreach (grade_item::fetch_all(['courseid' => $courseid, 'iteminfo' => gradebook::NAME]) as $record) {
    if ($record->itemname !== $itemname) {
        continue;
    }

    $item = $record;
    break;
}

if ($item === false) {
    $record = new grade_item();
    throw new dml_missing_record_exception($record->table);
}

// Contrôle les droits d'édition.
if (has_capability('local/apsolu:viewallgrades', context_system::instance()) === false) {
    // Ce n'est pas un gestionnaire. On vérifie que l'utilisateur a bien accès à ce cours.
    $find = false;
    foreach (Gradebook::get_courses() as $course) {
        if ($course->id !== $item->courseid) {
            continue;
        }

        $find = true;
        break;
    }

    if ($find === false) {
        throw new moodle_exception('nopermissions', 'error', '', get_capability_string('local/apsolu:editgrades'));
    }

    if (has_capability('local/apsolu:editgrades', context_course::instance($item->courseid)) === false) {
        throw new moodle_exception('nopermissions', 'error', '', get_capability_string('local/apsolu:editgrades'));
    }
}

// Vérifie que l'édition des notes n'est pas hors-délai.
if (has_capability('local/apsolu:editgradesafterdeadline', context_system::instance()) === false) {
    $calendar = $DB->get_record('apsolu_calendars', ['id' => $apsolugradeitem->calendarid], '*', MUST_EXIST);

    $now = time();
    $canedit = ((empty($calendar->gradestartdate) || $now > $calendar->gradestartdate) &&
        (empty($calendar->gradeenddate) || $now < $calendar->gradeenddate));
    if ($canedit === false) {
        throw new moodle_exception('nopermissions', 'error', '', get_capability_string('local/apsolu:editgradesafterdeadline'));
    }
}

// On supprime la note.
$label = get_string('grade_has_been_deleted', 'local_apsolu');
$status = \core\output\notification::NOTIFY_SUCCESS;

$currentgrade = grade_grade::fetch(['itemid' => $item->id, 'userid' => $userid]);
if ($currentgrade !== false && $currentgrade->delete($source = 'local_apsolu') === false) {
    $label = get_string('grade_has_not_been_deleted', 'local_apsolu');
    $status = \core\output\notification::NOTIFY_ERROR;
}

if ($rt === CONTEXT_SYSTEM) {
    $returnurl = new moodle_url('/local/apsolu/grades/admin/index.php');
} else {
    $returnurl = new moodle_url('/local/apsolu/grades/grade/index.php');
}

redirect($returnurl, $label, null, $status);
