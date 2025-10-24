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
 * Contrôleur pour gérer la partie des paiements par Pass Sport.
 *
 * @package    local_apsolu
 * @copyright  2025 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\federation\adhesion;

defined('MOODLE_INTERNAL') || die();

define('APSOLU_SELECT_ANY', '0');
define('APSOLU_SELECT_YES', '1');
define('APSOLU_SELECT_NO', '2');

require_once(__DIR__ . '/pass_sport_validation_form.php');

$mform = null;
$content = '';
if (empty(get_config('local_apsolu', 'enable_pass_sport_payment')) === true) {
    $content = $OUTPUT->notification(get_string('the_pass_sport_payment_is_not_enabled', 'local_apsolu'), 'notifyerror');
} else {
    // Définit les options d'état des certificats.
    $passsport = [
        APSOLU_SELECT_ANY => get_string('all'),
        APSOLU_SELECT_YES => get_string('pass_sport_validated', 'local_apsolu'),
        APSOLU_SELECT_NO => get_string('pass_sport_not_validated', 'local_apsolu'),
    ];

    $customdata = ['pass_sport' => $passsport];
    $mform = new local_apsolu_federation_pass_sport_validation(null, $customdata);
}

if ($mform !== null && $data = $mform->get_data()) {
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

    // Etat du paiement par Pass Sport.
    if ($data->pass_sport_status === APSOLU_SELECT_YES) {
        $parameters['status'] = Adhesion::PASS_SPORT_STATUS_VALIDATED;
        $conditions[] = "AND afa.passsportstatus = :status";
    } else if ($data->pass_sport_status === APSOLU_SELECT_NO) {
        $parameters['status'] = Adhesion::PASS_SPORT_STATUS_PENDING;
        $conditions[] = "AND afa.passsportstatus = :status";
    }

    $federationactivities = $DB->get_records('apsolu_federation_activities', [], $sort = 'name', $fields = 'code, name');

    $fullnamefields = core_user\fields::get_name_fields();
    $sql = "SELECT u.id, " . implode(', ', $fullnamefields) . ", u.idnumber, u.email, u.institution, afa.questionnairestatus,
                   afa.data, afa.passsportnumber, afa.passsportstatus, afa.federationnumber, afa.federationnumberrequestdate
              FROM {user} u
              JOIN {apsolu_federation_adhesions} afa ON u.id = afa.userid
             WHERE 1 = 1 " . implode(' ', $conditions) . "
               AND afa.federationnumber IS NULL
               AND afa.passsportnumber IS NOT NULL
          ORDER BY afa.federationnumberrequestdate DESC, u.lastname, u.firstname";

    $rows = [];
    $recordset = $DB->get_recordset_sql($sql, $parameters);
    $context = context_course::instance($courseid, MUST_EXIST);
    foreach ($recordset as $record) {
        $record->data = json_decode($record->data);
        if ($record->data === false) {
            // Les données JSON ne sont pas valides. Ce cas ne devrait jamais arriver.
            continue;
        }

        $profileurl = new moodle_url('/user/view.php', ['id' => $record->id, 'course' => $courseid]);

        $row = [];
        if (empty($record->federationnumberrequestdate) === true) {
            $row[] = get_string('never');
        } else {
            $title = userdate($record->federationnumberrequestdate, get_string('strftimedatetimeshort', 'local_apsolu'));
            $text = userdate($record->federationnumberrequestdate, get_string('strftimedatetimesortable', 'local_apsolu'));
            $row[] = '<span class="apsolu-cursor-help" title="' . s($title) . '">' . s(substr($text, 0, -3)) . '</span>';
        }
        $row[] = html_writer::link($profileurl, fullname($record));
        $row[] = $record->idnumber;
        $row[] = $record->institution;

        // Liste les activités de l'adhérant.
        $activities = [];
        foreach ($record->data->activity as $activity) {
            if (isset($federationactivities[$activity]) === false) {
                continue;
            }
            $federationactivity = $federationactivities[$activity];
            $activities[$federationactivity->code] = $federationactivity->name;
        }

        if (empty($record->questionnairestatus) === false) {
            $label = get_string('health_constraints', 'local_apsolu');
            $activities[] = '<i class="icon fa fa-medkit" aria-hidden="true" aria-selected="true"></i>' . $label;
        }

        $row[] = html_writer::alist($activities, $attributes = [], $tag = 'ul');

        $row[] = $record->passsportnumber;

        // Affiche l'état du paiement par Pass Sport.
        if ($record->passsportstatus === Adhesion::PASS_SPORT_STATUS_VALIDATED) {
            $cell = new html_table_cell(get_string('validated', 'local_apsolu'));
            $cell->attributes = ['class' => 'pass-sport-status table-success', 'data-userid' => $record->id];
        } else if ($record->passsportstatus === Adhesion::PASS_SPORT_STATUS_PENDING) {
            $cell = new html_table_cell(get_string('pending_approval', 'local_apsolu'));
            $cell->attributes = ['class' => 'pass-sport-status table-warning', 'data-userid' => $record->id];
        } else {
            $cell = new html_table_cell(get_string('not_validated', 'local_apsolu'));
            $cell->attributes = ['class' => 'pass-sport-status', 'data-userid' => $record->id];
        }
        $row[] = $cell;

        if ($record->passsportstatus !== Adhesion::PASS_SPORT_STATUS_PENDING) {
            // Aucune action disponible.
            $row[] = '';
        } else {
            // Les gestionnaires peuvent accepter ou refuser le paiement avec le Pass Sport.
            $attributes = [
                'class' => 'local-apsolu-federation-pass-sport-validation',
                'data-contextid' => $context->id,
                'data-target-validation' => Adhesion::PASS_SPORT_STATUS_VALIDATED,
                'data-target-validation-color' => 'table-success',
                'data-target-validation-text' => get_string('pass_sport_validated', 'local_apsolu'),
                'data-users' => $record->id,
            ];

            $menuoptions = [];

            // Option permettant la validation du certificat.
            $attributes['data-stringid'] = 'pass_sport_validation_message';
            $menuoptions[] = [
                'attributes' => $attributes,
                'icon' => 'i/grade_correct',
                'label' => get_string('validate', 'local_apsolu'),
            ];

            // Option permettant le refus du certificat (raison: mention du sport manquante).
            $attributes['data-stringid'] = 'pass_sport_refusal_message';
            $attributes['data-target-validation'] = Adhesion::PASS_SPORT_STATUS_PENDING;
            $attributes['data-target-validation-color'] = '';
            $attributes['data-target-validation-text'] = get_string('pass_sport_not_validated', 'local_apsolu');
            $menuoptions[] = [
                'attributes' => $attributes,
                'icon' => 'i/grade_incorrect',
                'label' => get_string('refuse', 'local_apsolu'),
            ];

            $menu = new action_menu();
            $menu->attributessecondary['class'] .= ' apsolu-dropdown-menu';
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
            get_string('pass_sport_number', 'local_apsolu'),
            get_string('pass_sport_status', 'local_apsolu'),
            $actioncell,
        ];

        $table = new html_table();
        $table->id = 'local-apsolu-pass-sport-validation-table';
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

$PAGE->requires->js_call_amd('local_apsolu/federation_pass_sport_validation', 'initialise');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pass_sport_validation', 'local_apsolu'));
echo $OUTPUT->tabtree($tabtree, $page);

if ($mform !== null) {
    $mform->display();
}
echo $content;

echo $OUTPUT->footer();
