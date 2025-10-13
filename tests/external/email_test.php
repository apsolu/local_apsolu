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

namespace local_apsolu\external;

use context_system;
use external_api;
use externallib_advanced_testcase;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * Tests for the email class.
 *
 * @package   local_apsolu
 * @category  test
 * @copyright 2023 Université Rennes 2 {@link https://www.univ-rennes2.fr}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \local_apsolu\external\email
 */
final class email_test extends externallib_advanced_testcase {
    /**
     * Setup testcase.
     */
    public function setUp(): void {
        global $CFG;

        parent::setUp();

        require_once($CFG->dirroot . '/message/lib.php');
    }

    /**
     * Test tearDown.
     */
    public function tearDown(): void {
        parent::tearDown();

        unset($this->sections);
        unset($this->activities);
    }

    /**
     * Test send_instant_emails.
     *
     * @covers ::send_instant_emails()
     * @runInSeparateProcess
     */
    public function test_send_instant_emails(): void {
        global $CFG, $DB;

        $this->resetAfterTest();

        // Transactions used in tests, tell phpunit use alternative reset method.
        $this->preventResetByRollback();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        $this->setUser($user1);

        // Disable mail.
        $CFG->noemailever = true;
        $CFG->emailbulkmessaging = 1;
        $noemaildebugmessage = 'Not sending email due to $CFG->noemailever config setting';

        // Create test message data.
        $message = [];
        $message['subject'] = 'the subject';
        $message['carboncopy'] = false;
        $message['carboncopysubject'] = '';
        $message['body'] = 'the message';
        $message['receivers'] = [$user2->id];
        $messages = [$message];

        // Test email sending.
        $sentemails = email::send_instant_emails($messages);
        $sentemails = external_api::clean_returnvalue(email::send_instant_emails_returns(), $sentemails);
        $this->assertDebuggingCalled($noemaildebugmessage, DEBUG_DEVELOPER);

        // Test carbon copy.
        $count = 2;
        $messages[0]['carboncopy'] = true;
        $sentemails = email::send_instant_emails($messages);
        $sentemails = external_api::clean_returnvalue(email::send_instant_emails_returns(), $sentemails);
        $this->assertDebuggingCalledCount(
            $count,
            array_fill(0, $count, $noemaildebugmessage),
            array_fill(0, $count, DEBUG_DEVELOPER)
        );

        // Test on deleted user.
        $user2->deleted = 1;
        $DB->update_record('user', $user2);

        $messages[0]['carboncopy'] = false;
        $sentemails = email::send_instant_emails($messages);
        $sentemails = external_api::clean_returnvalue(email::send_instant_emails_returns(), $sentemails);
        $this->assertDebuggingNotCalled($noemaildebugmessage);

        // Test to send message only once per user.
        $messages[0]['carboncopy'] = true;
        $messages[0]['receivers'] = [$user1->id];
        $sentemails = email::send_instant_emails($messages);
        $sentemails = external_api::clean_returnvalue(email::send_instant_emails_returns(), $sentemails);
        $this->assertDebuggingCalled($noemaildebugmessage, DEBUG_DEVELOPER);
    }

    /**
     * Test send_instant_emails with no capabilities.
     *
     * @covers ::send_instant_emails()
     * @runInSeparateProcess
     */
    public function test_send_instant_emails_no_capability(): void {
        global $CFG, $DB;

        $this->resetAfterTest(true);

        // Transactions used in tests, tell phpunit use alternative reset method.
        $this->preventResetByRollback();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        $this->setUser($user1);

        // Unset the required capabilities by the external function.
        $contextid = context_system::instance()->id;
        $userrole = $DB->get_record('role', ['shortname' => 'user']);
        $this->unassignUserCapability('moodle/site:sendmessage', $contextid, $userrole->id);

        // Enable messaging.
        $CFG->emailbulkmessaging = 1;

        // Create test message data.
        $message = [];
        $message['subject'] = 'the subject';
        $message['carboncopy'] = false;
        $message['carboncopysubject'] = '';
        $message['text'] = 'the message';
        $message['receivers'] = [$user2->id];
        $messages = [$message];

        $this->expectException('required_capability_exception');
        email::send_instant_emails($messages);
    }

    /**
     * Test send_instant_emails when emailbulkmessaging is disabled.
     *
     * @covers ::send_instant_emails()
     * @runInSeparateProcess
     */
    public function test_send_instant_emails_emailbulkmessaging_disabled(): void {
        global $CFG;

        $this->resetAfterTest(true);

        // Transactions used in tests, tell phpunit use alternative reset method.
        $this->preventResetByRollback();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        $this->setUser($user1);

        // Disable messaging.
        $CFG->emailbulkmessaging = 0;

        // Create test message data.
        $message = [];
        $message['subject'] = 'the subject';
        $message['carboncopy'] = false;
        $message['carboncopysubject'] = '';
        $message['text'] = 'the message';
        $message['receivers'] = [$user2->id];
        $messages = [$message];

        $this->expectException('moodle_exception');
        email::send_instant_emails($messages);
    }
}
