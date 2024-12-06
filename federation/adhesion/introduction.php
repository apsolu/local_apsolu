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
 * Page de présentation de la FFSU.
 *
 * @package    local_apsolu
 * @copyright  2024 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\federation\questionnaire as Questionnaire;

defined('MOODLE_INTERNAL') || die();

$quizstatus = optional_param('quizstatus', null, PARAM_INT);

$introduction = get_config('local_apsolu', 'ffsu_introduction');

// Texte de présentation de la FFSU.
echo html_writer::div($introduction, 'mx-auto my-5 w-75');

$buttons = [];
// Bouton pour passer au QCM médical.
$buttons[] = html_writer::link(new moodle_url('/local/apsolu/federation/adhesion/index.php', ['step' => 0]),
    get_string('continue'), ['class' => 'btn btn-primary']);

if ($adhesion->can_edit() === true) {
    // Bouton d'annulation et de désinscription.
    $attributes = new stdClass();
    $attributes->href = (string) new moodle_url('/local/apsolu/federation/adhesion/cancel.php');
    $attributes->class = 'btn btn-danger ml-2';
    $buttons[] = get_string('cancel_and_unenrol_link', 'local_apsolu', $attributes);
}

echo html_writer::div(implode(' ', $buttons));
