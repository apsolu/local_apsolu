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

use UniversiteRennes2\Apsolu as apsolu;

defined('MOODLE_INTERNAL') || die();

$returnurl = new \moodle_url('/local/apsolu/users/index.php?page=merge');

// Get the user_selector we will need.
$options = array('auth' => 'email', 'multiselect' => false);
$email_user_selector = new apsolu\auth_user_selector('email_users', $options);

$options = array('auth' => 'shibboleth', 'multiselect' => false);
$shibboleth_user_selector = new apsolu\auth_user_selector('shibboleth_users', $options);

// Process incoming user assignments to the cohort
if (optional_param('merge', null, PARAM_ALPHA) && confirm_sesskey()) {
    $notifications = array();

    $email_account = $email_user_selector->get_selected_users();
    $shibboleth_account = $shibboleth_user_selector->get_selected_users();

    if (count($email_account) !== 1) {
        $notifications[] = get_string('users_require_email_user', 'local_apsolu');
    }

    if (count($shibboleth_account) !== 1) {
        $notifications[] = get_string('users_require_shibboleth_user', 'local_apsolu');
    }

    if (isset($notifications[0]) === false) {
        $email_account = current($email_account);
        $email_account = $DB->get_record('user', array('id' => $email_account->id));

        $shibboleth_account = current($shibboleth_account);
        $shibboleth_account = $DB->get_record('user', array('id' => $shibboleth_account->id));

        $mergeable = apsolu\is_mergeable($shibboleth_account);
        if ($mergeable === true) {
            $notifications[] = apsolu\merge($email_account, $shibboleth_account);

            $email_user_selector->invalidate_selected_users();
            $shibboleth_user_selector->invalidate_selected_users();
        } else {
            $notifications[] = get_string('users_not_mergeable', 'local_apsolu', $shibboleth_account);
            $notifications[] = $mergeable;
            $notifications[] = get_string('users_not_mergeable_support', 'local_apsolu');
        }
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('settings_users_merge', 'local_apsolu'));

if (isset($notifications[0]) === true) {
    echo $OUTPUT->notification(implode('<br />', $notifications), 'notifymessage');
}

// Print the form.
?>
<form id="assignform" method="post" action="<?php echo $PAGE->url ?>"><div>
  <input type="hidden" name="sesskey" value="<?php echo sesskey() ?>" />
  <input type="hidden" name="returnurl" value="<?php echo $returnurl->out_as_local_url() ?>" />

  <table summary="" class="generaltable generalbox boxaligncenter" cellspacing="0">
    <tr>
      <td id="existingcell">
          <p><label for="email_users"><?php print_string('users_email_users', 'local_apsolu'); ?></label></p>
          <?php $email_user_selector->display() ?>
      </td>
      <td id="potentialcell">
          <p><label for="shibboleth_users"><?php print_string('users_shibboleth_users', 'local_apsolu'); ?></label></p>
          <?php $shibboleth_user_selector->display() ?>
      </td>
    </tr>
    <tr>
      <td colspan="2">
        <input name="merge" id="merge" class="btn btn-primary" type="submit" value="<?php echo s(get_string('users_merge_accounts', 'local_apsolu')); ?>" />
      </td>
    </td>
  </table>
</div></form>

<?php

echo $OUTPUT->footer();
