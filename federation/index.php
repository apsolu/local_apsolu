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
 * Contrôleur pour les pages d'administration de la FFSU.
 *
 * @package    local_apsolu
 * @copyright  2017 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\course AS Course;

require(__DIR__.'/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$page = optional_param('page', 'activities', PARAM_ALPHAEXT);

// Récupère l'id du cours FFSU.
$courseid = Course::get_federation_courseid();

if ($courseid === false) {
    throw new moodle_exception('undefined_federation_course', 'local_apsolu');
}

// Set tabs.
$pages = array();
$pages['settings'] = get_string('settings');
$pages['activities'] = get_string('activity_list', 'local_apsolu');
$pages['numbers'] = get_string('association_numbers', 'local_apsolu');
$pages['export'] = get_string('exporting_license', 'local_apsolu');
$pages['import'] = get_string('importing_license', 'local_apsolu');
$pages['certificates_validation'] = get_string('certificates_validation', 'local_apsolu');

$tabtree = array();
foreach ($pages as $pagename => $label) {
    $url = new moodle_url('/local/apsolu/index.php', array('page' => $pagename));
    $tabtree[] = new tabobject($pagename, $url, $label);
}

// Set default tabs.
if (isset($pages[$page]) === false) {
    $page = 'activities';
}

// Setup admin access requirement.
admin_externalpage_setup('local_apsolu_federation_'.$page);

require(__DIR__.'/'.$page.'/index.php');
