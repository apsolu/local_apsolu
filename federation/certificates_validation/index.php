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

use local_apsolu\core\federation\adhesion;

defined('MOODLE_INTERNAL') || die();

define('APSOLU_SELECT_ANY', '0');
define('APSOLU_SELECT_YES', '1');
define('APSOLU_SELECT_NO', '2');

require_once(__DIR__ . '/certificates_validation_form.php');

$idnumber = optional_param('idnumber', null, PARAM_INT);

// Définit les options d'état des certificats.
$certificates = [
    APSOLU_SELECT_ANY => get_string('all'),
    APSOLU_SELECT_YES => get_string('medical_certificate_validated', 'local_apsolu'),
    APSOLU_SELECT_NO => get_string('medical_certificate_not_validated', 'local_apsolu'),
];

$customdata = ['certificates' => $certificates];
$mform = new local_apsolu_federation_certificates_validation(null, $customdata);

$content = '';
if (($data = $mform->get_data()) || isset($idnumber) === true) {
    if (is_object($data) === false) {
        $data = (object) ['fullnameuser' => '', 'idnumber' => $idnumber, 'medical_certificate_status' => ''];
    }

    $parameters = [];
    $conditions = [];

    if (empty($data->fullnameuser) === false) {
        $parameters['fullnameuser'] = '%' . $data->fullnameuser . '%';
        $conditions[] = sprintf("AND %s LIKE :fullnameuser ", $DB->sql_fullname('u.firstname', 'u.lastname'));
    }

    if (empty($data->idnumber) === false) {
        $parameters['idnumber'] = '%' . $data->idnumber . '%';
        $conditions[] = "AND u.idnumber LIKE :idnumber ";
    }

    // Etat du certificat médical.
    if ($data->medical_certificate_status === APSOLU_SELECT_YES) {
        $parameters['status'] = Adhesion::MEDICAL_CERTIFICATE_STATUS_VALIDATED;
        $conditions[] = "AND afa.medicalcertificatestatus = :status";
    } else if ($data->medical_certificate_status === APSOLU_SELECT_NO) {
        $parameters['status'] = Adhesion::MEDICAL_CERTIFICATE_STATUS_PENDING;
        $conditions[] = "AND afa.medicalcertificatestatus = :status";
    }

    $federationactivities = $DB->get_records('apsolu_federation_activities', [], $sort = 'name', $fields = 'code, name');

    $fullnamefields = core_user\fields::get_name_fields();
    $sql = "SELECT u.id, " . implode(', ', $fullnamefields) . ", u.idnumber, u.email, u.institution, afa.id AS adhesionid
              FROM {user} u
              JOIN {apsolu_federation_adhesions} afa ON u.id = afa.userid
             WHERE 1 = 1 " . implode(' ', $conditions) . "
          ORDER BY afa.federationnumberrequestdate DESC, u.lastname, u.firstname";

    $rows = [];
    $recordset = $DB->get_recordset_sql($sql, $parameters);
    $context = context_course::instance($courseid, MUST_EXIST);
    foreach ($recordset as $record) {
        $adhesion = new Adhesion();
        $adhesion->load($record->adhesionid);
        if ($adhesion->id === 0) {
            continue;
        }

        $adhesion->data = json_decode($adhesion->data);
        if ($adhesion->data === false) {
            // Les données JSON ne sont pas valides. Ce cas ne devrait jamais arriver.
            continue;
        }

        $profileurl = new moodle_url('/user/view.php', ['id' => $record->id, 'course' => $courseid]);

        $row = [];
        if (empty($adhesion->federationnumberrequestdate) === true) {
            $row[] = get_string('never');
        } else {
            $title = userdate($adhesion->federationnumberrequestdate, get_string('strftimedatetimeshort', 'local_apsolu'));
            $text = userdate($adhesion->federationnumberrequestdate, get_string('strftimedatetimesortable', 'local_apsolu'));
            $row[] = '<span class="apsolu-cursor-help" title="' . s($title) . '">' . s(substr($text, 0, -3)) . '</span>';
        }
        $fullname = html_writer::link($profileurl, fullname($record));
        if (empty($record->idnumber) === false) {
            $fullname .= sprintf(' (%s)', $record->idnumber);
        }
        $row[] = $fullname;
        $row[] = $record->institution;

        // Liste les activités de l'adhérant.
        $activities = [];
        foreach ($adhesion->data->activity as $activity) {
            if (isset($federationactivities[$activity]) === false) {
                continue;
            }
            $federationactivity = $federationactivities[$activity];
            $activities[$federationactivity->code] = $federationactivity->name;
        }

        if (empty($adhesion->questionnairestatus) === false) {
            $label = get_string('health_constraints', 'local_apsolu');
            $activities[] = '<i class="icon fa fa-medkit" aria-hidden="true" aria-selected="true"></i>' . $label;
        }

        $row[] = html_writer::alist($activities, $attributes = [], $tag = 'ul');

        // Affiche la date d'émission du certificat médical.
        if (empty($adhesion->data->medicalcertificatedate) === false) {
            $row[] = userdate($adhesion->data->medicalcertificatedate, get_string('strftimedateshort', 'local_apsolu'));
        } else {
            $row[] = ''; // Aucune date de certificat médical.
        }

        // Affiche la colonne pour afficher les fichiers.
        if (
            $adhesion->medicalcertificatestatus === Adhesion::MEDICAL_CERTIFICATE_STATUS_EXEMPTED &&
            $adhesion->have_to_upload_parental_authorization() === false
        ) {
            // Cas où l'étudiant n'a pas de fichier à déposer.
            $row[] = ''; // Aucun fichier.
            $cell = new html_table_cell(get_string('medical_certificate_not_required', 'local_apsolu'));
            $cell->attributes = ['class' => 'medical-certificate-status table-info', 'data-userid' => $record->id];
            $row[] = $cell;
        } else {
            // Cas où l'étudiant doit déposer des fichiers.
            $fs = get_file_storage();
            [$component, $itemid] = ['local_apsolu', $record->id];
            $sort = 'itemid, filepath, filename';
            $files = [];
            foreach (['medicalcertificate', 'parentalauthorization'] as $filearea) {
                $files = array_merge($files, $fs->get_area_files(
                    $context->id,
                    $component,
                    $filearea,
                    $itemid,
                    $sort,
                    $includedirs = false
                ));
            }

            if (count($files) === 0) {
                $row[] = get_string('no_files', 'local_apsolu');
                if ($adhesion->medicalcertificatestatus === Adhesion::MEDICAL_CERTIFICATE_STATUS_PENDING) {
                    $cell = new html_table_cell(get_string('medical_certificate_not_validated', 'local_apsolu'));
                    $cell->attributes = ['class' => 'table-warning'];
                } else if ($adhesion->medicalcertificatestatus === Adhesion::MEDICAL_CERTIFICATE_STATUS_VALIDATED) {
                    // Probablement validé sans avoir été déposé sur APSOLU.
                    $cell = new html_table_cell(get_string('medical_certificate_validated', 'local_apsolu'));
                    $cell->attributes = ['class' => 'table-success'];
                } else {
                    // Ne devrait jamais arriver.
                    $cell = new html_table_cell(get_string('error'));
                    $cell->attributes = ['class' => 'table-danger'];
                }
                $row[] = $cell;
            } else {
                $items = [];
                foreach ($files as $file) {
                    $url = moodle_url::make_pluginfile_url(
                        $context->id,
                        $component,
                        $file->get_filearea(),
                        $itemid,
                        '/',
                        $file->get_filename(),
                        $forcedownload = false,
                        $includetoken = false
                    );

                    if ($file->get_filearea() === 'parentalauthorization') {
                        $areaicon = html_writer::tag('i', '', [
                            'aria-label' => get_string('parental_authorization', 'local_apsolu'),
                            'class' => 'icon fa fa-solid fa-fw fa-baby',
                            'role' => 'img',
                        ]);
                    } else {
                        $areaicon = html_writer::tag('i', '', [
                            'aria-label' => get_string('medical_certificate', 'local_apsolu'),
                            'class' => 'icon fa fa-solid fa-fw fa-kit-medical',
                            'role' => 'img',
                        ]);
                    }

                    $date = userdate($file->get_timemodified(), get_string('strftimedateshort', 'local_apsolu'));
                    $clockicon = html_writer::tag('i', '', ['class' => 'icon fa fa-clock-o fa-fw', 'role' => 'img']);
                    $clocklink = html_writer::tag('a', $clockicon, [
                        'aria-label' => get_string('help'),
                        'class' => 'btn btn-link p-0',
                        'data-bs-container' => 'body',
                        'data-bs-content' => format_string(get_string('uploaded_date', 'local_apsolu', $date)),
                        'data-bs-html' => 'false',
                        'data-bs-placement' => 'right',
                        'data-bs-toggle' => 'popover',
                        'data-bs-trigger' => 'focus',
                        'data-original-title' => '',
                        'role' => 'button',
                        'tabindex' => '0',
                        'title' => '',
                    ]);

                    $label = $areaicon . mb_strimwidth($file->get_filename(), 0, 16, '...');
                    $items[] = html_writer::link($url, $label) . ' ' . $clocklink;
                }
                $row[] = html_writer::alist($items, $attributes = ['class' => 'list-unstyled'], $tag = 'ul');

                if ($adhesion->medicalcertificatestatus === Adhesion::MEDICAL_CERTIFICATE_STATUS_PENDING) {
                    if (empty($adhesion->federationnumberrequestdate) === true) {
                        // Certificat déposé, mais pas validé par l'étudiant.
                        $cell = new html_table_cell(get_string('medical_certificate_not_validated', 'local_apsolu'));
                        $cell->attributes = ['class' => 'medical-certificate-status table-warning', 'data-userid' => $record->id];
                        $row[] = $cell;
                    } else {
                        // Certificat en attente de validation.
                        $cell = new html_table_cell(get_string('medical_certificate_awaiting_validation', 'local_apsolu'));
                        $cell->attributes = ['class' => 'medical-certificate-status table-danger', 'data-userid' => $record->id];
                        $row[] = $cell;
                    }
                } else if ($adhesion->medicalcertificatestatus === Adhesion::MEDICAL_CERTIFICATE_STATUS_VALIDATED) {
                    // Certificat validé.
                    $cell = new html_table_cell();
                    $cell->text = get_string('medical_certificate_validated', 'local_apsolu');
                    $cell->attributes = ['class' => 'medical-certificate-status table-success', 'data-userid' => $record->id];
                    $row[] = $cell;
                } else if ($adhesion->have_to_upload_parental_authorization() === true) {
                    // Lorsque l'étudiant a déposé seulement une autorisation parentale.
                    $cell = new html_table_cell('');
                    $cell->attributes = ['class' => 'medical-certificate-status', 'data-userid' => $record->id];
                    $row[] = $cell;
                }
            }
        }

        $actionmenu = $adhesion->get_action_menu($record->id, $context->id);
        $row[] = $actionmenu ? $OUTPUT->render($actionmenu) : '';

        $rows[] = $row;
    }

    $recordset->close();

    // Affiche le résultat au format HTML.
    if (empty($rows) === true) {
        $content = $OUTPUT->notification(get_string('no_results_with_these_criteria', 'local_apsolu'), 'notifyerror');
    } else {
        $actioncell = new html_table_cell();
        $actioncell->text = get_string('action');
        $actioncell->attributes = ['class' => 'filter-false sorter-false'];

        $headers = [
            html_writer::tag('abbr', get_string('request_date', 'local_apsolu'), [
                'title' => get_string('federation_number_request_date', 'local_apsolu'),
            ]),
            get_string('fullname'),
            get_string('institution'),
            get_string('activities'),
            get_string('certificate_date', 'local_apsolu'),
            get_string('file'),
            get_string('certificate_status', 'local_apsolu'),
            $actioncell,
        ];

        $table = new html_table();
        $table->id = 'local-apsolu-certificates-validation-table';
        $table->head  = $headers;
        $table->attributes['class'] = 'table table-bordered table-sortable';
        $table->caption = count($rows) . ' ' . get_string('users');
        $table->data  = $rows;
        $content = html_writer::table($table);
    }
}

$options = [];
$options['sortLocaleCompare'] = true;
$options['widgets'] = ['filter', 'resizable', 'stickyHeaders'];
$options['widgetOptions'] = ['resizable' => true, 'stickyHeaders_filteredToTop' => true, 'stickyHeaders_offset' => '50px'];
$PAGE->requires->js_call_amd('local_apsolu/sort', 'initialise', [$options]);

$PAGE->requires->js_call_amd('local_apsolu/federation_medical_certificate_validation', 'initialise');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('certificates_validation', 'local_apsolu'));
echo $OUTPUT->tabtree($tabtree, $page);

$mform->display();
echo $content;

echo $OUTPUT->footer();
