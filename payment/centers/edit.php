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
 * Page d'édition des centres de paiement.
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require(__DIR__.'/edit_form.php');

// Get center id.
$centerid = optional_param('centerid', 0, PARAM_INT);

// Generate object.
$center = false;
if ($centerid != 0) {
    $center = $DB->get_record('apsolu_payments_centers', ['id' => $centerid]);
}

if ($center === false) {
    $center = new stdClass();
    $center->id = 0;
    $center->name = '';
    $center->prefix = '';
    $center->idnumber = '';
    $center->sitenumber = '';
    $center->rank = '';
    $center->hmac = '';
} else {
    unset($center->hmac);
}

// Build form.
$customdata = ['center' => $center];
$mform = new local_apsolu_payment_centers_edit_form(null, $customdata);

if ($data = $mform->get_data()) {
    // Save data.
    $center = new stdClass();
    $center->id = $data->centerid;
    $center->name = trim($data->name);
    $center->prefix = trim($data->prefix);
    $center->idnumber = trim($data->idnumber);
    $center->sitenumber = trim($data->sitenumber);
    $center->rank = trim($data->rank);

    if ($center->id == 0) {
        $center->hmac = $data->hmac;

        $DB->insert_record('apsolu_payments_centers', $center);
    } else {
        $fields = ['hmac'];
        foreach ($fields as $field) {
            if (!empty($data->{$field})) {
                $center->{$field} = trim($data->{$field});
            }
        }

        $DB->update_record('apsolu_payments_centers', $center);
    }

    // Display notification and display elements list.
    $notificationform = $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');

    require(__DIR__.'/view.php');
} else {
    // Display form.
    echo '<h1>'.get_string('center_add', 'local_apsolu').'</h1>';

    $mform->display();
}
