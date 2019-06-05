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
        $apsolu_root = new admin_category('apsolu', get_string('settings_root', 'local_apsolu'));
        // Tri les enfants par ordre alphabétique.
        $apsolu_root->set_sorting($sort = true);
        // Place le noeud Apsolu avant le noeud Utilisateurs de Moodle.
        $ADMIN->add('root', $apsolu_root, 'users');
    }

    // Activités physiques.
    $ADMIN->add('apsolu', new admin_category('local_apsolu_courses', get_string('settings_activities', 'local_apsolu')));
    $ADMIN->add('local_apsolu_courses', new admin_category('local_apsolu_courses_courses', get_string('activities', 'local_apsolu')));
    $ADMIN->add('local_apsolu_courses', new admin_category('local_apsolu_courses_locations', get_string('locations', 'local_apsolu')));
    $ADMIN->add('local_apsolu_courses', new admin_category('local_apsolu_courses_periods', get_string('periods', 'local_apsolu')));
    $ADMIN->add('local_apsolu_courses', new admin_category('local_apsolu_courses_skills', get_string('skills', 'local_apsolu')));

    $items = array();
    $items['courses'] = array('groupings', 'categories', 'courses');
    $items['periods'] = array('periods');
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
            $url = new moodle_url('/local/apsolu/courses/index.php?tab='.$tab);
            $page = new admin_externalpage('local_apsolu_courses_'.$subtype.'_'.$tab, $label, $url, $capabilities);

            $ADMIN->add('local_apsolu_courses_'.$subtype, $page);
        }
    }

    $label = get_string('overview', 'local_apsolu');
    $url = new moodle_url('/local/apsolu/courses/index.php?tab=overview');
    $page = new admin_externalpage('local_apsolu_courses_courses_overview', $label, $url, $capabilities);
    $ADMIN->add('local_apsolu_courses', $page);

    // Activités complémentaires.
    if (isset($CFG->is_siuaps_rennes) === true) {
        $ADMIN->add('apsolu', new admin_category('local_apsolu_complements', get_string('settings_complements', 'local_apsolu')));

        // Activités complémentaires > Activités complémentaires.
        $label = get_string('settings_complements', 'local_apsolu');
        $url = new moodle_url('/local/apsolu/courses/complements.php?tab=complements');
        $ADMIN->add('local_apsolu_complements', new admin_externalpage('local_apsolu_complements_complements', $label, $url, $capabilities));

        // Activités complémentaires > FFSU.
        $label = get_string('settings_federations', 'local_apsolu');
        $url = new moodle_url('/local/apsolu/courses/complements.php?tab=federations');
        $ADMIN->add('local_apsolu_complements', new admin_externalpage('local_apsolu_complements_federations', $label, $url, $capabilities));
    }

    // Configuration.
    $ADMIN->add('apsolu', new admin_category('local_apsolu_configuration', get_string('settings_configuration', 'local_apsolu')));

    // Configuration > Adresse de contacts.
    $str = get_string('settings_configuration_contacts', 'local_apsolu');
    $url = new moodle_url('/local/apsolu/configuration/index.php', array('page' => 'contacts'));
    $ADMIN->add('local_apsolu_configuration', new admin_externalpage('local_apsolu_configuration_contacts', $str, $url, $capabilities));

    // Configuration > Calendriers.
    $str = get_string('settings_configuration_calendars', 'local_apsolu');
    $url = new moodle_url('/local/apsolu/configuration/index.php', array('page' => 'calendars'));
    $ADMIN->add('local_apsolu_configuration', new admin_externalpage('local_apsolu_configuration_calendars', $str, $url, $capabilities));

    // Configuration > Type de calendriers.
    $str = get_string('settings_configuration_calendarstypes', 'local_apsolu');
    $url = new moodle_url('/local/apsolu/configuration/index.php', array('page' => 'calendarstypes'));
    $ADMIN->add('local_apsolu_configuration', new admin_externalpage('local_apsolu_configuration_calendarstypes', $str, $url, $capabilities));

    // Fédération FSU.
    if (isset($CFG->is_siuaps_rennes) === true) {
        $ADMIN->add('apsolu', new admin_category('local_apsolu_federation', get_string('settings_federation', 'local_apsolu')));

        // Importer les licences.
        $str = get_string('settings_federation_import', 'local_apsolu');
        $url = new moodle_url('/local/apsolu/federation/index.php', array('page' => 'import'));
        $ADMIN->add('local_apsolu_federation', new admin_externalpage('local_apsolu_federation_import', $str, $url, $capabilities));
    }

    // Inscriptions.
    $ADMIN->add('apsolu', new admin_category('local_apsolu_enrol', get_string('enrolments', 'enrol')));

    // Inscriptions > Population.
    $url = new moodle_url('/enrol/select/administration.php?tab=colleges');
    $ADMIN->add('local_apsolu_enrol', new admin_externalpage('enrol_select_colleges', get_string('colleges', 'enrol_select'), $url, $capabilities));

    // Notations.
    if (isset($CFG->is_siuaps_rennes) === true) {
        $ADMIN->add('apsolu', new admin_category('local_apsolu_grades', get_string('grades', 'local_apsolu')));

        // Notations > Exporter.
        $label = get_string('export', 'local_apsolu');
        $url = new moodle_url('/local/apsolu/grades/grades.php?tab=export');
        $ADMIN->add('local_apsolu_grades', new admin_externalpage('local_apsolu_grades_export', $label, $url, $capabilities));
    }

    // Paiements.
    $ADMIN->add('apsolu', new admin_category('local_apsolu_payment',  get_string('settings_payments', 'local_apsolu')));

    $isgod = has_capability('moodle/site:config', context_system::instance());

    // Paiements > Adresses des serveurs Paybox.
    if ($isgod === true) {
        $label = get_string('settings_payments_servers', 'local_apsolu');
        $url = new moodle_url('/local/apsolu/payment/admin.php', array('tab' => 'configurations'));
        $ADMIN->add('local_apsolu_payment', new admin_externalpage('local_apsolu_payment_configurations', $label, $url, $capabilities));
    }

    // Paiements > Centres de paiement.
    if ($isgod === true) {
        $label = get_string('payment_centers', 'local_apsolu');
        $url = new moodle_url('/local/apsolu/payment/admin.php', array('tab' => 'centers'));
        $ADMIN->add('local_apsolu_payment', new admin_externalpage('local_apsolu_payment_centers', $label, $url, $capabilities));
    }

    // Paiements > Dates.
    $str = get_string('settings_configuration_dates', 'local_apsolu');
    $url = new moodle_url('/local/apsolu/configuration/index.php', array('page' => 'dates'));
    $ADMIN->add('local_apsolu_payment', new admin_externalpage('local_apsolu_configuration_dates', $str, $url, $capabilities));

    // Paiements > Paiements.
    $label = get_string('settings_payments_list', 'local_apsolu');
    $url = new moodle_url('/local/apsolu/payment/admin.php', array('tab' => 'payments'));
    $ADMIN->add('local_apsolu_payment', new admin_externalpage('local_apsolu_payment_payments', $label, $url, $capabilities));

    // Paiements > Notification.
    $label = get_string('notifications');
    $url = new moodle_url('/local/apsolu/payment/admin.php', array('tab' => 'notifications'));
    $ADMIN->add('local_apsolu_payment', new admin_externalpage('local_apsolu_payment_notifications', $label, $url, $capabilities));

    // Paiements > Tarif.
    $label = get_string('payment_cards', 'local_apsolu');
    $url = new moodle_url('/local/apsolu/payment/admin.php', array('tab' => 'prices'));
    $ADMIN->add('local_apsolu_payment', new admin_externalpage('local_apsolu_payment_prices', $label, $url, $capabilities));

    // Présentation.
    $ADMIN->add('apsolu', new admin_category('local_apsolu_appearance', get_string('appearance', 'admin')));

    // Présentation > Message d'entête.
    $str = get_string('settings_configuration_header', 'local_apsolu');
    $url = new moodle_url('/local/apsolu/configuration/index.php', array('page' => 'header'));
    $ADMIN->add('local_apsolu_appearance', new admin_externalpage('local_apsolu_configuration_header', $str, $url, $capabilities));
}
