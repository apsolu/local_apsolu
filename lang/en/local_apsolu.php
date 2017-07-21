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
$string['attendance_select_session'] = 'Sélectionner une session';
$string['attendance_present'] = 'Présent';
$string['attendance_late'] = 'En retard';
$string['attendance_excused'] = 'Dispensé';
$string['attendance_absent'] = 'Absent';
$string['attendance_display_inactive_enrolments'] = 'Afficher les inscriptions inatives';
$string['attendance_active_enrolment'] = 'Inscription ative';
$string['attendance_presence'] = 'Présence';
$string['attendance_comment'] = 'Commentaire';
$string['attendance_course_presences_count'] = 'Nombre de présences pour ce cours';
$string['attendance_activity_presences_count'] = 'Nombre de présences pour cette activité';
$string['attendance_valid_account'] = 'Compte valide';
$string['attendance_sport_card'] = 'Carte sport';
$string['attendance_allowed_enrolment'] = 'Inscription autorisée';
$string['attendance_enrolments_management'] = 'Gestion des inscriptions';
$string['attendance_edit_enrolment'] = 'Modifier l\'inscription';
$string['attendance_ontime_enrolment'] = 'Inscription ponctuelle';

// Settings.
$string['settings_root'] = 'Gestion du SIUAPS';
$string['settings_configuration'] = 'Configuration';
$string['settings_configuration_calendar'] = 'Dates et calendrier';
$string['settings_activities'] = 'Activités physiques';
$string['settings_complements'] = 'Activités complémentaires';
$string['settings_federations'] = 'FFSU';
$string['settings_statistics'] = 'Statistiques';
$string['settings_statistics_rosters'] = 'Effectifs';

// Webservices.
$string['ws_local_apsolu_enrol_user_description'] = 'Inscris un étudiant à un cours.';
$string['ws_local_apsolu_get_active_course_students_description'] = 'Retourne les étudiants ayant une inscription valide dans un cours.';
$string['ws_local_apsolu_get_authentification_token_description'] = 'Retourne le token d\'identification de l\'utilisateur qui a passé sa carte.';
$string['ws_local_apsolu_get_course_roles_description'] = 'TODO: Retourne les rôles attribuables pour un cours.';
$string['ws_local_apsolu_get_current_session_description'] = 'TODO: Retourne la session courante d\'un cours en fonction de l\'heure.';
$string['ws_local_apsolu_get_presence_statuses_description'] = 'TODO: retourne tous les statuts définis pour un cours.';
$string['ws_local_apsolu_get_sessions_description'] = 'TODO: retourne toutes les sessions d\'un cours.';
$string['ws_local_apsolu_get_students_description'] = 'TODO: retourne tous les étudiants.';
$string['ws_local_apsolu_get_teacher_courses_description'] = 'Retourne les cours d\'un enseignant.';
$string['ws_local_apsolu_set_presence_description'] = 'TODO: définis la présence d\'un étudiant.';
$string['ws_local_apsolu_set_user_card_description'] = 'Enregistre la carte d\'un utilisateur.';
$string['ws_local_apsolu_set_user_role_description'] = 'Attribue un rôle à un étudiant.';
