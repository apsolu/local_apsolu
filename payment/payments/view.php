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
 * Page listant les paiements.
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use UniversiteRennes2\Apsolu\Payment;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/local/apsolu/locallib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot.'/local/apsolu/classes/apsolu/payment.php');

$userid = optional_param('userid', null, PARAM_INT);
$showalltransactions = optional_param('showall', 0, PARAM_INT);

if (isset($userid)) {
    $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
    $customfields = profile_user_record($userid);

    $data = new stdClass();
    $data->wwwroot = $CFG->wwwroot;
    $data->userid = $userid;
    $data->useridentity = $OUTPUT->render(new user_picture($user)).' '.fullname($user);
    $data->payments = [];
    $data->count_payments = 0;
    $data->due_payments = [];
    $data->count_due_payments = 0;
    $data->has_sesame = (isset($customfields->apsolusesame) && $customfields->apsolusesame == 1);
    $data->user_auth = get_string('pluginname', 'auth_'.$user->auth);

    // Liste les cartes dûes de l'utilisateur.
    $cards = Payment::get_user_cards($userid);
    if (count($cards) > 0) {
        foreach ($cards as $card) {
            if (Payment::get_user_card_status($card, $userid) !== Payment::DUE) {
                continue;
            }

            $data->due_payments[] = $card->name;
            $data->count_due_payments++;
        }
    }

    // Liste toutes les transactions de l'utilisateur enregistrées dans la table apsolu_payments.
    $centers = $DB->get_records('apsolu_payments_centers');
    $payments = $DB->get_records('apsolu_payments', ['userid' => $userid], $sort = 'timemodified');
    foreach ($payments as $payment) {
        if (!empty($payment->timepaid)) {
            try {
                $timepaid = new DateTime($payment->timepaid);
                $payment->timepaid = core_date::strftime('%c', $timepaid->getTimestamp());
            } catch (Exception $exception) {
                // Logiquement, ça ne devrait pas arriver...
                $payment->timepaid = null;
            }
        } else if ($showalltransactions === 0) {
            // On ne traite pas ce paiement si la date de paiement n'est pas définie et qu'on n'affiche pas toutes les transactions.
            continue;
        }

        // Affiche le préfixe PayBox.
        $payment->prefix = '';
        if (isset($centers[$payment->paymentcenterid]) === true && empty($centers[$payment->paymentcenterid]->prefix) === false) {
            $payment->prefix = $centers[$payment->paymentcenterid]->prefix;
        }

        $format = new NumberFormatter('fr_FR', NumberFormatter::CURRENCY);
        $payment->amount_string = $format->formatCurrency($payment->amount, 'EUR');
        $payment->method_string = get_string('method_'.$payment->method, 'local_apsolu');
        $payment->source_string = get_string('source_'.$payment->source, 'local_apsolu');

        switch ($payment->status) {
            case Payment::PAID:
                $payment->status_style = 'table-success';
                $payment->status_string = get_string('paymentpaid', 'local_apsolu');
                break;
            case Payment::GIFT:
                $payment->amount_string = '-';
                $payment->method_string = '-';
                $payment->status_style = 'table-info';
                $payment->status_string = get_string('paymentgift', 'local_apsolu');
                break;
            case Payment::DUE:
            default:
                $payment->status_style = 'table-danger';
                $payment->status_string = get_string('paymentdue', 'local_apsolu');
        }

        $payment->items = [];
        $payment->count_items = 0;
        $sql = "SELECT api.*, apc.fullname".
            " FROM {apsolu_payments_items} api".
            " JOIN {apsolu_payments_cards} apc ON apc.id = api.cardid".
            " WHERE api.paymentid = :paymentid";
        $items = $DB->get_records_sql($sql, ['paymentid' => $payment->id]);
        foreach ($items as $item) {
            $payment->items[] = $item->fullname;
            $payment->count_items++;
        }

        $data->payments[] = $payment;
        $data->count_payments++;
    }

    $data->backurl = $CFG->wwwroot.'/local/apsolu/payment/admin.php?tab=payments';
} else {
    // Create the user selector objects.
    $options = ['multiselect' => false];
    $userselector = new \UniversiteRennes2\Apsolu\local_apsolu_payment_user_selector('userid', $options);
    ob_start();
    $userselector->display();
    $userselector = ob_get_contents();
    ob_end_clean();

    $data = new stdClass();
    $data->wwwroot = $CFG->wwwroot;
    $data->action = $CFG->wwwroot.'/local/apsolu/payment/admin.php?tab=payments';
    $data->user_selector = $userselector;
}

if (isset($notification)) {
    echo $notification;
}

$data->show_all_transactions = ($showalltransactions === 1);
$data->payments_centers = array_values($DB->get_records('apsolu_payments_centers', $conditions = null, $sort = 'name'));
$data->count_payments_centers = count($data->payments_centers);

echo $OUTPUT->render_from_template('local_apsolu/payment_payments', $data);
