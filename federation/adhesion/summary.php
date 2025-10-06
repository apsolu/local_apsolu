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
 * Page affichant le récapitulatif de l'adhésion FFSU.
 *
 * @package    local_apsolu
 * @copyright  2022 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\federation\activity as Activity;
use local_apsolu\core\federation\adhesion as Adhesion;
use UniversiteRennes2\Apsolu\Payment;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot.'/local/apsolu/classes/apsolu/payment.php');

$getconfig = get_config('local_apsolu');

$adhesion->data = json_decode($adhesion->data);
if ($adhesion->data === false) {
    // Ce cas ne devrait jamais arriver.
    throw new moodle_exception('error');
}

$licenses = [];
$licensetypes = Adhesion::get_license_types();
foreach ($adhesion->data->licensetype as $license) {
    if (isset($licensetypes[$license]) === false) {
        continue;
    }

    $licenses[] = $licensetypes[$license];
}

$data = new stdClass();
$data->fields = [];
$data->fields[] = ['label' => get_string('federation_number', 'local_apsolu'), 'value' => $adhesion->federationnumber];
$data->fields[] = ['label' => get_string('license_type', 'local_apsolu'), 'value' => implode(', ', $licenses)];
$data->fields[] = ['label' => get_string('user_title', 'local_apsolu'), 'value' => $adhesion->data->title];
$data->fields[] = ['label' => get_string('lastname'), 'value' => $USER->lastname];
$data->fields[] = ['label' => get_string('firstname'), 'value' => $USER->firstname];
$data->fields[] = ['label' => get_string('birthday', 'local_apsolu'),
    'value' => userdate($adhesion->birthday, get_string('strftimedate'))];
$data->fields[] = ['label' => get_string('nationality', 'local_apsolu'), 'value' => $adhesion->data->nationality];
$data->fields[] = ['label' => get_string('mail', 'local_apsolu'), 'value' => $USER->email];
$data->fields[] = ['label' => get_string('phone2', 'local_apsolu'), 'value' => $adhesion->data->phone2];
$data->fields[] = ['label' => get_string('discipline', 'local_apsolu'), 'value' => implode(', ', $adhesion->data->activity)];

// On récupère le champ autre fédération.
if (empty($getconfig->otherfederation) === false) {
    $data->fields[] = ['label' => get_string('other_federation', 'local_apsolu'), 'value' => $adhesion->data->otherfederation];
}

// On récupère la liste des champs oui/non.
$yesnofields = [];
$yesnofields['federaltexts'] = get_string('federal_texts', 'local_apsolu');
$yesnofields['policyagreed'] = get_string('terms_of_use_for_data', 'local_apsolu');
$yesnofields['commercialoffers'] = get_string('commercial_offers', 'local_apsolu');
$yesnofields['usepersonalimage'] = get_string('image_rights', 'local_apsolu');
$yesnofields['newsletter'] = get_string('newsletter', 'local_apsolu');
$yesnofields['insurance'] = get_string('insurance', 'local_apsolu');
foreach ($yesnofields as $field => $label) {
    $visibilitystrname = sprintf('%s_field_visibility', $field);
    if (isset($getconfig->$visibilitystrname) === true && empty($getconfig->$visibilitystrname) === true) {
        continue;
    }

    if (empty($adhesion->data->{$field}) === false) {
        $value = get_string('yes');
    } else {
        $value = get_string('no');
    }

    $data->fields[] = ['label' => $label, 'value' => $value];
}

// On récupère la date du certificat médical.
if (empty($adhesion->data->medicalcertificatedate) === false) {
    $data->fields[] = ['label' => get_string('doctor_name', 'local_apsolu'), 'value' => $adhesion->data->doctorname];
    $data->fields[] = ['label' => get_string('doctor_rpps', 'local_apsolu'), 'value' => $adhesion->data->doctorrpps];

    $value = userdate($adhesion->data->medicalcertificatedate, get_string('strftimedate'));
    $data->fields[] = ['label' => get_string('medical_certificate_date', 'local_apsolu'), 'value' => $value];
}

// On récupère les certificats.
$fs = get_file_storage();
$context = context_course::instance($federationcourse->id, MUST_EXIST);
list($component, $filearea, $itemid) = ['local_apsolu', 'medicalcertificate', $USER->id];
$sort = 'itemid, filepath, filename';
$files = $fs->get_area_files($context->id, $component, $filearea, $itemid, $sort, $includedirs = false);

$items = [];
foreach ($files as $file) {
    $url = moodle_url::make_pluginfile_url($context->id, $component, $filearea, $itemid, '/',
        $file->get_filename(), $forcedownload = false, $includetoken = false);
    $items[] = html_writer::link($url, $file->get_filename());
}

if (isset($items[0]) === true) {
    $data->fields[] = ['label' => get_string('medical_certificate', 'local_apsolu'), 'htmlvalue' => html_writer::alist($items)];
}

echo $OUTPUT->render_from_template('local_apsolu/federation_adhesion_summary', $data);
