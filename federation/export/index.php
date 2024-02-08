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

require_once($CFG->libdir.'/csvlib.class.php');
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

// Récupère la liste des activités FFSU.
$activities = [0 => get_string('all')];
foreach (FederationActivity::get_records(null, $sort = 'name') as $record) {
    $activities[$record->id] = $record->name;
}

$constraintactivities = FederationActivity::get_records(['restriction' => 1], $sort = 'name');

$customdata = ['numbers' => $numbers, 'payments' => $payments, 'certificates' => $certificates, 'licenses' => $licenses, 'statuses' => $statuses, 'activities' => $activities];
$mform = new local_apsolu_federation_export_licenses(null, $customdata);

$content = '';
if ($data = $mform->get_data()) {
    // Génère les entêtes d'exportation.
    $headers = FederationAdhesion::get_exportation_headers();

    if (isset($data->exportbutton) === false) {
        // En affichage web, on préfixe le tableau de la date de dernière modification.
        array_unshift($headers, get_string('last_modification', 'local_apsolu'));
    }

    // Récupère la liste des cartes de paiement nécessaires pour la FFSU.
    if (empty($data->payment) === false) {
        $payments = Payment::get_users_cards_status_per_course($courseid);
    }

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

    if (empty($data->activity) === false) {
        $parameters['mainsport'] = $activities[$data->activity];
        $conditions[] = " AND act.name = :mainsport";
    }

    $sql = "SELECT u.id AS userid, u.lastname, u.firstname, u.email, act.name AS mainsportname, adh.*".
        " FROM {apsolu_federation_adhesions} adh".
        " JOIN {apsolu_federation_activities} act ON act.id = adh.mainsport".
        " JOIN {user} u ON u.id = adh.userid".
        " JOIN {user_enrolments} ue ON u.id = ue.userid".
        " JOIN {enrol} e ON e.id = ue.enrolid AND e.enrol = 'select'".
        " WHERE e.courseid = :courseid".
        implode(' ', $conditions).
        " ORDER BY adh.timemodified DESC, u.lastname, u.firstname";

    $rows = [];
    $recordset = $DB->get_recordset_sql($sql, $parameters);
    foreach ($recordset as $record) {
        // Convertit les dates.
        $record->birthdayformat = userdate($record->birthday, '%F');

        if (empty($record->medicalcertificatedate) === true) {
            $record->medicalcertificatedateformat = '';
        } else {
            $record->medicalcertificatedateformat = userdate($record->medicalcertificatedate, '%F');
        }

        // Numéro AS
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

        // État du paiement de licence.
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

        // État du certificat médical.
        if (empty($data->medical) === false) {
            if ($data->medical === APSOLU_SELECT_YES && empty($record->medicalcertificatestatus) === true) {
                continue;
            } else if ($data->medical === APSOLU_SELECT_NO && empty($record->medicalcertificatestatus) === false) {
                continue;
            }
        }

        // État du numéro de licence.
        if (empty($data->license) === false) {
            if ($data->license === APSOLU_SELECT_YES && empty($record->federationnumber) === true) {
                continue;
            } else if ($data->license === APSOLU_SELECT_NO && empty($record->federationnumber) === false) {
                continue;
            }
        }

        // État de l'inscription.
        if (empty($data->status) === false) {
            // Rappel: APSOLU_SELECT_YES = en attende d'attribution d'un numéro, APSOLU_SELECT_NO = inscription en cours.
            if ($data->status === APSOLU_SELECT_YES && empty($record->federationnumberrequestdate) === true) {
                continue;
            } else if ($data->status === APSOLU_SELECT_NO && empty($record->federationnumberrequestdate) === false) {
                continue;
            }
        }

        // Remplit toutes les lignes.
        $row = [];

        if (isset($data->exportbutton) === false) {
            $title = userdate($record->timemodified, get_string('strftimedatetimeshort', 'local_apsolu'));
            $text = userdate($record->timemodified, get_string('strftimedatetimesortable', 'local_apsolu'));
            $row[] = '<span class="apsolu-cursor-help" title="'.s($title).'">'.s($text).'</span>';
        }

        foreach (FederationAdhesion::get_exportation_fields() as $field) {
            if (isset($data->exportbutton) === false) {
                // En affichage web, on améliore le rendu des champs.
                switch ($field) {
                    case 'firstname':
                    case 'lastname':
                        $profileurl = new moodle_url('/user/view.php', ['id' => $record->userid, 'course' => $courseid]);
                        $record->{$field} = html_writer::link($profileurl, $record->{$field});
                        break;
                    case 'federationnumberprefix':
                        if (empty($record->federationnumber) === false) {
                            $record->{$field} = $record->federationnumber;
                        }
                        break;
                    case 'disciplineid':
                        $record->{$field} = $disciplines[$record->disciplineid];
                        break;
                    case 'sport1':
                    case 'sport2':
                    case 'sport3':
                    case 'sport4':
                    case 'sport5':
                    case 'constraintsport1':
                    case 'constraintsport2':
                    case 'constraintsport3':
                    case 'constraintsport4':
                    case 'constraintsport5':
                        if (isset($activities[$record->{$field}]) === true) {
                            $record->{$field} = $activities[$record->{$field}];
                        } else {
                            $record->{$field} = get_string('none');
                        }
                        break;
                }
            }

            switch ($field) {
                case 'questionnairestatusno':
                    $row[] = intval(empty($record->questionnairestatus));
                    break;
                case 'questionnairestatusyes':
                    $row[] = intval(empty($record->questionnairestatus) === false);
                    break;
                case 'medicalcertificatestatus':
                    $row[] = intval($record->{$field} === "1");
                    break;
                default:
                    $row[] = $record->{$field};
            }
        }

        $rows[] = $row;
    }
    $recordset->close();

    if (empty($rows) === true) {
        $content = $OUTPUT->notification(get_string('no_results_with_these_criteria', 'local_apsolu'), 'notifyerror');
    } else {
        if (isset($data->exportbutton) === true) {
            // Exporte au format CSV.
            $filename = 'exportation_ffsu_'.strftime('%FT%T');

            $export = new csv_export_writer($delimiter = 'semicolon');
            $export->set_filename($filename);

            // Réencode les données du fichier en 'iso-8859-1'.
            $row = [];
            foreach ($headers as $value) {
                $row[] = core_text::convert($value, 'utf-8', 'iso-8859-1');
            }
            $export->add_data($row);

            foreach ($rows as $values) {
                $row = [];
                foreach ($values as $value) {
                    $row[] = core_text::convert($value, 'utf-8', 'iso-8859-1');
                }
                $export->add_data($row);
            }

            $export->download_file();
            exit();
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
        $content = html_writer::tag('div', html_writer::table($table), ['class' => 'tablesorter-wrapper']);
    }
}

$options = [];
$options['sortLocaleCompare'] = true;
$options['widgets'] = ['filter', 'stickyHeaders'];
$options['widgetOptions'] = ['stickyHeaders_attachTo' => '.tablesorter-wrapper'];
$PAGE->requires->js_call_amd('local_apsolu/sort', 'initialise', [$options]);

echo $OUTPUT->header();
echo $OUTPUT->heading_with_help(get_string('exporting_license', 'local_apsolu'), 'exporting_license', 'local_apsolu');
echo $OUTPUT->tabtree($tabtree, $page);

$mform->display();
echo $content;

echo $OUTPUT->footer();
