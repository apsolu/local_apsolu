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
 * Page d'édition des tarifs.
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require(__DIR__.'/edit_form.php');
require_once($CFG->dirroot.'/enrol/select/locallib.php');

// Vérifie qu'il existe au moins un type de calendrier.
$calendarstypes = $DB->get_records('apsolu_calendars_types', $conditions = [], $sort = 'name');
if (count($calendarstypes) === 0) {
    redirect($CFG->wwwroot.'/local/apsolu/configuration/index.php?page=calendarstypes',
        get_string('needcalendarstypefirst', 'local_apsolu'), null, \core\output\notification::NOTIFY_ERROR);
}

// Get card id.
$cardid = optional_param('cardid', 0, PARAM_INT);

// Generate object.
$instance = false;
if ($cardid != 0) {
    $instance = $DB->get_record('apsolu_payments_cards', ['id' => $cardid]);
}

if ($instance === false) {
    $instance = new stdClass();
    $instance->id = 0;
    $instance->name = '';
    $instance->fullname = '';
    $instance->trial = 0;
    $instance->price = '0.00';
    $instance->centerid = 0;
    $instance->cohorts = [];
    $instance->roles = [];
    $instance->calendarstypes = [];
} else {
    $instance->price = number_format($instance->price, 2);

    $instance->cohorts = array_keys($DB->get_records('apsolu_payments_cards_cohort', ['cardid' => $instance->id], '', 'cohortid'));
    $instance->roles = array_keys($DB->get_records('apsolu_payments_cards_roles', ['cardid' => $instance->id], '', 'roleid'));
    $instance->calendarstypes = $DB->get_records('apsolu_payments_cards_cals',
        ['cardid' => $instance->id], '', 'calendartypeid, value');
}

foreach ($calendarstypes as $type) {
    $name = 'types['.$type->id.']';
    $instance->{$name} = 0;
    if (isset($instance->calendarstypes[$type->id]) === true) {
        $instance->{$name} = $instance->calendarstypes[$type->id]->value;
    }
}

// Build form.
$cohorts = $DB->get_records('cohort', $conditions = [], $sort = 'name');
$roles = enrol_select_get_custom_student_roles();
$centers = $DB->get_records('apsolu_payments_centers');

$customdata = [$instance, $cohorts, $roles, $centers, $calendarstypes];
$mform = new local_apsolu_payment_cards_edit_form(null, $customdata);

if ($data = $mform->get_data()) {
    // Save data.
    $transaction = $DB->start_delegated_transaction();

    $instance = new stdClass();
    $instance->id = $data->cardid;
    $instance->name = trim($data->name);
    $instance->fullname = trim($data->fullname);
    $instance->trial = trim($data->trial);
    $instance->price = trim($data->price);
    $instance->centerid = trim($data->centerid);

    if ($instance->id == 0) {
        $instance->id = $DB->insert_record('apsolu_payments_cards', $instance);
    } else {
        $DB->update_record('apsolu_payments_cards', $instance);
    }

    // Mets à jour l'association des tarifs et des cohortes.
    $DB->delete_records('apsolu_payments_cards_cohort', ['cardid' => $instance->id]);
    if (isset($data->cohorts) === true) {
        foreach ($data->cohorts as $cohortid) {
            $DB->execute('INSERT INTO {apsolu_payments_cards_cohort}(cardid, cohortid) VALUES(?, ?)', [$instance->id, $cohortid]);
        }
    }

    // Mets à jour l'association des tarifs et des roles.
    $DB->delete_records('apsolu_payments_cards_roles', ['cardid' => $instance->id]);
    if (isset($data->roles) === true) {
        foreach ($data->roles as $roleid) {
            $DB->execute('INSERT INTO {apsolu_payments_cards_roles}(cardid, roleid) VALUES(?, ?)', [$instance->id, $roleid]);
        }
    }

    // Mets à jour l'association des tarifs et des calendriers.
    $DB->delete_records('apsolu_payments_cards_cals', ['cardid' => $instance->id]);
    if (isset($data->types) === true) {
        foreach ($data->types as $calendartypeid => $value) {
            $DB->execute('INSERT INTO {apsolu_payments_cards_cals} (cardid, calendartypeid, value)
                               VALUES (?, ?, ?)', [$instance->id, $calendartypeid, $value]);
        }
    }

    $transaction->allow_commit();

    // Display notification and display elements list.
    $notificationform = $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');

    require(__DIR__.'/view.php');
} else {
    // Display form.
    echo '<h1>'.get_string('card_add', 'local_apsolu').'</h1>';

    $mform->display();
}
