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

require_once($CFG->libdir . '/csvlib.class.php');

$headers = array(
    get_string('skill_fullnames', 'local_apsolu'),
    get_string('skill_shortnames', 'local_apsolu'),
);

$skills = $DB->get_records('apsolu_skills', null, 'name', 'name, shortname');
$filename = strtolower(get_string('skills', 'local_apsolu'));

$csvexport = new \csv_export_writer();
$csvexport->set_filename($filename);
$csvexport->add_data($headers);

foreach ($skills as $skill) {
    $csvexport->add_data((array) $skill);
}

$csvexport->download_file();

exit;
