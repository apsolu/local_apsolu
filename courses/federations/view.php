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
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace UniversiteRennes2\Apsolu;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/local/apsolu/locallib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');

$userid = optional_param('userid', null, PARAM_INT);

if (isset($userid)) {
    $user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
    $users[$user->id] = $user;

    $data = new \stdClass();
    $data->wwwroot = $CFG->wwwroot;
    $data->users = array();
    $data->count_users = 0;

    foreach ($users as $user) {
        $user->htmlpicture = $OUTPUT->render(new \user_picture($user));

        $customfields = profile_user_record($user->id);

        $user->groups = array();

        $sql = "SELECT g.*".
            " FROM {groups} g".
            " JOIN {groups_members} gm ON g.id = gm.groupid".
            " JOIN {apsolu_complements} ac ON ac.id = g.courseid AND ac.federation = 1".
            " WHERE gm.userid = :userid".
            " ORDER BY g.name";
        foreach ($DB->get_records_sql($sql, array('userid' => $user->id)) as $group) {
            $user->groups[] = $group->name;
        }
        $user->categories = implode(', ', $user->groups);

        if (isset($customfields->federationumber)) {
            $user->federationumber = $customfields->federationumber;
        } else {
            $user->federationumber = '';
        }

        if (isset($customfields->medicalcertificate) && $customfields->medicalcertificate == 1) {
            $user->medicalcertificate = get_string('yes');
        } else {
            $user->medicalcertificate = get_string('no');
        }

        if (isset($customfields->federationpaid) && $customfields->federationpaid == 1) {
            $user->federationpaid = get_string('yes');
        } else {
            $user->federationpaid = get_string('no');
        }

        $data->users[] = $user;
        $data->count_users++;
    }
} else {
    // Create the user selector objects.
    $options = array('multiselect' => false);
    $userselector = new local_apsolu_courses_federation_user_selector('userid', $options);
    ob_start();
    $userselector->display();
    $userselector = ob_get_contents();
    ob_end_clean();

    $data = new \stdClass();
    $data->wwwroot = $CFG->wwwroot;
    $data->action = $CFG->wwwroot.'/local/apsolu/courses/complements.php?tab=federations';
    $data->user_selector = $userselector;
}

if (isset($notificationform)) {
    $data->notification = $notificationform;
}

echo $OUTPUT->render_from_template('local_apsolu/courses_federations', $data);
