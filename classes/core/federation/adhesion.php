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

namespace local_apsolu\core\federation;

use context_course;
use DateTime;
use Exception;
use local_apsolu\core\federation\course as FederationCourse;
use action_menu;
use action_menu_link_secondary;
use local_apsolu\core\federation\activity;
use local_apsolu\core\federation\number;
use local_apsolu\core\record;
use local_apsolu\event\federation_adhesion_updated;
use local_apsolu\event\notification_sent;
use moodle_url;
use pix_icon;
use stdClass;
use UniversiteRennes2\Apsolu\Payment;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/cohort/lib.php');

/**
 * Classe gérant les adhésions.
 *
 * @package    local_apsolu
 * @copyright  2022 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class adhesion extends record {
    /**
     * Nom de la table de référence en base de données.
     */
    const TABLENAME = 'apsolu_federation_adhesions';

    /**
     * Valeur du questionnaire médical lorsque toutes les réponses données sont négatives.
     */
    const HEALTH_QUESTIONNAIRE_ANSWERED_NO = '0';

    /**
     * Valeur du questionnaire médical lorsqu'au moins une réponse donnée est positive.
     */
    const HEALTH_QUESTIONNAIRE_ANSWERED_YES_ONCE = '1';

    /**
     * État du certificat médical lorsqu'il est en attente de validation.
     */
    const MEDICAL_CERTIFICATE_STATUS_PENDING = '0';

    /**
     * État du certificat médical lorsqu'il a été validé.
     */
    const MEDICAL_CERTIFICATE_STATUS_VALIDATED = '1';

    /**
     * État du certificat médical lorsqu'il ne doit pas être fourni.
     */
    const MEDICAL_CERTIFICATE_STATUS_EXEMPTED = '2';

    /**
     * Pattern du Pass Sport.
     *
     * Le pass Sport prend la forme d'un code composé de 10 caractères alphanumériques, différents de ceux de 2024.
     *     Exemple : 25-XXXXX-XXXXX.
     * Source: https://www.pass.sports.gouv.fr/v2/jeunes-et-parents#activate-code
     */
    const PASS_SPORT_PATTERN = '/^[0-9]{2}-[A-Za-z0-9]{4}-[A-Za-z0-9]{4}$/';

    /**
     * État du paiement par Pass Sport lorsqu'il est en attente de validation.
     */
    const PASS_SPORT_STATUS_PENDING = '0';

    /**
     * État du paiement par Pass Sport lorsqu'il a été validé.
     */
    const PASS_SPORT_STATUS_VALIDATED = '1';

    /**
     * Valeur d'un champ d'adhésion caché.
     */
    const FIELD_HIDDEN = '0';

    /**
     * Valeur d'un champ d'adhésion visible.
     */
    const FIELD_VISIBLE = '1';

    /**
     * Valeur d'un champ d'adhésion verrouillé.
     */
    const FIELD_LOCKED = '2';

    /** @var int|string $id Identifiant numérique de la correspondance d'activités. */
    public $id = 0;

    /** @var string $birthday Date de naissance de l'adhérent (AAAA-MM-JJ). */
    public $birthday = '';

    /** @var int|string $medicalcertificatestatus État de validation du certificat médical. */
    public $medicalcertificatestatus = null;

    /** @var int|string $questionnairestatus État des réponses au questionnaire médical. */
    public $questionnairestatus = null;

    /** @var int|string $agreementaccepted État d'acceptation de la charte. */
    public $agreementaccepted = null;

    /** @var string $passsportnumber Numéro Pass Sport de l'adhérant. */
    public $passsportnumber = null;

    /** @var int|string $passsportstatus État de paiement avec Pass Sport. */
    public $passsportstatus = null;

    /** @var string $federationnumberprefix Préfixe utilisé pour le numéro FFSU (4 caractères). */
    public $federationnumberprefix = null;

    /** @var string $federationnumber Numéro FFSU de l'adhérant. */
    public $federationnumber = null;

    /** @var int|string $federationnumberrequestdate Timestamp Unix de la demande de numéro de licence. */
    public $federationnumberrequestdate = null;

    /** @var string $data Données du formulaire d'adhésion saisies par l'étudiant au format JSON. */
    public $data = null;

    /** @var int|string $timemodified Timestamp Unix de création de l'adhésion. */
    public $timecreated = 0;

    /** @var int|string $timemodified Timestamp Unix de modification de l'adhésion. */
    public $timemodified = 0;

    /** @var int|string $userid Identifiant numérique de l'adhérant. */
    public $userid = '';

    /**
     * Déterminer si un certificat médical peut être modifié / qu'il n'a pas été validé.
     *
     * @return boolean
     */
    public function can_edit() {
        return empty($this->federationnumberrequestdate) === true;
    }

    /**
     * Détermine si l'adhérent peut faire une demande de numéro de licence.
     *
     * @return boolean
     */
    public function can_request_a_federation_number() {
        global $CFG;

        require_once($CFG->dirroot . '/local/apsolu/classes/apsolu/payment.php');

        if (empty($this->federationnumber) === false) {
            // Un numéro de licence a déjà été attribué.
            return false;
        }

        if (empty($this->federationnumberrequestdate) === false) {
            // Une demande de licence est déjà en cours.
            return false;
        }

        if ($this->questionnairestatus === null) {
            // Le questionnaire médical n'a pas été rempli.
            return false;
        }

        if (empty($this->agreementaccepted) === true) {
            // La charte n'a pas été acceptée.
            return false;
        }

        if (strpos($this->data, '"federaltexts":"1"') === false) {
            // Le formulaire d'adhésion n'a pas été rempli.
            return false;
        }

        $federationcourse = new FederationCourse();
        $federationcourse->id = $federationcourse->get_courseid();

        if ($this->have_to_upload_parental_authorization() === true) {
            $fs = get_file_storage();
            $context = context_course::instance($federationcourse->id, MUST_EXIST);
            [$component, $filearea, $itemid] = ['local_apsolu', 'parentalauthorization', $this->userid];
            $sort = 'itemid, filepath, filename';
            $files = $fs->get_area_files($context->id, $component, $filearea, $itemid, $sort, $includedirs = false);
            if (count($files) === 0) {
                // Aucune autorisation parentale n'a été déposée.
                return false;
            }
        }

        if ($this->have_to_upload_medical_certificate() === true) {
            $fs = get_file_storage();
            $context = context_course::instance($federationcourse->id, MUST_EXIST);
            [$component, $filearea, $itemid] = ['local_apsolu', 'medicalcertificate', $this->userid];
            $sort = 'itemid, filepath, filename';
            $files = $fs->get_area_files($context->id, $component, $filearea, $itemid, $sort, $includedirs = false);
            if (count($files) === 0) {
                // Aucun certificat médical déposé.
                return false;
            }
        }

        foreach (Payment::get_user_cards_status_per_course($federationcourse->id, $this->userid) as $card) {
            if ($card->status !== Payment::DUE) {
                continue;
            }

            // Un paiement est du.
            return false;
        }

        // L'adhérent peut valider sa demande de licence.
        return true;
    }

    /**
     * Décode les données JSON du champ data.
     *
     * @return object
     */
    public function decode_data() {
        if (is_string($this->data) === false) {
            return new stdClass();
        }

        $json = json_decode($this->data);
        if ($json === false) {
            return new stdClass();
        }

        return $json;
    }

    /**
     * Retourne le menu d'actions permettant aux gestionnaires de valider ou refuser une adhésion sur la page de validation des
     * certificats.
     *
     * @param int|string $userid Identifiant de l'utilisateur ayant réalisé une demande.
     * @param int|string $contextid Identifiant du contexte du cours FFSU.
     *
     * @return action_menu|null Retourne null si aucune action n'est disponible pour l'utilisateur donné.
     */
    public function get_action_menu(int|string $userid, int|string $contextid): ?action_menu {
        if (empty($this->federationnumberrequestdate) === true) {
            // L'utilisateur n'a pas encore validé sa demande.
            return null;
        }

        $menuoptions = [];

        $attributes = [
            'class' => 'local-apsolu-federation-medical-certificate-validation',
            'data-contextid' => $contextid,
            'data-target-validation' => self::MEDICAL_CERTIFICATE_STATUS_VALIDATED,
            'data-target-validation-color' => 'table-success',
            'data-target-validation-text' => get_string('medical_certificate_validated', 'local_apsolu'),
            'data-users' => $userid,
        ];

        if ($this->medicalcertificatestatus !== self::MEDICAL_CERTIFICATE_STATUS_EXEMPTED) {
            // Option permettant la validation du certificat.
            $attributes['data-stringid'] = 'medical_certificate_validation_message';
            $menuoptions[] = [
                'attributes' => $attributes,
                'icon' => 'i/grade_correct',
                'label' => get_string('validate', 'local_apsolu'),
            ];

            // Option permettant le refus du certificat (raison: plus d'un an).
            $attributes['data-stringid'] = 'medical_certificate_refusal_message_for_one_year_expiration';
            $attributes['data-target-validation'] = self::MEDICAL_CERTIFICATE_STATUS_PENDING;
            $attributes['data-target-validation-color'] = 'table-warning';
            $attributes['data-target-validation-text'] = get_string('medical_certificate_not_validated', 'local_apsolu');
            $reason = strtolower(get_string('more_than_one_year', 'local_apsolu'));
            $menuoptions[] = [
                'attributes' => $attributes,
                'icon' => 'i/grade_incorrect',
                'label' => get_string('refuse_with_reasons_X', 'local_apsolu', $reason),
            ];

            // Option permettant le refus du certificat (raison: plus de 6 mois).
            $attributes['data-stringid'] = 'medical_certificate_refusal_message_for_six_months_expiration';
            $reason = strtolower(get_string('more_than_six_months', 'local_apsolu'));
            $menuoptions[] = [
                'attributes' => $attributes,
                'icon' => 'i/grade_incorrect',
                'label' => get_string('refuse_with_reasons_X', 'local_apsolu', $reason),
            ];

            // Option permettant le refus du certificat (raison: mention du sport manquante).
            $attributes['data-stringid'] = 'medical_certificate_refusal_message_for_mention_missing';
            $reason = strtolower(get_string('mention_missing', 'local_apsolu'));
            $menuoptions[] = [
                'attributes' => $attributes,
                'icon' => 'i/grade_incorrect',
                'label' => get_string('refuse_with_reasons_X', 'local_apsolu', $reason),
            ];

            if (
                empty($this->federationnumber) === true &&
                $this->medicalcertificatestatus === Adhesion::MEDICAL_CERTIFICATE_STATUS_VALIDATED
            ) {
                $menuoptions[0]['attributes']['class'] .= ' d-none'; // Supprime l'option de validation.
            }
        } else {
            $attributes['data-stringid'] = 'medical_certificate_refusal_message';
            $attributes['data-target-validation'] = self::MEDICAL_CERTIFICATE_STATUS_EXEMPTED;
            if ($this->have_to_upload_parental_authorization() === true) {
                $attributes['data-target-validation-color'] = 'table-default';
                $attributes['data-target-validation-text'] = '';
            } else {
                $attributes['data-target-validation-color'] = 'table-info';
                $attributes['data-target-validation-text'] = get_string('medical_certificate_not_required', 'local_apsolu');
            }
            $menuoptions[] = [
                'attributes' => $attributes,
                'icon' => 'i/grade_incorrect',
                'label' => get_string('refuse', 'local_apsolu'),
            ];
        }

        $actionmenu = new action_menu();
        $actionmenu->attributessecondary['class'] .= ' apsolu-dropdown-menu';
        $actionmenu->set_menu_trigger(get_string('edit'));

        foreach ($menuoptions as $value) {
            $menulink = new action_menu_link_secondary(
                new moodle_url(''),
                new pix_icon($value['icon'], '', null, ['class' => 'smallicon']),
                $value['label'],
                $value['attributes'],
            );
            $actionmenu->add($menulink);
        }

        return $actionmenu;
    }

    /**
     * Retourne un tableau de chaînes indiquant que le certificat a été validé. Ajoute éventuellement l'adresse du
     * contact fonctionnel.
     *
     * @return array
     */
    public static function get_contacts() {
        $messages = [];
        $messages[] = get_string('your_request_is_being_processed', 'local_apsolu') . '.';

        $functionalcontactmail = get_config('local_apsolu', 'functional_contact');
        if (filter_var($functionalcontactmail, FILTER_VALIDATE_EMAIL) !== false) {
            $messages[] = get_string('if_you_want_to_make_a_change_please_contact_X', 'local_apsolu', $functionalcontactmail);
        }

        return $messages;
    }

    /**
     * Retourne la liste des pays.
     *
     * @return array
     */
    public static function get_countries() {
        $countries = [];
        $countries['AF'] = 'AFGHANISTAN';
        $countries['AL'] = 'ALBANIE';
        $countries['AQ'] = 'ANTARCTIQUE';
        $countries['DZ'] = 'ALGERIE';
        $countries['AS'] = 'SAMOA AMERICAINES';
        $countries['AD'] = 'ANDORRE';
        $countries['AO'] = 'ANGOLA';
        $countries['AG'] = 'ANTIGUA-ET-BARBUDA';
        $countries['AZ'] = 'AZERBAIDJAN';
        $countries['AR'] = 'ARGENTINE';
        $countries['AU'] = 'AUSTRALIE';
        $countries['AT'] = 'AUTRICHE';
        $countries['BS'] = 'BAHAMAS';
        $countries['BH'] = 'BAHREIN';
        $countries['BD'] = 'BANGLADESH';
        $countries['AM'] = 'ARMENIE';
        $countries['BB'] = 'BARBADE';
        $countries['BE'] = 'BELGIQUE';
        $countries['BM'] = 'BERMUDES';
        $countries['BT'] = 'BHOUTAN';
        $countries['BO'] = 'ETAT PLURINATIONAL DE BOLIVIE';
        $countries['BA'] = 'BOSNIE-HERZEGOVINE';
        $countries['BW'] = 'BOTSWANA';
        $countries['BV'] = 'ILE BOUVET';
        $countries['BR'] = 'BRESIL';
        $countries['BZ'] = 'BELIZE';
        $countries['IO'] = 'OCEAN INDIEN (TERRIT.BRITANNIQ)';
        $countries['SB'] = 'SALOMON (ILES)';
        $countries['VG'] = 'ILES VIERGES BRITANNIQUES';
        $countries['BN'] = 'BRUNEI DARUSSALAM';
        $countries['BG'] = 'BULGARIE';
        $countries['MM'] = 'BIRMANIE';
        $countries['BI'] = 'BURUNDI';
        $countries['BY'] = 'BIELORUSSIE';
        $countries['KH'] = 'CAMBODGE';
        $countries['CM'] = 'CAMEROUN';
        $countries['CA'] = 'CANADA';
        $countries['CV'] = 'CAP-VERT';
        $countries['KY'] = 'ILES CAIMANES';
        $countries['CF'] = 'REPUBLIQUE CENTRAFRICAINE';
        $countries['LK'] = 'SRI LANKA';
        $countries['TD'] = 'TCHAD';
        $countries['CL'] = 'CHILI';
        $countries['CN'] = 'CHINE';
        $countries['TW'] = 'TAIWAN';
        $countries['CX'] = 'ILE CHRISTMAS';
        $countries['CC'] = 'ILES COCOS (KEELING)';
        $countries['CO'] = 'COLOMBIE';
        $countries['KM'] = 'COMORES';
        $countries['CG'] = 'CONGO';
        $countries['CD'] = 'CONGO (REPUBLIQUE DEMOCRATIQUE)';
        $countries['CK'] = 'ILES COOK';
        $countries['CR'] = 'COSTA RICA';
        $countries['HR'] = 'CROATIE';
        $countries['CU'] = 'CUBA';
        $countries['CY'] = 'CHYPRE';
        $countries['CS'] = 'TCHECOSLOVAQUIE';
        $countries['CZ'] = 'REPUBLIQUE TCHEQUE';
        $countries['BJ'] = 'BENIN';
        $countries['DK'] = 'DANEMARK';
        $countries['DM'] = 'DOMINIQUE';
        $countries['DO'] = 'REPUBLIQUE DOMINICAINE';
        $countries['EC'] = 'EQUATEUR';
        $countries['SV'] = 'SALVADOR';
        $countries['GQ'] = 'GUINEE EQUATORIALE';
        $countries['ET'] = 'ETHIOPIE';
        $countries['ER'] = 'ETAT D\'ERYTHREE';
        $countries['EE'] = 'ESTONIE';
        $countries['FO'] = 'ILES FEROE';
        $countries['FK'] = 'ILES FALKLAND (MALVINAS)';
        $countries['GS'] = 'GEORGIE DU SUD-ILES SANDWICH SUD';
        $countries['FJ'] = 'FIDJI';
        $countries['FI'] = 'FINLANDE';
        $countries['FR'] = 'FRANCE';
        $countries['TF'] = 'TERRES AUSTRALES FRANCAISES';
        $countries['DJ'] = 'DJIBOUTI';
        $countries['GA'] = 'GABON';
        $countries['GE'] = 'GEORGIE';
        $countries['GM'] = 'GAMBIE';
        $countries['PS'] = 'ETAT DE PALESTINE';
        $countries['DE'] = 'ALLEMAGNE';
        $countries['GH'] = 'GHANA';
        $countries['GI'] = 'GIBRALTAR';
        $countries['KI'] = 'KIRIBATI';
        $countries['GR'] = 'GRECE';
        $countries['GL'] = 'GROENLAND';
        $countries['GD'] = 'GRENADE';
        $countries['GU'] = 'GUAM';
        $countries['GT'] = 'GUATEMALA';
        $countries['GN'] = 'GUINEE';
        $countries['GY'] = 'GUYANA';
        $countries['HT'] = 'HAITI';
        $countries['HM'] = 'ILES HEARD ET MC DONALD';
        $countries['VA'] = 'VATICAN (SAINT-SIEGE)';
        $countries['HN'] = 'HONDURAS';
        $countries['HK'] = 'HONG-KONG';
        $countries['HU'] = 'HONGRIE';
        $countries['IS'] = 'ISLANDE';
        $countries['IN'] = 'INDE';
        $countries['ID'] = 'INDONESIE';
        $countries['IR'] = 'IRAN (REPUBLIQUE ISLAMIQUE D\')';
        $countries['IQ'] = 'IRAQ';
        $countries['IE'] = 'IRLANDE';
        $countries['IL'] = 'ISRAEL';
        $countries['IT'] = 'ITALIE';
        $countries['CI'] = 'COTE D\'IVOIRE';
        $countries['JM'] = 'JAMAIQUE';
        $countries['JP'] = 'JAPON';
        $countries['KZ'] = 'KAZAKHSTAN';
        $countries['JO'] = 'JORDANIE';
        $countries['KE'] = 'KENYA';
        $countries['KR'] = 'COREE (REP. POPULAIR. DEMOCRATI)';
        $countries['KS'] = 'COREE (REPUBLIQUE DE)';
        $countries['KW'] = 'KOWEIT';
        $countries['KG'] = 'KIRGHIZISTAN';
        $countries['LA'] = 'LAOS (REP. DEMOC. POPULAIRE)';
        $countries['LB'] = 'LIBAN';
        $countries['LS'] = 'LESOTHO';
        $countries['LV'] = 'LETTONIE';
        $countries['LR'] = 'LIBERIA';
        $countries['LY'] = 'LIBYE';
        $countries['LI'] = 'LIECHTENSTEIN';
        $countries['LT'] = 'LITUANIE';
        $countries['LU'] = 'LUXEMBOURG';
        $countries['MO'] = 'MACAO';
        $countries['MG'] = 'MADAGASCAR';
        $countries['MW'] = 'MALAWI';
        $countries['MY'] = 'MALAISIE';
        $countries['MV'] = 'MALDIVES';
        $countries['ML'] = 'MALI';
        $countries['MT'] = 'MALTE';
        $countries['MR'] = 'MAURITANIE';
        $countries['MU'] = 'MAURICE';
        $countries['MX'] = 'MEXIQUE';
        $countries['MC'] = 'MONACO';
        $countries['MN'] = 'MONGOLIE';
        $countries['MD'] = 'MOLDAVIE';
        $countries['ME'] = 'MONTENEGRO';
        $countries['MS'] = 'MONTSERRAT';
        $countries['MA'] = 'MAROC';
        $countries['MZ'] = 'MOZAMBIQUE';
        $countries['OM'] = 'OMAN';
        $countries['NA'] = 'NAMIBIE';
        $countries['NR'] = 'NAURU';
        $countries['NP'] = 'NEPAL';
        $countries['NL'] = 'PAYS-BAS';
        $countries['CW'] = 'CURACAO';
        $countries['AN'] = 'ANTILLES NEERLANDAISES';
        $countries['AW'] = 'ARUBA';
        $countries['SX'] = 'SAINT MARTIN PARTIE NEERLANDAISE';
        $countries['BQ'] = 'BONAIRE SAINT EUSTACHE ET SABA';
        $countries['NT'] = 'ZONE NEUTRE';
        $countries['NC'] = 'NOUVELLE CALEDONIE';
        $countries['VY'] = 'VANUATU';
        $countries['NZ'] = 'NOUVELLE-ZELANDE';
        $countries['NI'] = 'NICARAGUA';
        $countries['NE'] = 'NIGER';
        $countries['NG'] = 'NIGERIA';
        $countries['NU'] = 'NIOUE';
        $countries['NF'] = 'ILE NORFOLK';
        $countries['NO'] = 'NORVEGE';
        $countries['MP'] = 'ILES MARIANNES DU NORD';
        $countries['UM'] = 'ILES MINEURES ELOIGNEES DES E.U.';
        $countries['FM'] = 'MICRONESIE (ETATS FEDERES DE)';
        $countries['MH'] = 'MARSHALL (ILES)';
        $countries['PW'] = 'PALAOS';
        $countries['PK'] = 'PAKISTAN';
        $countries['PA'] = 'PANAMA';
        $countries['PG'] = 'ET INDT DE PAPOUASIE NLLE GUINEE';
        $countries['PY'] = 'PARAGUAY';
        $countries['PE'] = 'PEROU';
        $countries['PH'] = 'PHILIPPINES';
        $countries['PN'] = 'PITCAIRN';
        $countries['PL'] = 'POLOGNE';
        $countries['PT'] = 'PORTUGAL';
        $countries['GW'] = 'GUINEE-BISSAO';
        $countries['TL'] = 'TIMOR ORIENTAL';
        $countries['PR'] = 'PORTO-RICO';
        $countries['QA'] = 'QATAR';
        $countries['RO'] = 'ROUMANIE';
        $countries['RU'] = 'FEDERATION DE RUSSIE';
        $countries['RW'] = 'RWANDA';
        $countries['SH'] = 'SAINTE-HELENE';
        $countries['KN'] = 'SAINT-CHRISTOPHE-ET-NIEVES';
        $countries['AI'] = 'ANGUILLA';
        $countries['LC'] = 'SAINTE-LUCIE';
        $countries['VC'] = 'SAINT-VINCENT-ET-LES-GRENADINES';
        $countries['SM'] = 'SAINT-MARIN';
        $countries['ST'] = 'SAO TOME-ET-PRINCIPE';
        $countries['SA'] = 'ARABIE SAOUDITE';
        $countries['SN'] = 'SENEGAL';
        $countries['RS'] = 'SERBIE';
        $countries['SC'] = 'SEYCHELLES';
        $countries['SL'] = 'SIERRA LEONE';
        $countries['SG'] = 'SINGAPOUR';
        $countries['SK'] = 'SLOVAQUIE';
        $countries['VN'] = 'VIET NAM';
        $countries['SI'] = 'SLOVENIE';
        $countries['SO'] = 'REPUBLIQUE FEDERALE DE SOMALIE';
        $countries['ZA'] = 'AFRIQUE DU SUD';
        $countries['ZW'] = 'ZIMBABWE';
        $countries['YES'] = 'YEMEN DU SUD';
        $countries['ES'] = 'ESPAGNE';
        $countries['SS'] = 'SOUDAN DU SUD';
        $countries['EH'] = 'SAHARA OCCIDENTAL';
        $countries['SD'] = 'SOUDAN';
        $countries['SR'] = 'SURINAME';
        $countries['SJ'] = 'SVALBARD ET ILE JAN MAYEN';
        $countries['SZ'] = 'SWAZILAND';
        $countries['SE'] = 'SUEDE';
        $countries['CH'] = 'SUISSE';
        $countries['SY'] = 'SYRIE';
        $countries['TJ'] = 'TADJIKISTAN';
        $countries['TH'] = 'THAILANDE';
        $countries['TG'] = 'TOGO';
        $countries['TK'] = 'TOKELAU';
        $countries['TO'] = 'TONGA';
        $countries['TT'] = 'TRINITE-ET-TOBAGO';
        $countries['AE'] = 'EMIRATS ARABES UNIS';
        $countries['TN'] = 'TUNISIE';
        $countries['TR'] = 'TURQUIE';
        $countries['TM'] = 'TURKMENISTAN';
        $countries['TC'] = 'ILES TURKS ET CAIQUES';
        $countries['TV'] = 'TUVALU';
        $countries['UG'] = 'OUGANDA';
        $countries['UA'] = 'UKRAINE';
        $countries['MK'] = 'EX-REP.YOUGOSLAVE DE MACEDOINE';
        $countries['SU'] = 'URSS';
        $countries['EG'] = 'EGYPTE';
        $countries['GB'] = 'ROYAUME-UNI';
        $countries['GG'] = 'GUERNESEY';
        $countries['JE'] = 'JERSEY';
        $countries['IM'] = 'ILE DE MAN';
        $countries['TZ'] = 'TANZANIE';
        $countries['US'] = 'ETATS-UNIS';
        $countries['VI'] = 'ILES VIERGES DES ETATS-UNIS';
        $countries['BF'] = 'BURKINA FASO';
        $countries['UY'] = 'URUGUAY';
        $countries['UZ'] = 'OUZBEKISTAN';
        $countries['VE'] = 'VENEZUELA';
        $countries['WF'] = 'ILES WALLIS ET FUTUNA';
        $countries['WS'] = 'SAMOA';
        $countries['YEN'] = 'YEMEN DU NORD';
        $countries['YE'] = 'YEMEN';
        $countries['YU'] = 'YOUGOSLAVIE';
        $countries['RFY'] = 'REPUBLIQUE FEDER. DE YOUGOSLAVIE';
        $countries['ZM'] = 'ZAMBIE';
        $countries['ICC'] = 'INCONNU';
        $countries['XK'] = 'KOSOVO';
        $countries['APY'] = 'AUTRES PAYS';
        $countries['MER'] = 'MER';

        return $countries;
    }

    /**
     * Retourne la liste des départements français.
     *
     * @return array
     */
    public static function get_departments() {
        $departments = [];
        $departments['01'] = '01 - Ain';
        $departments['02'] = '02 - Aisne';
        $departments['03'] = '03 - Allier';
        $departments['04'] = '04 - Alpes-de-Haute-Provence';
        $departments['05'] = '05 - Hautes-Alpes';
        $departments['06'] = '06 - Alpes-Maritimes';
        $departments['07'] = '07 - Ardèche';
        $departments['08'] = '08 - Ardennes';
        $departments['09'] = '09 - Ariège';
        $departments['10'] = '10 - Aube';
        $departments['11'] = '11 - Aude';
        $departments['12'] = '12 - Aveyron';
        $departments['13'] = '13 - Bouches-du-Rhône';
        $departments['14'] = '14 - Calvados';
        $departments['15'] = '15 - Cantal';
        $departments['16'] = '16 - Charente';
        $departments['17'] = '17 - Charente-Maritime';
        $departments['18'] = '18 - Cher';
        $departments['19'] = '19 - Corrèze';
        $departments['2A'] = '2A - Corse-du-Sud';
        $departments['2B'] = '2B - Haute-Corse';
        $departments['21'] = '21 - Côte-d’Or';
        $departments['22'] = '22 - Côtes-d’Armor';
        $departments['23'] = '23 - Creuse';
        $departments['24'] = '24 - Dordogne';
        $departments['25'] = '25 - Doubs';
        $departments['26'] = '26 - Drôme';
        $departments['27'] = '27 - Eure';
        $departments['28'] = '28 - Eure-et-Loir';
        $departments['29'] = '29 - Finistère';
        $departments['30'] = '30 - Gard';
        $departments['31'] = '31 - Haute-Garonne';
        $departments['32'] = '32 - Gers';
        $departments['33'] = '33 - Gironde';
        $departments['34'] = '34 - Hérault';
        $departments['35'] = '35 - Ille-et-Vilaine';
        $departments['36'] = '36 - Indre';
        $departments['37'] = '37 - Indre-et-Loire';
        $departments['38'] = '38 - Isère';
        $departments['39'] = '39 - Jura';
        $departments['40'] = '40 - Landes';
        $departments['41'] = '41 - Loir-et-Cher';
        $departments['42'] = '42 - Loire';
        $departments['43'] = '43 - Haute-Loire';
        $departments['44'] = '44 - Loire-Atlantique';
        $departments['45'] = '45 - Loiret';
        $departments['46'] = '46 - Lot';
        $departments['47'] = '47 - Lot-et-Garonne';
        $departments['48'] = '48 - Lozère';
        $departments['49'] = '49 - Maine-et-Loire';
        $departments['50'] = '50 - Manche';
        $departments['51'] = '51 - Marne';
        $departments['52'] = '52 - Haute-Marne';
        $departments['53'] = '53 - Mayenne';
        $departments['54'] = '54 - Meurthe-et-Moselle';
        $departments['55'] = '55 - Meuse';
        $departments['56'] = '56 - Morbihan';
        $departments['57'] = '57 - Moselle';
        $departments['58'] = '58 - Nièvre';
        $departments['59'] = '59 - Nord';
        $departments['60'] = '60 - Oise';
        $departments['61'] = '61 - Orne';
        $departments['62'] = '62 - Pas-de-Calais';
        $departments['63'] = '63 - Puy-de-Dôme';
        $departments['64'] = '64 - Pyrénées-Atlantiques';
        $departments['65'] = '65 - Hautes-Pyrénées';
        $departments['66'] = '66 - Pyrénées-Orientales';
        $departments['67'] = '67 - Bas-Rhin';
        $departments['68'] = '68 - Haut-Rhin';
        $departments['69'] = '69 - Rhône';
        $departments['70'] = '70 - Haute-Saône';
        $departments['71'] = '71 - Saône-et-Loire';
        $departments['72'] = '72 - Sarthe';
        $departments['73'] = '73 - Savoie';
        $departments['74'] = '74 - Haute-Savoie';
        $departments['75'] = '75 - Paris';
        $departments['76'] = '76 - Seine-Maritime';
        $departments['77'] = '77 - Seine-et-Marne';
        $departments['78'] = '78 - Yvelines';
        $departments['79'] = '79 - Deux-Sèvres';
        $departments['80'] = '80 - Somme';
        $departments['81'] = '81 - Tarn';
        $departments['82'] = '82 - Tarn-et-Garonne';
        $departments['83'] = '83 - Var';
        $departments['84'] = '84 - Vaucluse';
        $departments['85'] = '85 - Vendée';
        $departments['86'] = '86 - Vienne';
        $departments['87'] = '87 - Haute-Vienne';
        $departments['88'] = '88 - Vosges';
        $departments['89'] = '89 - Yonne';
        $departments['90'] = '90 - Territoire de Belfort';
        $departments['91'] = '91 - Essonne';
        $departments['92'] = '92 - Hauts-de-Seine';
        $departments['93'] = '93 - Seine-Saint-Denis';
        $departments['94'] = '94 - Val-de-Marne';
        $departments['95'] = '95 - Val-d’Oise';
        $departments['971'] = '971 - Guadeloupe';
        $departments['972'] = '972 - Martinique';
        $departments['973'] = '973 - Guyane';
        $departments['974'] = '974 - La Réunion';
        $departments['975'] = '975 - Saint-Pierre-et-Miquelon';
        $departments['976'] = '976 - Mayotte';
        $departments['977'] = '977 - St Barthelemy';
        $departments['978'] = '978 - St Martin';
        $departments['987'] = '987 - Polynésie Française';

        return $departments;
    }

    /**
     * Retourne la liste des noms officiels des activités FFSU.
     *
     * @return array
     */
    public static function get_disciplines() {
        $disciplines = [];
        $disciplines['Animation'] = 'Animation';
        $disciplines['Architecture'] = 'Architecture';
        $disciplines['Arts'] = 'Arts';
        $disciplines['Audiovisuel'] = 'Audiovisuel';
        $disciplines['Commerce'] = 'Commerce';
        $disciplines['Communication'] = 'Communication';
        $disciplines['Défense'] = 'Défense';
        $disciplines['Droit/Sciences Po'] = 'Droit/Sciences Po';
        $disciplines['Enseignement'] = 'Enseignement';
        $disciplines['Ingénierie'] = 'Ingénierie';
        $disciplines['Langues'] = 'Langues';
        $disciplines['Lettres'] = 'Lettres';
        $disciplines['Médecine/santé'] = 'Médecine/santé';
        $disciplines['Métiers du sport'] = 'Métiers du sport';
        $disciplines['Sciences Eco/Gestion'] = 'Sciences Eco/Gestion';
        $disciplines['Sciences'] = 'Sciences';
        $disciplines['Sciences Humaines'] = 'Sciences Humaines';
        $disciplines['Technique'] = 'Technique';

        return $disciplines;
    }

    /**
     * Retourne un tableau avec le nom des champs pour l'exportation FFSU.
     *
     * @return array Tableau avec pour clé le nom de l'attribut de la valeur et pour valeur la chaîne de caractères.
     */
    public static function get_exportation_fields() {
        return [
            'federationnumber' => get_string('member_code', 'local_apsolu'),
            'title' => get_string('user_title', 'local_apsolu'),
            'lastname' => get_string('lastname', 'local_apsolu'),
            'firstname' => get_string('firstname'),
            'birthday' => get_string('birthday', 'local_apsolu'),
            'birthname' => get_string('birthname', 'local_apsolu'),
            'birthcountry' => get_string('birthcountry', 'local_apsolu'),
            'nationality' => get_string('nationality', 'local_apsolu'),
            'birthdepartment' => get_string('department_of_birth', 'local_apsolu'),
            'birthtown' => get_string('birthtown', 'local_apsolu'),
            'birthplace' => get_string('birthplace', 'local_apsolu'),
            'handicap' => get_string('disability', 'local_apsolu'),
            'licenseetype' => get_string('student_shortened', 'local_apsolu'),
            'cursus' => get_string('discipline_cursus_shortened', 'local_apsolu'),
            'studycycle' => get_string('study_cycle', 'local_apsolu'),
            'otherfederation' => get_string('other_federation', 'local_apsolu'),
            'commercialoffers' => get_string('authorisation_for_commercial_offers', 'local_apsolu'),
            'usepersonalimage' => get_string('legal_authorisation', 'local_apsolu'),
            'policyagreed' => get_string('authorisation_for_data_use', 'local_apsolu'),
            'newsletter' => get_string('authorisation_for_newsletter', 'local_apsolu'),
            'federaltexts' => get_string('federal_texts', 'local_apsolu'),
            'tracknumber' => get_string('track_number', 'local_apsolu'),
            'tracktype' => get_string('track_type', 'local_apsolu'),
            'trackname' => get_string('track_name', 'local_apsolu'),
            'building' => get_string('building', 'local_apsolu'),
            'staircase' => get_string('staircase', 'local_apsolu'),
            'lieudit' => get_string('lieudit', 'local_apsolu'),
            'postalcode' => get_string('postal_code', 'local_apsolu'),
            'city' => get_string('town', 'local_apsolu'),
            'country' => get_string('country'),
            'email' => get_string('mail', 'local_apsolu'),
            'workmail' => get_string('work_mail', 'local_apsolu'),
            'phone1' => get_string('phone1', 'local_apsolu'),
            'phone2' => get_string('phone2', 'local_apsolu'),
            'titleprimarylegalrepresentative' => get_string('title_primary_legal_representative', 'local_apsolu'),
            'lastnameprimarylegalrepresentative' => get_string('lastname_primary_legal_representative', 'local_apsolu'),
            'firstnameprimarylegalrepresentative' => get_string('firstname_primary_legal_representative', 'local_apsolu'),
            'phoneprimarylegalrepresentative' => get_string('phone_primary_legal_representative_shortened', 'local_apsolu'),
            'emailprimarylegalrepresentative' => get_string('email_primary_legal_representative_shortened', 'local_apsolu'),
            'titlesecondarylegalrepresentative' => get_string('title_secondary_legal_representative', 'local_apsolu'),
            'lastnamesecondarylegalrepresentative' => get_string('lastname_secondary_legal_representative', 'local_apsolu'),
            'firstnamesecondarylegalrepresentative' => get_string('firstname_secondary_legal_representative', 'local_apsolu'),
            'phonesecondarylegalrepresentative' => get_string('phone_secondary_legal_representative_shortened', 'local_apsolu'),
            'emailsecondarylegalrepresentative' => get_string('email_secondary_legal_representative_shortened', 'local_apsolu'),
            'insurance' => get_string('with_insurance', 'local_apsolu'),
            'licensetype' => get_string('license_type', 'local_apsolu'),
            'questionnairestatus' => get_string('negative_health_questionnaire', 'local_apsolu'),
            'doctorname' => get_string('doctor_name', 'local_apsolu'),
            'doctorrpps' => get_string('doctor_rpps', 'local_apsolu'),
            'medicalcertificatedate' => get_string('medical_certificate_date_shortened', 'local_apsolu'),
            'medicalcertifiatevalidated' => get_string('medical_certificate_validated_shortened', 'local_apsolu'),
            'schoolcertificatevalidated' => get_string('school_certificate_validated', 'local_apsolu'),
            'activity' => get_string('disciplines', 'local_apsolu'),
            'federationnumberprefix' => get_string('section'),
        ];
    }

    /**
     * Calcule le préfixe de numéro FFSU à attribuer à l'étudiant en fonction de différents critères de son profil.
     *
     * @return string|false Retourne false si aucun critère ne correspond à l'utilisateur.
     */
    public function get_federation_number_prefix() {
        global $DB;

        $user = $DB->get_record('user', ['id' => $this->userid], $fields = '*', MUST_EXIST);

        $customfields = profile_user_record($user->id);
        $user->apsoluufr = $customfields->apsoluufr;

        $numbers = Number::get_records($conditions = null, $sort = 'sortorder');
        foreach ($numbers as $number) {
            if (isset($user->{$number->field}) === false) {
                continue;
            }

            $value = trim($user->{$number->field});
            if ($number->value !== $value) {
                continue;
            }

            if (ctype_digit($number->number) === false) {
                return '';
            }

            return $number->number;
        }

        // Aucun numéro ne correspond à l'utilisateur.
        return false;
    }

    /**
     * Retourne l'identifiant du groupe Moodle en fonction de l'identifiant d'une activité FFSU.
     *
     * @param int $activityid Identifiant de l'activité FFSU.
     * @param int $courseid   Identifiant du cours FFSU.
     *
     * @return int|false Retourne false si aucun groupe n'a été trouvé.
     */
    public static function get_groupid_from_activityid($activityid, $courseid) {
        $activity = new Activity();
        $activity->load($activityid, $required = true);

        return groups_get_group_by_name($courseid, $activity->name);
    }

    /**
     * Retourne la liste des types de licence.
     *
     * @return array
     */
    public static function get_license_types() {
        $licenses = [];
        $licenses['S'] = 'SPORTIVE';
        $licenses['D'] = 'DIRIGEANT.E';
        $licenses['A'] = 'ARBITRE';
        $licenses['E'] = 'ENCADRANT.E';
        $licenses['PSU'] = 'Licence promotionnelle';

        return $licenses;
    }

    /**
     * Retourne la liste officielle des types d'utilisateur.
     *
     * @return array
     */
    public static function get_licensee_types() {
        $types = [];
        $types[1] = get_string('student', 'local_apsolu');
        $types[0] = get_string('not_student', 'local_apsolu');

        return $types;
    }

    /**
     * Retourne les licences nécessitant une déclaration d'honorabilité.
     *
     * @return array
     */
    public static function get_licenses_with_honorability() {
        return ['A' => 'A', 'D' => 'D', 'E' => 'E'];
    }

    /**
     * Retourne la liste des nationalités.
     *
     * @return array
     */
    public static function get_nationalities() {
        $countries = [];
        $countries['AF'] = 'AFGHANISTAN';
        $countries['ZA'] = 'AFRIQUE DU SUD';
        $countries['AX'] = 'ÅLAND, ÎLES';
        $countries['AL'] = 'ALBANIE';
        $countries['DZ'] = 'ALGÉRIE';
        $countries['DE'] = 'ALLEMAGNE';
        $countries['AD'] = 'ANDORRE';
        $countries['AO'] = 'ANGOLA';
        $countries['AI'] = 'ANGUILLA';
        $countries['AQ'] = 'ANTARCTIQUE';
        $countries['AG'] = 'ANTIGUA-ET-BARBUDA';
        $countries['SA'] = 'ARABIE SAOUDITE';
        $countries['AR'] = 'ARGENTINE';
        $countries['AM'] = 'ARMÉNIE';
        $countries['AW'] = 'ARUBA';
        $countries['AU'] = 'AUSTRALIE';
        $countries['AT'] = 'AUTRICHE';
        $countries['AZ'] = 'AZERBAÏDJAN';
        $countries['BS'] = 'BAHAMAS';
        $countries['BH'] = 'BAHREÏN';
        $countries['BD'] = 'BANGLADESH';
        $countries['BB'] = 'BARBADE';
        $countries['BY'] = 'BÉLARUS';
        $countries['BE'] = 'BELGIQUE';
        $countries['BZ'] = 'BELIZE';
        $countries['BJ'] = 'BÉNIN';
        $countries['BM'] = 'BERMUDES';
        $countries['BT'] = 'BHOUTAN';
        $countries['BO'] = 'BOLIVIE, l\'ÉTAT PLURINATIONAL DE';
        $countries['BQ'] = 'BONAIRE, SAINT-EUSTACHE ET SABA';
        $countries['BA'] = 'BOSNIE-HERZÉGOVINE';
        $countries['BW'] = 'BOTSWANA';
        $countries['BV'] = 'BOUVET, ÎLE';
        $countries['BR'] = 'BRÉSIL';
        $countries['BN'] = 'BRUNEI DARUSSALAM';
        $countries['BG'] = 'BULGARIE';
        $countries['BF'] = 'BURKINA FASO';
        $countries['BI'] = 'BURUNDI';
        $countries['KY'] = 'CAÏMANS, ÎLES';
        $countries['KH'] = 'CAMBODGE';
        $countries['CM'] = 'CAMEROUN';
        $countries['CA'] = 'CANADA';
        $countries['CV'] = 'CAP-VERT';
        $countries['CF'] = 'CENTRAFRICAINE, RÉPUBLIQUE';
        $countries['CL'] = 'CHILI';
        $countries['CN'] = 'CHINE';
        $countries['CX'] = 'CHRISTMAS, ÎLE';
        $countries['CY'] = 'CHYPRE';
        $countries['CC'] = 'COCOS (KEELING), ÎLES';
        $countries['CO'] = 'COLOMBIE';
        $countries['KM'] = 'COMORES';
        $countries['CG'] = 'CONGO';
        $countries['CD'] = 'CONGO, LA RÉPUBLIQUE DÉMOCRATIQUE DU';
        $countries['CK'] = 'COOK, ÎLES';
        $countries['KR'] = 'CORÉE, RÉPUBLIQUE DE';
        $countries['KP'] = 'CORÉE, RÉPUBLIQUE POPULAIRE DÉMOCRATIQUE DE';
        $countries['CR'] = 'COSTA RICA';
        $countries['CI'] = 'CÔTE D\'IVOIRE';
        $countries['HR'] = 'CROATIE';
        $countries['CU'] = 'CUBA';
        $countries['CW'] = 'CURAÇAO';
        $countries['DK'] = 'DANEMARK';
        $countries['DJ'] = 'DJIBOUTI';
        $countries['DO'] = 'DOMINICAINE, RÉPUBLIQUE';
        $countries['DM'] = 'DOMINIQUE';
        $countries['EG'] = 'ÉGYPTE';
        $countries['SV'] = 'EL SALVADOR';
        $countries['AE'] = 'ÉMIRATS ARABES UNIS';
        $countries['EC'] = 'ÉQUATEUR';
        $countries['ER'] = 'ÉRYTHRÉE';
        $countries['ES'] = 'ESPAGNE';
        $countries['EE'] = 'ESTONIE';
        $countries['US'] = 'ÉTATS-UNIS';
        $countries['ET'] = 'ÉTHIOPIE';
        $countries['FK'] = 'FALKLAND, ÎLES (MALVINAS)';
        $countries['FO'] = 'FÉROÉ, ÎLES';
        $countries['FJ'] = 'FIDJI';
        $countries['FI'] = 'FINLANDE';
        $countries['FR'] = 'FRANCE';
        $countries['GA'] = 'GABON';
        $countries['GM'] = 'GAMBIE';
        $countries['GE'] = 'GÉORGIE';
        $countries['GS'] = 'GÉORGIE DU SUD-ET-LES ÎLES SANDWICH DU SUD';
        $countries['GH'] = 'GHANA';
        $countries['GI'] = 'GIBRALTAR';
        $countries['GR'] = 'GRÈCE';
        $countries['GD'] = 'GRENADE';
        $countries['GL'] = 'GROENLAND';
        $countries['GP'] = 'GUADELOUPE';
        $countries['GU'] = 'GUAM';
        $countries['GT'] = 'GUATEMALA';
        $countries['GG'] = 'GUERNESEY';
        $countries['GN'] = 'GUINÉE';
        $countries['GQ'] = 'GUINÉE ÉQUATORIALE';
        $countries['GW'] = 'GUINÉE-BISSAU';
        $countries['GY'] = 'GUYANA';
        $countries['GF'] = 'GUYANE FRANÇAISE';
        $countries['HT'] = 'HAÏTI';
        $countries['HM'] = 'HEARD-ET-ÎLES MACDONALD, ÎLE';
        $countries['HN'] = 'HONDURAS';
        $countries['HK'] = 'HONG KONG';
        $countries['HU'] = 'HONGRIE';
        $countries['IM'] = 'ÎLE DE MAN';
        $countries['UM'] = 'ÎLES MINEURES ÉLOIGNÉES DES ÉTATS-UNIS';
        $countries['VG'] = 'ÎLES VIERGES BRITANNIQUES';
        $countries['VI'] = 'ÎLES VIERGES DES ÉTATS-UNIS';
        $countries['IN'] = 'INDE';
        $countries['ID'] = 'INDONÉSIE';
        $countries['IR'] = 'IRAN, RÉPUBLIQUE ISLAMIQUE D\'';
        $countries['IQ'] = 'IRAQ';
        $countries['IE'] = 'IRLANDE';
        $countries['IS'] = 'ISLANDE';
        $countries['IL'] = 'ISRAËL';
        $countries['IT'] = 'ITALIE';
        $countries['JM'] = 'JAMAÏQUE';
        $countries['JP'] = 'JAPON';
        $countries['JE'] = 'JERSEY';
        $countries['JO'] = 'JORDANIE';
        $countries['KZ'] = 'KAZAKHSTAN';
        $countries['KE'] = 'KENYA';
        $countries['KG'] = 'KIRGHIZISTAN';
        $countries['KI'] = 'KIRIBATI';
        $countries['KW'] = 'KOWEÏT';
        $countries['LA'] = 'LAO, RÉPUBLIQUE DÉMOCRATIQUE POPULAIRE';
        $countries['LS'] = 'LESOTHO';
        $countries['LV'] = 'LETTONIE';
        $countries['LB'] = 'LIBAN';
        $countries['LR'] = 'LIBÉRIA';
        $countries['LY'] = 'LIBYE';
        $countries['LI'] = 'LIECHTENSTEIN';
        $countries['LT'] = 'LITUANIE';
        $countries['LU'] = 'LUXEMBOURG';
        $countries['MO'] = 'MACAO';
        $countries['MK'] = 'MACÉDOINE, L\'EX-RÉPUBLIQUE YOUGOSLAVE DE';
        $countries['MG'] = 'MADAGASCAR';
        $countries['MY'] = 'MALAISIE';
        $countries['MW'] = 'MALAWI';
        $countries['MV'] = 'MALDIVES';
        $countries['ML'] = 'MALI';
        $countries['MT'] = 'MALTE';
        $countries['MP'] = 'MARIANNES DU NORD, ÎLES';
        $countries['MA'] = 'MAROC';
        $countries['MH'] = 'MARSHALL, ÎLES';
        $countries['MQ'] = 'MARTINIQUE';
        $countries['MU'] = 'MAURICE';
        $countries['MR'] = 'MAURITANIE';
        $countries['YT'] = 'MAYOTTE';
        $countries['MX'] = 'MEXIQUE';
        $countries['FM'] = 'MICRONÉSIE, ÉTATS FÉDÉRÉS DE';
        $countries['MD'] = 'MOLDOVA, RÉPUBLIQUE DE';
        $countries['MC'] = 'MONACO';
        $countries['MN'] = 'MONGOLIE';
        $countries['ME'] = 'MONTÉNÉGRO';
        $countries['MS'] = 'MONTSERRAT';
        $countries['MZ'] = 'MOZAMBIQUE';
        $countries['MM'] = 'MYANMAR';
        $countries['NA'] = 'NAMIBIE';
        $countries['NR'] = 'NAURU';
        $countries['NP'] = 'NÉPAL';
        $countries['NI'] = 'NICARAGUA';
        $countries['NE'] = 'NIGER';
        $countries['NG'] = 'NIGÉRIA';
        $countries['NU'] = 'NIUÉ';
        $countries['NF'] = 'NORFOLK, ÎLE';
        $countries['NO'] = 'NORVÈGE';
        $countries['NC'] = 'NOUVELLE-CALÉDONIE';
        $countries['NZ'] = 'NOUVELLE-ZÉLANDE';
        $countries['IO'] = 'OCÉAN INDIEN, TERRITOIRE BRITANNIQUE DE L\'';
        $countries['OM'] = 'OMAN';
        $countries['UG'] = 'OUGANDA';
        $countries['UZ'] = 'OUZBÉKISTAN';
        $countries['PK'] = 'PAKISTAN';
        $countries['PW'] = 'PALAOS';
        $countries['PS'] = 'PALESTINE, ÉTAT DE';
        $countries['PA'] = 'PANAMA';
        $countries['PG'] = 'PAPOUASIE-NOUVELLE-GUINÉE';
        $countries['PY'] = 'PARAGUAY';
        $countries['NL'] = 'PAYS-BAS';
        $countries['PE'] = 'PÉROU';
        $countries['PH'] = 'PHILIPPINES';
        $countries['PN'] = 'PITCAIRN';
        $countries['PL'] = 'POLOGNE';
        $countries['PF'] = 'POLYNÉSIE FRANÇAISE';
        $countries['PR'] = 'PORTO RICO';
        $countries['PT'] = 'PORTUGAL';
        $countries['QA'] = 'QATAR';
        $countries['RE'] = 'RÉUNION';
        $countries['RO'] = 'ROUMANIE';
        $countries['GB'] = 'ROYAUME-UNI';
        $countries['RU'] = 'RUSSIE, FÉDÉRATION DE';
        $countries['RW'] = 'RWANDA';
        $countries['EH'] = 'SAHARA OCCIDENTAL';
        $countries['BL'] = 'SAINT-BARTHÉLEMY';
        $countries['KN'] = 'SAINT-KITTS-ET-NEVIS';
        $countries['SM'] = 'SAINT-MARIN';
        $countries['SX'] = 'SAINT-MARTIN (PARTIE NÉERLANDAISE)';
        $countries['MF'] = 'SAINT-MARTIN(PARTIE FRANÇAISE)';
        $countries['PM'] = 'SAINT-PIERRE-ET-MIQUELON';
        $countries['VA'] = 'SAINT-SIÈGE (ÉTAT DE LA CITÉ DU VATICAN)';
        $countries['VC'] = 'SAINT-VINCENT-ET-LES GRENADINES';
        $countries['SH'] = 'SAINTE-HÉLÈNE, ASCENSION ET TRISTAN DA CUNHA';
        $countries['LC'] = 'SAINTE-LUCIE';
        $countries['SB'] = 'SALOMON, ÎLES';
        $countries['WS'] = 'SAMOA';
        $countries['AS'] = 'SAMOA AMÉRICAINES';
        $countries['ST'] = 'SAO TOMÉ-ET-PRINCIPE';
        $countries['SN'] = 'SÉNÉGAL';
        $countries['RS'] = 'SERBIE';
        $countries['SC'] = 'SEYCHELLES';
        $countries['SL'] = 'SIERRA LEONE';
        $countries['SG'] = 'SINGAPOUR';
        $countries['SK'] = 'SLOVAQUIE';
        $countries['SI'] = 'SLOVÉNIE';
        $countries['SO'] = 'SOMALIE';
        $countries['SD'] = 'SOUDAN';
        $countries['SS'] = 'SOUDAN DU SUD';
        $countries['LK'] = 'SRI LANKA';
        $countries['SE'] = 'SUÈDE';
        $countries['CH'] = 'SUISSE';
        $countries['SR'] = 'SURINAME';
        $countries['SJ'] = 'SVALBARD ET ÎLE JAN MAYEN';
        $countries['SZ'] = 'SWAZILAND';
        $countries['SY'] = 'SYRIENNE, RÉPUBLIQUE ARABE';
        $countries['TJ'] = 'TADJIKISTAN';
        $countries['TW'] = 'TAÏWAN, PROVINCE DE CHINE';
        $countries['TZ'] = 'TANZANIE, RÉPUBLIQUE-UNIE DE';
        $countries['TD'] = 'TCHAD';
        $countries['CZ'] = 'TCHÈQUE, RÉPUBLIQUE';
        $countries['TF'] = 'TERRES AUSTRALES FRANÇAISES';
        $countries['TH'] = 'THAÏLANDE';
        $countries['TL'] = 'TIMOR-LESTE';
        $countries['TG'] = 'TOGO';
        $countries['TK'] = 'TOKELAU';
        $countries['TO'] = 'TONGA';
        $countries['TT'] = 'TRINITÉ-ET-TOBAGO';
        $countries['TN'] = 'TUNISIE';
        $countries['TM'] = 'TURKMÉNISTAN';
        $countries['TC'] = 'TURKS-ET-CAÏCOS, ÎLES';
        $countries['TR'] = 'TURQUIE';
        $countries['TV'] = 'TUVALU';
        $countries['UA'] = 'UKRAINE';
        $countries['UY'] = 'URUGUAY';
        $countries['VU'] = 'VANUATU';
        $countries['VE'] = 'VENEZUELA, RÉPUBLIQUE BOLIVARIENNE DU';
        $countries['VN'] = 'VIET NAM';
        $countries['WF'] = 'WALLIS ET FUTUNA';
        $countries['YE'] = 'YÉMEN';
        $countries['ZM'] = 'ZAMBIE';
        $countries['ZW'] = 'ZIMBABWE';

        return $countries;
    }

    /**
     * Retourne la liste officielle des autres fédérations.
     *
     * @return array
     */
    public static function get_other_federations() {
        $federations = [];
        $federations['Fédération Française d\'Athlétisme'] = 'Fédération Française d\'Athlétisme';
        $federations['Fédération Française d\'Aviron'] = 'Fédération Française d\'Aviron';
        $federations['Fédération Française d\'Échecs'] = 'Fédération Française d\'Échecs';
        $federations['Fédération Française d\'Équitation'] = 'Fédération Française d\'Équitation';
        $federations['Fédération Française d\'Escrime'] = 'Fédération Française d\'Escrime';
        $federations['Fédération Française d\'Études et de Sports Sous-Marins'] =
            'Fédération Française d\'Études et de Sports Sous-Marins';
        $federations['Fédération Française d\'Haltérophilie, Musculation et Culturisme'] =
            'Fédération Française d\'Haltérophilie, Musculation et Culturisme';
        $federations['Fédération Française de Badminton'] = 'Fédération Française de Badminton';
        $federations['Fédération Française de Baseball et Softball'] = 'Fédération Française de Baseball et Softball';
        $federations['Fédération Française de Basket-ball'] = 'Fédération Française de Basket-ball';
        $federations['Fédération Française de Billard'] = 'Fédération Française de Billard';
        $federations['Fédération Française de Bowling et de Sports de Quilles'] =
            'Fédération Française de Bowling et de Sports de Quilles';
        $federations['Fédération Française de Boxe'] = 'Fédération Française de Boxe';
        $federations['Fédération Française de Bridge'] = 'Fédération Française de Bridge';
        $federations['Fédération Française de Canoë-Kayak'] = 'Fédération Française de Canoë-Kayak';
        $federations['Fédération Française de Course d\'Orientation'] = 'Fédération Française de Course d\'Orientation';
        $federations['Fédération Française de Cyclisme'] = 'Fédération Française de Cyclisme';
        $federations['Fédération Française de Danse'] = 'Fédération Française de Danse';
        $federations['Fédération Française de Darts'] = 'Fédération Française de Darts';
        $federations['Fédération Française de Flying Disc'] = 'Fédération Française de Flying Disc';
        $federations['Fédération Française de Football'] = 'Fédération Française de Football';
        $federations['Fédération Française de Football Américain'] = 'Fédération Française de Football Américain';
        $federations['Fédération Française de Football de Table'] = 'Fédération Française de Football de Table';
        $federations['Fédération Française de Force Athlétique'] = 'Fédération Française de Force Athlétique';
        $federations['Fédération Française de Golf'] = 'Fédération Française de Golf';
        $federations['Fédération Française de Gymnastique'] = 'Fédération Française de Gymnastique';
        $federations['Fédération Française de Handball'] = 'Fédération Française de Handball';
        $federations['Fédération Française de Hockey'] = 'Fédération Française de Hockey';
        $federations['Fédération Française de Hockey sur Glace'] = 'Fédération Française de Hockey sur Glace';
        $federations['Fédération Française de Judo et Disciplines Associées'] =
            'Fédération Française de Judo et Disciplines Associées';
        $federations['Fédération Française de Karaté'] = 'Fédération Française de Karaté';
        $federations['Fédération Française de Kickboxing, Muaythai et Disciplines Associées'] =
            'Fédération Française de Kickboxing, Muaythai et Disciplines Associées';
        $federations['Fédération Française de la Montagne et de l\'Escalade'] =
            'Fédération Française de la Montagne et de l\'Escalade';
        $federations['Fédération Française de Lutte et Disciplines Associées'] =
            'Fédération Française de Lutte et Disciplines Associées';
        $federations['Fédération Française de Natation'] = 'Fédération Française de Natation';
        $federations['Fédération Française de Pelote Basque'] = 'Fédération Française de Pelote Basque';
        $federations['Fédération Française de Pentathlon Moderne'] = 'Fédération Française de Pentathlon Moderne';
        $federations['Fédération Française de Pétanque et de Jeu Provençal'] =
            'Fédération Française de Pétanque et de Jeu Provençal';
        $federations['Fédération Française de Roller et Skateboard'] = 'Fédération Française de Roller et Skateboard';
        $federations['Fédération Française de Rugby'] = 'Fédération Française de Rugby';
        $federations['Fédération Française de Rugby A XIII'] = 'Fédération Française de Rugby A XIII';
        $federations['Fédération Française de Sauvetage et de Secourisme'] = 'Fédération Française de Sauvetage et de Secourisme';
        $federations['Fédération Française de Savate Boxe Française et Disciplines Associées'] =
            'Fédération Française de Savate Boxe Française et Disciplines Associées';
        $federations['Fédération Française de Ski'] = 'Fédération Française de Ski';
        $federations['Fédération Française de Sport Automobile'] = 'Fédération Française de Sport Automobile';
        $federations['Fédération Française de Squash'] = 'Fédération Française de Squash';
        $federations['Fédération Française de Surf'] = 'Fédération Française de Surf';
        $federations['Fédération Française de Taekwondo et Disciplines Associées'] =
            'Fédération Française de Taekwondo et Disciplines Associées';
        $federations['Fédération Française de Tennis'] = 'Fédération Française de Tennis';
        $federations['Fédération Française de Tennis de Table'] = 'Fédération Française de Tennis de Table';
        $federations['Fédération Française de Tir'] = 'Fédération Française de Tir';
        $federations['Fédération Française de Tir à l\'Arc'] = 'Fédération Française de Tir à l\'Arc';
        $federations['Fédération Française de Triathlon et Disciplines Enchaînées'] =
            'Fédération Française de Triathlon et Disciplines Enchaînées';
        $federations['Fédération Française de Voile'] = 'Fédération Française de Voile';
        $federations['Fédération Française de Volley'] = 'Fédération Française de Volley';
        $federations['Fédération Française des Sports de Glace'] = 'Fédération Française des Sports de Glace';

        return $federations;
    }

    /**
     * Retourne la liste officielle des cycles d'études.
     *
     * @return array
     */
    public static function get_study_cycles() {
        $cycles = [];
        $cycles['BAC+1'] = 'BAC+1';
        $cycles['BAC+2'] = 'BAC+2';
        $cycles['BAC+3'] = 'BAC+3';
        $cycles['BAC+4'] = 'BAC+4';
        $cycles['BAC+5'] = 'BAC+5';
        $cycles['BAC+6 et plus'] = 'BAC+6 et plus';

        return $cycles;
    }

    /**
     * Retourne la liste des noms officiels des activités FFSU.
     *
     * @return array
     */
    public static function get_user_titles() {
        $types = [];
        $types['Mme'] = get_string('madam', 'local_apsolu');
        $types['M'] = get_string('mister', 'local_apsolu');

        return $types;
    }

    /**
     * Retourne si l'adhesion contient au moins un sport à contrainte.
     *
     * @return boolean
     */
    public function has_constraint_sports() {
        $constraintsports = [];
        foreach (Activity::get_records(['restriction' => 1]) as $activity) {
            $constraintsports[$activity->code] = $activity->name;
        }

        $data = $this->decode_data();
        if (isset($data->activity) === false) {
            $data->activity = [];
        }

        foreach ($data->activity as $activity) {
            if (isset($constraintsports[$activity]) === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retourne si l'adhesion nécessite le dépôt d'un certificat médical.
     *
     * @return boolean
     */
    public function have_to_upload_medical_certificate() {
        if ($this->has_constraint_sports() === true) {
            return true;
        }

        return ($this->questionnairestatus == self::HEALTH_QUESTIONNAIRE_ANSWERED_YES_ONCE);
    }

    /**
     * Retourne si l'adhésion nécessite le dépôt d'une autorisation parentale.
     *
     * @return boolean|null Retourne null si la variable birthday n'a pas été initialisée.
     */
    public function have_to_upload_parental_authorization() {
        return $this->is_minor();
    }

    /**
     * Indique si l'adhésion a été rempli par un mineur.
     *
     * @return boolean|null Retourne null si la variable birthday n'a pas été initialisée.
     */
    public function is_minor() {
        if (is_numeric($this->birthday) === false) {
            return null;
        }

        $datetime = new DateTime();
        $datetime->setTimestamp($this->birthday);

        $major = new DateTime('-18 years');

        return $datetime >= $major;
    }

    /**
     * Valide le format du numéro du Pass Sport.
     *
     * @param string $number Numéro du Pass Sport.
     *
     * @return boolean
     */
    public static function is_valid_pass_sport_number(string $number) {
        return (preg_match(self::PASS_SPORT_PATTERN, $number) === 1);
    }

    /**
     * Génère une chaine en JSON contenant les données du formulaire.
     *
     * @param object $data Objet mform.
     *
     * @return string Données au format JSON.
     */
    public function make_json($data) {
        global $DB;

        if (is_string($this->data) === true) {
            $json = json_decode($this->data);
            if ($json === false) {
                $this->data = '';
            }
        }

        if (empty($this->data) === true) {
            // Initialise les données JSON.
            $json = new stdClass();
        }

        if ($data === null || isset($data->step) === false) {
            return json_encode($json);
        }

        switch ($data->step) {
            case APSOLU_PAGE_MEMBERSHIP:
                $json->title = $data->title;
                $json->postalcode = $data->postalcode ?? '';
                $json->city = $data->city ?? '';
                $json->phone1 = '';
                $json->phone2 = preg_replace('/[^0-9]/', '', $data->phone2);
                $json->licenseetype = $data->licenseetype ?? '';
                $json->handicap = $data->handicap;
                $json->licensetype = $data->licensetype ?? '';
                $json->nationality = $data->nationality ?? '';
                $json->birthname = $data->birthname ?? '';
                $json->birthcountry = $data->birthcountry ?? '';
                $json->birthdepartment = '';
                $json->birthtown = $data->birthtown ?? '';
                $json->birthplace = $data->birthplace ?? '';
                $json->activity = $data->activity;
                $json->insurance = $data->insurance ?? '';
                $json->cursus = $data->cursus ?? '';
                $json->studycycle = $data->studycycle ?? '';
                $json->otherfederation = $data->otherfederation ?? '';
                $json->federaltexts = $data->federaltexts;
                $json->policyagreed = $data->policyagreed;
                $json->commercialoffers = $data->commercialoffers;
                $json->usepersonalimage = $data->usepersonalimage;
                $json->newsletter = $data->newsletter;

                if (self::require_honorability($json->licensetype) === false) {
                    $json->birthname = '';
                    $json->birthcountry = '';
                    $json->birthtown = '';
                    $json->birthplace = '';
                    $json->birthdepartment = '';
                } else if (empty($json->birthtown) === false && is_string($json->birthtown) === true) {
                    $json->birthplace = '';

                    $municipalities = $DB->get_records('apsolu_municipalities', ['inseecode' => $json->birthtown]);
                    if ($municipalities !== []) {
                        $municipality = current($municipalities);
                        $json->birthdepartment = $municipality->departmentid;
                    }
                } else if (empty($json->birthplace) === false) {
                    $json->birthtown = '';
                    $json->birthdepartment = '';
                }

                $getconfig = get_config('local_apsolu');
                foreach (['licenseetype', 'licensetype', 'insurance', 'otherfederation'] as $field) {
                    $visibility = sprintf('%s_field_visibility', $field);
                    if (isset($getconfig->$visibility) === false || empty($getconfig->$visibility) === false) {
                        // Le champ est visible. L'utilisateur a pu saisir une donnée.
                        continue;
                    }

                    $default = sprintf('%s_field_default', $field);
                    if (isset($getconfig->$default) === false) {
                        $getconfig->$default = '';
                    }

                    $json->$field = $getconfig->$default;
                }

                break;
            case APSOLU_PAGE_PARENTAL_AUTHORIZATION:
                $json->titleprimarylegalrepresentative = $data->titleprimarylegalrepresentative;
                $json->lastnameprimarylegalrepresentative = $data->lastnameprimarylegalrepresentative;
                $json->firstnameprimarylegalrepresentative = $data->firstnameprimarylegalrepresentative;
                $json->phoneprimarylegalrepresentative = $data->phoneprimarylegalrepresentative;
                $json->emailprimarylegalrepresentative = $data->emailprimarylegalrepresentative;
                $json->titlesecondarylegalrepresentative = $data->titlesecondarylegalrepresentative;
                $json->lastnamesecondarylegalrepresentative = $data->lastnamesecondarylegalrepresentative;
                $json->firstnamesecondarylegalrepresentative = $data->firstnamesecondarylegalrepresentative;
                $json->phonesecondarylegalrepresentative = $data->phonesecondarylegalrepresentative;
                $json->emailsecondarylegalrepresentative = $data->emailsecondarylegalrepresentative;
                break;
            case APSOLU_PAGE_MEDICAL_CERTIFICATE:
                $json->doctorname = $data->doctorname;
                $json->doctorrpps = $data->doctorrpps;
                $json->medicalcertificatedate = $data->medicalcertificatedate;
                break;
        }

        return json_encode($json);
    }

    /**
     * Notifie les contacts fonctionnels pour signaler les nouvelles demandes de licence FFSU.
     *
     * @return bool Retourne false lorsque l'adresse de contact fonctionnel est vide.
     */
    public function notify_functional_contact() {
        global $DB;

        $user = $DB->get_record('user', ['id' => $this->userid]);
        if ($user === false) {
            // L'utilisateur n'existe plus.
            return false;
        }

        // Notifie l'adresse du contact fonctionnel pour valider l'adhésion.
        $functionalcontact = get_config('local_apsolu', 'functional_contact');
        if (empty($functionalcontact) === true) {
            return false;
        }

        $strarguments = ['federationnumberprefix' => $this->federationnumberprefix, 'institution' => $user->institution];
        $subject = get_string('request_of_federation_number_subject', 'local_apsolu', $strarguments);

        $extra = [];

        $parameters = [];
        $parameters['fullname'] = fullname($user);
        $parameters['export_url'] = (string) new moodle_url('/local/apsolu/federation/index.php', ['page' => 'export']);
        if ($this->have_to_upload_medical_certificate() === true && empty($this->medicalcertificatestatus) === true) {
            // Le certificat médical doit être validé.
            $parameters = ['page' => 'certificates_validation'];
            if (empty($user->idnumber) === false) {
                $parameters = ['idnumber' => $user->idnumber];
            }
            $url = (string) new moodle_url('/local/apsolu/federation/index.php', $parameters);

            $extra[] = get_string('request_of_federation_number_with_medical_certificate', 'local_apsolu', $url);
        }

        if ($this->have_to_upload_parental_authorization() === true) {
            // L'autorisation parentale doit être validée.

            $parameters = ['page' => 'certificates_validation'];
            if (empty($user->idnumber) === false) {
                $parameters = ['idnumber' => $user->idnumber];
            }
            $url = (string) new moodle_url('/local/apsolu/federation/index.php', $parameters);

            $extra[] = get_string('request_of_federation_number_with_medical_certificate', 'local_apsolu', $url);
        }

        if (empty($this->passsportnumber) === false) {
            // Le Pass Sport doit être validé.
            $parameters = ['page' => 'pass_sport_validation'];
            if (empty($user->idnumber) === false) {
                $parameters = ['idnumber' => $user->idnumber];
            }
            $url = (string) new moodle_url('/local/apsolu/federation/index.php', $parameters);

            $extra[] = get_string('request_of_federation_number_with_pass_sport', 'local_apsolu', $url);
        }

        $parameters['extra'] = implode('', $extra);
        $messagetext = get_string('request_of_federation_number_message', 'local_apsolu', $parameters);

        // Solution de contournement pour pouvoir envoyer un message à une adresse mail n'appartenant pas
        // à un utilisateur Moodle.
        $admin = get_admin();
        $admin->auth = 'manual'; // Force l'auth. en manual, car email_to_user() ignore le traitement des comptes en nologin.
        $admin->email = $functionalcontact;

        email_to_user($admin, $user, $subject, $messagetext);

        // Enregistre un évènement.
        $federationcourse = new FederationCourse();
        $federationcourse->id = $federationcourse->get_courseid();
        $context = context_course::instance($federationcourse->id, MUST_EXIST);

        $event = notification_sent::create([
            'relateduserid' => $user->id,
            'context' => $context,
            'other' => ['sender' => $user->id, 'receiver' => $admin->email, 'subject' => $subject],
            ]);
        $event->trigger();

        return true;
    }

    /**
     * Détermine si une des licences choisies est soumise au contrôle de l'honorabilité.
     *
     * @param array $licences Un tableau contenant les codes des licences.
     *
     * @return boolean
     */
    public static function require_honorability(array $licences) {
        // Licences soumises au contrôle de l'honorabilité : Encadrant (E), Dirigeant (D) et Arbitre (A).
        foreach (self::get_licenses_with_honorability() as $type) {
            if (isset($licences[$type]) === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * Enregistre un objet en base de données.
     *
     * @throws dml_exception A DML specific exception is thrown for any errors.
     *
     * @param object|null $data  StdClass représentant l'objet à enregistrer.
     * @param object|null $mform Mform représentant un formulaire Moodle nécessaire à la gestion d'un champ de type editor.
     * @param bool        $check Témoin permettant de passer les vérifications avant l'enregistrement des données.
     *
     * @return void
     */
    public function save(?object $data = null, ?object $mform = null, bool $check = true) {
        global $DB, $USER;

        $federationcourse = new FederationCourse();
        $courseid = $federationcourse->get_courseid();

        if ($data !== null) {
            $this->data = $this->make_json($data);
            $this->set_vars($data);
        }

        if ($check === true) {
            if ($this->can_edit() === false) {
                throw new Exception(get_string('your_medical_certificate_has_already_been_validated', 'local_apsolu'));
            }

            $json = json_decode($this->data);

            // Ajoute/retire l'étudiant de la cohorte assurance FFSU.
            $insurancecohortid = get_config('local_apsolu', 'insurance_cohort');
            if (empty($insurancecohortid) === false) {
                if (empty($this->insurance) === false) {
                    cohort_add_member($insurancecohortid, $this->userid);
                } else {
                    cohort_remove_member($insurancecohortid, $this->userid);
                }
            }

            // Recalcule l'état attendu du certificat médical lorsqu'il n'a pas été validé.
            if ($this->medicalcertificatestatus !== self::MEDICAL_CERTIFICATE_STATUS_VALIDATED) {
                if ($this->have_to_upload_medical_certificate() === true) {
                    $this->medicalcertificatestatus = self::MEDICAL_CERTIFICATE_STATUS_PENDING;
                } else {
                    // Nettoie les données.
                    $json->doctorname = '';
                    $json->doctorrpps = '';
                    $json->medicalcertificatedate = '';

                    // TODO: supprimer le fichier.

                    $this->medicalcertificatestatus = self::MEDICAL_CERTIFICATE_STATUS_EXEMPTED;
                }
            }

            // Recalcule les groupes.
            $groups = [];
            foreach (groups_get_all_groups($courseid) as $group) {
                $groups[$group->name] = $group;
            }

            $usergroups = [];
            foreach (groups_get_all_groups($courseid, $this->userid) as $group) {
                $usergroups[$group->name] = $group;
            }

            $activities = $DB->get_records(Activity::TABLENAME, [], $sort = 'name', $field = 'code, name');
            foreach ($json->activity as $code) {
                if (isset($activities[$code]) === false) {
                    continue;
                }

                $activity = $activities[$code]->name;
                if (isset($usergroups[$activity]) === true) {
                    unset($usergroups[$activity]);
                    continue;
                }

                if (isset($groups[$activity]) === false) {
                    continue;
                }

                groups_add_member($groups[$activity]->id, $this->userid);
            }

            foreach ($usergroups as $group) {
                groups_remove_member($group->id, $this->userid);
            }

            $this->data = json_encode($json);
        }

        if (empty($this->id) === true) {
            $this->timecreated = time();
            $this->timemodified = $this->timecreated;

            $this->id = $DB->insert_record(self::TABLENAME, $this);
        } else {
            if ($USER->id == $this->userid) {
                $this->timemodified = time();
            }

            $DB->update_record(self::TABLENAME, $this);
        }

        // Enregistre un évènement dans les logs.
        $event = federation_adhesion_updated::create([
            'objectid' => $this->id,
            'context' => context_course::instance($courseid),
            ]);
        $event->trigger();
    }
}
