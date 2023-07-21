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
 * Page listant les numéros d'association.
 *
 * @package    local_apsolu
 * @copyright  2022 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\federation\number as Number;

defined('MOODLE_INTERNAL') || die();

$fields = Number::get_default_fields();
$numbers = $DB->get_records('apsolu_federation_numbers', $conditions = array(), $sort = 'sortorder');
$count = count($numbers);
$sortorder = 1;
foreach ($numbers as $id => $number) {
    $numbers[$id]->first = ($sortorder === 1);
    $numbers[$id]->last = ($sortorder === $count);
    $numbers[$id]->field = $fields[$number->field];
    $sortorder++;
}

echo $OUTPUT->header();
echo $OUTPUT->heading_with_help(get_string('association_numbers', 'local_apsolu'), 'association_numbers', 'local_apsolu');
echo $OUTPUT->tabtree($tabtree, $page);

$data = new stdClass();
$data->numbers = array_values($numbers);
$data->wwwroot = $CFG->wwwroot;
echo $OUTPUT->render_from_template('local_apsolu/federation_numbers', $data);

echo $OUTPUT->footer();
