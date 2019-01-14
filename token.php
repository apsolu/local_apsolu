<?php

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
