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
 * Page affichant la liste des relances de paiements.
 *
 * @package    local_apsolu
 * @copyright  2017 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/local/apsolu/locallib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');

$userid = optional_param('userid', null, PARAM_INT);


// $sql = "SELECT DISTINCT u.*".//, uid1.data AS card, uid2.data AS muscu, uid3.data AS ffsu, uid4.data AS hla".
$sql = "SELECT COUNT(institution) AS total, institution".
    " FROM {user} u".
    " LEFT JOIN {user_info_data} uid1 ON u.id = uid1.userid AND uid1.fieldid = 12 AND uid1.data != 1". // sport card.
    // " LEFT JOIN {user_info_data} uid2 ON u.id = uid2.userid AND uid2.fieldid = 15". // sportif haut niveau.
    " JOIN {user_enrolments} ue ON u.id = ue.userid AND ue.status = 0".
    " JOIN {enrol} e ON e.id = ue.enrolid AND e.enrol = 'select'".
    " JOIN {apsolu_courses} ac ON ac.id = e.courseid".
    " JOIN {context} ctx ON ac.id = ctx.instanceid AND ctx.contextlevel = 50".
    " JOIN {role_assignments} ra ON u.id = ra.userid AND ctx.id = ra.contextid AND ra.component = 'enrol_select' AND ra.itemid = e.id".
    " JOIN {cohort_members} cm ON u.id = cm.userid".
    " JOIN {apsolu_colleges_members} acm ON acm.cohortid = cm.cohortid".
    " JOIN {apsolu_colleges} aco ON aco.id = acm.collegeid AND aco.roleid = ra.roleid AND aco.userprice != 0".
    " WHERE u.auth = 'shibboleth'".
    " GROUP BY u.institution".
    " ORDER BY institution, lastname, firstname";
$users = $DB->get_records_sql($sql);

if (isset($notification)) {
    echo $notification;
}

$data = new stdClass();
$data->wwwroot = $CFG->wwwroot;
$data->users = array_values($users);

echo $OUTPUT->render_from_template('local_apsolu/payment_notifications', $data);
