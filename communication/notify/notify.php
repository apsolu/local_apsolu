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
 * Page de notification.
 *
 * @package    local_apsolu
 * @copyright  2024 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\form\communication\notify as form;
use PhpOffice\PhpSpreadsheet\Style\Border as MoodleExcelBorder;

defined('MOODLE_INTERNAL') || die;

$templateid = optional_param('template', 0, PARAM_INT);

require_once($CFG->libdir . '/csvlib.class.php');
require_once($CFG->libdir . '/excellib.class.php');

if (empty($templateid) === false) {
    $template = $DB->get_record('apsolu_communication_templates', ['id' => $templateid, 'hidden' => 0]);
    if ($template === false) {
        unset($template);
        $templateid = 0;
    }
}

$url = new moodle_url('/local/apsolu/communication/index.php', ['page' => 'notify', 'template' => $templateid]);

$defaultdata = new stdClass();
$defaultdata->template = $templateid;

if (isset($template) === true) {
    $defaultdata->subject = $template->subject;
    $defaultdata->message = ['format' => FORMAT_HTML, 'text' => $template->body];
    $defaultdata->carboncopy = $template->carboncopy;
    $defaultdata->notify_functional_contact = $template->functionalcontact;
    $filters = json_decode($template->filters);
    if ($filters !== false) {
        foreach ($filters as $key => $value) {
            $defaultdata->{$key} = $value;
        }
    }
}

$recipients = [];
$redirecturl = null;
$mform = new form($url, [$defaultdata, $recipients, $redirecturl]);

$notification = '';
if ($data = $mform->get_data()) {
    // Calcule la liste des utilisateurs à notifier.
    $users = $mform->get_filtered_users($data);

    if (isset($data->saveastemplate, $data->notify) === true) {
        $filters = ['groupings', 'categories', 'courses', 'teachers', 'enrollists', 'calendars', 'roles', 'cohorts', 'locations'];

        $template = new stdClass();
        $template->subject = $data->subject;
        $template->body = $data->message['text'];
        $template->carboncopy = isset($data->carboncopy);
        $template->functionalcontact = isset($data->notify_functional_contact);
        $template->filters = [];
        $template->hidden = empty($data->saveastemplate);
        foreach ($filters as $filter) {
            $template->filters[$filter] = '';

            if (isset($data->{$filter}) === true) {
                $template->filters[$filter] = $data->{$filter};
            }
        }

        $template->filters = json_encode($template->filters);

        $template->id = $DB->insert_record('apsolu_communication_templates', $template);
    }

    $count = count($users);
    if ($count === 0) {
        $message = get_string('no_users_found_with_these_search_criteria', 'local_apsolu');
        $status = 'notifyproblem';

        $notification = $OUTPUT->notification($message, $status);
    } else {
        if (isset($data->exportcsv) === true || isset($data->exportexcel) === true) {
            // Gestion des exports CSV et Excel.
            $filename = get_string('exporting_users', 'local_apsolu');
            $headers = [];
            $headers[] = get_string('firstname');
            $headers[] = get_string('lastname');
            $headers[] = get_string('idnumber');
            $headers[] = get_string('email');

            $rows = [];
            foreach ($users as $user) {
                $columns = [];
                $columns[] = $user->firstname;
                $columns[] = $user->lastname;
                $columns[] = $user->idnumber;
                $columns[] = $user->email;

                $rows[] = $columns;
            }

            if (isset($data->exportcsv) === true) {
                // Définit les entêtes.
                $csvexport = new csv_export_writer();
                $csvexport->set_filename($filename);
                $csvexport->add_data($headers);

                // Définit les données.
                foreach ($rows as $row) {
                    $csvexport->add_data($row);
                }

                // Transmet le fichier au navigateur.
                $csvexport->download_file();
                exit(0);
            } else if (isset($data->exportexcel) === true) {
                // Définit les entêtes.
                $workbook = new MoodleExcelWorkbook("-");
                $workbook->send($filename);
                $myxls = $workbook->add_worksheet();
                $properties = ['border' => MoodleExcelBorder::BORDER_THIN];
                $excelformat = new MoodleExcelFormat($properties);
                foreach ($headers as $position => $value) {
                    $myxls->write_string(0, $position, $value, $excelformat);
                }

                // Définit les données.
                $line = 1;
                foreach ($rows as $row => $values) {
                    foreach ($values as $columnnumber => $value) {
                        $myxls->write_string($line, $columnnumber, $value, $excelformat);
                    }
                    $line++;
                }

                // Transmet le fichier au navigateur.
                $workbook->close();
                exit(0);
            }
        } else if (isset($data->notify) === true) {
            $userids = [];
            foreach ($users as $user) {
                // Le tableau doit être indexé avec le userid.
                $userids[$user->id] = $user->id;
            }

            $mform->local_apsolu_notify($userids, $template->id);

            $returnurl = new moodle_url('/local/apsolu/communication/index.php', ['page' => 'templates']);
            $message = get_string('notifications_have_been_sent', 'local_apsolu');
            redirect($returnurl, $message, $delay = null, \core\output\notification::NOTIFY_SUCCESS);
        } else if (isset($data->preview) === true) {
            // On régénère le formulaire pour indiquer que le bouton "preview" a été cliqué.
            $defaultdata->submitpreview = 1;
            $mform = new form($url, [$defaultdata, $recipients, $redirecturl]);
        }
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('notify', 'local_apsolu'));
echo $OUTPUT->tabtree($tabtree, $page);

echo $notification;
$mform->display();
if (isset($data->preview) === true) {
    $data = new stdClass();
    $data->rows = array_values($users);
    $data->count_rows = count($data->rows);
    echo $OUTPUT->render_from_template('local_apsolu/communication_preview', $data);
}
echo $OUTPUT->footer();
