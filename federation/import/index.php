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
 * Script d'importation des licences FFSU.
 *
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\federation\adhesion as Adhesion;
use local_apsolu\event\federation_number_created;
use local_apsolu\event\federation_number_updated;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/csvlib.class.php');

require_once(__DIR__.'/import_form.php');

$context = context_course::instance($courseid, MUST_EXIST);
$returnurl = new moodle_url('/local/apsolu/federation/index.php', ['page' => 'import']);

$mform = new local_apsolu_federation_import_licences();

if ($formdata = $mform->get_data()) {
    $iid = csv_import_reader::get_new_iid('local_apsolu_federation_import');
    $cir = new csv_import_reader($iid, 'local_apsolu_federation_import');

    $content = $mform->get_file_content('userfile');

    $readcount = $cir->load_csv_content($content, $formdata->encoding, $formdata->delimiter_name);
    $csvloaderror = $cir->get_error();
    unset($content);

    if ($csvloaderror !== null) {
        throw new moodle_exception('csvloaderror', '', $returnurl, $csvloaderror);
    }

    if (isset($formdata->importbutton) === true) {
        $sql = "SELECT u.email, u.firstname, u.lastname, adh.*".
            " FROM {user} u".
            " JOIN {apsolu_federation_adhesions} adh ON u.id = adh.userid";
        $users = $DB->get_records_sql($sql);

        $emailcolumnindex = $formdata->emailcolumn;
        $federationnumbercolumnindex = $formdata->federationnumbercolumn;

        if ($emailcolumnindex === $federationnumbercolumnindex) {
            $params = new stdClass();
            $params->field1 = get_string('federation_number', 'local_apsolu');
            $params->field2 = get_string('email');
            $errornotification = get_string('the_field_X_cannot_have_the_same_value_as_the_field_Y', 'local_apsolu', $params);

            // Hack pour n'enregistrer aucun donnée. Ajouter une méthode validation() au formulaire ne fonctionne pas.
            unset($formdata->importbutton);
            $formdata->previewbutton = true;
        }

        $result = [];
    }

    // Init csv import helper.
    $cir->init();

    $columns = $cir->get_columns();

    $i = 0;
    while ($line = $cir->next()) {
        if (isset($formdata->previewbutton) === true) {
            // Prévisualisation.
            $data[] = $line;

            $i++;

            if (isset($formdata->import) === false && $i >= $formdata->previewrows) {
                break;
            }
        } else if (isset($formdata->importbutton) === true) {
            // Import.
            $email = trim($line[$emailcolumnindex]);
            if (isset($users[$email]) === false) {
                // Utilisateur non trouvé.
                $result[] = get_string('the_user_with_email_X_was_not_found', 'local_apsolu', $email);
                continue;
            }

            $adhesion = $users[$email];
            $profileurl = new moodle_url('/user/profile.php', ['id' => $adhesion->userid]);

            $licenseid = trim($line[$federationnumbercolumnindex]);
            if (empty($licenseid) === true) {
                // Numéro de license vide.
                continue;
            }

            if (ctype_alnum($licenseid) === false) {
                // Numéro de licence invalide.
                $params = new stdClass();
                $params->licenseid = $licenseid;
                $params->profile = html_writer::link($profileurl, $adhesion->firstname.' '.$adhesion->lastname);
                $result[] = get_string('the_license_number_X_associated_to_Y_is_invalid', 'local_apsolu', $params);
                continue;
            }

            if ($adhesion->federationnumber === $licenseid) {
                continue;
            }

            $oldlicenseid = $adhesion->federationnumber;

            $sql = "UPDATE {apsolu_federation_adhesions}
                       SET federationnumber = :federationnumber
                     WHERE id = :id
                       AND userid = :userid";
            $DB->execute($sql, ['federationnumber' => $licenseid, 'id' => $adhesion->id, 'userid' => $adhesion->userid]);

            $params = new stdClass();
            $params->licenseid = $licenseid;
            $params->profile = html_writer::link($profileurl, $adhesion->firstname.' '.$adhesion->lastname);

            if (empty($oldlicenseid) === true) {
                // Création d'un numéro AS.
                $result[] = get_string('federation_insert_license', 'local_apsolu', $params);

                // Enregistre un événement dans les logs.
                $event = federation_number_created::create([
                    'objectid' => $adhesion->id,
                    'context' => $context,
                    'relateduserid' => $adhesion->userid,
                    'other' => ['federationnumber' => $licenseid],
                    ]);
                $event->trigger();
            } else {
                // Mise à jour du numéro AS.
                $params->oldlicenseid = $oldlicenseid;
                $result[] = get_string('federation_update_license', 'local_apsolu', $params);

                // Enregistre un événement dans les logs.
                $event = federation_number_updated::create([
                    'objectid' => $adhesion->id,
                    'context' => $context,
                    'relateduserid' => $adhesion->userid,
                    'other' => ['federationnumber' => $licenseid, 'oldfederationnumber' => $oldlicenseid],
                    ]);
                $event->trigger();
            }
        } else {
            // Quitte la boucle si le boutton aperçu ou importer n'a pas été pressé.
            break;
        }
    }

    $cir->close();

    if (isset($formdata->importbutton) === true) {
        $cir->cleanup(true);
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading_with_help(get_string('importing_license', 'local_apsolu'), 'importing_license', 'local_apsolu');
echo $OUTPUT->tabtree($tabtree, $page);

if (isset($errornotification) === true) {
    echo $OUTPUT->notification($errornotification, 'notifyproblem');
}

if (isset($formdata->previewbutton) === true) {
    $table = new html_table();
    $table->id = "uupreview";
    $table->attributes['class'] = 'generaltable';
    $table->tablealign = 'center';
    $table->summary = get_string('federation_preview', 'local_apsolu');
    $table->head = $columns;
    $table->data = $data;

    $previewtable = html_writer::tag('div', html_writer::table($table), ['class' => 'flexible-wrap']);

    // On régénère le formulaire pour afficher l'aperçu et permettre l'association des colonnes.
    $mform = new local_apsolu_federation_import_licences(null, [$columns, $previewtable]);
}

$mform->display();

if (isset($formdata->importbutton) === true) {
    echo '<h3>'.get_string('federation_result', 'local_apsolu').'</h3>';
    if (isset($result[0]) === false) {
        $content = html_writer::tag('p', get_string('federation_no_import', 'local_apsolu'));
    } else {
        $content = html_writer::alist($result);
    }

    echo html_writer::tag('div', $content, ['class' => 'alert alert-info']);
}

echo $OUTPUT->footer();
