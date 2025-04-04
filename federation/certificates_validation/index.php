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
$certificates = [
    APSOLU_SELECT_ANY => get_string('all'),
    APSOLU_SELECT_YES => get_string('medical_certificate_validated', 'local_apsolu'),
    APSOLU_SELECT_NO => get_string('medical_certificate_not_validated', 'local_apsolu'),
];

$customdata = ['certificates' => $certificates];
$mform = new local_apsolu_federation_certificates_validation(null, $customdata);

$content = '';
if ($data = $mform->get_data()) {
    $parameters = [];
    $conditions = [];

    if (empty($data->fullnameuser) === false) {
        $parameters['fullnameuser'] = '%'.$data->fullnameuser.'%';
        $conditions[] = sprintf("AND %s LIKE :fullnameuser ", $DB->sql_fullname('u.firstname', 'u.lastname'));
    }

    if (empty($data->idnumber) === false) {
        $parameters['idnumber'] = '%'.$data->idnumber.'%';
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

    $federationactivities = $DB->get_records('apsolu_federation_activities');

    $fullnamefields = core_user\fields::get_name_fields();
    $sql = "SELECT u.id, ".implode(', ', $fullnamefields).", u.idnumber, u.email, u.institution, afa.questionnairestatus,
                   afa.mainsport, afa.sport1, afa.sport2, afa.sport3, afa.sport4, afa.sport5,
                   afa.constraintsport1, afa.constraintsport2, afa.constraintsport3, afa.constraintsport4, afa.constraintsport5,
                   afa.medicalcertificatedate, afa.medicalcertificatestatus, afa.federationnumber, afa.federationnumberrequestdate
              FROM {user} u
              JOIN {apsolu_federation_adhesions} afa ON u.id = afa.userid
             WHERE 1 = 1 ".implode(' ', $conditions)."
          ORDER BY afa.federationnumberrequestdate DESC, u.lastname, u.firstname";

    $rows = [];
    $recordset = $DB->get_recordset_sql($sql, $parameters);
    $context = context_course::instance($courseid, MUST_EXIST);
    foreach ($recordset as $record) {
        $profileurl = new moodle_url('/user/view.php', ['id' => $record->id, 'course' => $courseid]);

        $row = [];
        if (empty($record->federationnumberrequestdate) === true) {
            $row[] = get_string('never');
        } else {
            $title = userdate($record->federationnumberrequestdate, get_string('strftimedatetimeshort', 'local_apsolu'));
            $text = userdate($record->federationnumberrequestdate, get_string('strftimedatetimesortable', 'local_apsolu'));
            $row[] = '<span class="apsolu-cursor-help" title="'.s($title).'">'.s(substr($text, 0, -3)).'</span>';
        }
        $row[] = html_writer::link($profileurl, fullname($record));
        $row[] = $record->idnumber;
        $row[] = $record->institution;

        // Liste les activités de l'adhérant.
        $activities = [];
        foreach (Adhesion::get_activity_fields() as $field) {
            if ($record->{$field} === Adhesion::SPORT_NONE) {
                continue;
            }

            if (isset($federationactivities[$record->{$field}]) === false) {
                continue;
            }

            $activities[$record->{$field}] = $federationactivities[$record->{$field}]->name;
        }

        if (empty($record->questionnairestatus) === false) {
            $label = get_string('health_constraints', 'local_apsolu');
            $activities[] = '<i class="icon fa fa-medkit" aria-hidden="true" aria-selected="true"></i>'.$label;
        }

        $row[] = html_writer::alist($activities, $attributes = [], $tag = 'ul');

        // Affiche la date d'émission du certificat médical.
        if (empty($record->medicalcertificatedate) === false) {
            $row[] = userdate($record->medicalcertificatedate, get_string('strftimedateshort', 'local_apsolu'));
        } else {
            $row[] = ''; // Aucune date de certificat médical.
        }

        if ($record->medicalcertificatestatus === Adhesion::MEDICAL_CERTIFICATE_STATUS_EXEMPTED) {
            $row[] = ''; // Aucun fichier.
            $cell = new html_table_cell(get_string('medical_certificate_not_required', 'local_apsolu'));
            $cell->attributes = ['class' => 'medical-certificate-status table-info', 'data-userid' => $record->id];
            $row[] = $cell;

            if (empty($record->federationnumberrequestdate) === false) {
                // L'étudiant a validé sa demande.
                // Les gestionnaires peuvent annuler sa demande, afin que l'étudiant puisse la modifier.
                $menulink = new action_menu_link_secondary(
                    new moodle_url(''),
                    new pix_icon('i/grade_incorrect', '', null, ['class' => 'smallicon']),
                    get_string('refuse', 'local_apsolu'),
                    [
                        'class' => 'local-apsolu-federation-medical-certificate-validation',
                        'data-contextid' => $context->id,
                        'data-target-validation' => Adhesion::MEDICAL_CERTIFICATE_STATUS_EXEMPTED,
                        'data-target-validation-color' => 'table-info',
                        'data-target-validation-text' => get_string('medical_certificate_not_required', 'local_apsolu'),
                        'data-stringid' => 'medical_certificate_refusal_message',
                        'data-users' => $record->id,
                    ],
                );
                $menu = new action_menu();
                $menu->set_menu_trigger(get_string('edit'));
                $menu->add($menulink);

                $row[] = $OUTPUT->render($menu); // Action de refus.
            } else {
                // L'étudiant n'a pas encore validé sa demande. Il est inutile de permettre aux gestionnaires de refuser sa demande.
                $row[] = ''; // Aucune action.
            }
        } else {
            // Récupère les fichiers déposés.
            $fs = get_file_storage();
            list($component, $itemid) = ['local_apsolu', $record->id];
            $sort = 'itemid, filepath, filename';
            $files = [];
            foreach (['medicalcertificate', 'parentalauthorization'] as $filearea) {
                $files = array_merge($files, $fs->get_area_files($context->id, $component, $filearea,
                    $itemid, $sort, $includedirs = false));
            }

            if (count($files) === 0) {
                $row[] = get_string('no_files', 'local_apsolu');
                if ($record->medicalcertificatestatus === Adhesion::MEDICAL_CERTIFICATE_STATUS_PENDING) {
                    $cell = new html_table_cell(get_string('medical_certificate_not_validated', 'local_apsolu'));
                    $cell->attributes = ['class' => 'table-warning'];
                } else if ($record->medicalcertificatestatus === Adhesion::MEDICAL_CERTIFICATE_STATUS_VALIDATED) {
                    // Probablement validé sans avoir été déposé sur APSOLU.
                    $cell = new html_table_cell(get_string('medical_certificate_validated', 'local_apsolu'));
                    $cell->attributes = ['class' => 'table-success'];
                } else {
                    // Ne devrait jamais arriver.
                    $cell = new html_table_cell(get_string('error'));
                    $cell->attributes = ['class' => 'table-danger'];
                }
                $row[] = $cell;
                $row[] = ''; // Aucune action.
            } else {
                $items = [];
                foreach ($files as $file) {
                    $url = moodle_url::make_pluginfile_url($context->id, $component, $file->get_filearea(), $itemid, '/',
                        $file->get_filename(), $forcedownload = false, $includetoken = false);
                    $helpstr = get_string('help');
                    $date = userdate($file->get_timemodified(), get_string('strftimedateshort', 'local_apsolu'));
                    $datacontent = format_string(get_string('uploaded_date', 'local_apsolu', $date));
                    $link = '<a class="btn btn-link p-0" role="button" data-container="body" data-toggle="popover"
                        data-placement="right" data-content="'.$datacontent.'" data-html="false" tabindex="0" data-trigger="focus"
                        aria-label="'.$helpstr.'" data-original-title="" title=""><i class="icon fa fa-clock-o fa-fw"
                        role="img"></i></a>';
                    $items[] = html_writer::link($url, mb_strimwidth($file->get_filename(), 0, 16, '...')).' '.$link;
                }
                $row[] = html_writer::alist($items, $attributes = [], $tag = 'ul');

                $attributes = [
                    'class' => 'local-apsolu-federation-medical-certificate-validation',
                    'data-contextid' => $context->id,
                    'data-target-validation' => Adhesion::MEDICAL_CERTIFICATE_STATUS_VALIDATED,
                    'data-target-validation-color' => 'table-success',
                    'data-target-validation-text' => get_string('medical_certificate_validated', 'local_apsolu'),
                    'data-users' => $record->id,
                ];

                $menuoptions = [];

                // Option permettant la validation du certificat.
                $attributes['data-stringid'] = 'medical_certificate_validation_message';
                $menuoptions[] = [
                    'attributes' => $attributes,
                    'icon' => 'i/grade_correct',
                    'label' => get_string('validate', 'local_apsolu'),
                ];

                // Option permettant le refus du certificat (raison: plus d'un an).
                $attributes['data-stringid'] = 'medical_certificate_refusal_message_for_one_year_expiration';
                $attributes['data-target-validation'] = Adhesion::MEDICAL_CERTIFICATE_STATUS_PENDING;
                $attributes['data-target-validation-color'] = 'table-warning';
                $attributes['data-target-validation-text'] = get_string('medical_certificate_not_validated', 'local_apsolu');
                $reason = strtolower(get_string('more_than_one_year', 'local_apsolu'));
                $menuoptions[] = [
                    'attributes' => $attributes,
                    'icon' => 'i/grade_incorrect',
                    'label' => get_string('refuse_with_reasons_X', 'local_apsolu', $reason),
                ];

                // Option permettant le refus du certificat (raison: plus de 6 mois).
                $attributes['data-stringid'] = 'medical_certificate_refusal_message_for_six_months_expiration';
                $reason = strtolower(get_string('more_than_six_months', 'local_apsolu'));
                $menuoptions[] = [
                    'attributes' => $attributes,
                    'icon' => 'i/grade_incorrect',
                    'label' => get_string('refuse_with_reasons_X', 'local_apsolu', $reason),
                ];

                // Option permettant le refus du certificat (raison: mention du sport manquante).
                $attributes['data-stringid'] = 'medical_certificate_refusal_message_for_mention_missing';
                $reason = strtolower(get_string('mention_missing', 'local_apsolu'));
                $menuoptions[] = [
                    'attributes' => $attributes,
                    'icon' => 'i/grade_incorrect',
                    'label' => get_string('refuse_with_reasons_X', 'local_apsolu', $reason),
                ];

                if ($record->medicalcertificatestatus === Adhesion::MEDICAL_CERTIFICATE_STATUS_PENDING) {
                    if (empty($record->federationnumberrequestdate) === true) {
                        // Certificat déposé, mais pas validé par l'étudiant.
                        $cell = new html_table_cell(get_string('medical_certificate_not_validated', 'local_apsolu'));
                        $cell->attributes = ['class' => 'medical-certificate-status table-warning', 'data-userid' => $record->id];
                        $row[] = $cell;
                    } else {
                        // Certificat en attente de validation.
                        $cell = new html_table_cell(get_string('medical_certificate_awaiting_validation', 'local_apsolu'));
                        $cell->attributes = ['class' => 'medical-certificate-status table-danger', 'data-userid' => $record->id];
                        $row[] = $cell;

                        $menu = new action_menu();
                        $menu->set_menu_trigger(get_string('edit'));

                        foreach ($menuoptions as $value) {
                            $menulink = new action_menu_link_secondary(
                                new moodle_url(''),
                                new pix_icon($value['icon'], '', null, ['class' => 'smallicon']),
                                $value['label'],
                                $value['attributes'],
                            );
                            $menu->add($menulink);
                        }

                        $row[] = $OUTPUT->render($menu);
                    }
                } else if ($record->medicalcertificatestatus === Adhesion::MEDICAL_CERTIFICATE_STATUS_VALIDATED) {
                    // Certificat validé.
                    $cell = new html_table_cell();
                    $cell->text = get_string('medical_certificate_validated', 'local_apsolu');
                    $cell->attributes = ['class' => 'medical-certificate-status table-success', 'data-userid' => $record->id];
                    $row[] = $cell;

                    if (empty($record->federationnumber) === true) {
                        $menu = new action_menu();
                        $menu->set_menu_trigger(get_string('edit'));

                        $menuoptions[0]['attributes']['class'] .= ' d-none'; // Supprime l'option de validation.
                        foreach ($menuoptions as $value) {
                            $menulink = new action_menu_link_secondary(
                                new moodle_url(''),
                                new pix_icon($value['icon'], '', null, ['class' => 'smallicon']),
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
        $actioncell = new html_table_cell();
        $actioncell->text = get_string('action');
        $actioncell->attributes = ['class' => 'filter-false sorter-false'];

        $headers = [
            get_string('federation_number_request_date', 'local_apsolu'),
            get_string('fullname'),
            get_string('idnumber'),
            get_string('institution'),
            get_string('activities'),
            get_string('medical_certificate_date', 'local_apsolu'),
            get_string('file'),
            get_string('medical_certificate_status', 'local_apsolu'),
            $actioncell,
        ];

        $table = new html_table();
        $table->id = 'local-apsolu-certificates-validation-table';
        $table->head  = $headers;
        $table->attributes['class'] = 'table table-bordered table-sortable';
        $table->caption = count($rows).' '.get_string('users');
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
