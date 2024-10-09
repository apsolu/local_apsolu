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
 * Liste les modèles de messages.
 *
 * @package    local_apsolu
 * @copyright  2024 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$templates = $DB->get_records('apsolu_communication_templates', ['hidden' => 0], $sort = 'subject');

$data = new stdClass();
$data->wwwroot = $CFG->wwwroot;
$data->templates = array_values($templates);
$data->count_templates = count($data->templates);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('templates', 'local_apsolu'));
echo $OUTPUT->tabtree($tabtree, $page);
echo $OUTPUT->render_from_template('local_apsolu/communication_templates', $data);
echo $OUTPUT->footer();

