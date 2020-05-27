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

    // Ajoute les différents types de présences.
    $statuses = array('present', 'late', 'excused', 'absent');
    foreach ($statuses as $status) {
        $record = new stdClass();
        $record->name = $status;
        $record->code = 'attendance_'.$status;

        $DB->insert_record('apsolu_attendance_statuses', $record);
    }

    // Ajoute les différents champs de profil complémentaires.
    $fields = $DB->get_records('user_info_field', array(), $sort = 'sortorder DESC');
    if (count($fields) === 0) {
        // Ajoute une sous-catégorie de champs complémentaires.
        $category = $DB->get_record('user_info_category', array('sortorder' => 1));
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

    $customs = array();
    $customs[] = (object) ['shortname' => 'apsolupostalcode', 'datatype' => 'text', 'param1' => 30, 'param2' => 2048, 'param3' => 0, 'visible' => 0];
    $customs[] = (object) ['shortname' => 'apsolusex', 'datatype' => 'text', 'param1' => 30, 'param2' => 2048, 'param3' => 0, 'visible' => 0];
    $customs[] = (object) ['shortname' => 'apsolubirthday', 'datatype' => 'text', 'param1' => 30, 'param2' => 2048, 'param3' => 0, 'visible' => 1];
    $customs[] = (object) ['shortname' => 'apsoluufr', 'datatype' => 'text', 'param1' => 30, 'param2' => 2048, 'param3' => 0, 'visible' => 1];
    $customs[] = (object) ['shortname' => 'apsolucycle', 'datatype' => 'text', 'param1' => 30, 'param2' => 2048, 'param3' => 0, 'visible' => 1];
    $customs[] = (object) ['shortname' => 'apsolucardpaid', 'datatype' => 'checkbox', 'param1' => null, 'param2' => null, 'param3' => null, 'visible' => 0];
    $customs[] = (object) ['shortname' => 'apsolufederationpaid', 'datatype' => 'checkbox', 'param1' => null, 'param2' => null, 'param3' => null, 'visible' => 0];
    $customs[] = (object) ['shortname' => 'apsolumuscupaid', 'datatype' => 'checkbox', 'param1' => null, 'param2' => null, 'param3' => null, 'visible' => 0];
    $customs[] = (object) ['shortname' => 'apsolusesame', 'datatype' => 'checkbox', 'param1' => null, 'param2' => null, 'param3' => null, 'visible' => 0];
    $customs[] = (object) ['shortname' => 'apsolumedicalcertificate', 'datatype' => 'checkbox', 'param1' => null, 'param2' => null, 'param3' => null, 'visible' => 1];
    $customs[] = (object) ['shortname' => 'apsolufederationnumber', 'datatype' => 'text', 'param1' => 30, 'param2' => 2048, 'param3' => 0, 'visible' => 1];
    $customs[] = (object) ['shortname' => 'apsoluhighlevelathlete', 'datatype' => 'checkbox', 'param1' => null, 'param2' => null, 'param3' => null, 'visible' => 0];
    $customs[] = (object) ['shortname' => 'apsoluidcardnumber', 'datatype' => 'text', 'param1' => 30, 'param2' => 2048, 'param3' => 0, 'visible' => 0];
    $customs[] = (object) ['shortname' => 'apsoludoublecursus', 'datatype' => 'checkbox', 'param1' => null, 'param2' => null, 'param3' => null, 'visible' => 0];

    foreach ($customs as $custom) {
        $field->shortname = $custom->shortname;
        $field->name = get_string('fields_'.$field->shortname, 'local_apsolu');
        $field->datatype = $custom->datatype;
        $field->visible = $custom->visible;
        $field->param1 = $custom->param1;
        $field->param2 = $custom->param2;
        $field->param3 = $custom->param3;
        $field->sortorder++;

        $DB->insert_record('user_info_field', $field);
    }

    return true;
}
