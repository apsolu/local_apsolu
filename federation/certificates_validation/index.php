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
 * Contrôleur pour gérer la partie liste des activités FFSU.
 *
 * @package    local_apsolu
 * @copyright  2022 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\federation\adhesion as Adhesion;

defined('MOODLE_INTERNAL') || die();

define('APSOLU_SELECT_ANY', '0');
define('APSOLU_SELECT_YES', '1');
define('APSOLU_SELECT_NO', '2');

require_once(__DIR__.'/certificates_validation_form.php');

// Définit les options d'état des certificats.
$certificates = array(
    APSOLU_SELECT_ANY => get_string('all'),
    APSOLU_SELECT_YES => get_string('medical_certificate_validated', 'local_apsolu'),
    APSOLU_SELECT_NO => get_string('medical_certificate_not_validated', 'local_apsolu'),
);

$customdata = array('certificates' => $certificates);
$mform = new local_apsolu_federation_certificates_validation(null, $customdata);

$content = '';
if ($data = $mform->get_data()) {
    $parameters = array();
    $conditions = array();
    $url_options = array('page' => 'certificates_validation');

    if (empty($data->fullnameuser) === false) {
        $parameters['fullnameuser'] = '%'.$data->fullnameuser.'%';
        $conditions[] = sprintf("AND %s LIKE :fullnameuser ", $DB->sql_fullname('u.firstname', 'u.lastname'));

        $url_options['fullnameuser'] = $parameters['fullnameuser'];
    }

    if (empty($data->idnumber) === false) {
        $parameters['idnumber'] = '%'.$data->idnumber.'%';
        $conditions[] = "AND u.idnumber LIKE :idnumber ";

        $url_options['idnumber'] = $parameters['idnumber'];
    }

    // État du certificat médical.
    if ($data->medical_certificate_status === APSOLU_SELECT_YES) {
        $parameters['status'] = Adhesion::MEDICAL_CERTIFICATE_STATUS_VALIDATED;
        $conditions[] = "AND afa.medicalcertificatestatus = :status";

        $url_options['medical_certificate_status'] = $parameters['status'];
    } elseif ($data->medical_certificate_status === APSOLU_SELECT_NO) {
        $parameters['status'] = Adhesion::MEDICAL_CERTIFICATE_STATUS_PENDING;
        $conditions[] = "AND afa.medicalcertificatestatus = :status";

        $url_options['medical_certificate_status'] = $parameters['status'];
    }

    $sql = "SELECT u.id, u.lastname, u.firstname, u.idnumber, u.email, afa.medicalcertificatedate, afa.medicalcertificatestatus, afa.federationnumber".
        " FROM {user} u".
        " JOIN {apsolu_federation_adhesions} afa ON u.id = afa.userid".
        " WHERE 1 = 1 ".implode(' ', $conditions).
        " ORDER BY u.lastname, u.firstname";

    $rows = array();
    $recordset = $DB->get_recordset_sql($sql, $parameters);
    foreach ($recordset as $record) {
        $row = array();
        $row[] = $record->lastname;
        $row[] = $record->firstname;
        $row[] = $record->idnumber;

        if ($record->medicalcertificatestatus === Adhesion::MEDICAL_CERTIFICATE_STATUS_EXEMPTED) {
            $row[] = ''; // Aucun fichier.
            $row[] = get_string('medical_certificate_not_required', 'local_apsolu');
            $row[] = ''; // Aucune action.
        } else {
            // Récupère les fichiers déposés.
            $fs = get_file_storage();
            $context = context_course::instance($courseid, MUST_EXIST);
            list($component, $filearea, $itemid) = array('local_apsolu', 'medicalcertificate', $record->id);
            $sort = 'itemid, filepath, filename';
            $files = $fs->get_area_files($context->id, $component, $filearea, $itemid, $sort, $includedirs = false);
            if (count($files) === 0) {
                $row[] = get_string('no_files', 'local_apsolu');
                $row[] = get_string('medical_certificate_not_validated', 'local_apsolu');
                $row[] = ''; // Aucune action.
            } else {
                $items = array();
                foreach ($files as $file) {
                    $url = moodle_url::make_pluginfile_url($context->id, $component, $filearea, $itemid, '/', $file->get_filename(), $forcedownload = false, $includetoken = false);
                    $items[] = html_writer::link($url, $file->get_filename());
                }
                $row[] = html_writer::alist($items, $attributes = array(), $tag = 'ul');

                $attributes = array(
                    'class' => 'local-apsolu-federation-medical-certificate-validation',
                    'data-contextid' => $context->id,
                    'data-target-validation' => Adhesion::MEDICAL_CERTIFICATE_STATUS_VALIDATED,
                    'data-target-validation-text' => get_string('medical_certificate_validated', 'local_apsolu'),
                    'data-users' => $record->id,
                );

                $menuoptions = array();

                $attributes['data-stringid'] = 'medical_certificate_validation_message';
                $menuoptions[] = array(
                    'attributes' => $attributes,
                    'icon' => 'i/grade_correct',
                    'label' => get_string('validate', 'local_apsolu')
                );
                $attributes['data-stringid'] = 'medical_certificate_refusal_message_for_one_year_expiration';
                $attributes['data-target-validation'] = Adhesion::MEDICAL_CERTIFICATE_STATUS_PENDING;
                $attributes['data-target-validation-text'] = get_string('medical_certificate_not_validated', 'local_apsolu');
                $menuoptions[] = array(
                    'attributes' => $attributes,
                    'icon' => 'i/grade_incorrect',
                    'label' => get_string('refuse_with_reasons_X', 'local_apsolu', strtolower(get_string('more_than_one_year', 'local_apsolu')))
                );
                $attributes['data-stringid'] = 'medical_certificate_refusal_message_for_six_months_expiration';
                $menuoptions[] = array(
                    'attributes' => $attributes,
                    'icon' => 'i/grade_incorrect',
                    'label' => get_string('refuse_with_reasons_X', 'local_apsolu', strtolower(get_string('more_than_six_months', 'local_apsolu')))
                );
                $attributes['data-stringid'] = 'medical_certificate_refusal_message_for_mention_missing';
                $menuoptions[] = array(
                    'attributes' => $attributes,
                    'icon' => 'i/grade_incorrect',
                    'label' => get_string('refuse_with_reasons_X', 'local_apsolu', strtolower(get_string('mention_missing', 'local_apsolu')))
                );

                if ($record->medicalcertificatestatus === Adhesion::MEDICAL_CERTIFICATE_STATUS_PENDING) {
                    // Certificat en attente de validation.
                    $cell = new html_table_cell();
                    $cell->text = get_string('medical_certificate_not_validated', 'local_apsolu');
                    $cell->attributes = array('class' => 'medical-certificate-status', 'data-userid' => $record->id);
                    $row[] = $cell;

                    $menu = new action_menu();
                    $menu->set_menu_trigger(get_string('edit'));

                    foreach ($menuoptions as $value) {
                        $menulink = new action_menu_link_secondary(
                            new moodle_url(''),
                            new pix_icon($value['icon'], '', null, array('class' => 'smallicon')),
                            $value['label'],
                            $value['attributes'],
                        );
                        $menu->add($menulink);
                    }

                    $row[] = $OUTPUT->render($menu);
                } else if ($record->medicalcertificatestatus === Adhesion::MEDICAL_CERTIFICATE_STATUS_VALIDATED) {
                    // Certificat validé.
                    $cell = new html_table_cell();
                    $cell->text = get_string('medical_certificate_validated', 'local_apsolu');
                    $cell->attributes = array('class' => 'medical-certificate-status', 'data-userid' => $record->id);
                    $row[] = $cell;

                    if (empty($record->federationnumber) === true) {
                        $menu = new action_menu();
                        $menu->set_menu_trigger(get_string('edit'));

                        $menuoptions[0]['attributes']['class'] .= ' d-none'; // Supprime l'option de validation.
                        foreach ($menuoptions as $value) {
                            $menulink = new action_menu_link_secondary(
                                new moodle_url(''),
                                new pix_icon($value['icon'], '', null, array('class' => 'smallicon')),
                                $value['label'],
                                $value['attributes'],
                            );
                            $menu->add($menulink);
                        }

                        $row[] = $OUTPUT->render($menu);
                    } else {
                        $row[] = ''; // Certificat ne pouvant plus être annulé.
                    }
                }
            }
        }

        $rows[] = $row;
    }

    $recordset->close();

    // Affiche le résultat au format HTML.
    if (empty($rows) === true) {
        $content = $OUTPUT->notification(get_string('no_results_with_these_criteria', 'local_apsolu'), 'notifyerror');
    } else {
        $headers = array(
            get_string('lastname'),
            get_string('firstname'),
            get_string('idnumber'),
            get_string('file'),
            get_string('medical_certificate_status', 'local_apsolu'),
            get_string('action'),
        );

        $table = new html_table();
        $table->id = 'local-apsolu-certificates-validation-table';
        $table->head  = $headers;
        $table->caption = count($rows).' '.get_string('users');
        $table->data  = $rows;
        $content = html_writer::table($table);
    }
}

$PAGE->requires->js_call_amd('local_apsolu/federation_medical_certificate_validation', 'initialise');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('certificates_validation', 'local_apsolu'));

$mform->display();
echo $content;

echo $OUTPUT->footer();