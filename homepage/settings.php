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
 * @package    local_apsolu
 * @copyright  2017 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// Setup admin access requirement.
admin_externalpage_setup('local_apsolu_appearance_homepage');

require_once($CFG->dirroot.'/local/apsolu/homepage/settings_form.php');

// Build form.
$defaults = new stdClass();
$defaults->homepage_enable = get_config('local_apsolu', 'homepage_enable');

$defaults->homepage_section1_text = array('text' => get_config('local_apsolu', 'homepage_section1_text'), 'format' => 1);
// TODO: $defaults->homepage_section1_image = get_config('local_apsolu', 'homepage_section1_image');
// TODO: $defaults->homepage_section1_credit = get_config('local_apsolu', 'homepage_section1_credit');

// TODO: $defaults->homepage_section2_image = get_config('local_apsolu', 'homepage_section2_image');
// TODO: $defaults->homepage_section2_credit = get_config('local_apsolu', 'homepage_section2_credit');

$defaults->homepage_section3_text = array('text' => get_config('local_apsolu', 'homepage_section3_text'), 'format' => 1);
// TODO: $defaults->homepage_section3_image = get_config('local_apsolu', 'homepage_section3_image');
// TODO: $defaults->homepage_section3_credit = get_config('local_apsolu', 'homepage_section3_credit');

$defaults->homepage_section4_institutional_account_url = get_config('local_apsolu', 'homepage_section4_institutional_account_url');
$defaults->homepage_section4_non_institutional_account_url = get_config('local_apsolu', 'homepage_section4_non_institutional_account_url');

$customdata = array($defaults);
$mform = new local_apsolu_homepage_form(null, $customdata);

$notification = '';
if ($data = $mform->get_data()) {
    if (isset($data->homepage_enable) === false) {
        $data->homepage_enable = 0;
    }

    set_config('homepage_enable', $data->homepage_enable, 'local_apsolu');

    set_config('homepage_section1_text', $data->homepage_section1_text['text'], 'local_apsolu');
    // TODO: set_config('homepage_section1_image', $data->homepage_section1_image, 'local_apsolu');
    // TODO: set_config('homepage_section1_credit', $data->homepage_section1_credit, 'local_apsolu');

    // TODO: set_config('homepage_section2_image', $data->homepage_section2_image, 'local_apsolu');
    // TODO: set_config('homepage_section2_credit', $data->homepage_section2_credit, 'local_apsolu');

    set_config('homepage_section3_text', $data->homepage_section3_text['text'], 'local_apsolu');
    // TODO: set_config('homepage_section3_image', $data->homepage_section3_image, 'local_apsolu');
    // TODO: set_config('homepage_section3_credit', $data->homepage_section3_credit, 'local_apsolu');

    set_config('homepage_section4_institutional_account_url', $data->homepage_section4_institutional_account_url, 'local_apsolu');
    set_config('homepage_section4_non_institutional_account_url', $data->homepage_section4_non_institutional_account_url, 'local_apsolu');

    if ($defaults->homepage_enable !== $data->homepage_enable) {
        if (empty($data->homepage_enable) === true) {
            set_config('customfrontpageinclude', '');
        } else {
            set_config('customfrontpageinclude', $CFG->dirroot.'/local/apsolu/homepage/index.php');
        }
    }

    $notification = $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('settings_configuration_homepage', 'local_apsolu'));
echo $notification;
$mform->display();
echo $OUTPUT->footer();
