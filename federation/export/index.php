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
 * Script d'exportation des licences FFSU.
 *
 * @package    local_apsolu
 * @copyright  2022 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\course AS Course;
use local_apsolu\core\federation\activity AS FederationActivity;
use local_apsolu\core\federation\adhesion AS FederationAdhesion;
use local_apsolu\core\federation\number AS FederationNumber;
use UniversiteRennes2\Apsolu\Payment;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/excellib.class.php');
require_once(__DIR__.'/export_form.php');
require_once($CFG->dirroot.'/local/apsolu/classes/apsolu/payment.php');

define('APSOLU_SELECT_ANY', '0');
define('APSOLU_SELECT_YES', '1');
define('APSOLU_SELECT_NO', '2');

$returnurl = new moodle_url('/local/apsolu/federation/index.php', ['page' => 'export']);

// Récupère la liste des numéros d'association.
$numbers = [];
foreach (FederationNumber::get_records(null, $sort = 'number') as $record) {
    $numbers[$record->id] = $record->number;
}

$payments = [
    APSOLU_SELECT_ANY => get_string('all'),
    APSOLU_SELECT_YES => get_string('license_paid', 'local_apsolu'),
    APSOLU_SELECT_NO => get_string('license_not_paid', 'local_apsolu'),
];

$certificates = [
    APSOLU_SELECT_ANY => get_string('all'),
    APSOLU_SELECT_YES => get_string('medical_certificate_validated', 'local_apsolu'),
    APSOLU_SELECT_NO => get_string('medical_certificate_not_validated', 'local_apsolu'),
];

$licenses = [
    APSOLU_SELECT_ANY => get_string('all'),
    APSOLU_SELECT_YES => get_string('license_number_assigned', 'local_apsolu'),
    APSOLU_SELECT_NO => get_string('license_number_not_assigned', 'local_apsolu'),
];

$statuses = [
    APSOLU_SELECT_ANY => get_string('all'),
    APSOLU_SELECT_NO => get_string('not_validated_by_the_student', 'local_apsolu'),
    APSOLU_SELECT_YES => get_string('validated_by_the_student', 'local_apsolu'),
];

$disciplines = FederationAdhesion::get_disciplines();
$licensetypes = FederationAdhesion::get_license_types();

// Récupère la liste des activités FFSU.
$activities = [0 => get_string('all')];
foreach (FederationActivity::get_records(null, $sort = 'name') as $record) {
    $activities[$record->code] = $record->name;
}

$constraintactivities = FederationActivity::get_records(['restriction' => 1], $sort = 'name');

$customdata = ['numbers' => $numbers, 'payments' => $payments, 'certificates' => $certificates,
    'licenses' => $licenses, 'statuses' => $statuses, 'licensetypes' => $licensetypes, 'activities' => $activities];
$mform = new local_apsolu_federation_export_licenses(null, $customdata);

$content = '';
if ($data = $mform->get_data()) {
    // Génère les entêtes et les champs d'exportation.
    $headers = [];
    $fields = [];
    foreach (FederationAdhesion::get_exportation_fields() as $field => $label) {
        $headers[] = $label;
        $fields[] = $field;
    }

    if (isset($data->exportbutton) === false) {
        // En affichage web, on préfixe le tableau de la date de dernière modification.
        array_unshift($headers, get_string('last_modification', 'local_apsolu'));
    }

    // Récupère la liste des cartes de paiement nécessaires pour la FFSU.
    if (empty($data->payment) === false) {
        $payments = Payment::get_users_cards_status_per_course($courseid);
    }

    // Récupère la liste des licences soumises au contrôle de l'honorabilité.
    $licenseswithhonorability = FederationAdhesion::get_licenses_with_honorability();

    // Récupère la liste des utilisateurs en fonction des critères.
    $parameters = [];
    $parameters['courseid'] = $courseid;

    $conditions = [];
    if (empty($data->fullnameuser) === false) {
        $parameters['fullnameuser'] = '%'.$data->fullnameuser.'%';
        $conditions[] = sprintf(" AND %s LIKE :fullnameuser ", $DB->sql_fullname('u.firstname', 'u.lastname'));
    }

    if (empty($data->idnumber) === false) {
        $parameters['idnumber'] = '%'.$data->idnumber.'%';
        $conditions[] = " AND u.idnumber LIKE :idnumber ";
    }

    $sql = "SELECT u.id AS userid, u.lastname, u.firstname, u.email, adh.*
              FROM {apsolu_federation_adhesions} adh
              JOIN {user} u ON u.id = adh.userid
              JOIN {user_enrolments} ue ON u.id = ue.userid
              JOIN {enrol} e ON e.id = ue.enrolid AND e.enrol = 'select'
             WHERE e.courseid = :courseid".implode(' ', $conditions)."
          ORDER BY adh.timemodified DESC, u.lastname, u.firstname";

    $rows = [];
    $recordset = $DB->get_recordset_sql($sql, $parameters);
    foreach ($recordset as $record) {
        // Numéro AS.
        if (empty($data->numbers) === false) {
            $found = false;
            foreach ($data->numbers as $number) {
                if ($record->federationnumberprefix !== $numbers[$number]) {
                    // Le numéro ne correspond pas au numéro recherché.
                    continue;
                }

                $found = true;
                break;
            }

            if ($found === false) {
                // Le numéro ne correspond pas au numéro recherché.
                continue;
            }
        }

        // Etat du paiement de licence.
        if (empty($data->payment) === false) {
            if (isset($payments[$record->userid]) === false) {
                $payments[$record->userid] = [];
            }

            if ($data->payment === APSOLU_SELECT_YES) {
                // On ne récupère que les licences payées.
                $skip = false;
                foreach ($payments[$record->userid] as $payment) {
                    if ($payment->status === Payment::DUE) {
                        $skip = true;
                        break;
                    }
                }
            } else if ($data->payment === APSOLU_SELECT_NO) {
                // On ne récupère que les licences non payées.
                $skip = true;
                foreach ($payments[$record->userid] as $payment) {
                    if ($payment->status === Payment::DUE) {
                        $skip = false;
                        break;
                    }
                }
            }

            if ($skip === true) {
                continue;
            }
        }

        // Etat du certificat médical.
        if (empty($data->medical) === false) {
            if ($data->medical === APSOLU_SELECT_YES && empty($record->medicalcertificatestatus) === true) {
                continue;
            } else if ($data->medical === APSOLU_SELECT_NO && empty($record->medicalcertificatestatus) === false) {
                continue;
            }
        }

        // Etat du numéro de licence.
        if (empty($data->licensenumber) === false) {
            if ($data->licensenumber === APSOLU_SELECT_YES && empty($record->federationnumber) === true) {
                continue;
            } else if ($data->licensenumber === APSOLU_SELECT_NO && empty($record->federationnumber) === false) {
                continue;
            }
        }

        // Etat de l'inscription.
        if (empty($data->status) === false) {
            // Rappel: APSOLU_SELECT_YES = en attende d'attribution d'un numéro, APSOLU_SELECT_NO = inscription en cours.
            if ($data->status === APSOLU_SELECT_YES && empty($record->federationnumberrequestdate) === true) {
                continue;
            } else if ($data->status === APSOLU_SELECT_NO && empty($record->federationnumberrequestdate) === false) {
                continue;
            }
        }

        // Remplit toutes les lignes.
        $json = json_decode($record->data);
        if (empty($json) === true) {
            $json = new stdClass();
        }

        // TODO: correction temporaire pour traiter les données erronées présentes avant le commit 6343d21.
        unset($json->birthday);

        if (isset($json->licensetype) === false || is_array($json->licensetype) === false) {
            $json->licensetype = [];
        }

        // Filtre par activité.
        if (empty($data->activity) === false) {
            if (isset($json->activity) === false || in_array($data->activity, $json->activity, $strict = true) === false) {
                continue;
            }
        }

        foreach ($json->licensetype as $licensetype) {
            if ($data->licensetype !== $licensetype) {
                continue;
            }

            $row = [];

            if (isset($data->exportbutton) === false) {
                $title = userdate($record->timemodified, get_string('strftimedatetimeshort', 'local_apsolu'));
                $text = userdate($record->timemodified, get_string('strftimedatetimesortable', 'local_apsolu'));
                $row[] = '<span class="apsolu-cursor-help" title="'.s($title).'">'.s($text).'</span>';
            }

            foreach ($fields as $field) {
                if (isset($data->exportbutton) === false) {
                    // En affichage web, on améliore le rendu des champs.
                    switch ($field) {
                        case 'firstname':
                        case 'lastname':
                            $profileurl = new moodle_url('/user/view.php', ['id' => $record->userid, 'course' => $courseid]);
                            $record->{$field} = html_writer::link($profileurl, $record->{$field});
                            break;
                    }
                }

                if (isset($json->{$field}) === true) {
                    $record->{$field} = $json->{$field};
                }

                if (isset($record->{$field}) === false) {
                    $record->{$field} = '';
                }

                switch ($field) {
                    case 'birthday':
                    case 'medicalcertificatedate':
                        try {
                            if (empty($record->{$field}) === true) {
                                throw new Exception('empty date');
                            }
                            $row[] = core_date::strftime('%d/%m/%Y', $record->{$field});
                        } catch (Exception $exception) {
                            $row[] = '';
                        }
                        break;
                    case 'handicap':
                    case 'licenseetype':
                    case 'commercialoffers':
                    case 'usepersonalimage':
                    case 'policyagreed':
                    case 'newsletter':
                    case 'federaltexts':
                    case 'insurance':
                        if (empty($record->{$field}) === true) {
                            $row[] = get_string('no');
                        } else {
                            $row[] = get_string('yes');
                        }
                        break;
                    case 'questionnairestatus':
                        if (empty($record->{$field}) === true) {
                            $row[] = get_string('yes');
                        } else {
                            $row[] = get_string('no');
                        }
                        break;
                    case 'licensetype':
                        $row[] = $licensetype;
                        break;
                    case 'honorability':
                        $honorability = [];
                        foreach ($record->licensetype as $type) {
                            if (isset($licenseswithhonorability[$type]) === false) {
                                continue;
                            }
                            $honorability[] = $type;
                        }
                        $row[] = implode(',', $honorability);
                        break;
                    case 'medicalcertifiatevalidated':
                        if (empty($record->medicalcertificatedate) === false) {
                            $row[] = get_string('yes');
                        } else {
                            $row[] = '';
                        }
                        break;
                    case 'schoolcertificatevalidated':
                        if (empty($record->licenseetype) === true) {
                            $row[] = get_string('no');
                        } else {
                            $row[] = get_string('yes');
                        }
                        break;
                    case 'activity':
                        $row[] = implode(',', $record->{$field});
                        break;
                    default:
                        $row[] = $record->{$field};
                }
            }

            $rows[] = $row;
        }
    }
    $recordset->close();

    if (empty($rows) === true) {
        $content = $OUTPUT->notification(get_string('no_results_with_these_criteria', 'local_apsolu'), 'notifyerror');
    } else {
        if (isset($data->exportbutton) === true) {
            // Export au format excel.
            $filename = 'exportation_ffsu_'.core_date::strftime('%F_%T');

            $workbook = new MoodleExcelWorkbook("-");
            $workbook->send($filename);
            $myxls = $workbook->add_worksheet();

            $properties = ['border' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN];
            $excelformat = new MoodleExcelFormat($properties);

            // Set headers.
            foreach ($headers as $position => $value) {
                $myxls->write_string(0, $position, $value, $excelformat);
            }

            // Set data.
            $line = 1;
            foreach ($rows as $values) {
                foreach ($values as $position => $value) {
                    $myxls->write_string($line, $position, $value, $excelformat);
                }

                $line++;
            }

            // MDL-83543: positionne un cookie pour qu'un script js déverrouille le bouton submit après le téléchargement.
            setcookie('moodledownload_' . sesskey(), time());

            // Transmet le fichier au navigateur.
            $workbook->close();
            exit(0);
        }

        // Affiche le résultat au format HTML.
        $table = new html_table();
        $table->head = [];
        foreach ($headers as $value) {
            if (mb_strlen($value) <= 16) {
                $table->head[] = $value;
                continue;
            }

            // Tronque les chaines de plus de 16 caractères.
            $table->head[] = '<span title="'.s($value).'">'.mb_strimwidth($value, 0, 16, "...").'</span>';
        }
        $table->attributes['class'] = 'table table-sortable';
        $table->caption = count($rows).' '.get_string('users');
        $table->data = $rows;
        $table->responsive = false;
        $content = html_writer::div(html_writer::table($table), 'table-responsive');
    }
}

$options = [];
$options['sortLocaleCompare'] = true;
$options['widgets'] = ['filter', 'stickyHeaders'];
$options['widgetOptions'] = ['stickyHeaders_filteredToTop' => true, 'stickyHeaders_offset' => '50px'];
$PAGE->requires->js_call_amd('local_apsolu/sort', 'initialise', [$options]);

echo $OUTPUT->header();
echo $OUTPUT->heading_with_help(get_string('exporting_license', 'local_apsolu'), 'exporting_license', 'local_apsolu');
echo $OUTPUT->tabtree($tabtree, $page);

$mform->display();
echo $content;

echo $OUTPUT->footer();
