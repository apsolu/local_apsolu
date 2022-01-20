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
 * Formulaire de demande de coordonnées postales du porteur de carte pour la directive DSP 2.
 *
 * @package    local_apsolu
 * @copyright  2022 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use UniversiteRennes2\Apsolu\Payment;
use local_apsolu\core\paybox;

require_once(__DIR__.'/../../../config.php');
require_once(__DIR__.'/address_form.php');
require_once($CFG->dirroot.'/local/apsolu/classes/apsolu/payment.php');

require_login();

$context = context_user::instance($USER->id);

$PAGE->set_url('/local/apsolu/payment/index.php');
$PAGE->set_pagelayout('base');

$PAGE->set_context($context);

$PAGE->set_title(get_string('payment', 'local_apsolu'));

// Navigation.
$PAGE->navbar->add(get_string('payment', 'local_apsolu'));

// Gère le retour de PayBox.
$status = optional_param('status', null, PARAM_ALPHA);

if ($status !== null) {
    $data = new stdClass();
    $data->wwwroot = $CFG->wwwroot;

    switch ($status) {
        case 'accepted':
            $data->status = get_string('status_'.$status, 'local_apsolu');
            $data->state = 'alert-success';
            break;
        case 'cancel':
        case 'wait':
            $data->status = get_string('status_'.$status, 'local_apsolu');
            $data->state = 'alert-info';
            break;
        case 'refused':
            $data->status = get_string('status_'.$status, 'local_apsolu');
            $data->state = 'alert-danger';
            break;
        default:
            $data->status = get_string('status_unknown', 'local_apsolu');
            $data->state = 'alert-info';
    }

    // Display page.
    echo $OUTPUT->header();

    echo $OUTPUT->render_from_template('local_apsolu/payment_paybox_return', $data);

    echo $OUTPUT->footer();

    exit(0);
}

// Vérifie si les paiements sont ouverts.
if (Payment::is_open() === false) {
    // Module de paiement fermé !

    // Display page.
    echo $OUTPUT->header();

    $payments_startdate = get_config('local_apsolu', 'payments_startdate');

    $data = new stdClass();
    if (time() < $payments_startdate) {
        $date = userdate($payments_startdate, get_string('strftimedaydatetime', 'langconfig'));
        $data->complement = get_string('opened_period', 'local_apsolu', (object) ['date' => $date]);
    }

    echo $OUTPUT->render_from_template('local_apsolu/payment_closed', $data);

    echo $OUTPUT->footer();

    exit(0);
}

// Generate object.
$address = paybox::get_address($USER->id);

// Build form.
$customdata = array($address);
$mform = new local_apsolu_payment_address_form(null, $customdata);

if ($data = $mform->get_data()) {
    // Save data.
    paybox::save_address($data);

    // Redirige vers la page générale.
    $returnurl = new moodle_url('/local/apsolu/payment/validation.php');
    redirect($returnurl);
}

// Display.
echo $OUTPUT->header();

$payboxserver = paybox::get_server();
if ($payboxserver === false) {
    echo $OUTPUT->render_from_template('local_apsolu/payment_unavailable_server', $data = null);
} else {
    echo html_writer::div(get_string('disclaimer_dsp2', 'local_apsolu'), 'alert alert-info');
    $mform->display();
}

echo $OUTPUT->footer();
