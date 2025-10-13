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
 * Page affichant la liste des messages de relances de paiements.
 *
 * @package    local_apsolu
 * @copyright  2019 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$dunningid = required_param('dunningid', PARAM_INT);

require_once($CFG->dirroot . '/local/apsolu/locallib.php');

$data = new stdClass();
$data->wwwroot = $CFG->wwwroot;
$data->dunning = $DB->get_record('apsolu_dunnings', ['id' => $dunningid]);
$data->posts = [];
$data->count_posts = 0;

if ($data->dunning !== false) {
    $sql = "SELECT adp.*, u.firstname, u.lastname, u.email" .
        " FROM {apsolu_dunnings_posts} adp" .
        " JOIN {user} u ON u.id = adp.userid" .
        " WHERE adp.dunningid = :dunningid" .
        " ORDER BY u.lastname, u.firstname";
    $posts = $DB->get_records_sql($sql, ['dunningid' => $dunningid]);

    foreach ($posts as $post) {
        $post->timecreated = userdate($post->timecreated, get_string('strftimedatetime', 'local_apsolu'));

        $data->posts[] = $post;
        $data->count_posts++;
    }
}

echo $OUTPUT->render_from_template('local_apsolu/payment_notifications_posts', $data);
