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
 * Post installation hook for adding data.
 *
 * @package    local_apsolu
 * @copyright  2017 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\task\manager;
use local_apsolu\core\attendance\status as AttendanceStatus;
use local_apsolu\core\federation\activity;
use local_apsolu\core\federation\adhesion;
use local_apsolu\core\messaging;
use local_apsolu\core\municipality;
use local_apsolu\task\setup_behat_data;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/apsolu/locallib.php');

/**
 * Post installation procedure.
 */
function xmldb_local_apsolu_install() {
    global $DB;

    // Initialise les variables du plugin.
    set_config('payments_startdate', '', 'local_apsolu');
    set_config('payments_enddate', '', 'local_apsolu');

    set_config('functional_contact', '', 'local_apsolu');
    set_config('technical_contact', '', 'local_apsolu');

    set_config('apsoluheaderactive', 0, 'local_apsolu');
    set_config('apsoluheadercontent', '', 'local_apsolu');

    set_config('replytoaddresspreference', messaging::DISABLE_REPLYTO_ADDRESS, 'local_apsolu');
    set_config('defaultreplytoaddresspreference', messaging::USE_REPLYTO_ADDRESS, 'local_apsolu');

    set_config('display_fields', '["email","institution","department"]', 'local_apsolu');
    set_config('export_fields', '["email","institution","department"]', 'local_apsolu');
    set_config('userhiddenfields', 'address,apsolupostalcode,apsolubirthday,country,phone1,phone2,city', 'local_apsolu');

    set_config('ffsu_acceptedfiles', '.pdf .odt .doc .docx .jpe .jpeg .jpg .png', 'local_apsolu');
    set_config('ffsu_maxfiles', 1, 'local_apsolu');
    set_config('ffsu_agreement', get_string('default_federation_agreement', 'local_apsolu'), 'local_apsolu');
    set_config('ffsu_introduction', get_string('federation_introduction', 'local_apsolu'), 'local_apsolu');
    set_config('parental_authorization_description', '', 'local_apsolu');

    set_config('enable_pass_sport_payment', '0', 'local_apsolu');
    set_config('insurance_field_default', '0', 'local_apsolu');
    set_config('licenseetype_field_default', '1', 'local_apsolu');
    set_config('licensetype_field_default', '["S"]', 'local_apsolu');

    set_config('insurance_field_visibility', Adhesion::FIELD_HIDDEN, 'local_apsolu');
    set_config('licenseetype_field_visibility', Adhesion::FIELD_VISIBLE, 'local_apsolu');
    set_config('licensetype_field_visibility', Adhesion::FIELD_LOCKED, 'local_apsolu');
    set_config('otherfederation_field_visibility', Adhesion::FIELD_VISIBLE, 'local_apsolu');

    // Initialise les variables liées à la prise de présences.
    set_config('qrcode_enabled', 0, 'local_apsolu');
    set_config('qrcode_starttime', 15 * 60, 'local_apsolu');
    set_config('qrcode_presentstatus', 1, 'local_apsolu');
    set_config('qrcode_latetimeenabled', 1, 'local_apsolu');
    set_config('qrcode_latetime', 15 * 60, 'local_apsolu');
    set_config('qrcode_latestatus', 2, 'local_apsolu');
    set_config('qrcode_endtimeenabled', 1, 'local_apsolu');
    set_config('qrcode_endtime', 30 * 60, 'local_apsolu');
    set_config('qrcode_automarkenabled', 1, 'local_apsolu');
    set_config('qrcode_automarkstatus', 4, 'local_apsolu');
    set_config('qrcode_automarktime', DAYSECS, 'local_apsolu');
    set_config('qrcode_allowguests', 0, 'local_apsolu');
    set_config('qrcode_autologout', 1, 'local_apsolu');
    set_config('qrcode_rotate', 0, 'local_apsolu');

    // Initialise les paramètres de l'offre de formations.
    UniversiteRennes2\Apsolu\set_initial_course_offerings_settings();

    // Ajoute les différents champs de profil complémentaires.
    $fields = $DB->get_records('user_info_field', [], $sort = 'sortorder DESC');
    if (count($fields) === 0) {
        // Ajoute une sous-catégorie de champs complémentaires.
        $category = $DB->get_record('user_info_category', ['sortorder' => 1]);
        if ($category === false) {
            $category = new stdClass();
            $category->name = get_string('fields_complements_category', 'local_apsolu');
            $category->sortorder = 1;
            $category->id = $DB->insert_record('user_info_category', $category);
        }

        $field = (object) [
            'datatype' => 'text',
            'description' => '',
            'descriptionformat' => '',
            'categoryid' => $category->id,
            'sortorder' => '0',
            'required' => '0',
            'locked' => '1',
            'visible' => '1',
            'forceunique' => '0',
            'signup' => '0',
            'defaultdata' => '',
            'defaultdataformat' => '0',
            'param1' => '30',
            'param2' => '2048',
            'param3' => '0',
            'param4' => '',
            'param5' => '',
           ];
    } else {
        $field = current($fields);
        unset($field->id);
    }

    $customs = [];
    $customs[] = (object) ['shortname' => 'apsolupostalcode', 'datatype' => 'text',
        'param1' => 30, 'param2' => 2048, 'param3' => 0, 'visible' => 0];
    $customs[] = (object) ['shortname' => 'apsolusex', 'datatype' => 'text',
        'param1' => 30, 'param2' => 2048, 'param3' => 0, 'visible' => 0];
    $customs[] = (object) ['shortname' => 'apsolubirthday', 'datatype' => 'text',
        'param1' => 30, 'param2' => 2048, 'param3' => 0, 'visible' => 1];
    $customs[] = (object) ['shortname' => 'apsoluufr', 'datatype' => 'text',
        'param1' => 30, 'param2' => 2048, 'param3' => 0, 'visible' => 1];
    $customs[] = (object) ['shortname' => 'apsolucycle', 'datatype' => 'text',
        'param1' => 30, 'param2' => 2048, 'param3' => 0, 'visible' => 1];
    $customs[] = (object) ['shortname' => 'apsolusesame', 'datatype' => 'checkbox',
        'param1' => null, 'param2' => null, 'param3' => null, 'visible' => 0];
    $customs[] = (object) ['shortname' => 'apsoluhighlevelathlete', 'datatype' => 'checkbox',
        'param1' => null, 'param2' => null, 'param3' => null, 'visible' => 0];
    $customs[] = (object) ['shortname' => 'apsoluidcardnumber', 'datatype' => 'text',
        'param1' => 30, 'param2' => 2048, 'param3' => 0, 'visible' => 0];
    $customs[] = (object) ['shortname' => 'apsoludoublecursus', 'datatype' => 'checkbox',
        'param1' => null, 'param2' => null, 'param3' => null, 'visible' => 0];
    $customs[] = (object) ['shortname' => 'apsoluusertype', 'datatype' => 'text',
        'param1' => 30, 'param2' => 2048, 'param3' => 0, 'visible' => 1];

    foreach ($customs as $custom) {
        $field->shortname = $custom->shortname;
        $field->name = get_string('fields_' . $field->shortname, 'local_apsolu');
        $field->datatype = $custom->datatype;
        $field->visible = $custom->visible;
        $field->param1 = $custom->param1;
        $field->param2 = $custom->param2;
        $field->param3 = $custom->param3;
        $field->sortorder++;

        $DB->insert_record('user_info_field', $field);
    }

    // Ajoute les données dans la table des activités de la FFSU.
    Activity::synchronize_database();

    // Initialise les données dans la table apsolu_attendance_statuses.
    AttendanceStatus::generate_default_values();

    // Initialise les données dans la table apsolu_municipalities.
    Municipality::initialize_dataset();

    // Initialise des données fictives pour les tests Behat.
    if (defined('BEHAT_UTIL') === true) {
        // Astuce afin de pouvoir injecter les données de tests lors de l'initialisation de la base de données Behat.
        $task = new setup_behat_data();
        manager::queue_adhoc_task($task, $checkforexisting = true);
        sleep(1);
    }

    return true;
}
