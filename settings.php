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
 * Add page to admin menu.
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(
    'moodle/category:manage',
    'moodle/course:create',
);

if ($hassiteconfig or has_any_capability($capabilities, context_system::instance())) {
    // Ajoute un noeud Apsolu au menu d'administration.
    if (empty($ADMIN->locate('apsolu')) === true) {
        // Crée le noeud.
        $apsoluroot = new admin_category('apsolu', get_string('settings_root', 'local_apsolu'));
        // Tri les enfants par ordre alphabétique.
        $apsoluroot->set_sorting($sort = true);
        // Place le noeud Apsolu avant le noeud Utilisateurs de Moodle.
        $ADMIN->add('root', $apsoluroot, 'users');
    }

    // Activités physiques.
    $ADMIN->add('apsolu', new admin_category('local_apsolu_courses', get_string('settings_activities', 'local_apsolu')));
    $ADMIN->add('local_apsolu_courses', new admin_category('local_apsolu_courses_courses', get_string('activities', 'local_apsolu')));
    $ADMIN->add('local_apsolu_courses', new admin_category('local_apsolu_courses_locations', get_string('locations', 'local_apsolu')));
    $ADMIN->add('local_apsolu_courses', new admin_category('local_apsolu_courses_periods', get_string('periods', 'local_apsolu')));
    $ADMIN->add('local_apsolu_courses', new admin_category('local_apsolu_courses_skills', get_string('skills', 'local_apsolu')));

    $items = array();
    $items['courses'] = array('groupings', 'categories', 'courses');
    $items['periods'] = array('periods', 'holidays');
    if (isset($CFG->is_siuaps_rennes) === true) {
        $items['skills'] = array('skills', 'skills_descriptions');
        unset($items['skills'][1]); // Supprime temporairement l'entrée "skills_descriptions".
    } else {
        $items['skills'] = array('skills');
    }
    $items['locations'] = array('locations', 'areas', 'cities', 'managers');

    foreach ($items as $subtype => $tabs) {
        foreach ($tabs as $tab) {
            $label = get_string($tab, 'local_apsolu');
            $url = new moodle_url('/local/apsolu/courses/index.php', ['tab' => $tab]);
            $page = new admin_externalpage('local_apsolu_courses_'.$subtype.'_'.$tab, $label, $url, $capabilities);

            $ADMIN->add('local_apsolu_courses_'.$subtype, $page);
        }
    }

    // Activités complémentaires.
    if (isset($CFG->is_siuaps_rennes) === true) {
        $ADMIN->add('apsolu', new admin_category('local_apsolu_complements', get_string('settings_complements', 'local_apsolu')));

        // Activités complémentaires > Activités complémentaires.
        $label = get_string('settings_complements', 'local_apsolu');
        $url = new moodle_url('/local/apsolu/courses/complements.php', ['tab' => 'complements']);
        $ADMIN->add('local_apsolu_complements', new admin_externalpage('local_apsolu_complements_complements', $label, $url, $capabilities));
    }

    // Configuration.
    $ADMIN->add('apsolu', new admin_category('local_apsolu_configuration', get_string('settings_configuration', 'local_apsolu')));

    // Configuration > Calendriers.
    $str = get_string('calendars', 'local_apsolu');
    $url = new moodle_url('/local/apsolu/configuration/index.php', array('page' => 'calendars'));
    $ADMIN->add('local_apsolu_configuration', new admin_externalpage('local_apsolu_configuration_calendars', $str, $url, $capabilities));

    // Configuration > Cours spéciaux.
    $str = get_string('special_courses', 'local_apsolu');
    $url = new moodle_url('/local/apsolu/configuration/index.php', array('page' => 'specialcourses'));
    $ADMIN->add('local_apsolu_configuration', new admin_externalpage('local_apsolu_configuration_special_courses', $str, $url, $capabilities));

    // Configuration > Messagerie.
    $str = get_string('messaging', 'local_apsolu');
    $url = new moodle_url('/local/apsolu/configuration/index.php', array('page' => 'messaging'));
    $ADMIN->add('local_apsolu_configuration', new admin_externalpage('local_apsolu_configuration_messaging', $str, $url, $capabilities));

    // Configuration > Type de calendriers.
    $str = get_string('calendars_types', 'local_apsolu');
    $url = new moodle_url('/local/apsolu/configuration/index.php', array('page' => 'calendarstypes'));
    $ADMIN->add('local_apsolu_configuration', new admin_externalpage('local_apsolu_configuration_calendars_types', $str, $url, $capabilities));

    // Configuration > Type de présences.
    $str = get_string('attendance_statuses', 'local_apsolu');
    $url = new moodle_url('/local/apsolu/configuration/index.php', array('page' => 'attendancestatuses'));
    $ADMIN->add('local_apsolu_configuration', new admin_externalpage('local_apsolu_configuration_attendance_statuses', $str, $url, $capabilities));

    // Fédération FSU.
    if (local_apsolu\core\course::get_federation_courseid() !== false) {
        $ADMIN->add('apsolu', new admin_category('local_apsolu_federation', get_string('settings_federation', 'local_apsolu')));

        // Paramétrages.
        $str = get_string('settings');
        $url = new moodle_url('/local/apsolu/federation/index.php', array('page' => 'settings'));
        $ADMIN->add('local_apsolu_federation', new admin_externalpage('local_apsolu_federation_settings', $str, $url, $capabilities));

        // Liste des activités.
        $str = get_string('activity_list', 'local_apsolu');
        $url = new moodle_url('/local/apsolu/federation/index.php', array('page' => 'activities'));
        $ADMIN->add('local_apsolu_federation', new admin_externalpage('local_apsolu_federation_activities', $str, $url, $capabilities));

        // Numéros d'association.
        $str = get_string('association_numbers', 'local_apsolu');
        $url = new moodle_url('/local/apsolu/federation/index.php', array('page' => 'numbers'));
        $ADMIN->add('local_apsolu_federation', new admin_externalpage('local_apsolu_federation_numbers', $str, $url, $capabilities));

        // Exporter les licences.
        $str = get_string('exporting_license', 'local_apsolu');
        $url = new moodle_url('/local/apsolu/federation/index.php', array('page' => 'export'));
        $ADMIN->add('local_apsolu_federation', new admin_externalpage('local_apsolu_federation_export', $str, $url, $capabilities));

        // Importer les licences.
        $str = get_string('importing_license', 'local_apsolu');
        $url = new moodle_url('/local/apsolu/federation/index.php', array('page' => 'import'));
        $ADMIN->add('local_apsolu_federation', new admin_externalpage('local_apsolu_federation_import', $str, $url, $capabilities));

        // Validation des certificats.
        $str = get_string('certificates_validation', 'local_apsolu');
        $url = new moodle_url('/local/apsolu/federation/index.php', array('page' => 'certificates_validation'));
        $ADMIN->add('local_apsolu_federation', new admin_externalpage('local_apsolu_federation_certificates_validation', $str, $url, $capabilities));
    }

    // Inscriptions.
    require_once($CFG->dirroot.'/enrol/select/settings.php');

    // Notations.
    $ADMIN->add('apsolu', new admin_category('local_apsolu_grades', get_string('grades', 'local_apsolu')));

    // Notations > Réglages des éléments d'évaluation.
    $label = get_string('gradeitemsettings', 'grades');
    $url = new moodle_url('/local/apsolu/grades/admin/index.php', array('tab' => 'gradeitems'));
    $ADMIN->add('local_apsolu_grades', new admin_externalpage('local_apsolu_grades_gradeitems', $label, $url, $capabilities));

    if (has_capability('local/apsolu:viewallgrades', context_system::instance()) === true) {
        // Notations > Carnet de notes.
        $label = get_string('gradebook', 'grades');
        $url = new moodle_url('/local/apsolu/grades/admin/index.php', array('tab' => 'gradebooks'));
        $ADMIN->add('local_apsolu_grades', new admin_externalpage('local_apsolu_grades_gradebooks', $label, $url, $capabilities));
    }

    // Paiements.
    $ADMIN->add('apsolu', new admin_category('local_apsolu_payment',  get_string('settings_payments', 'local_apsolu')));

    if (has_capability('local/apsolu:configpaybox', context_system::instance()) === true) {
        // Paiements > Adresses des serveurs Paybox.
        $label = get_string('settings_payments_servers', 'local_apsolu');
        $url = new moodle_url('/local/apsolu/payment/admin.php', array('tab' => 'configurations'));
        $ADMIN->add('local_apsolu_payment', new admin_externalpage('local_apsolu_payment_configurations', $label, $url, $capabilities));

        // Paiements > Centres de paiement.
        $label = get_string('payment_centers', 'local_apsolu');
        $url = new moodle_url('/local/apsolu/payment/admin.php', array('tab' => 'centers'));
        $ADMIN->add('local_apsolu_payment', new admin_externalpage('local_apsolu_payment_centers', $label, $url, $capabilities));
    }

    // Paiements > Dates.
    $str = get_string('dates', 'local_apsolu');
    $url = new moodle_url('/local/apsolu/configuration/index.php', array('page' => 'dates'));
    $ADMIN->add('local_apsolu_payment', new admin_externalpage('local_apsolu_configuration_dates', $str, $url, $capabilities));

    // Paiements > Liste des paiements.
    $label = get_string('settings_payments_list', 'local_apsolu');
    $url = new moodle_url('/local/apsolu/payment/admin.php', array('tab' => 'payments'));
    $ADMIN->add('local_apsolu_payment', new admin_externalpage('local_apsolu_payment_payments', $label, $url, $capabilities));

    // Paiements > Relance de paiement.
    $label = get_string('dunning', 'local_apsolu');
    $url = new moodle_url('/local/apsolu/payment/admin.php', array('tab' => 'notifications'));
    $ADMIN->add('local_apsolu_payment', new admin_externalpage('local_apsolu_payment_notifications', $label, $url, $capabilities));

    // Paiements > Tarif.
    $label = get_string('payment_cards', 'local_apsolu');
    $url = new moodle_url('/local/apsolu/payment/admin.php', array('tab' => 'prices'));
    $ADMIN->add('local_apsolu_payment', new admin_externalpage('local_apsolu_payment_prices', $label, $url, $capabilities));

    // Présentation.
    if (empty($ADMIN->locate('local_apsolu_appearance')) === true) {
        $ADMIN->add('apsolu', new admin_category('local_apsolu_appearance', get_string('appearance', 'admin')));
    }

    // Présentation > Affichage de l'offre de formations.
    $str = get_string('settings_course_offerings', 'local_apsolu');
    $url = new moodle_url('/local/apsolu/configuration/index.php', array('page' => 'courseofferings'));
    $ADMIN->add('local_apsolu_appearance', new admin_externalpage('local_apsolu_configuration_course_offerings', $str, $url, $capabilities));

    // Présentation > Message d'entête.
    $str = get_string('header_message', 'local_apsolu');
    $url = new moodle_url('/local/apsolu/configuration/index.php', array('page' => 'headermessage'));
    $ADMIN->add('local_apsolu_appearance', new admin_externalpage('local_apsolu_configuration_header_message', $str, $url, $capabilities));

    // Statistiques.
    $ADMIN->add('apsolu', new admin_category('local_apsolu_statistics', get_string('statistics', 'local_apsolu')));
    $ADMIN->add('local_apsolu_statistics', new admin_category('local_apsolu_statistics_population', get_string('statistics_population', 'local_apsolu')));
    $label = get_string('statistics_dashboard', 'local_apsolu');
    $url = new moodle_url('/local/apsolu/statistics/population/index.php', array('page' => 'dashboard'));
    $ADMIN->add('local_apsolu_statistics_population', new admin_externalpage('local_apsolu_statistics_population_dashboard', $label, $url, $capabilities));
    $label = get_string('statistics_reports', 'local_apsolu');
    $url = new moodle_url('/local/apsolu/statistics/population/index.php', array('page' => 'reports'));
    $ADMIN->add('local_apsolu_statistics_population', new admin_externalpage('local_apsolu_statistics_population_reports', $label, $url, $capabilities));
    $label = get_string('statistics_custom', 'local_apsolu');
    $url = new moodle_url('/local/apsolu/statistics/population/index.php', array('page' => 'custom'));
    $ADMIN->add('local_apsolu_statistics_population', new admin_externalpage('local_apsolu_statistics_population_custom', $label, $url, $capabilities));

    $ADMIN->add('local_apsolu_statistics', new admin_category('local_apsolu_statistics_programme', get_string('statistics_programme', 'local_apsolu')));
    $label = get_string('statistics_dashboard', 'local_apsolu');
    $url = new moodle_url('/local/apsolu/statistics/programme/index.php', array('page' => 'dashboard'));
    $ADMIN->add('local_apsolu_statistics_programme', new admin_externalpage('local_apsolu_statistics_programme_dashboard', $label, $url, $capabilities));
    $label = get_string('statistics_reports', 'local_apsolu');
    $url = new moodle_url('/local/apsolu/statistics/programme/index.php', array('page' => 'reports'));
    $ADMIN->add('local_apsolu_statistics_programme', new admin_externalpage('local_apsolu_statistics_programme_reports', $label, $url, $capabilities));
    $label = get_string('statistics_custom', 'local_apsolu');
    $url = new moodle_url('/local/apsolu/statistics/programme/index.php', array('page' => 'custom'));
    $ADMIN->add('local_apsolu_statistics_programme', new admin_externalpage('local_apsolu_statistics_programme_custom', $label, $url, $capabilities));
}
