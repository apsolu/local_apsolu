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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/csvlib.class.php');

require_once(__DIR__.'/import_form.php');

$returnurl = new moodle_url('/local/apsolu/federation/index.php?page=importation');

$mform = new local_apsolu_federation_import_licences();

if ($formdata = $mform->get_data()) {
    $iid = csv_import_reader::get_new_iid('local_apsolu_federation_import');
    $cir = new csv_import_reader($iid, 'local_apsolu_federation_import');

    $content = $mform->get_file_content('userfile');

    $readcount = $cir->load_csv_content($content, $formdata->encoding, $formdata->delimiter_name);
    $csvloaderror = $cir->get_error();
    unset($content);

    if ($csvloaderror !== null) {
        print_error('csvloaderror', '', $returnurl, $csvloaderror);
    }

    // init csv import helper
    $cir->init();

    $i = 0;
    while ($line = $cir->next()) {
        if (isset($formdata->previewbutton) === true) {
            // Prévisualisation.
            $data[] = $line;

            $i++;

            if (isset($formdata->import) === false && $i > $formdata->previewrows) {
                break;
            }
        } else if (isset($formdata->importbutton) === true) {
            $result = array();

            $sql = "SELECT u.email, u.firstname, u.lastname, adh.*".
                " FROM {user} u".
                " JOIN {apsolu_federation_adhesions} adh ON u.id = adh.userid";
            $users = $DB->get_records_sql($sql);

            // Import.
            $email = trim($line[10]);
            if (isset($users[$email]) === false) {
                continue;
            }

            $licenseid = trim($line[0]);
            if (empty($licenseid) === true) {
                continue;
            }

            $adhesion = $users[$email];
            if ($adhesion->federationnumber === $licenseid) {
                continue;
            }

            $oldlicenseid = $adhesion->federationnumber;

            $sql = "UPDATE {apsolu_federation_adhesions} SET federationnumber = :federationnumber WHERE id = :id AND userid = :userid";
            $DB->execute($sql, array('federationnumber' => $licenseid, 'id' => $adhesion->id, 'userid' => $adhesion->userid));

            $params = new stdClass();
            $params->licenseid = $licenseid;
            $params->profile = html_writer::link('/user/profile.php?id='.$adhesion->userid, $adhesion->firstname.' '.$adhesion->lastname);
            if (empty($oldlicenseid) === true) {
                // Création d'un numéro AS.
                $result[] = get_string('federation_insert_license', 'local_apsolu', $params);
            } else {
                // Mise à jour du numéro AS.
                $params->oldlicenseid = $oldlicenseid;
                $result[] = get_string('federation_update_license', 'local_apsolu', $params);
            }

            // Add event.
            \core\event\user_updated::create_from_userid($adhesion->userid)->trigger();
        } else {
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

$mform->display();

if (isset($formdata->previewbutton) === true) {
    $table = new html_table();
    $table->id = "uupreview";
    $table->attributes['class'] = 'generaltable';
    $table->tablealign = 'center';
    $table->summary = get_string('federation_preview', 'local_apsolu');
    $table->head = Adhesion::get_exportation_headers();
    $table->data = $data;

    echo '<h3>'.get_string('federation_preview', 'local_apsolu').'</h3>';
    echo html_writer::tag('div', html_writer::table($table), array('class' => 'flexible-wrap'));
} else if (isset($formdata->importbutton) === true) {
    echo '<h3>'.get_string('federation_result', 'local_apsolu').'</h3>';
    if (isset($result[0]) === false) {
        $content = html_writer::tag('p', get_string('federation_no_import', 'local_apsolu'));
    } else {
        $content = html_writer::alist($result);
    }

    echo html_writer::tag('div', $content, array('class' => 'alert alert-info'));
}

echo $OUTPUT->footer();
