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
 * Interface for paybox payment.
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use UniversiteRennes2\Apsolu as apsolu;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot.'/local/apsolu/locallib.php');
require_once($CFG->dirroot.'/enrol/select/lib.php');
require_once($CFG->dirroot.'/enrol/select/locallib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');

require_login();

$context = context_user::instance($USER->id);

$PAGE->set_url('/local/apsolu/payment/index.php');
$PAGE->set_pagelayout('course');

$PAGE->set_context($context);

$PAGE->set_title(get_string('payment', 'local_apsolu'));

// Vérifie la période d'ouverture des paiements.
$payments_startdate = get_config('local_apsolu', 'payments_startdate');
$payments_enddate = get_config('local_apsolu', 'payments_enddate');
if (time() < $payments_startdate || time() > $payments_enddate) {
    // Module de paiement fermé !

    // Navigation.
    $PAGE->navbar->add(get_string('payment', 'local_apsolu'));

    // Display page.
    echo $OUTPUT->header();

    $data = new stdClass();
    if (time() < $payments_startdate) {
        $date = userdate($payments_startdate, get_string('strftimedaydatetime', 'langconfig'));
        $data->complement = get_string('opened_period', 'local_apsolu', (object) ['date' => $date]);
    }

    echo $OUTPUT->render_from_template('local_apsolu/payment_closed', $data);

    echo $OUTPUT->footer();

    exit(0);
}

// Gère les utilisateurs non autorisés à payer.
$customfields = profile_user_record($USER->id);
if (($USER->auth !== 'shibboleth' || !isset($customfields->validsesame) || $customfields->validsesame == 0) && is_siteadmin() === false) {
    // Navigation.
    $PAGE->navbar->add(get_string('payment', 'local_apsolu'));

    // Display page.
    echo $OUTPUT->header();

    $data = new stdClass();
    if ($USER->auth === 'shibboleth') {
        $admin = current(get_admins());
        $options = (object) ['email' => $admin->email];
        $data->alert = get_string('invalid_user_invalid_sesame', 'local_apsolu', $options);
    } else {
        $options = (object) ['url' => $CFG->wwwroot.'/local/apsolu_auth/edit.php'];
        $data->alert = get_string('invalid_user_no_sesame', 'local_apsolu', $options);
    }

    echo $OUTPUT->render_from_template('local_apsolu/payment_invalid_user', $data);

    echo $OUTPUT->footer();

    exit(0);
}

// Gère le retour de PayBox.
$status = optional_param('status', null, PARAM_ALPHA);

if (isset($status)) {
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

    // Navigation.
    $PAGE->navbar->add(get_string('payment', 'local_apsolu'));

    // Display page.
    echo $OUTPUT->header();

    echo $OUTPUT->render_from_template('local_apsolu/payment_paybox_return', $data);

    echo $OUTPUT->footer();

    exit(0);
}

// Data.
$paymentcenters = apsolu\get_data_user_payment_centers();

// Navigation.
$PAGE->navbar->add(get_string('payment', 'local_apsolu'));

// Javascript.
$PAGE->requires->js_call_amd('local_apsolu/payment', 'initialise');

// Display.
echo $OUTPUT->header();

$data = new stdClass();
$data->wwwroot = $CFG->wwwroot;
$data->payment_centers = array_values($paymentcenters);
$data->count_payment_centers = count($data->payment_centers);

$payboxserver = false;

$servers = explode(',', get_config('local_apsolu', 'paybox_servers_outgoing'));
foreach ($servers as $server) {
    $options = array();

    $server = trim($server);

    if (empty($server)) {
        continue;
    }

    if (!empty($CFG->proxyhost) && !is_proxybypass('https://'.$server)) {
        if (!empty($CFG->proxyport)) {
            $proxy = $CFG->proxyhost.':'.$CFG->proxyport;
        } else {
            $proxy = $CFG->proxyhost;
        }

        $options = array(
            'http' => array (
                'proxy' => $proxy,
            ),
            'https' => array (
                'proxy' => $proxy,
            )
        );
    }

    // Création du contexte de transaction.
    $ctx = stream_context_create($options);

    // Récupération des données.
    $content = file_get_contents('https://'.$server.'/load.html', false, $ctx);
    if (strpos($content, '<div id="server_status" style="text-align:center;">OK</div>') !== false) {
        // Le serveur est prêt et les services opérationnels.
        $payboxserver = $server;
        break;
    }
    // La machine est disponible mais les services ne le sont pas.
}

if ($payboxserver === false) {
    $data->can_paid = false;
} else {
    $data->can_paid = true;
    $data->action_url = 'https://'.$payboxserver.'/cgi/MYchoix_pagepaiement.cgi';
}

echo $OUTPUT->render_from_template('local_apsolu/payment_index', $data);

echo $OUTPUT->footer();
