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

use UniversiteRennes2\Apsolu as apsolu;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/enrol/select/locallib.php');

$cards = $DB->get_records('apsolu_payments_cards', $conditions = array(), $sort = 'name');

$cohorts = $DB->get_records('cohort', $conditions = array(), $sort = 'name');
$roles = apsolu\get_custom_student_roles();
$centers = $DB->get_records('apsolu_payments_centers');
$calendarstypes = $DB->get_records('apsolu_calendars_types', $conditions = array(), $sort = 'name');

$data = new stdClass();
$data->wwwroot = $CFG->wwwroot;
$data->cards = array();
$data->count_cards = 0;

foreach ($cards as $card) {
    $cardscohorts = $DB->get_records('apsolu_payments_cards_cohort', $conditions = array('cardid' => $card->id), $sort = '', $fields = 'cohortid');
    $cardsroles = $DB->get_records('apsolu_payments_cards_roles', $conditions = array('cardid' => $card->id), $sort = '', $fields = 'roleid');
    $cardscalendarstypes = $DB->get_records('apsolu_payments_cards_cals', $conditions = array('cardid' => $card->id), $sort = '', $fields = 'calendartypeid, value');

    $card->price = number_format($card->price, 2).'€';

    $card->center = '';
    if (isset($centers[$card->centerid]) === true) {
        $card->center = $centers[$card->centerid]->name;
    }

    $card->cohorts = array();
    $card->count_cohorts = 0;
    foreach ($cardscohorts as $cohort) {
        if (isset($cohorts[$cohort->cohortid]) === true) {
            $card->cohorts[] = $cohorts[$cohort->cohortid]->name;
            $card->count_cohorts++;
        }
    }

    $card->roles = array();
    $card->count_roles = 0;
    foreach ($cardsroles as $role) {
        if (isset($roles[$role->roleid]) === true) {
            $card->roles[] = $roles[$role->roleid]->name;
            $card->count_roles++;
        }
    }

    $card->calendars_types = array();
    $card->count_calendars_types = 0;
    foreach ($calendarstypes as $type) {
        $value = 0;
        if (isset($cardscalendarstypes[$type->id]) === true) {
            $value = $cardscalendarstypes[$type->id]->value;
        }

        $card->calendars_types[] = $type->name.' : '.$value;
        $card->count_calendars_types++;
    }

    $data->cards[] = $card;
    $data->count_cards++;
}

if (isset($notificationform)) {
    $data->notification = $notificationform;
}

echo $OUTPUT->render_from_template('local_apsolu/payment_cards', $data);
