<?php
/**
 * Gestionnaire des interactions avec l'API Atouts Normandie (Dialog).
 * * Cette classe centralise tous les appels vers le WebService SOAP de Dialog.
 * Elle gère l'authentification double (JWT + Token de session), la récupération
 * de solde, le débit final et l'annulation des transactions.
 *
 * @package    local_apsolu
 * @copyright  2025 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_apsolu\core;

defined('MOODLE_INTERNAL') || die();

class atouts_manager {

    /**
     * Génère un jeton JWT (JSON Web Token) pour sécuriser l'enveloppe HTTP des requêtes.
     * * Le serveur Dialog exige un JWT signé en HS256 dans le header "Authorization".
     * Note : Ce jeton est différent du Token de session métier obtenu via AuthentificationExtranet.
     * * @return string Le jeton JWT encodé au format header.payload.signature.
     */
    public static function get_jwt() {
        $key = get_config('local_apsolu', 'atouts_jwt_cle');
        $issuer = get_config('local_apsolu', 'atouts_jwt_issuer');
        $audience = get_config('local_apsolu', 'atouts_jwt_audience');

        // On définit une expiration très lointaine pour éviter les renouvellements inutiles.
        $ts_limite = "2100-01-01T00:00:00";

        $header = json_encode(["typ" => "JWT", "alg" => "HS256"]);
        $payload = json_encode([
            "iss" => $issuer,
            "aud" => $audience,
            "exp" => $ts_limite,
            "signingCredentials" => $key
        ]);

        // Encodage Base64URL (remplacement des caractères + / = par - _ et vide).
        $b64header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $b64payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        // Signature HMAC SHA256 avec la clé secrète fournie par Atouts Normandie.
        $signature = hash_hmac('sha256', $b64header . "." . $b64payload, $key, true);
        $b64sig = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return $b64header . "." . $b64payload . "." . $b64sig;
    }

    /**
     * Analyse le XML de réponse SOAP pour identifier des erreurs logiques ou techniques.
     * * @param string $response Le corps XML retourné par le serveur Dialog.
     * @return string|null Retourne une description de l'erreur ou null si la réponse est valide.
     */
    public static function parse_soap_error($response) {
        if (empty($response)) {
            return "Réponse vide du serveur.";
        }

        // 1. Extraction du code d'erreur métier Dialog (ex: <b:codeErreur>EXTCHEQ009</b:codeErreur>).
        $code = null;
        if (preg_match('/<[^:>]*:?codeErreur>(.*?)<\/[^:>]*:?codeErreur>/', $response, $matches)) {
            $code = trim($matches[1]);
        }

        // Codes '0' (succès) ou 'EXTCHEQ000' (valide) ne sont pas des erreurs.
        if ($code && $code !== '0' && $code !== 'EXTCHEQ000') {
            $message = "";
            if (preg_match('/<[^:>]*:?message>(.*?)<\/[^:>]*:?message>/', $response, $msg_matches)) {
                $message = " : " . trim($msg_matches[1]);
            }

            // Dictionnaire des erreurs connues pour faciliter le support utilisateur/admin.
            $explanations = [
                // Authentification
                'EXTCHEQ001' => 'Paramètre d’entrée incorrect.',
                'EXTCHEQ003' => 'Problème interne lors de la mise à jour.',
                'EXTCHEQ014' => 'Identifiant ou mot de passe incorrect ou solde insuffisant.',
                'EXTCHEQ015' => 'Mot de passe expiré.',
                'EXTCHEQ067' => 'Compte bloqué (mot de passe).',
                'EXTCHEQ009' => 'Carte non valide ou aucun résultat pour cette recherche (QR Code inconnu).',
                'EXTCHEQ032' => 'Partenaire non conventionné pour cet article.',
                'EXTCHEQ074' => 'Authentification partenaire incorrecte ou incohérente avec le token.',
            ];

            $desc = isset($explanations[$code]) ? $explanations[$code] : "Erreur métier non répertoriée";
            return "[$code] $desc$message";
        }

        // 2. Capture des erreurs SOAP standards (Fautes de protocole ou crash serveur).
        if (preg_match('/<[^:>]*:?Reason>.*?<[^:>]*:?Text[^>]*>(.*?)<\/[^:>]*:?Text>.*?<\/[^:>]*:?Reason>/s', $response, $matches)) {
            return "Erreur SOAP : " . trim($matches[1]);
        }

        // Backup Fault pour SOAP 1.1
        if (preg_match('/<faultstring>(.*?)<\/faultstring>/', $response, $matches)) {
            return "Erreur SOAP (v1) : " . trim($matches[1]);
        }

        return null;
    }

    /**
     * Envoie une requête SOAP structurée vers l'URL Dialog via l'API cURL de Moodle.
     * * @param string $action Nom de l'action SOAP (utilisée pour l'en-tête Action et le Header WCF).
     * @param string $xml_body Fragment XML contenant les paramètres spécifiques à l'action.
     * @return string La réponse brute (XML) du serveur.
     * @throws \Exception Si une erreur réseau survient ou si l'URL est invalide.
     */
    public static function send_request($action, $xml_body) {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $url = get_config('local_apsolu', 'atouts_ws_url');
        // $jwt = self::get_jwt();
        // Pour les tests
        $jwt = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJQUkVTVEEgVEVTVCIsImF1ZCI6Im1lbWJlcnoiLCJleHAiOiIyMTAwLTAxLTAxVDAwOjAwOjAwIiwic2lnbmluZ0NyZWRlbnRpYWxzIjoiYTc3ODlkMmItYzFlZi00OWVmLTliNTQtNTQyNTc4MDQwMDdjIn0.Oj7E7SUXRlNzEXe3s91ik4z4WE0exzzIIx63Tq6FVik';

        $curl = new \curl();

        $curl->setHeader("Authorization: Bearer $jwt");
        $curl->setHeader("Content-Type: application/soap+xml;charset=UTF-8;action=\"http://tempuri.org/ITransactionPartenaire/$action\"");
        $curl->setHeader("Accept-Encoding: gzip,deflate");
        $curl->setHeader("Accept-Language: fr-FR");

        // Construction de l'enveloppe SOAP 1.2 complète.
        $full_xml = '<?xml version="1.0" encoding="UTF-8"?>
        <soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" xmlns:tem="http://tempuri.org/"
                       xmlns:wcf="http://schemas.datacontract.org/2004/07/WcfExtranetChequeBL.POCO.Authentification"
                       xmlns:wcf1="http://schemas.datacontract.org/2004/07/WcfExtranetChequeBL.POCO.Campagne"
                       xmlns:wcf2="http://schemas.datacontract.org/2004/07/WcfExtranetChequeBL.POCO.Commun"
                       xmlns:wcf3="http://schemas.datacontract.org/2004/07/WcfExtranetChequeBL.POCO.Carte"
                       xmlns:wcf4="http://schemas.datacontract.org/2004/07/WcfExtranetChequeBL.POCO.Beneficiaire">
            <soap:Header xmlns:wsa="http://www.w3.org/2005/08/addressing">
                <wsa:Action>http://tempuri.org/ITransactionPartenaire/' . $action . '</wsa:Action>
                <wsa:To>' . $url . '</wsa:To>
            </soap:Header>
            <soap:Body>' . $xml_body . '</soap:Body>
        </soap:Envelope>';

        $response = $curl->post($url, $full_xml);

        // Journalisation pour le mode développeur (très utile pour débugger les trames XML).
        if (debugging('', DEBUG_DEVELOPER)) {
            $logdir = make_temp_directory('local_apsolu');
            $logfile = $logdir . '/atouts_soap.log';
            $logdata = date('Y-m-d H:i:s') . " | ACTION: $action\n";
            $logdata .= "REQUEST:\n$full_xml\n";
            if ($curl->get_errno()) {
                $logdata .= "CURL ERROR: " . $curl->error . " (Code: " . $curl->get_errno() . ")\n";
            }
            $logdata .= "RESPONSE:\n$response\n";
            $logdata .= "----------------------------------------------------------\n";
            file_put_contents($logfile, $logdata, FILE_APPEND);
        }

        if ($curl->get_errno()) {
            throw new \Exception("Erreur de connexion réseau ($action) : " . $curl->error);
        }

        return $response;
    }

    /**
     * Étape 1 de la transaction métier : Récupère un tokenId (session temporaire).
     * * Ce jeton identifie la session du partenaire Moodle auprès du serveur Dialog.
     * Il est obligatoire pour toutes les opérations suivantes (GetSoldesBeneficiaire, DebiteBeneficiaire, etc.).
     * * @return string Le tokenId à injecter dans les prochaines requêtes.
     */
    private static function get_session_token() {
        $site_id = get_config('local_apsolu', 'atouts_numero_site');
        $type    = get_config('local_apsolu', 'atouts_type_individu');
        $id      = get_config('local_apsolu', 'atouts_numero_partenaire');
        $pwd     = get_config('local_apsolu', 'atouts_pwd_partenaire');

        $xml_auth = '
        <tem:AuthentificationExtranet>
            <tem:numSite>' . $site_id . '</tem:numSite>
            <tem:authentification>
                <wcf:typeIndividu>' . $type . '</wcf:typeIndividu>
                <wcf:idIndividu>' . $id . '</wcf:idIndividu>
                <wcf:pwd>' . $pwd . '</wcf:pwd>
            </tem:authentification>
        </tem:AuthentificationExtranet>';

        $response = self::send_request('AuthentificationExtranet', $xml_auth);

        if (preg_match('/<b:tokenId>(.*?)<\/b:tokenId>/', $response, $matches)) {
            return $matches[1];
        }

        $error = self::parse_soap_error($response);
        throw new \Exception($error ?: "Échec de l'authentification Atouts (Token non reçu).");
    }

    /**
     * Interroge le serveur Atouts pour connaître le solde d'un article spécifique sur une carte (Action GetSoldesBeneficiaire).
     * * @param string $nocarte Le numéro de carte saisi par l'étudiant.
     * @return float Le solde disponible converti en Euros.
     */
    public static function get_solde_disponible($nocarte) {
        $site_id       = get_config('local_apsolu', 'atouts_numero_site');
        $id_partenaire = get_config('local_apsolu', 'atouts_numero_partenaire');
        $id_article    = get_config('local_apsolu', 'atouts_numero_article');

        // On récupère d'abord le token de session
        $token_id = self::get_session_token();

        $xml_solde = '
        <tem:GetSoldesBeneficiaire>
            <tem:numeroSite>' . $site_id . '</tem:numeroSite>
            <tem:filtresSoldesBeneficiaire>
                <wcf:numeroCarte>' . $nocarte . '</wcf:numeroCarte>
                <wcf:numeroPartenaire>' . $id_partenaire . '</wcf:numeroPartenaire>
                <wcf:numeroArticle>' . $id_article . '</wcf:numeroArticle>
            </tem:filtresSoldesBeneficiaire>
            <tem:tokenId>' . $token_id . '</tem:tokenId>
        </tem:GetSoldesBeneficiaire>';

        $response = self::send_request('GetSoldesBeneficiaire', $xml_solde);

        if (!$response) {
            throw new \Exception('Le serveur Atouts ne répond pas.');
        }

        // Recherche du montant en centimes dans la balise <b:solde>.
        if (preg_match('/<b:solde>(.*?)<\/b:solde>/', $response, $matches)) {
            $solde_centimes = (int)$matches[1];

            // On retourne directement en Euros pour simplifier le JS
            return $solde_centimes / 100;
        }

        // Gestion d'erreur métier (ex: carte inconnue)
        $error_detail = self::parse_soap_error($response);
        throw new \Exception($error_detail ?: "Impossible de récupérer le solde.");
    }

    /**
     * Valide définitivement une réduction Atouts en débitant le serveur Dialog (Action DebiteBeneficiaire).
     * * Cette méthode doit être appelée uniquement après confirmation du paiement CB
     * (ou validation manuelle). Elle met à jour le statut et le ticket de transaction en base.
     * * @param int $atout_record_id ID interne de l'enregistrement de pré-paiement.
     * @return bool True si le débit est confirmé par Dialog.
     */
    public static function effectuer_debit_final($atout_record_id) {
        global $DB;

        $record = $DB->get_record('apsolu_atouts_payments', ['id' => $atout_record_id], '*', MUST_EXIST);

        // On évite les doubles débits si le script est relancé par erreur.
        if ($record->status == 1) {
            return true;
        }

        $site_id       = get_config('local_apsolu', 'atouts_numero_site');
        $id_partenaire = get_config('local_apsolu', 'atouts_numero_partenaire');
        $id_article    = get_config('local_apsolu', 'atouts_numero_article');

        // 1. On récupère d'abord le token de session
        $token_id = self::get_session_token();

        $montant_centimes = (int)round($record->amount * 100);
        $no_ticket = time(); // Identifiant de transaction envoyé par Moodle.

        $xml_debite = '
        <tem:DebiteBeneficiaire>
            <tem:numeroSite>' . $site_id . '</tem:numeroSite>
            <tem:filtresSoldesBeneficiaire>
                <wcf3:numeroCarte>' . $record->nocarte . '</wcf3:numeroCarte>
                <wcf3:numeroPartenaire>' . $id_partenaire . '</wcf3:numeroPartenaire>
            </tem:filtresSoldesBeneficiaire>
            <tem:debitCompte>
                <wcf3:codeArticle>' . $id_article . '</wcf3:codeArticle>
                <wcf3:montant>' . $montant_centimes . '</wcf3:montant>
                <wcf3:numeroTicket>' . $no_ticket . '</wcf3:numeroTicket>
            </tem:debitCompte>
            <tem:tokenId>' . $token_id . '</tem:tokenId>
        </tem:DebiteBeneficiaire>';

        $response = self::send_request('DebiteBeneficiaire', $xml_debite);

        // On tente de récupérer le numéro de dossier définitif généré par Dialog.
        preg_match('/<b:numeroDossier>(.*?)<\/b:numeroDossier>/', $response, $matches);
        $ticket_reel = (isset($matches[1])) ? $matches[1] : $no_ticket;

        // Si la réponse contient un solde, Dialog a accepté la transaction.
        if (strpos($response, 'b:solde') !== false) {
            $record->status = 1; // MARQUÉ COMME DÉBITÉ RÉELLEMENT
            $record->ticket = $ticket_reel; // On enregistre le vrai numéro
            $record->timemodified = time();
            $DB->update_record('apsolu_atouts_payments', $record);
            return true;
        }

        $error = self::parse_soap_error($response);
        throw new \Exception($error ?: "Erreur inconnue lors du débit Atouts.");
    }

    /**
     * Annule un débit Atouts Normandie.
     * @param int $paymentid L'ID de la commande principale.
     * @return bool
     */
    public static function annule_debit($paymentid) {
        global $DB;

        $atout_payment = $DB->get_record('apsolu_atouts_payments', ['paymentid' => $paymentid, 'status' => 1]);
        if (!$atout_payment) {
            return false;
        }

        $site_id = get_config('local_apsolu', 'atouts_numero_site');
        $id_partenaire = get_config('local_apsolu', 'atouts_numero_partenaire');
        $id_article = get_config('local_apsolu', 'atouts_numero_article');
        $nocarte = $atout_payment->nocarte;
        $date_ticket = date('d/m/Y', $atout_payment->ticket);
        $ws_montant = (int)round($atout_payment->amount * 100);

        // 1. On récupère d'abord le token de session
        $token_id = self::get_session_token();

        $xml_annulation = '
        <tem:AnnuleDebitBeneficiaire>
          <tem:numeroSite>'.$site_id.'</tem:numeroSite>
          <tem:filtresSoldesBeneficiaire>
            <wcf3:numeroCarte>'.$nocarte.'</wcf3:numeroCarte>
            <wcf3:numeroPartenaire>'.$id_partenaire.'</wcf3:numeroPartenaire>
          </tem:filtresSoldesBeneficiaire>
          <tem:debitCompte>
            <wcf3:codeArticle>'.$id_article.'</wcf3:codeArticle>
            <wcf3:montant>'.$ws_montant.'</wcf3:montant>
            <wcf3:numeroTicket>'.$atout_payment->ticket.'</wcf3:numeroTicket>
            <wcf3:date>'.$date_ticket.'</wcf3:date>
          </tem:debitCompte>
          <tem:tokenId>'.$token_id.'</tem:tokenId>
        </tem:AnnuleDebitBeneficiaire>';

        try {
            $response = self::send_request('AnnuleDebitBeneficiaire', $xml_annulation);
            if ($response && strpos($response, 'b:solde') !== false) {
                $atout_payment->status = 2;
                $DB->update_record('apsolu_atouts_payments', $atout_payment);
                return true;
            }
        } catch (\Exception $e) { }

        return false;
    }
}
