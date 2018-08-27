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
 * Backoffice to extend moodle courses attributes.
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$tab = optional_param('tab', 'courses', PARAM_ALPHA);
$action = optional_param('action', 'view', PARAM_ALPHA);

$PAGE->requires->js_call_amd('local_apsolu/sort', 'initialise');

// Set tabs.
$coursestabs = array('courses', 'groupings', 'categories', 'skills', 'periods', 'overview');
$locationstabs = array('locations', 'areas', 'cities', 'managers');
$periodstabs = array('periods');

// Set default tabs.
if (in_array($tab, $locationstabs, $strict = true) === true) {
    $subpage = 'locations';
    $tabslist = $locationstabs;
}else if (in_array($tab, $periodstabs, $strict = true) === true) {
    $subpage = 'periods';
    $tabslist = $periodstabs;
} else {
    $subpage = 'courses';
    $tabslist = $coursestabs;
    if (in_array($tab, $coursestabs, $strict = true) === false) {
        $tab = $tabslist[0];
    }
}

$tabsbar = array();
foreach ($tabslist as $tabname) {
    $url = new moodle_url('/local/apsolu/courses/index.php', array('tab' => $tabname));
    $tabsbar[] = new tabobject($tabname, $url, get_string($tabname, 'local_apsolu'));
}

// Setup admin access requirement.
admin_externalpage_setup('local_apsolu_courses_'.$subpage.'_'.$tab);

// Display page.
ob_start();
require(__DIR__.'/'.$tab.'/index.php');
$content = ob_get_contents();
ob_end_clean();

echo $OUTPUT->header();
// echo $OUTPUT->tabtree($tabsbar, $tab);
echo $content;
echo $OUTPUT->footer();
