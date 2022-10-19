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
 * Classe représentant la tâche permettant d'alimenter le témoin sportif de haut niveau.
 *
 * @package    local_apsolu
 * @copyright  2017 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_apsolu\task;

use UniversiteRennes2\Apsolu as apsolu;

/**
 * Classe représentant la tâche permettant d'alimenter le témoin sportif de haut niveau.
 *
 * @package    local_apsolu
 * @copyright  2017 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class set_high_level_athletes extends \core\task\scheduled_task {
    /**
     * Retourne le nom de la tâche.
     *
     * @return string
     */
    public function get_name() {
        // Shown in admin screens.
        return get_string('set_high_level_athletes', 'local_apsolu');
    }

    /**
     * Execute la tâche.
     *
     * @return void
     */
    public function execute() {
        global $CFG, $DB;

        if (isset($CFG->is_siuaps_rennes) === false) {
            // TODO: implémenter l'équivalent pour les autres universités.
            return true;
        }

        // TODO: sortir ces 3 tâches qui n'ont rien à voir avec les athlètes de haut-niveau.
        // Positionne le flag "apsolumedicalcertificate" à 1 sur les étudiants dont le certificat FFSU est validé dans le cours 249.
        $sql = "UPDATE {user_info_data} SET data = 1".
            " WHERE fieldid = (SELECT uif.id FROM {user_info_field} uif WHERE uif.shortname = 'apsolumedicalcertificate')".
            " AND userid IN (SELECT ag.userid from {assign} a JOIN {assign_grades} ag ON ag.assignment = a.id WHERE a.id = 75 AND a.course = 249 AND ag.grade > 0)".
            " AND data != '1'";
        $DB->execute($sql);

        // Positionne le flag "apsolumedicalcertificate" à 1 sur les étudiants dont la note au questionnaire FFSU est 1 dans le cours 249.
        $sql = "UPDATE {user_info_data} SET data = 1".
            " WHERE fieldid = (SELECT uif.id FROM {user_info_field} uif WHERE uif.shortname = 'apsolumedicalcertificate')".
            " AND userid IN (SELECT userid FROM {quiz_attempts} WHERE quiz = 14 AND state = 'finished' AND sumgrades = '1')".
            " AND data != '1'";
        $DB->execute($sql);

        // Positionne le flag "apsolufederationpaid" à 1 sur les étudiants dont le paiement de la carte FFSU (id 4) est payé (status 1) ou offert (status 3).
        $sql = "UPDATE {user_info_data} SET data = 1".
            " WHERE fieldid = (SELECT uif.id FROM {user_info_field} uif WHERE uif.shortname = 'apsolufederationpaid')".
            " AND userid IN (SELECT ap.userid FROM {apsolu_payments} ap JOIN {apsolu_payments_items} api ON ap.id = api.paymentid WHERE ap.status IN (1, 3) AND api.cardid = 4)".
            " AND data != '1'";
        $DB->execute($sql);

        // Positionne le flag "apsolumuscupaid" à 1 sur les étudiants dont le paiment de la carte muscu (id 3) est payé (status 1) ou offert (status 3).
        $sql = "UPDATE {user_info_data} SET data = 1".
            " WHERE fieldid = (SELECT uif.id FROM {user_info_field} uif WHERE uif.shortname = 'apsolumuscupaid')".
            " AND userid IN (SELECT ap.userid FROM {apsolu_payments} ap JOIN {apsolu_payments_items} api ON ap.id = api.paymentid WHERE ap.status IN (1, 3) AND api.cardid = 3)".
            " AND data != '1'";
        $DB->execute($sql);

        // TODO: faire une page pour configurer le groupe et le cours (menu déroulant + ids) à synchroniser.
        $groupingid = 4;
        $courseid = 423;

        $field = $DB->get_record('user_info_field', array('shortname' => 'apsoluhighlevelathlete'));
        if ($field === false) {
            mtrace('Le champ "apsoluhighlevelathlete" n\'existe pas.');
            return false;
        }

        $grouping = $DB->get_record('groupings', array('id' => $groupingid, 'courseid' => $courseid));
        if ($grouping === false) {
            mtrace('Le groupement "athlètes de haut niveau validés" n\'existe pas.');
            return false;
        }

        $sql = "SELECT DISTINCT gm.userid".
            " FROM {groups_members} gm".
            " JOIN {groupings_groups} gg ON gg.groupid = gm.groupid".
            " WHERE gg.groupingid = :groupingid";
        $members = $DB->get_records_sql($sql, array('groupingid' => $grouping->id));
        foreach ($members as $member) {
            $data = new \stdClass();
            $data->userid = $member->userid;
            $data->fieldid = $field->id;
            $data->data = '1';
            $data->dataformat = '0';

            $conditions = array('userid' => $data->userid, 'fieldid' => $data->fieldid);
            if ($record = $DB->get_record('user_info_data', $conditions)) {
                if ($data->data !== $record->data) {
                    $data->id = $record->id;
                    $DB->update_record('user_info_data', $data);
                    mtrace("\t update ".$field->shortname." : userid=".$data->userid." data=".$data->data);
                }
            } else {
                $DB->insert_record('user_info_data', $data);
                mtrace("\t insert ".$field->shortname." : userid=".$data->userid." data=".$data->data);
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
                mtrace("\t remove ".$field->shortname." : userid=".$data->userid." data=".$data->data);
            }
        }
    }
}
