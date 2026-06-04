<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Script de confirmation pour les paiements intégralement réglés par Atouts Normandie.
 * * Ce fichier est appelé lorsqu'un utilisateur clique sur "Confirmer ma commande"
 * après avoir appliqué une réduction Atouts égale au montant total dû.
 * Il déclenche l'appel SOAP final et valide l'inscription dans Moodle.
 *
 * @package    local_apsolu
 * @copyright  2026 Université de Caen Normandie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\atouts_manager;
use UniversiteRennes2\Apsolu\Payment;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/local/apsolu/classes/apsolu/payment.php');

// Récupération de l'ID du paiement Apsolu.
$id = required_param('id', PARAM_INT);

// SÉCURITÉ.
// L'utilisateur doit être connecté.
require_login();
// Le jeton de session (sesskey) est obligatoire pour prévenir les validations forcées (CSRF).
require_sesskey();

// 1. RÉCUPÉRATION DES DONNÉES
// On récupère le paiement (seulement s'il appartient à l'utilisateur et qu'il est encore en attente : status=0).
$payment = $DB->get_record('apsolu_payments', ['id' => $id, 'userid' => $USER->id, 'status' => 0], '*', MUST_EXIST);
// On récupère l'enregistrement de la réduction Atouts liée.
$atoutrecord = $DB->get_record('apsolu_atouts_payments', ['paymentid' => $id], '*', MUST_EXIST);

// VÉRIFICATION DE COHÉRENCE.
// Sécurité critique : On s'assure que le montant Atouts est suffisant pour clore la commande.
// Si ce n'est pas le cas, l'utilisateur doit repasser par le tunnel de paiement standard (Paybox).
if ($atoutrecord->amount < $payment->amount) {
    throw new moodle_exception('error_amount_mismatch', 'local_apsolu', $CFG->wwwroot . '/local/apsolu/payment/validation.php');
}

try {
    // 2. TRANSACTION RÉELLE (DÉBIT SOAP)
    // On contacte le serveur Dialog Atouts Normandie pour valider le débit.
    // Cette méthode met à jour la table {apsolu_atouts_payments} en cas de succès.
    $success = atouts_manager::effectuer_debit_final($atoutrecord->id);

    if ($success) {
        // On recharge l'objet paiement pour garantir l'intégrité des données avant modification.
        $payment = $DB->get_record('apsolu_payments', ['id' => $id]);

        // 3. MISE À JOUR DU STATUT MOODLE
        // On marque le paiement comme validé dans la table principale {apsolu_payments}.
        $payment->status = 1; // Défini comme payé.
        $payment->method = 'atouts'; // On précise la méthode pour le suivi administratif.
        $payment->timepaid = date('Y-m-d\TH:i:s'); // Format attendu par le plugin original.
        $payment->timemodified = $payment->timepaid;
        $DB->update_record('apsolu_payments', $payment);

        // 4. FINALISATION DE L'INSCRIPTION
        // On déclenche la méthode interne du plugin Apsolu qui gère l'inscription effective
        // de l'étudiant aux cours/activités et l'envoi des notifications.
        Payment::confirm_payment($payment);

        // Redirection vers la page de validation avec un message de succès persistant.
        $url = new moodle_url('/local/apsolu/payment/validation.php');
        redirect(
            $url,
            "Votre paiement Atouts Normandie a été validé. Vous pouvez maintenant accéder à vos activités.",
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } else {
        // Cas où le serveur Atouts répond mais refuse la transaction.
        throw new Exception("Le débit de votre carte Atouts a échoué auprès du serveur régional.");
    }
} catch (Exception $e) {
    // GESTION DES ERREURS
    // En cas d'échec (réseau, solde, expiration), on logue l'erreur pour l'admin et on informe l'utilisateur.
    debugging("Erreur Atouts : " . $e->getMessage());
    $msg = $e->getMessage();
    redirect(new moodle_url('/local/apsolu/payment/validation.php'), $msg, null, \core\output\notification::NOTIFY_ERROR);
}
