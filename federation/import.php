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
 * Bulk user registration script from a comma separated file
 *
 * @package    tool
 * @subpackage uploaduser
 * @copyright  2004 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/csvlib.class.php');

require_once('import_form.php');

core_php_time_limit::raise(60*60); // 1 hour should be enough
raise_memory_limit(MEMORY_HUGE);

$returnurl = new moodle_url('/local/apsolu/federation/index.php?page=importation');

$mform = new local_apsolu_federation_import_licences();

if ($formdata = $mform->get_data()) {
    if (isset($formdata->importbutton) === true) {
        $result = array();

        $sql = "SELECT u.email, u.id, u.firstname, u.lastname, uid3.data AS licenseid".
            " FROM {user} u".
            " JOIN {user_info_data} uid1 ON u.id = uid1.userid AND uid1.fieldid = 13 AND uid1.data = 1". // TODO: certificat médical.
            " JOIN {user_info_data} uid2 ON u.id = uid2.userid AND uid2.fieldid = 9 AND uid2.data = 1". // TODO: fédération paid
            " LEFT JOIN {user_info_data} uid3 ON u.id = uid3.userid AND uid3.fieldid = 14"; // TODO: federationumber.
        $users = $DB->get_records_sql($sql);
    }

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
            // Import.
            if (isset($line[1]) === false) {
                continue;
            }

            $email = trim($line[1]);
            if (isset($users[$email]) === false) {
                continue;
            }

            $licenseid = trim($line[0]);
            if ($users[$email]->licenseid === $licenseid) {
                continue;
            }

            $license = $DB->get_record('user_info_data', array('userid' => $users[$email]->id, 'fieldid' => 14));
            if ($license === false) {
                $license = new stdClass();
                $license->fieldid = 14;
                $license->userid = $users[$email]->id;
                $license->data = $licenseid;

                $DB->insert_record('user_info_data', $license);

                $params = new stdClass();
                $params->licenseid = $licenseid;
                $params->profile = html_writer::link('/user/profile.php?id='.$license->userid, $users[$email]->firstname.' '.$users[$email]->lastname);
                $result[] = get_string('federation_insert_license', 'local_apsolu', $params);

            } else {
                $oldlicenseid = $license->data;

                $license->data = $licenseid;
                $DB->update_record('user_info_data', $license);

                $params = new stdClass();
                $params->licenseid = $licenseid;
                $params->profile = html_writer::link('/user/profile.php?id='.$license->userid, $users[$email]->firstname.' '.$users[$email]->lastname);
                if (empty($oldlicenseid) === true) {
                    $result[] = get_string('federation_update_new_license', 'local_apsolu', $params);
                } else {
                    $params->oldlicenseid = $oldlicenseid;
                    $result[] = get_string('federation_update_old_license', 'local_apsolu', $params);
                }
            }

            // Add event.
            \core\event\user_updated::create_from_userid($license->userid)->trigger();
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

echo $OUTPUT->heading_with_help(get_string('federation_importation', 'local_apsolu'), 'federation_importation', 'local_apsolu');

$mform->display();

if (isset($formdata->previewbutton) === true) {
    $table = new html_table();
    $table->id = "uupreview";
    $table->attributes['class'] = 'generaltable';
    $table->tablealign = 'center';
    $table->summary = get_string('federation_preview', 'local_apsolu');
    $table->head = array(get_string('federation_licenseid', 'local_apsolu'), get_string('email'));
    $table->data = $data;

    echo '<h3>'.get_string('federation_preview', 'local_apsolu').'</h3>';
    echo html_writer::tag('div', html_writer::table($table), array('class'=>'flexible-wrap'));
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
