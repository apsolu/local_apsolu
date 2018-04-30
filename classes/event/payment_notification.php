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

namespace local_apsolu\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event to log user notifications.
 *
 * @package    enrol_select
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class payment_notification extends \core\event\base {
    protected function init() {
        // Values: c(reate), r(ead), u(pdate) or d(elete).
        $this->data['crud'] = 'c';

        // Values: LEVEL_TEACHING, LEVEL_PARTICIPATING or LEVEL_OTHER.
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    public static function get_name() {
        return get_string('courses_event_payment_notification', 'local_apsolu');
    }

    public function get_description() {
        return "User #{$this->userid} notified user #{$this->relateduserid}.";
    }

    public function get_url() {
        return new \moodle_url('local/apsolu_courses/reports.php');
    }

    public function get_legacy_logdata() {
        // Override if you are migrating an add_to_log() call.
        return array($this->courseid, 'local_apsolu', 'notified', '...........', $this->objectid, $this->contextinstanceid);
    }

    protected function get_legacy_eventdata() {
        // Override if you migrating events_trigger() call.
        $data = new \stdClass();
        $data->id = $this->objectid;
        $data->userid = $this->relateduserid;
        return $data;
    }
}
