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
use Exception;
use local_apsolu\core\course as FederationCourse;
use local_apsolu\core\federation\activity as Activity;
use local_apsolu\core\federation\number as Number;
use local_apsolu\core\record;
use local_apsolu\event\federation_adhesion_updated;

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

    /** @var string $sex Sexe de l'adhérent (M ou F). */
    public $sex = '';

    /** @var int|string $insurance Assurance IA FFSU (1: oui, 0: non). */
    public $insurance = '';

    /** @var string $birthday Date de naissance de l'adhérent (AAAA-MM-JJ). */
    public $birthday = '';

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

    /** @var int|string $managerlicensetype Pour licence dirigeant pour Non-étudiant/Etudiant (1: pour étudiant, 0: pour non-étudiant). */
    public $managerlicensetype = 0;

    /** @var string $starlicense Licence étoile (O: oui, N: non). */
    public $starlicense = '';

    /** @var int|string $usepersonaldata Booléen indiquant si le droit à l'image a été donnée (1: oui, 0: non). */
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
     * Retourne la liste des noms officiels des activités FFSU.
     *
     * @return array
     */
    public static function get_disciplines() {
        $disciplines = array();
        $disciplines[1] = 'Droit / Sciences Po';
        $disciplines[2] = 'Sciences Eco / Gestion';
        $disciplines[3] = 'Lettres / Sciences humaines / Art';
        $disciplines[4] = 'Commerce';
        $disciplines[5] = 'Sciences / Technique';
        $disciplines[6] = 'Métiers du sport';
        $disciplines[7] = 'Langues';
        $disciplines[8] = 'Médecine / Santé';
        $disciplines[9] = 'Enseignement';

        return $disciplines;
    }

    /**
     * Retourne la liste des noms officiels des activités FFSU.
     *
     * @return array
     */
    public static function get_exportation_fields() {
        return array(
            'federationnumberprefix', // Préfixe du numéro AS sur 4 caractères.
            'lastname',
            'firstname',
            'sex',
            'birthdayformat',
            'address1',
            'address2',
            'postalcode',
            'city',
            'phone',
            'email',
            'instagram', // Instagram (30 caractères).
            'disciplineid', // Discipline-Cursus 1 chiffre entre 1 et 10.
            'otherfederation', // Autre fédération (30 caractères).
            'mainsportname', // Sport (20 caractères).
            'sportlicense', // Licence sportive (1 = oui, 0 = non).
            'managerlicense', // Licence dirigeant (1 = oui, 0 = non).
            'refereelicense', // Licence arbitre (1 = oui, 0 = non).
            'managerlicensetype', // Pour licence dirigeant : Non-étudiant / étudiant (1 pour étudiant, 0 pour non-étudiant).
            'starlicense', // Licence étoile (O ou N).
            'usepersonaldata', // Autorisation / droit à l'image (1 = oui, 0 = non).
            'insurance', // Assurance (1 = oui, 0 = non).
            'sport1', // Certificat médical - activité 1 sans contrainte particulière [ 1 nombre entre 1 et 60(***)].
            'sport2', // Certificat médical - activité 2 sans contrainte particulière [ 1 nombre entre 1 et 60(***)].
            'sport3', // Certificat médical - activité 3 sans contrainte particulière [ 1 nombre entre 1 et 60(***)].
            'sport4', // Certificat médical - activité 4 sans contrainte particulière [ 1 nombre entre 1 et 60(***)].
            'sport5', // Certificat médical - activité 5 sans contrainte particulière [ 1 nombre entre 1 et 60(***)].
            'constraintsport1', // Certificat médical - activité 1 à contraintes particulières [ 1 nombre entre 1 et 53(****)].
            'constraintsport2', // Certificat médical - activité 2 à contraintes particulières [ 1 nombre entre 1 et 53(****)].
            'constraintsport3', // Certificat médical - activité 3 à contraintes particulières [ 1 nombre entre 1 et 53(****)].
            'constraintsport4', // Certificat médical - activité 4 à contraintes particulières [ 1 nombre entre 1 et 53(****)].
            'constraintsport5', // Certificat médical - activité 5 à contraintes particulières [ 1 nombre entre 1 et 53(****)].
            'medicalcertificatedateformat', // Date du certificat médical [ au format AAAA-MM-JJ ].
            'questionnairestatus', // J’ai répondu NON à toutes les questions du questionnaire de santé (1 pour oui ou 0 pour non).
            'medicalcertificatestatus', // J’ai répondu OUI à une rubrique du questionnaire de santé (1 pour oui ou 0 pour non).
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
            get_string('firstname'),
            get_string('sex', 'local_apsolu'),
            get_string('birthday', 'local_apsolu'),
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
        $types[0] = get_string('not_student', 'local_apsolu');
        $types[1] = get_string('student', 'local_apsolu');

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
     * Enregistre un objet en base de données.
     *
     * @throws dml_exception A DML specific exception is thrown for any errors.
     *
     * @param object|null $data  StdClass représentant l'objet à enregistrer.
     * @param object|null $mform Mform représentant un formulaire Moodle nécessaire à la gestion d'un champ de type editor.
     * @param boolean     $check Témoin permettant de passer les vérifications avant l'enregistrement des données.
     *
     * @return void
     */
    public function save(object $data = null, object $mform = null, bool $check = true) {
        global $DB;

        $courseid = FederationCourse::get_federation_courseid();

        if ($data !== null) {
            $this->set_vars($data);
        }

        if ($check === true) {
            if ($this->can_edit() === false) {
                throw new Exception(get_string('your_medical_certificate_has_already_been_validated', 'local_apsolu'));
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
                $groupid = self::get_groupid_from_activityid($this->mainsport, $courseid);
                if ($groupid !== false) {
                    groups_add_member($groupid, $this->userid);
                }
            }
        }

        if (empty($this->id) === true) {
            $this->id = $DB->insert_record(self::TABLENAME, $this);
        } else {
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
