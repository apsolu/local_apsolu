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
 * Permet de gérer les tokens pour les boitiers Famoco.
 *
 * @package    local_apsolu
 * @copyright  2018 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('AJAX_SCRIPT', true);
define('REQUIRE_CORRECT_ACCESS', true);
define('NO_MOODLE_COOKIES', true);

require __DIR__.'/../../config.php';

// Allow CORS requests.
// header('Access-Control-Allow-Origin: *');

$cardnumber = required_param('idcardnumber', PARAM_ALPHANUM);

if (!$CFG->enablewebservices) {
    throw new moodle_exception('enablewsdescription', 'webservice');
}

$response = new stdClass();
$response->token = null;

$sql = "SELECT uid.*".
    " FROM {user_info_data} uid".
    " JOIN {user_info_field} uif ON uif.id = uid.fieldid AND uif.shortname = 'apsoluidcardnumber'".
    " WHERE uid.data = :data";
$card = $DB->get_record_sql($sql, array('data' => $cardnumber));

if ($card) {
    $service = $DB->get_record('external_services', array('component' => 'local_apsolu', 'enabled' => 1));
    if ($service) {
        $externaltoken = $DB->get_record('external_tokens', array('userid' => $card->userid, 'externalserviceid' => $service->id));

        if ($externaltoken) {
            $response = new stdClass();
            $response->token = $externaltoken->token;
        } else {
            if (empty($CFG->debug) === false) {
                $response->debug = 'user token not found / generated';
            }
        }
    } else {
        if (empty($CFG->debug) === false) {
            $response->debug = 'webservice not found / disabled';
        }
    }
} else {
    if (empty($CFG->debug) === false) {
        $response->debug = 'user cardid not found';
    }
}

echo json_encode($response);
