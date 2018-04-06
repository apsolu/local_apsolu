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
 * Strings for component 'local_apsolu'
 *
 * @package    local
 * @subpackage apsolu
 * @copyright  2017 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'APSOLU';

// Général.
$string['close_link'] = '<a id="apsolu-cancel-a" href="{$a->href}" class="{$a->class}">Fermer</a>';
$string['display'] = 'Afficher';
$string['export'] = 'Exporter au format Excel';
$string['notify'] = 'Notifier';
$string['paid'] = 'Payé';
$string['sexe'] = 'Sexe';
$string['male'] = 'Garçon';
$string['female'] = 'Fille';
$string['no_students'] = 'Aucun étudiant';
$string['defaultnotifysubject'] = 'Notification du SIUAPS';

$string['accessdenied'] = 'Vous n\'avez pas le droit d\'accéder à cette page.';
$string['departmentslist'] = 'Liste des départements';

$string['studentname'] = 'Nom de famille';
$string['studentname_help'] = 'Noms de famille partiels d\'une ou plusieurs personnnes séparés par des virgules.<br />Exemple: neveu,niece';

$string['departments'] = 'Départements';
$string['departments_help'] = 'Noms partiels d\'un ou plusieurs départements séparés par des virgules.<br />Exemple: pharma,ondo';

// Configuration.
$string['semester1'] = 'Semestre 1';
$string['semester1_enrol_startdate'] = 'Date de début d\'inscription du S1';
$string['semester1_enrol_enddate'] = 'Date de fin d\'inscription du S1';
$string['semester1_startdate'] = 'Date de début des cours du S1';
$string['semester1_enddate'] = 'Date de fin des cours du S1';
$string['semester1_reenrol_startdate'] = 'Date de début de réinscription du S1';
$string['semester1_reenrol_enddate'] = 'Date de fin de réinscription du S1';
$string['semester2'] = 'Semestre 2';
$string['semester2_enrol_startdate'] = 'Date de début d\'inscription du S2';
$string['semester2_enrol_enddate'] = 'Date de fin d\'inscription du S2';
$string['semester2_startdate'] = 'Date de début des cours du S2';
$string['semester2_enddate'] = 'Date de fin des cours du S2';
$string['payments_startdate'] = 'Date de début des paiements';
$string['payments_enddate'] = 'Date de fin des paiments';
$string['semester1_grading_deadline'] = 'Date limite pour rendre les notes du S1';
$string['semester2_grading_deadline'] = 'Date limite pour rendre les notes du S2';
$string['functional_contact'] = 'Contact fonctionnel';
$string['technical_contact'] = 'Contact technique';

// Federation.
$string['federation_importation'] = 'Importation des licences FFSU';
$string['federation_importation_help'] = 'Pour importer des licences FFSU, vous devez renseigner un fichier csv comportant 2 colonnes :

- la première colonne doit contenir les numéros de licence
- la seconde colonne doit contenir l\'adresse du licencié

La première ligne du fichier n\'est pas traitée';
$string['federation_licenseid'] = 'Numéro de licence';
$string['federation_import'] = 'Importer';
$string['federation_preview'] = 'Aperçu';
$string['federation_result'] = 'Résultat';
$string['federation_insert_license'] = 'Le numéro de licence {$a->licenseid} a été ajouté au profil de {$a->profile}';
$string['federation_update_license'] = 'Le numéro de licence {$a->licenseid} a été mis à jour dans le profile de {$a->profile} (ancien numéro: {$a->oldlicenseid})';
$string['federation_no_import'] = 'Aucune donnée insérée ou modifiée.';

// Grades.
$string['grades_mygradedstudents'] = 'Mes étudiants à évaluer';
$string['grades_mystudents'] = 'Liste de mes étudiants';
$string['grades_grade1'] = 'Note pratique S1';
$string['grades_grade2'] = 'Note théorique S1';
$string['grades_grade3'] = 'Note pratique S2';
$string['grades_grade4'] = 'Note théorique S2';
$string['grades_practicegrade'] = 'Note pratique';
$string['grades_theorygrade'] = 'Note théorique';
$string['grades_firstsemester'] = '1er semestre';
$string['grades_secondsemester'] = '2nd semestre';
$string['grades_accessdenied'] = 'Vous n\'avez pas le droit d\'accéder à cette page.';
$string['grades_no_grading_student'] = 'Aucun étudiant à noter.';

// Homepage.
$string['homepage_home'] = 'Accueil';
$string['homepage_activities'] = 'Les activités';
$string['homepage_signup'] = 'S\'inscrire';
$string['homepage_login'] = 'Se connecter';
$string['homepage_siuaps'] = 'Service interuniversitaire des activités physiques et sportives';
$string['homepage_ur1'] = 'Université de Rennes 1';
$string['homepage_ur2'] = 'Université Rennes 2';
$string['homepage_summary'] = '<p>Bienvenue sur le site de gestion des inscriptions du <strong><a href="https://siuaps.univ-rennes.fr">Service Inter-Universitaire des Activités Physiques et Sportives</a></strong> (SIUAPS) de Rennes.</p>'.
'<p>Le SIUAPS propose à l’ensemble des étudiants et des personnels des Universités de <strong><a href="https://www.univ-rennes1.fr">Rennes&nbsp;1</a></strong> et <strong><a href="https://www.univ-rennes2.fr">Rennes&nbsp;2</a></strong> une formation pour tous les niveaux à la pratique des activités physiques et sportives.</p>';
$string['homepage_activitieslist'] = 'Liste des activités';
$string['homepage_signupsummary'] = '<ul>'.
    '<li>'.
        '<p>Les <strong>cours du SIUAPS</strong> sont accessibles à tous les étudiants et tous les personnels des universités de Rennes&nbsp;1 et de Rennes&nbsp;2.</p>'.
        '<p>Pour les personnels et pour les étudiants qui souhaitent avoir une pratique libre, vous devez vous acquitter de la carte sport (non remboursable) :</p>'.
        '<dl id="apsolu-register-dl">'.
            '<div>'.
                '<dt class="apsolu-register-dt">Tarif étudiants</dt>'.
                '<dd class="apsolu-register-dd">26€</dd>'.
            '</div>'.
            '<div>'.
                '<dt class="apsolu-register-dt">Tarif personnels</dt>'.
                '<dd class="apsolu-register-dd">40€</dd>'.
            '</div>'.
        '</dl>'.
        '<p>Elle est obligatoire et autorise la participation à plusieurs activités.</p>'.
    '</li>'.
    '<li>'.
        '<p>Deux <strong>salles de musculation</strong> sont mises à disposition pendant les heures d\'ouverture sur les campus de Beaulieu et de Villejean.</p>'.
        '<p>L\'acquittement d\'une carte musculation de 42€ est nécessaire. Elle permet l\'accès illimité aux salles.</p>'.
    '</li>'.
    '<li>'.
    '<p>Des <strong>compétitions</strong> et des <strong>rencontres universitaires</strong> sont organisées au sein des associations sportives de Rennes&nbsp;1 ou de Rennes&nbsp;2. Les étudiants qui souhaitent y participer doivent s\'acquitter de la licence FFSU de 15€ et fournir un certificat médical de non contre indication à la pratique en compétition du sport choisi.</p>'.
    '</li>'.
'</ul>';
$string['homepage_sesame'] = 'J\'ai un compte Sésame';
$string['homepage_nosesame'] = 'Je n\'ai pas de compte Sésame';

// Attendances.
$string['attendance'] = 'Prise des présences';
$string['attendance_sessionsview'] = 'Vue par sessions';
$string['attendance_overview'] = 'Vue d\'ensemble';
$string['attendance_sessions_edit'] = 'Éditer des sessions';
$string['attendance_no_periods'] = 'Aucune période de cours définie.';
$string['attendance_no_sessions'] = 'Aucune session de cours définie.';
$string['attendance_add_session'] = 'Ajouter une nouvelle session';
$string['attendance_undeletable_session'] = 'La session "{$a}" ne peut pas être supprimée car des présences ont déjà été prises.';
$string['attendance_delete_session'] = 'Supprimer la session "{$a}" ?';
$string['attendance_presences_summary'] = 'Résumé des présences';
$string['attendance_select_session'] = 'Sélectionner une session';
$string['attendance_present'] = 'Présent';
$string['attendance_present_short'] = 'P';
$string['attendance_present_style'] = 'success';
$string['attendance_present_total'] = 'Total des présences';
$string['attendance_late'] = 'En retard';
$string['attendance_late_short'] = 'R';
$string['attendance_late_style'] = 'warning';
$string['attendance_late_total'] = 'Total des retards';
$string['attendance_excused'] = 'Dispensé';
$string['attendance_excused_short'] = 'D';
$string['attendance_excused_style'] = 'info';
$string['attendance_excused_total'] = 'Total des excusés';
$string['attendance_absent'] = 'Absent';
$string['attendance_absent_short'] = 'A';
$string['attendance_absent_style'] = 'danger';
$string['attendance_absent_total'] = 'Total des absences';
$string['attendance_undefined'] = '-';
$string['attendance_undefined_short'] = '-';
$string['attendance_undefined_style'] = 'left';
$string['attendance_display_inactive_enrolments'] = 'Afficher les inscriptions inactives (semestres précédents)';
$string['attendance_display_invalid_enrolments'] = 'Afficher les inscriptions non validées (liste principale et liste complémentaire)';
$string['attendance_table_caption'] = 'Nombre d\'inscrits potentiels : {$a->count_students} étudiant(s)';
$string['attendance_enrolment_state'] = 'État de l\'inscription';
$string['attendance_presence'] = 'Présence';
$string['attendance_comment'] = 'Commentaire';
$string['attendance_course_presences_count'] = '<abbr title="Nombre">Nb</abbr> de présences pour ce cours';
$string['attendance_activity_presences_count'] = '<abbr title="Nombre">Nb</abbr> de présences pour cette activité';
$string['attendance_valid_account'] = 'Compte valide';
$string['attendance_invalid_account'] = 'Compte Sésame non valide';
$string['attendance_sport_card'] = 'Carte sport';
$string['attendance_no_sport_card'] = 'Carte sport absente';
$string['attendance_enrolment_type'] = 'Type d\'inscription';
$string['attendance_complement'] = 'Information';
$string['attendance_allowed_enrolment'] = 'Inscription autorisée';
$string['attendance_forbidden_enrolment'] = 'Type d\'inscription non autorisée';
$string['attendance_enrolment_list'] = 'Liste d\'inscription';
$string['attendance_enrolments_management'] = 'Gestion des inscriptions';
$string['attendance_edit_enrolment'] = 'Modifier l\'inscription';
$string['attendance_ontime_enrolment'] = 'Inscription ponctuelle';
$string['attendance_forum_notify'] = 'Notifier dans le forum';
$string['attendance_error_no_modification'] = 'La notification dans le forum des annonces n\'a pas été publiée car ni le jour de la session, ni le lieu de la session n\'ont été modifés.';
$string['attendance_error_no_news_forum'] = 'Le forum des annonces est absent de ce cours. Il est donc impossible d\'écrire le message dans ce forum pour notifier les étudiants.';
$string['attendance_success_message_forum'] = 'La notification a été publiée dans le forum des annonces.';
$string['attendance_error_message_forum'] = 'La notification n\'a pas pu être publiée dans le forum des annonces.';
$string['attendance_forum_create_session_subject'] = 'ajout d\'une session le {$a}';
$string['attendance_forum_create_session_message'] = '<p>Bonjour,</p>'.
    '<p>Une session de cours a été ajoutée :</p>'.
    '<dl class="dl-horizontal">'.
        '<dt>Jour</dt>'.
        '<dd>{$a->datetime}</dd>'.
        '<dt>Lieu</dt>'.
        '<dd>{$a->location}<dd>'.
    '</dl>'.
    '<p>Pensez à en prendre note dans votre agenda. ;-)</p>'.
    '<p>Cordialement</p>';
$string['attendance_forum_edit_session_subject'] = 'modification de la session du {$a}';
$string['attendance_forum_edit_session_message'] = '<p>Bonjour,</p>'.
    '<p>Une session de cours a été modifiée :</p>'.
    '<dl class="dl-horizontal">'.
        '<dt>Jour</dt>'.
        '<dd>{$a->datetime}</dd>'.
        '<dt>Lieu</dt>'.
        '<dd>{$a->location}<dd>'.
    '</dl>'.
    '<p>Pensez à en prendre note dans votre agenda. ;-)</p>'.
    '<p>Cordialement</p>';
$string['attendance_forum_delete_session_subject'] = 'suppression de la session du {$a}';
$string['attendance_forum_delete_session_message'] = '<p>Bonjour,</p>'.
    '<p>Une session de cours a été supprimée :</p>'.
    '<dl class="dl-horizontal">'.
        '<dt>Jour</dt>'.
        '<dd>{$a->datetime}</dd>'.
        '<dt>Lieu</dt>'.
        '<dd>{$a->location}<dd>'.
    '</dl>'.
    '<p>Pensez à en prendre note dans votre agenda. ;-)</p>'.
    '<p>Cordialement</p>';
$string['strftimeabbrday'] = '%d %b';
$string['strftimedatetime'] = '%A %d %B à %Hh%M';
$string['strftimedate'] = '%A %d %B';

// Reports.
$string['reports_found_students'] = '{$a} utilisateur(s) trouvé(s)';
$string['reports_mystudents'] = 'Liste de mes étudiants';

// Settings.
$string['settings_root'] = 'Gestion du SIUAPS';
$string['settings_configuration'] = 'Configuration';
$string['settings_configuration_calendar'] = 'Dates et calendrier';
$string['settings_configuration_contacts'] = 'Adresses de contact';
$string['settings_activities'] = 'Activités physiques';
$string['settings_complements'] = 'Activités complémentaires';
$string['settings_federations'] = 'FFSU';
$string['settings_federation'] = 'FFSU';
$string['settings_federation_import'] = 'Importation des licences FFSU';
$string['settings_statistics'] = 'Statistiques';
$string['settings_statistics_rosters'] = 'Effectifs';
$string['settings_users'] = 'Utilisateurs';
$string['settings_users_merge'] = 'Fusion de comptes';

// Tasks.
$string['set_high_level_athletes'] = 'Moulinette pour les sportifs de haut-niveau';
$string['task_set_high_level_athletes'] = 'Tâche pour traiter les sportifs de haut-niveau';
$string['task_run_mailqueue'] = 'Tâche pour envoyer les notifications';

// Users.
$string['users_merge_accounts'] = 'Fusionner les comptes';
$string['users_email_users'] = 'Comptes manuels';
$string['users_shibboleth_users'] = 'Comptes Sésame';
$string['users_require_email_user'] = 'Vous devez sélectionner un utilisateur ayant un compte manuel.';
$string['users_require_shibboleth_user'] = 'Vous devez sélectionner un utilisateur ayant un compte Sésame.';
$string['users_not_mergeable'] = 'Les 2 comptes sélectionnés ne peuvent pas être fusionnés.';
$string['users_not_mergeable_support'] = 'Merci de contacter la DSI.';
$string['users_accounts_merged'] = 'Le compte Sésame <a href="{$a->wwwroot}/user/profile.php?id={$a->id2}">{$a->username2}</a> a été fusionné avec le compte <a href="{$a->wwwroot}/user/profile.php?id={$a->id1}">{$a->username1}</a>.<br />'.
' Le compte <a href="{$a->wwwroot}/user/profile.php?id={$a->id1}">{$a->username1}</a> a été supprimé.';
$string['users_not_confirmed_shibboleth'] = 'Le compte Sésame sélectionné n\'est pas valide. L\'inscription universitaire n\'est peut-être pas validée pour l\'année en cours.';
$string['users_enrolments_shibboleth'] = 'Le compte Sésame sélectionné contient déjà des inscriptions.';

// Webservices.
$string['ws_local_apsolu_get_users_description'] = 'Retourne tous les utilisateurs';
$string['ws_local_apsolu_get_activities_description'] = 'Retourne toutes les activités';
$string['ws_local_apsolu_get_courses_description'] = 'Retourne tous les cours';
$string['ws_local_apsolu_get_registrations_description'] = 'Retourne toutes les inscriptions';
$string['ws_local_apsolu_get_unenrolments_description'] = 'Retourne toutes les désinscriptions';
$string['ws_local_apsolu_get_teachers_description'] = 'Retourne tous les enseignants';
$string['ws_local_apsolu_get_attendances_description'] = 'Retourne toutes les prises de présences';
$string['ws_local_apsolu_set_card_description'] = 'Associe un utilisateur et une carte donnés';
$string['ws_local_apsolu_set_presence_description'] = 'Ajoute une présence via un utilisateur, un cours et un timestamp donnés';
$string['ws_local_apsolu_ping_description'] = 'Réponds pong.';
$string['ws_local_apsolu_debugging_description'] = 'Enregistre des remontées d\'information.';

$string['ws_value_since'] = 'Timestamp UNIX permettant de retourner les données uniquement modifiées depuis ce timestamp.';
$string['ws_value_from'] = 'Timestamp UNIX permettant de retourner les données correspondant à des créneaux supérieur ou également à ce timestamp.';
$string['ws_value_idregistration'] = 'identifiant de l\'inscription';
$string['ws_value_iduser'] = 'identifiant de l\'utilisateur';
$string['ws_value_idcourse'] = 'identifiant du cours';
$string['ws_value_nbpresence'] = 'nombre de présences';
$string['ws_value_validity'] = 'validation de l\'inscription';
$string['ws_value_timestamp'] = 'horodatage du badgeage';
$string['ws_value_idactivity'] = 'identifiant de l\'activité';
$string['ws_value_activity_name'] = 'nom de l\'activité';
$string['ws_value_event'] = 'spécialité du cours';
$string['ws_value_skill'] = 'niveau du cours';
$string['ws_value_numweekday'] = 'numéro jour de la semaine du cours (1 = lundi, etc)';
$string['ws_value_starttime'] = 'heure de début du cours';
$string['ws_value_endtime'] = 'heure de fin du cours';
$string['ws_value_datetime'] = 'horodatage du cours';
$string['ws_value_instuid'] = 'identifiant institutionnel de l\'utilisateur';
$string['ws_value_firstname'] = 'prénom de l\'utilisateur';
$string['ws_value_lastname'] = 'nom de famille de l\'utilisateur';
$string['ws_value_cardnumber'] = 'numéro de carte de l\'utilisateur';
$string['ws_value_category'] = 'type d\'utilisateur (uniquement étudiant ou enseignant)';
$string['ws_value_institution'] = 'institution de rattachement de l\'utilisateur';
$string['ws_value_nosportcard'] = 'jeton apparaissant quand l\'utilisateur n\'a pas payé sa carte sport';
$string['ws_value_boolean'] = 'un booléen vrai/faux';
$string['ws_value_serial'] = 'numéro de série de l\'équipement';
$string['ws_value_idteacher'] = 'identifiant de l\'enseignant';
$string['ws_value_message'] = 'message de type texte';
