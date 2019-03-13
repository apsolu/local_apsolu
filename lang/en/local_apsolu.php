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
 * @package    local_apsolu
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
$string['male'] = 'Garçon';
$string['female'] = 'Fille';
$string['no_student'] = 'Aucun étudiant';
$string['defaultnotifysubject'] = 'Notification du SIUAPS';
$string['general'] = 'Générales';
$string['payment'] = 'Paiement';
$string['activities'] = 'Activités';
$string['calendar'] = 'Calendrier';
$string['calendarname'] = 'Nom du calendrier';
$string['calendartype'] = 'Type de calendrier';
$string['calendartypename'] = 'Nom du type de calendrier';
$string['calendar_add'] = 'Ajouter un calendrier';
$string['calendar_type_add'] = 'Ajouter un type de calendrier';
$string['no_calendar'] = 'Aucun calendrier';
$string['no_calendar_type'] = 'Aucun type de calendrier';
$string['calendars'] = 'Calendriers';
$string['reenrolments'] = 'Réinscriptions';
$string['enrolstartdate'] = 'Date de début des inscriptions';
$string['enrolenddate'] = 'Date de fin des inscriptions';
$string['coursestartdate'] = 'Date de début des cours';
$string['courseenddate'] = 'Date de fin des cours';
$string['reenrolstartdate'] = 'Date de début des réinscriptions';
$string['reenrolenddate'] = 'Date de fin des réinscriptions';
$string['gradestartdate'] = 'Date de début de saisie des notes';
$string['gradeenddate'] = 'Date de fin de saisie des notes';
$string['needcalendarstypefirst'] = 'Vous devez créer un type de calendrier pour commencer.';
$string['freecourses'] = 'Nombre de créneaux offerts pour les cours du calendrier <em>{$a}</em>';
$string['shortfreecourses'] = 'Nombre de créneaux offerts';

$string['author'] = 'Auteur';
$string['compose'] = 'Rédaction';
$string['timecreated'] = 'Date de création';
$string['countposts'] = 'Nombre de messages envoyés';
$string['nohistory'] = 'Aucun historique';
$string['dunningsaved'] = 'La relance de paiement a été enregistrée. Les notifications seront envoyées au plus tard dans une heure.';
$string['state'] = 'État';
$string['waiting'] = 'En attente';
$string['running'] = 'En cours';
$string['finished'] = 'Terminé';

$string['paymentdue'] = 'Dû';
$string['paymentpaid'] = 'Payé';
$string['paymentfree'] = 'Non dû';
$string['paymentgift'] = 'Offert';

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

// Courses (ex: local/apsolu_course).
$string['settings_root'] = 'Gestion du SIUAPS';
$string['settings_activities'] = 'Activités physiques';
$string['settings_complements'] = 'Activités complémentaires';
$string['settings_federations'] = 'FFSU';
$string['federations'] = 'FFSU';
$string['notify'] = 'Notifier';
$string['subject'] = 'Sujet';
$string['message'] = 'Message';
$string['notifications_sent'] = 'Notifications envoyées';
$string['notifications_notsent'] = 'Notifications non envoyées';
$string['receivers_list'] = 'Liste des {$a->count} destinataires';
$string['nobody_to_notify'] = 'Aucune personne à notifier';
$string['nodata'] = 'Aucune donnée à afficher';
$string['event_payment_notification'] = 'Évènement pour la notification des paiements';

// Overview.
$string['overview'] = 'Vue d\'ensemble';
$string['no_enrols'] = 'Pas de méthode d\'inscription';
$string['back'] = 'Revenir';

// Courses (Slots).
$string['courses'] = 'Créneaux horaires';
$string['courses_csv_export'] = 'Exporter les créneaux au format csv';
$string['courses_list'] = 'Liste des créneaux';
$string['course_add'] = 'Ajouter un créneau';
$string['no_course'] = 'Aucun créneau';
$string['license'] = 'Inscription FFSU obligatoire';
$string['on_homepage'] = 'Affiché sur la page d\'accueil';

// Groupings.
$string['grouping'] = 'Groupement d\'activités';
$string['groupings'] = 'Groupements d\'activités';
$string['groupings_list'] = 'Liste des groupements d\'activités';
$string['grouping_add'] = 'Ajouter un groupement d\'activités sportives';
$string['no_grouping'] = 'Aucun groupement d\'activités sportives';

// Categories (Sports).
$string['categories'] = 'Activités sportives';
$string['categories_list'] = 'Liste des activités sportives';
$string['category_add'] = 'Ajouter une activité sportive';
$string['no_category'] = 'Aucune activité sportive';
$string['federation'] = 'FFSU';

// Skills.
$string['skills'] = 'Niveaux';
$string['skills_csv_export'] = 'Exporter les niveaux au format csv';
$string['skills_list'] = 'Liste des niveaux';
$string['skill_add'] = 'Ajouter un niveau';
$string['skill_fullname'] = 'Nom complet';
$string['skill_fullnames'] = 'Noms complets';
$string['skill_shortname'] = 'Nom abrégé';
$string['skill_shortnames'] = 'Noms abrégés';
$string['no_skill'] = 'Aucun niveau';

// Skills descriptions.
$string['skills_descriptions'] = 'Description des niveaux';

// Genders.
$string['genders'] = 'Genres / types';
$string['genders_list'] = 'Liste des genres';
$string['gender_add'] = 'Ajouter un genre';
$string['no_gender'] = 'Aucun genre';

// Periods.
$string['periods'] = 'Périodes';
$string['periods_list'] = 'Liste des périodes';
$string['period_add'] = 'Ajouter une période';
$string['no_period'] = 'Aucune période';
$string['generic_name'] = 'Nom générique';
$string['weeks'] = 'Semaines';

// Locations.
$string['locations'] = 'Lieux';
$string['locations_list'] = 'Liste des lieux';
$string['location_add'] = 'Ajouter un lieu';
$string['no_location'] = 'Aucun lieu';

$string['area'] = 'Zone géographique';
$string['address'] = 'Adresse postale';
$string['addresses'] = 'Adresses postales';
$string['email'] = 'Adresse de courriel';
$string['emails'] = 'Adresses de courriel';
$string['phone'] = 'Numéro de téléphone';
$string['phones'] = 'Numéros de téléphone';
$string['longitude'] = 'Longitude';
$string['longitudes'] = 'Longitudes';
$string['latitude'] = 'Latitude';
$string['latitudes'] = 'Latitudes';
$string['wifi_access'] = 'Accès Wifi';
$string['indoor'] = 'Couvert';
$string['restricted_access'] = 'Accès restreint';
$string['manager'] = 'Gestionnaire';

// Areas.
$string['areas'] = 'Zones géographiques';
$string['areas_list'] = 'Liste des zones géographiques';
$string['area_add'] = 'Ajouter une zone géographique';
$string['no_area'] = 'Aucune zone géographique';

// Cities.
$string['city'] = 'Site';
$string['cities'] = 'Sites';
$string['cities_list'] = 'Liste des sites';
$string['city_add'] = 'Ajouter un site';
$string['no_city'] = 'Aucun site';

// Managers.
$string['managers'] = 'Gestionnaires de lieux';
$string['managers_list'] = 'Liste des gestionnaires de lieux';
$string['manager_add'] = 'Ajouter un gestionnaire de lieux';
$string['no_manager'] = 'Aucun gestionnaire de lieux';

// Complements.
$string['complements'] = 'Activités complémentaires';
$string['complements_list'] = 'Liste des activités complémentaires';
$string['complement_add'] = 'Ajouter une activité complémentaire';
$string['no_complement'] = 'Aucune activité complémentaire';
$string['price'] = 'Prix';
$string['prices'] = 'Prix';

// Federations.
$string['federations_users_list'] = 'Liste des adhérents';
$string['federations_users_list_export_r1'] = 'Exporter la liste des adhérents (Rennes 1)';
$string['federations_users_list_export_r2'] = 'Exporter la liste des adhérents (Rennes 2)';
$string['no_user'] = 'Aucun adhérent';
$string['federation_number'] = 'Numéro de licence';
$string['medical_certificate'] = 'Certificat médical';
$string['federation_paid'] = 'Adhésion payée';
$string['association_number'] = 'N° A.S.';
$string['sex'] = 'Sexe';
$string['birthday'] = 'Date de naissance';
$string['address1'] = 'Adresse 1';
$string['address2'] = 'Adresse 2';
$string['postal_code'] = 'Code postal';
$string['discipline'] = 'Discipline';
$string['study_year'] = 'Année d\'étude';
$string['sport'] = 'Sport';
$string['sport_license'] = 'Licence sportive';
$string['manager_license'] = 'Licence dirigeante';
$string['referee_license'] = 'Licence arbitre';
$string['star_license'] = 'Licence étoile';
$string['autorisation'] = 'Autorisation';
$string['assurance'] = 'Assurance';

// Misc.
$string['empty_field'] = 'Le champ "{$a}" ne peut pas être vide.';
$string['bad_url'] = 'Le champ "{$a}" ne contient pas une URL.';
$string['cancel_link'] = '<a id="apsolu-cancel-a" href="{$a->href}" class="{$a->class}">Annuler</a>';

$string['category'] = 'Domaine';
$string['sport'] = 'Activité';
$string['event'] = 'Libellé complémentaire';
$string['skill'] = 'Niveau';
$string['weekday'] = 'Jour';
$string['weekdays'] = 'Jours';
$string['schedule'] = 'Horaires';
$string['times'] = 'Horaires';
$string['starttime'] = 'Heure de début';
$string['endtime'] = 'Heure de fin';
$string['duration'] = 'Durée';
$string['location'] = 'Lieu';
$string['period'] = 'Période';
$string['center'] = 'Centre de paiement';

$string['role'] = 'Type d\'inscription';

$string['go_to_course'] = 'Accéder au cours';
$string['enrol_teachers'] = 'Inscrire un enseignant';

// Grades.
$string['grades'] = 'Notations';
$string['configure'] = 'Configurer';
$string['export'] = 'Exporter';
$string['grades_extraction'] = 'Extraction des notes';
$string['extraction'] = 'Extraction';
$string['semesters'] = 'Semestres';
$string['semester1'] = 'Semestre 1';
$string['semester2'] = 'Semestre 2';
$string['semester1_grading_deadline'] = 'Date limite pour rendre les notes du S1';
$string['semester2_grading_deadline'] = 'Date limite pour rendre les notes du S2';

$string['ufr'] = 'UFR';
$string['ufrs'] = 'UFR';
$string['ufrs_help'] = 'Noms partiels d\'une ou plusieurs UFR séparées par des virgules.<br />Exemple: math,langue';

// Error.
$string['courses_error_no_grouping'] = 'Aucun groupement d\'activités trouvé. Merci d\'ajouter un groupement d\'activités avant de créer une activité.';
$string['courses_error_no_category'] = 'Aucune activité sportive trouvée. Merci d\'ajouter une activité sportive avant de créer un créneau.';
$string['courses_error_no_skill'] = 'Aucun niveau de pratique sportive trouvé. Merci d\'ajouter un niveau de pratique sportive avant de créer un créneau.';
$string['courses_error_no_location'] = 'Aucun lieu de pratique sportive trouvé. Merci d\'ajouter un lieu de pratique sportive avant de créer un créneau.';
$string['courses_error_no_period'] = 'Aucune période de pratique sportive trouvée. Merci d\'ajouter une période de pratique sportive avant de créer un créneau.';
$string['courses_error_no_area'] = 'Aucune zone géographique trouvée. Merci d\'ajouter une zone géographique avant de créer un lieu.';
$string['courses_error_no_manager'] = 'Aucun gestionnaire de lieu trouvé. Merci d\'ajouter un gestionnaire de lieu avant de créer un lieu.';
$string['courses_error_no_center'] = 'Aucun centre de paiement trouvée. Merci d\'ajouter un centre de paiement avant de créer un créneau.';


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
$string['mygradedstudents'] = 'Mes étudiants à évaluer';
$string['mystudents'] = 'Liste de mes étudiants';
$string['grade1'] = 'Note pratique S1';
$string['grade2'] = 'Note théorique S1';
$string['grade3'] = 'Note pratique S2';
$string['grade4'] = 'Note théorique S2';
$string['practicegrade'] = 'Note pratique';
$string['theorygrade'] = 'Note théorique';
$string['firstsemester'] = '1er semestre';
$string['secondsemester'] = '2nd semestre';
$string['accessdenied'] = 'Vous n\'avez pas le droit d\'accéder à cette page.';
$string['no_grading_student'] = 'Aucun étudiant à noter.';

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
        '<p>Les <b>cours du SIUAPS</b> sont accessibles à tous les étudiants et tous les personnels des universités de Rennes&nbsp;1 et de Rennes&nbsp;2, au coût suivant selon le cas :</p>'.
        '<dl>'.
            '<dt>Tarif étudiants :</dt>'.
            '<dd>'.
                '<ul>'.
                '<li>Cette année, la participation à un premier créneau hebdomadaire est <em>gratuite<sup>*</sup></em> ;</li>'.
                '<li>30€ à partir d’un 2<sup>ème</sup> créneau dans le même semestre</li>'.
                '</ul>'.
            '</dd>'.
            '<dt>Tarif personnels :</dt>'.
            '<dd>'.
                '<ul>'.
                    '<li>40€</li>'.
                '</ul>'.
            '</dd>'.
        '</dl>'.
        '<p><em><sup>*</sup> Préalablement à votre inscription à l’Université, vous avez payé votre Contribution Vie Etudiante et Campus (Médecine, sport, culture, FSDIE…) au CROUS. Cette taxe comprend un premier niveau d’accès au sport dans votre établissement dans la limite des places disponibles.</em></p>'.
    '</li>'.
    '<li>À Rennes uniquement, deux <b>salles de musculation</b> sont mises à disposition pendant les heures d\'ouverture sur les campus de Beaulieu et de Villejean. L\'acquittement d\'une carte musculation de 42€ est nécessaire. Elle permet l\'accès illimité aux salles.</li>'.
    '<li>À Rennes et à St Brieuc, Des <b>compétitions</b> et des <b>rencontres universitaires</b> sont organisées au sein des associations sportives de Rennes&nbsp;1 ou de Rennes&nbsp;2. Les étudiants qui souhaitent y participer doivent s\'acquitter de la licence FFSU (15€ à Rennes, 20€ à St Brieuc), et fournir un certificat médical de non contre-indication à la pratique en compétition du sport choisi.</li>'.
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

// Payments.
$string['paymentsiuaps'] = 'Paiement auprès du SIUAPS';
$string['paymentasso'] = 'Paiement auprès de l\'Association Sportive';
$string['opened_period'] = 'L\'ouverture des paiements aura lieu le {$a->date}.';
$string['closed_period'] = 'Le module des paiements est actuellement fermé.';
$string['no_sesame'] = 'L\'utilisateur n\'a pas de compte Sésame valide ou n\'est plus présent dans le référentiel de son établissement.';
$string['invalid_user'] = '<p>Vous n\'êtes pas autorisé à payer.</p>';
$string['invalid_user_no_sesame'] = '<p>Actuellement, vous n\'utilisez pas votre compte Sésame pour accéder cette plateforme.<br />'.
    ' Avant d\'accéder aux paiments, vous devez suivre <a href="{$a->url}">cette procédure pour transformer votre compte</a>.</p>';
$string['invalid_user_invalid_sesame'] = '<p>Votre compte ne semble pas être présent dans le référentiel communiqué au SIUAPS.<br />'.
    ' Si vous avez un compte Sésame en cours de validité dans votre établissement et une inscription pour l\'année universitaire en cours, merci d\'envoyer un courriel à <a href="mailto:{$a->email}">{$a->email}</a> en précisant que votre compte est bien valable actuellement.</p>';
$string['no_courses'] = 'Vous ne pouvez pas payer car vous n\'avez aucune inscription validée ou valide pour le moment.';
$string['no_courses_complements'] = 'Pour les cours du SIUAPS, si vous êtes inscrit en évaluation, vérifier avec votre enseignant que vous êtes autorisé à l\'être.< br/>'.
' Pour l\'accès aux salles de musculation, vous devez avoir validé votre séance gratuite avant de pouvoir payer.';
$string['no_paybox'] = 'Notre prestataire de paiement semble rencontrer des difficultés techniques. Merci de réessayer ultérieurement.';
$string['warning_payment'] = '<p><strong>Attention</strong> : vérifiez les cours qui vous sont facturés. Si vous ne souhaitez plus suivre une activité, merci de contacter votre enseignant.</p>'.
    '<p>Le SIUAPS ne procèdera à aucun remboursement.'.
    ' Si vous constatez une erreur dans le montant à payer, merci d\'envoyer un mail à l\'adresse <a href="mailto:{$a}">{$a}</a>.</p>'.
    '<p>Enfin, si vous rencontrez le problème "<em>Demande de paiement dupliquée. Accès refusé !</em>", merci de réessayer votre paiement le lendemain.</p>';
$string['status_accepted'] = 'Paiement accepté.';
$string['status_refused'] = 'Paiement refusé.';
$string['status_wait'] = 'Paiement en attente.';
$string['status_cancel'] = 'Paiement annulé.';
$string['status_unknown'] = 'Le retour du paiement ne s\'est pas passé comme prévu. Merci de prendre contact avec le SIUAPS.';
$string['sportcard'] = 'Carte sport';
$string['cards'] = 'Cartes';
$string['activitiesdescription'] = 'Accès aux cours, aux installations extérieures';
$string['bodybuilding'] = 'Carte musculation';
$string['bodybuildingdescription'] = 'Accès aux salles de musculation en autonomie sur des créneaux dédiés';
$string['association'] = 'Licence FFSU';
$string['associationdescription'] = 'Pour participer aux compétitions, aux rencontres';
$string['configurations'] = 'Configurations';
$string['configuration_edit'] = 'Modifier une configuration';
$string['names'] = 'Noms';
$string['value'] = 'Valeur';
$string['values'] = 'Valeurs';
$string['payment_centers'] = 'Centres de paiement';
$string['centers'] = $string['payment_centers'];
$string['paybox_idnumber'] = 'Identifiant PayBox';
$string['paybox_sitenumber'] = 'Numéro de site';
$string['paybox_rank'] = 'Rang';
$string['paybox_hmac'] = 'Clé HMAC';
$string['no_center'] = 'Aucun centre de paiement';
$string['center_add'] = 'Ajouter un centre de paiement';
$string['payments'] = 'Paiements';
$string['add_payment'] = 'Saisir un paiement';
$string['select_user'] = 'Sélectionner l\'utilisateur';
$string['no_payments'] = 'Aucun paiement effectué';
$string['payment_number'] = 'Identifiant de transaction';
$string['date'] = 'Date de paiement';
$string['status'] = 'Statut du paiement';
$string['actions'] = 'Actions';
$string['inprogress'] = 'En cours';
$string['success'] = 'Terminé';
$string['amount'] = 'Montant';
$string['method'] = 'Moyen de paiement';
$string['source'] = 'Source';
$string['items'] = 'Paiements';
$string['method_apogee'] = 'Apogée';
$string['method_card'] = 'Carte';
$string['method_check'] = 'Chèque';
$string['method_coins'] = 'Espèce';
$string['method_paybox'] = 'PayBox';
$string['source_apogee'] = 'Apogée';
$string['source_apsolu'] = 'Apsolu';
$string['source_manual'] = 'Saisie manuelle';
$string['status_success'] = 'Payé';
$string['status_error'] = 'Non payé';
$string['opened_period'] = 'L\'ouverture des paiements aura lieu le {$a->date}.';
$string['closed_period'] = 'Le module des paiements est actuellement fermé.';
$string['payment_card'] = 'Tarif';
$string['no_card'] = 'Aucun tarif';
$string['card_add'] = 'Ajouter un tarif';
$string['payment_cards'] = 'Tarifs';
$string['freetrial'] = 'Nombre de sessions gratuites';
$string['startat'] = 'À partir de';
$string['alt_paid'] = 'payé';
$string['alt_due'] = 'dû';
$string['alt_free'] = 'gratuit';
$string['alt_gift'] = 'offert';
$string['definition_paid'] = 'paiment effectué';
$string['definition_due'] = 'paiment à effectuer';
$string['definition_free'] = 'pas de paiment à effectuer';
$string['definition_gift'] = 'carte offerte';
$string['dunning'] = 'Relance de paiement';
$string['history'] = 'Historique';

// Administration - notifications.
$string['notifications'] = 'Notifications';

// Divers.
$string['missing'] = 'absent';
$string['back'] = 'retour';
$string['research_user'] = 'Rechercher un nouvel utilisateur';
$string['cancel_link'] = '<a id="apsolu-cancel-a" href="{$a->href}" class="{$a->class}">Annuler</a>';
$string['error_no_payment_found'] = 'Une erreur est survenue (écriture non retrouvée).';
$string['error_payment_not_editable'] = 'Ce paiement ne peut pas être modifié.';
$string['error_missing_items'] = 'Vous devez cocher au moins une carte (carte sport, carte musculation ou carte ffsu).';

// Reports.
$string['found_students'] = '{$a} utilisateur(s) trouvé(s)';
$string['mystudents'] = 'Liste de mes étudiants';

// Fields.
$string['fields_complements_category'] = 'Informations complémentaires';
$string['fields_apsolupostalcode'] = 'Code postal';
$string['fields_apsolusex'] = 'Sexe';
$string['fields_apsolubirthday'] = 'Date de naissance';
$string['fields_apsoluufr'] = 'UFR';
$string['ufrs_help'] = 'Noms partiels d\'une ou plusieurs UFR séparées par des virgules.<br />Exemple: math,langue';
$string['fields_apsolucycle'] = 'Cycle LMD';
$string['fields_apsolucardpaid'] = 'Carte sport payée';
$string['fields_apsolufederationpaid'] = 'Adhésion à l\'association payée';
$string['fields_apsolumuscupaid'] = 'Musculation payée';
$string['fields_apsolusesame'] = 'Compte Sésame validé';
$string['fields_apsolumedicalcertificate'] = 'Certificat médical';
$string['fields_apsolufederationnumber'] = 'Numéro de licence FFSU';
$string['fields_apsoluhighlevelathlete'] = 'Sportif de haut niveau';
$string['fields_apsoluidcardnumber'] = 'Carte vie universitaire';
$string['fields_apsoludoublecursus'] = 'Double cursus';

// Settings.
$string['settings_root'] = 'APSOLU';
$string['settings_configuration'] = 'Configuration';
$string['settings_configuration_calendars'] = 'Calendriers';
$string['settings_configuration_calendarstypes'] = 'Types de calendriers';
$string['settings_configuration_calendars_types'] = 'Types de calendriers';
$string['settings_configuration_contacts'] = 'Adresses de contact';
$string['settings_configuration_dates'] = 'Dates';
$string['settings_configuration_header'] = 'Bandeau';
$string['settings_activities'] = 'Activités physiques';
$string['settings_complements'] = 'Activités complémentaires';
$string['settings_federations'] = 'FFSU';
$string['settings_federation'] = 'FFSU';
$string['settings_federation_import'] = 'Importation des licences FFSU';
$string['settings_payment'] = 'Frais d\'inscription';
$string['settings_payments'] = 'Paiements';

// Tasks.
$string['set_high_level_athletes'] = 'Moulinette pour les sportifs de haut-niveau';
$string['task_set_high_level_athletes'] = 'Tâche pour traiter les sportifs de haut-niveau';
$string['task_run_mailqueue'] = 'Tâche pour envoyer les notifications';
$string['task_send_dunnings'] = 'Tâche pour envoyer les relances de paiements';
$string['task_grant_ws_access'] = 'Tâche pour attribuer des droits d\'accès aux webservices APSOLU';

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
$string['ws_value_nbpresence'] = 'nombre de présences dans ce cours';
$string['ws_value_nbpresenceglobale'] = 'nombre de présences pour cette activité';
$string['ws_value_validity'] = 'validation de l\'inscription';
$string['ws_value_sportcard'] = 'identifiant du statut du paiement de la carte sport';
$string['ws_value_evaluation'] = 'identifiant du type d\'inscription de l\'étudiant';
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
