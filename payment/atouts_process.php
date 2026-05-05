<?php
/**
 * Point d'entrée AJAX pour l'intégration Atouts Normandie.
 * * Ce script traite les requêtes asynchrones provenant de l'interface de paiement :
 * 1. Vérification du solde d'une carte via le WebService Dialog.
 * 2. Enregistrement d'une intention de réduction (pré-paiement) en base de données.
 *
 * @package    local_apsolu
 * @copyright  2025 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\atouts_manager;

// Indique à Moodle que ce script est une réponse AJAX (désactive les headers HTML standards).
define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');

// --- SÉCURITÉ MOODLE ---
// Vérifie que l'utilisateur est bien authentifié sur la plateforme.
require_login();
// Protection contre les failles CSRF : vérifie que la requête vient bien du formulaire Moodle.
require_sesskey();

// Récupération des paramètres obligatoires.
// PARAM_ALPHAEXT : Autorise lettres, chiffres, tirets et underscores.
$action    = required_param('action', PARAM_ALPHAEXT);
// PARAM_INT : Force la conversion en entier pour éviter les injections SQL.
$paymentid = required_param('paymentid', PARAM_INT);

// On définit le type de contenu en JSON pour une interprétation correcte par le JavaScript.
header('Content-Type: application/json');

try {
    // 1. VÉRIFICATION DE LA COMMANDE
    // On s'assure que le paiement appartient bien à l'utilisateur connecté (sécurité transversale).
    $payment = $DB->get_record('apsolu_payments', ['id' => $paymentid, 'userid' => $USER->id], '*', MUST_EXIST);

    // On interdit toute modification si le paiement a déjà été traité (status != 0).
    if ($payment->status != 0) {
        throw new Exception('Ce paiement a déjà été validé.');
    }

    switch ($action) {

        /**
         * Action : check_balance
         * Interroge l'API Atouts pour obtenir le solde d'une carte spécifique.
         */
        case 'check_balance':
            // PARAM_RAW : On garde le numéro tel quel (peut contenir des caractères spéciaux ou QR Code).
            $nocarte = required_param('nocarte', PARAM_RAW);

            // Appel au manager pour la communication SOAP pour la récupération du solde.
            $solde_eur = atouts_manager::get_solde_disponible($nocarte);

            echo json_encode([
                'success'      => true,
                'solde_raw'    => $solde_eur, // Valeur numérique pour calculs JS.
                'solde_format' => number_format($solde_eur, 2, ',', ' ') . ' €' // Valeur formatée pour l'affichage.
            ]);
            break;

        /**
         * Action : apply_deduction
         * Enregistre le montant que l'utilisateur souhaite déduire via sa carte Atouts.
         */
        case 'apply_deduction':
            $amount  = required_param('amount', PARAM_FLOAT);
            // On récupère à nouveau le nocarte ici pour l'enregistrement.
            $nocarte = required_param('nocarte', PARAM_RAW);

            if (empty($nocarte)) {
                throw new Exception("Erreur technique : Le numéro de carte n'a pas été transmis par le navigateur.");
            }

            // --- SÉCURITÉ MÉTIER ---
            // On sature le montant : l'étudiant ne peut pas déduire plus que le total de sa commande.
            if ($amount > $payment->amount) {
                $amount = $payment->amount;
            }

            // Préparation de l'enregistrement pour la table {apsolu_atouts_payments}.
            $atout_record = new stdClass();
            $atout_record->paymentid   = $paymentid;
            $atout_record->userid      = $USER->id;
            $atout_record->nocarte     = trim($nocarte); // Nettoyage des espaces pour la BDD.
            $atout_record->amount      = $amount;
            $atout_record->status      = 0; // 0 = En attente (sera passé à 1 par paybox.php lors du succès CB).
            $atout_record->ticket      = 0;
            $atout_record->timecreated = time();

            // Gestion de l'unicité : Un seul avantage Atouts par paiement Apsolu.
            $existing = $DB->get_record('apsolu_atouts_payments', ['paymentid' => $paymentid]);

            if ($existing) {
                // Mise à jour si l'utilisateur change d'avis sur le montant avant de payer.
                $atout_record->id = $existing->id;
                $DB->update_record('apsolu_atouts_payments', $atout_record);
            } else {
                // Création du premier enregistrement.
                $DB->insert_record('apsolu_atouts_payments', $atout_record);
            }

            echo json_encode([
                'success' => true,
                'message' => "La réduction de " . number_format($amount, 2) . " € a été préparée."
            ]);
            break;

        /**
         * Action : cancel_deduction
         * Annule le montant que l'utilisateur souhaite déduire via sa carte Atouts.
         */
        case 'cancel_deduction':
            // Suppression de la réduction associée à ce paiement pour cet utilisateur
            $DB->delete_records('apsolu_atouts_payments', [
                'paymentid' => $paymentid,
                'userid' => $USER->id
            ]);

            echo json_encode([
                'success' => true,
                'message' => "La réduction a été supprimée."
            ]);
            break;

        default:
            throw new Exception('Action non reconnue.');
    }

} catch (Exception $e) {
    // Capture de toutes les erreurs (SQL, API, Logique) pour un retour propre au format JSON.
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}

// Fin du script, empêche toute sortie de texte parasite après le JSON.
exit;
