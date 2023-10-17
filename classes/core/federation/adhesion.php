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
 * Classe gérant les adhésions.
 *
 * @package    local_apsolu
 * @copyright  2022 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_apsolu\core\federation;

use context_course;
use DateTime;
use Exception;
use local_apsolu\core\federation\course as FederationCourse;
use local_apsolu\core\federation\activity as Activity;
use local_apsolu\core\federation\number as Number;
use local_apsolu\core\record;
use local_apsolu\event\federation_adhesion_updated;

require_once($CFG->dirroot.'/cohort/lib.php');

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

    /**
     * Valeur aucun sport des champs sportN et constraintsportN.
     */
    const SPORT_NONE = '1';

    /** @var int|string $id Identifiant numérique de la correspondance d'activités. */
    public $id = 0;

    /** @var string $birthname Nom de naissance de l'adhérent. */
    public $birthname = '';

    /** @var string $sex Sexe de l'adhérent (M ou F). */
    public $sex = '';

    /** @var int|string $insurance Assurance IA FFSU (1: oui, 0: non). */
    public $insurance = '';

    /** @var string $birthday Date de naissance de l'adhérent (AAAA-MM-JJ). */
    public $birthday = '';

    /** @var string $nativecountry Pays de naissance de l'adhérent. */
    public $nativecountry = '';

    /** @var int|string $departmentofbirth Identifiant numérique du département de naissance de l'adhérent. */
    public $departmentofbirth = '0';

    /** @var string $cityofbirth Ville de naissance de l'adhérent. */
    public $cityofbirth = '';

    /** @var string $address1 Adresse 1 de l'adhérent. */
    public $address1 = '';

    /** @var string $address2 Adresse 2 de l'adhérent. */
    public $address2 = '';

    /** @var string $postalcode Code postal de l'adhérent. */
    public $postalcode = '';

    /** @var string $city Ville de l'adhérent. */
    public $city = '';

    /** @var string $phone Téléphone de l'adhérent. */
    public $phone = '';

    /** @var string $instagram Identifiant Instagram. */
    public $instagram = '';

    /** @var int|string $disciplineid Identifiant numérique du secteur d'étude. */
    public $disciplineid = '';

    /** @var string $otherfederation Éventuelle licence dans une autre fédération. */
    public $otherfederation = '';

    /** @var int|string $mainsport Identifiant numérique du sport principal. */
    public $mainsport = '';

    /** @var int|string $complementaryconstraintsport Témoin booléen de pratique complémentaire de sports à contraintes (1: oui, 0: non). */
    public $complementaryconstraintsport = 0;

    /** @var int|string $sportlicense Booléen indiquant si l'adhésion comprend une licence sport (1: oui, 0: non). */
    public $sportlicense = 1;

    /** @var int|string $managerlicense Booléen indiquant si l'adhésion comprend une licence dirigeant (1: oui, 0: non). */
    public $managerlicense = 0;

    /** @var int|string $refereelicense Booléen indiquant si l'adhésion comprend une licence arbitre (1: oui, 0: non). */
    public $refereelicense = 0;

    /** @var int|string $honorabilityagreement Booléen indiquant l'acceptation du contrôle d'honorabilité pour arbitres et
        dirigeants (1: oui, 0: non). */
    public $honorabilityagreement = 0;

    /** @var int|string $managerlicensetype Pour licence dirigeant pour Non-étudiant/Etudiant (1: pour étudiant, 0: pour non-étudiant). */
    public $managerlicensetype = 0;

    /** @var string $starlicense Licence étoile (O: oui, N: non). */
    public $starlicense = '';

    /** @var int|string $usepersonalimage Booléen indiquant si le droit à l'image a été donnée (1: oui, 0: non). */
    public $usepersonalimage = null;

    /** @var int|string $usepersonaldata Booléen indiquant si le consentement d'utilisation des données personnelles
        a été donnée (1: oui, 0: non). */
    public $usepersonaldata = null;

    /** @var int|string $sport1 Identifiant numérique de l'activité choisie sans contrainte médicale. */
    public $sport1 = self::SPORT_NONE;

    /** @var int|string $sport2 Identifiant numérique de l'activité choisie sans contrainte médicale. */
    public $sport2 = self::SPORT_NONE;

    /** @var int|string $sport3 Identifiant numérique de l'activité choisie sans contrainte médicale. */
    public $sport3 = self::SPORT_NONE;

    /** @var int|string $sport4 Identifiant numérique de l'activité choisie sans contrainte médicale. */
    public $sport4 = self::SPORT_NONE;

    /** @var int|string $sport5 Identifiant numérique de l'activité choisie sans contrainte médicale. */
    public $sport5 = self::SPORT_NONE;

    /** @var int|string $constraintsport1 Identifiant numérique de l'activité choisie avec contrainte médicale. */
    public $constraintsport1 = self::SPORT_NONE;

    /** @var int|string $constraintsport2 Identifiant numérique de l'activité choisie avec contrainte médicale. */
    public $constraintsport2 = self::SPORT_NONE;

    /** @var int|string $constraintsport3 Identifiant numérique de l'activité choisie avec contrainte médicale. */
    public $constraintsport3 = self::SPORT_NONE;

    /** @var int|string $constraintsport4 Identifiant numérique de l'activité choisie avec contrainte médicale. */
    public $constraintsport4 = self::SPORT_NONE;

    /** @var int|string $constraintsport5 Identifiant numérique de l'activité choisie avec contrainte médicale. */
    public $constraintsport5 = self::SPORT_NONE;

    /** @var string $medicalcertificatedate Date du certificat médical. */
    public $medicalcertificatedate = '';

    /** @var int|string $medicalcertificatestatus État de validation du certificat médical. */
    public $medicalcertificatestatus = null;

    /** @var int|string $questionnairestatus État des réponses au questionnaire médical. */
    public $questionnairestatus = null;

    /** @var int|string $agreementaccepted État d'acceptation de la charte. */
    public $agreementaccepted = null;

    /** @var string $federationnumberprefix Préfixe utilisé pour le numéro FFSU (4 caractères). */
    public $federationnumberprefix = null;

    /** @var string $federationnumber Numéro FFSU de l'adhérant. */
    public $federationnumber = null;

    /** @var int|string $timemodified Timestamp Unix de la demande de numéro de licence. */
    public $federationnumberrequestdate = null;

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
     * Retourne un tableau d'entiers contenant les identifiants des activités choisies par l'adhérent.
     *
     * @return array
     */
    public function get_activities() {
        $activities = array();
        foreach (self::get_activity_fields() as $field) {
            if (isset($this->{$field}) === false) {
                continue;
            }

            if ($this->{$field} === self::SPORT_NONE) {
                // Ignore les champs ne contenant pas une activité.
                continue;
            }

            if (in_array($field, $activities, $strict = true) === true) {
                // Empêche l'ajout de doublons.
                continue;
            }

            $activities[] = $this->{$field};
        }

        return $activities;
    }

    /**
     * Retourne un tableau de chaines contenant la liste des champs représentant une activité.
     *
     * @return array
     */
    public static function get_activity_fields() {
        $fields = [];
        $fields[] = 'mainsport';
        $fields[] = 'sport1';
        $fields[] = 'sport2';
        $fields[] = 'sport3';
        $fields[] = 'sport4';
        $fields[] = 'sport5';
        $fields[] = 'constraintsport1';
        $fields[] = 'constraintsport2';
        $fields[] = 'constraintsport3';
        $fields[] = 'constraintsport4';
        $fields[] = 'constraintsport5';

        return $fields;
    }

    /**
     * Retourne un tableau de chaînes indiquant que le certificat a été validé. Ajoute éventuellement l'adresse du contact fonctionnel.
     *
     * @return array
     */
    public static function get_contacts() {
        $messages = array();
        $messages[] = get_string('your_medical_certificate_has_already_been_validated', 'local_apsolu');

        $functional_contact_mail = get_config('local_apsolu', 'functional_contact');
        if (filter_var($functional_contact_mail, FILTER_VALIDATE_EMAIL) !== false) {
            $messages[] = get_string('if_you_want_to_make_a_change_please_contact_X', 'local_apsolu', $functional_contact_mail);
        }

        return $messages;
    }

    /**
     * Retourne la liste des départements français.
     *
     * @return array
     */
    public static function get_departments() {
        $departments = array();
        $departments['0'] = 'Autre';
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
        $departments['2A'] = 'Corse-du-Sud';
        $departments['2B'] = 'Haute-Corse';
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

        return $departments;
    }

    /**
     * Retourne la liste des noms officiels des activités FFSU.
     *
     * @return array
     */
    public static function get_disciplines() {
        $disciplines = array();
        $disciplines[15] = 'Animation';
        $disciplines[13] = 'Architecture';
        $disciplines[17] = 'Arts';
        $disciplines[18] = 'Audiovisuel';
        $disciplines[4] = 'Commerce';
        $disciplines[14] = 'Communication';
        $disciplines[19] = 'Défense';
        $disciplines[11] = 'Dirigeant';
        $disciplines[1] = 'Droit / Sciences Po.';
        $disciplines[9] = 'Enseignement';
        $disciplines[10] = 'Ingénierie';
        $disciplines[7] = 'Langues';
        $disciplines[3] = 'Lettres';
        $disciplines[8] = 'Médecine / Santé';
        $disciplines[6] = 'Métiers du Sport';
        $disciplines[2] = 'Science Eco./Gestion';
        $disciplines[5] = 'Sciences';
        $disciplines[16] = 'Sciences Humaines';
        $disciplines[12] = 'Technique';

        return $disciplines;
    }

    /**
     * Retourne la liste des noms officiels des activités FFSU.
     *
     * @return array
     */
    public static function get_exportation_fields() {
        return array(
            'federationnumberprefix', // Champ A : N° A.S. [ 4 caractères ].
            'lastname', // Champ B : Nom [ 25 caractères ].
            'birthname', // Champ C : Nom de naissance [ 25 caractères ].
            'firstname', // Champ D : Prénom [ 25 caractères ].
            'sex', // Champ E : Sexe [ 1 caractère : M ou F ].
            'birthdayformat', // Champ F : Date de naissance [ au format AAAA-MM-JJ ].
            'nativecountry', // Champ G : Pays de naissance [ 25 caractères ].
            'departmentofbirth', // Champ H : Département de naissance [ 3 caractères ] de 01 à 19, 2A, 2B, de 21 à 95, 971, 972,
                // 973, 974, 975, 976, 0 pour autre.
            'cityofbirth', // Champ I : Ville de naissance [ 25 caractères ].
            'address1', // Champ J : Adresse 1 [ 30 caractères ].
            'address2', // Champ K : Adresse 2 [ 30 caractères ] Vous pouvez laisser vide ce champ.
            'postalcode', // Champ L : Code postal [ 5 caractères numériques ].
            'city', // Champ M : Ville [ 25 caractères ].
            'phone', // Champ N : Téléphone [ 20 caractères ] Vous pouvez laisser vide ce champ.
            'email', // Champ O : Email [ 50 caractères ].
            'instagram', // Champ p : Instagram [ 30 caractères ].
            'disciplineid', // Champ Q : Discipline-Cursus [ 1 chiffre entre 1 et 10 (*) ].
            'otherfederation', // Champ R : Autre fédé [ 30 caractères ].
            'mainsportname', // Champ S : Sport [ 20 caractères (**)].
            'sportlicense', // Champ T : Licence sportive [ 1 chiffre : 1 pour oui ou 0 pour non ].
            'managerlicense', // Champ U : Licence dirigeant [ 1 chiffre : 1 pour oui ou 0 pour non ].
            'refereelicense', // Champ V : Licence arbitre [ 1 chiffre : 1 pour oui ou 0 pour non ].
            'managerlicensetype', // Champ W : Pour licence sportive ou dirigeant : Non-étudiant/Etudiant [ 1 chiffre
                // : 1 pour Etudiant ou 0 pour Non-étudiant ].
            'starlicense', // Champ X : Licence étoile [ 1 caractère : O ou N ].
            'usepersonalimage', // Champ Y : Autorisation Droit à l’image[ 1 chiffre : 1 pour oui ou 0 pour non ].
            'usepersonaldata', // Champ Z : Autorisation Loi Informatique & Libertés[ 1 chiffre : 1 pour oui ou 0 pour non ].
            'insurance', // Champ AA : Assurance [ 1 chiffre : 1 pour oui ou 0 pour non ].
            'sport1', // Champ AB : Certificat médical - activité 1 sans contrainte particulière [ 1 nombre entre 1 et 62(***)].
            'sport2', // Champ AC : Certificat médical - activité 2 sans contrainte particulière [ 1 nombre entre 1 et 62(***)].
            'sport3', // Champ AD : Certificat médical - activité 3 sans contrainte particulière [ 1 nombre entre 1 et 62(***)].
            'sport4', // Champ AE : Certificat médical - activité 4 sans contrainte particulière [ 1 nombre entre 1 et 62(***)].
            'sport5', // Champ AF : Certificat médical - activité 5 sans contrainte particulière [ 1 nombre entre 1 et 62(***)].
            'constraintsport1', // Champ AG:Certificat médical-activité 1 à contraintes particulières [1 nombre entre 1 et 63(****)].
            'constraintsport2', // Champ AH:Certificat médical-activité 2 à contraintes particulières [1 nombre entre 1 et 63(****)].
            'constraintsport3', // Champ AI:Certificat médical-activité 3 à contraintes particulières [1 nombre entre 1 et 63(****)].
            'constraintsport4', // Champ AJ:Certificat médical-activité 4 à contraintes particulières [1 nombre entre 1 et 63(****)].
            'constraintsport5', // Champ AK:Certificat médical-activité 5 à contraintes particulières [1 nombre entre 1 et 63(****)].
            'medicalcertificatedateformat', // Champ AL : Date du certificat médical [ au format AAAA-MM-JJ ].
            'questionnairestatusno', // Champ AM : J’ai répondu NON à toutes les questions du questionnaire de santé (je peux
                // pratiquer TOUTES les activités sans contrainte particulière sans fournir de
                // certificat médical)[ 1 chiffre : 1 pour oui ou 0 pour non ].
            'questionnairestatusyes', // Champ AN : J’ai répondu OUI à une rubrique du questionnaire de santé et atteste avoir
                // présenté un certificat médical de non-contre-indication à la pratique d’un/des sport.s en compétition
                // de moins de 6 mois [ 1 chiffre : 1 pour oui ou 0 pour non ].
            'medicalcertificatestatus', // Champ AO : Je souhaite pratiquer une activité à contraintes particulières
                // (Rugby(s), Boxe(s) combat plein contact, Tir sportif, Biathlon, Karting, Pentathlon, Taekwondo combat)
                // et atteste avoir présenté un certificat médical de non-contre-indication à la pratique des sports de
                // compétition de moins d’un an [ 1 chiffre : 1 pour oui ou 0 pour non ]
            'honorabilityagreement', // Champ AP : J'atteste avoir compris l'objet du contrôle d'honorabilité pour arbitres
                // et dirigeants [ 1 chiffre : 1 pour oui ou 0 pour non ]
        );
    }

    /**
     * Retourne la liste des noms officiels des activités FFSU.
     *
     * @return array
     */
    public static function get_exportation_headers() {
        return array(
            get_string('association_number', 'local_apsolu'),
            get_string('lastname'),
            get_string('birthname', 'local_apsolu'),
            get_string('firstname'),
            get_string('sex', 'local_apsolu'),
            get_string('birthday', 'local_apsolu'),
            get_string('native_country', 'local_apsolu'),
            get_string('department_of_birth', 'local_apsolu'),
            get_string('city_of_birth', 'local_apsolu'),
            get_string('address1', 'local_apsolu'),
            get_string('address2', 'local_apsolu'),
            get_string('postal_code', 'local_apsolu'),
            get_string('city'),
            get_string('phone'),
            get_string('email'),
            get_string('instagram', 'local_apsolu'),
            get_string('discipline', 'local_apsolu'),
            get_string('other_federation', 'local_apsolu'),
            get_string('sport', 'local_apsolu'),
            get_string('sport_license', 'local_apsolu'),
            get_string('manager_license', 'local_apsolu'),
            get_string('referee_license', 'local_apsolu'),
            get_string('manager_license_type', 'local_apsolu'),
            get_string('star_license', 'local_apsolu'),
            get_string('use_personal_image', 'local_apsolu'),
            get_string('use_personal_data', 'local_apsolu'),
            get_string('insurance', 'local_apsolu'),
            get_string('medical_certificate_X_without_constraint', 'local_apsolu', 1),
            get_string('medical_certificate_X_without_constraint', 'local_apsolu', 2),
            get_string('medical_certificate_X_without_constraint', 'local_apsolu', 3),
            get_string('medical_certificate_X_without_constraint', 'local_apsolu', 4),
            get_string('medical_certificate_X_without_constraint', 'local_apsolu', 5),
            get_string('medical_certificate_X_with_specific_constraints', 'local_apsolu', 1),
            get_string('medical_certificate_X_with_specific_constraints', 'local_apsolu', 2),
            get_string('medical_certificate_X_with_specific_constraints', 'local_apsolu', 3),
            get_string('medical_certificate_X_with_specific_constraints', 'local_apsolu', 4),
            get_string('medical_certificate_X_with_specific_constraints', 'local_apsolu', 5),
            get_string('medical_certificate_date', 'local_apsolu'),
            get_string('i_answered_no_to_all_the_questions_in_the_health_questionnaire_short', 'local_apsolu'),
            get_string('i_answered_yes_to_a_section_of_the_health_questionnaire_and_attest_to_having_presented_a_medical_certificate_short', 'local_apsolu'),
            get_string('i_wish_to_practice_an_activity_with_particular_constraints_and_certify_that_i_have_presented_a_medical_certificate_short', 'local_apsolu'),
            get_string('i_certify_that_i_understand_the_purpose_of_the_integrity_check_for_arbitrators_and_managers', 'local_apsolu'),
        );
    }

    /**
     * Calcule le préfixe de numéro FFSU à attribuer à l'étudiant en fonction de différents critères de son profil.
     *
     * @return string|false Retourne false si aucun critère ne correspond à l'utilisateur.
     */
    public function get_federation_number_prefix() {
        global $DB;

        $user = $DB->get_record('user', array('id' => $this->userid), $fields = '*', MUST_EXIST);

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
     * Retourne l'identifiant d'une activité FFSU en fonction du groupe d'appartenance de l'utilisateur.
     *
     * @param int $federationcourseid   Identifiant du cours FFSU.
     * @param int $userid Identifiant de l'utilisateur.
     *
     * @return int|null Retourne l'identifiant d'une activité FFSU ou null si le groupe ne correspond à aucune activité.
     */
    public static function get_mainsportid_from_user_group($federationcourseid, $userid) {
        $groups = groups_get_user_groups($federationcourseid, $userid);
        if (isset($groups[0]) === true) {
            foreach ($groups[0] as $groupid) {
                $group = groups_get_group($groupid);
                foreach (Activity::get_records(array('name' => $group->name)) as $activity) {
                    return $activity->id;
                }
            }
        }

        return null;
    }

    /**
     * Retourne la liste des noms officiels des activités FFSU.
     *
     * @return array
     */
    public static function get_manager_types() {
        $types = array();
        $types[1] = get_string('student', 'local_apsolu');
        $types[0] = get_string('not_student', 'local_apsolu');

        return $types;
    }

    /**
     * Retourne la liste des noms officiels des activités FFSU.
     *
     * @return array
     */
    public static function get_sexes() {
        $types = array();
        $types['F'] = get_string('woman', 'local_apsolu');
        $types['M'] = get_string('man', 'local_apsolu');

        return $types;
    }

    /**
     * Retourne la liste des valeurs possibles pour le champ starlicense.
     *
     * @return array
     */
    public static function get_star_license_values() {
        $values = array();
        $values['O'] = get_string('yes');
        $values['N'] = get_string('no');

        return $values;
    }

    /**
     * Retourne si l'adhesion contient au moins un sport à contrainte.
     *
     * @return boolean
     */
    public function has_constraint_sports() {
        $constraintsports = Activity::get_records(array('restriction' => 1));

        if (isset($constraintsports[$this->mainsport]) === true) {
            return true;
        }

        if (empty($this->complementaryconstraintsport) === false) {
            return true;
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
        $enablecontrol = get_config('local_apsolu', 'parental_authorization_enabled');
        if (empty($enablecontrol) === true) {
            return false;
        }

        return $this->is_minor();
    }

    /**
     * Indique si l'adhésion a été rempli par un mineur.
     *
     * @return boolean|null Retourne null si la variable birthday n'a pas été initialisée.
     */
    public function is_minor() {
        if (ctype_digit($this->birthday) === false) {
            return null;
        }

        $datetime = new DateTime();
        $datetime->setTimestamp($this->birthday);

        $major = new DateTime('-18 years');

        return $datetime >= $major;
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
    public function save(object $data = null, object $mform = null, bool $check = true) {
        global $DB, $USER;

        $federationcourse = new FederationCourse();
        $courseid = $federationcourse->get_courseid();

        if ($data !== null) {
            $this->set_vars($data);
        }

        if ($check === true) {
            if ($this->can_edit() === false) {
                throw new Exception(get_string('your_medical_certificate_has_already_been_validated', 'local_apsolu'));
            }

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
                    $this->medicalcertificatestatus = self::MEDICAL_CERTIFICATE_STATUS_EXEMPTED;
                }
            }

            if ($this->have_to_upload_medical_certificate() === false) {
                // Si l'utilisateur n'a pas de certificat à déposer, on réinitialise tout.
                foreach (array('sport', 'constraintsport') as $sport) {
                    for ($i = 1; $i <= 5; $i++) {
                        $property = $sport.$i;
                        $this->{$property} = self::SPORT_NONE;
                    }
                }
            } else {
                // Recalcule les valeurs sportN et constraintsportN.
                if ($this->has_constraint_sports() === true) {
                    $sportkeeped = array('constraintsport1', 'constraintsport2', 'constraintsport3', 'constraintsport4', 'constraintsport5');
                    $sportremoved = array('sport1', 'sport2', 'sport3', 'sport4', 'sport5');
                    $constraint = 1;
                } else {
                    $sportkeeped = array('sport1', 'sport2', 'sport3', 'sport4', 'sport5');
                    $sportremoved = array('constraintsport1', 'constraintsport2', 'constraintsport3', 'constraintsport4', 'constraintsport5');
                    $constraint = 0;
                }

                // On réinialise la catégorie de sports qu'on ne souhaite pas conserver.
                foreach ($sportremoved as $sport) {
                    $this->{$sport} = self::SPORT_NONE;
                }

                // On liste tous les sports qu'on souhaite conserver (sauf les NONE).
                $items = array();
                foreach ($sportkeeped as $sport) {
                    if ($this->{$sport} == self::SPORT_NONE) {
                        continue;
                    }

                    $items[$this->{$sport}] = $this->{$sport};
                }
                $items = array_values($items);

                // On place le sport principal au début de la liste des sports à conserver.
                if ($constraint === 1 || $this->questionnairestatus == self::HEALTH_QUESTIONNAIRE_ANSWERED_YES_ONCE) {
                    $sports = Activity::get_records(array('restriction' => $constraint));
                    if (isset($sports[$this->mainsport]) === true && in_array($this->mainsport, $items, $strict = true) === false) {
                        array_unshift($items, $this->mainsport);
                    }
                }

                // On réécrit la liste des sports à conserver.
                foreach ($sportkeeped as $i => $sport) {
                    if (isset($items[$i]) === true) {
                        $this->{$sport} = $items[$i];
                    } else {
                        $this->{$sport} = self::SPORT_NONE;
                    }
                }
            }

            // Recalcule le groupe.
            if ($this->mainsport !== self::get_mainsportid_from_user_group($courseid, $this->userid)) {
                // Supprime tous les groupes de l'utilisateur.
                groups_delete_group_members($courseid, $this->userid);

                // Attribut le nouveau groupe.
                $groupid = self::get_groupid_from_activityid($this->mainsport, $courseid);
                if ($groupid !== false) {
                    groups_add_member($groupid, $this->userid);
                }
            }
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
        $event = federation_adhesion_updated::create(array(
            'objectid' => $this->id,
            'context' => context_course::instance($courseid),
            ));
        $event->trigger();
    }
}
