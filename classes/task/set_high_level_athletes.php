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
 * Tasks to synchronise students
 *
 * @package    local
 * @subpackage apsolu
 * @copyright  2017 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_apsolu\task;

use UniversiteRennes2\Apsolu as apsolu;

class set_high_level_athletes extends \core\task\scheduled_task {
    public function get_name() {
        // Shown in admin screens.
        return get_string('set_high_level_athletes', 'local_apsolu');
    }

    public function execute() {
        global $DB;

        // TODO: faire une page pour configurer le groupe et le cours (menu déroulant + ids) à synchroniser.
        // TODO: carte muscu offerte.

        $field = $DB->get_record('user_info_field', array('shortname' => 'highlevelathlete'));
        if ($field === false) {
            mtrace('Le champ "highlevelathlete" n\'existe pas.');
            return false;
        }

        $group = $DB->get_record('groups', array('id' => 53, 'courseid' => 320));
        if ($group === false) {
            mtrace('Le groupe "athlètes de haut niveau validés" n\'existe pas.');
            return false;
        }

        $members = $DB->get_records('groups_members', array('groupid' => $group->id));
        foreach ($members as $member) {
            $data = new \stdClass();
            $data->userid = $user->id;
            $data->fieldid = $field->id;
            $data->data = '1';

            $conditions = array('userid' => $data->userid, 'fieldid' => $data->fieldid);
            if ($data = $DB->get_record('user_info_data', $conditions)) {
                if ($data->data !== $data->data) {
                    $data->id = $data->id;
                    $DB->update_record('user_info_data', $data);
                    mtrace("\t update ".$field->shortname." : #".$data->userid." ".$data->data);
                }
            } else {
                $DB->insert_record('user_info_data', $data);
                mtrace("\t insert ".$field->shortname." : #".$data->userid."".$data->data);
            }
        }

        $datas = $DB->get_records('user_info_data', array('fieldid' => $field->id));
        foreach ($datas as $data) {
            if ($data->data !== '1') {
                continue; // Ne traiter que les comptes marqués haut niveau.
            }

            if (isset($members[$data->userid]) === false) {
                $data->data = 0;
                $DB->update_record('user_info_data', $data);
                mtrace("\t remove ".$field->shortname." : #".$data->userid." ".$data->data);
            }
        }
    }
}
