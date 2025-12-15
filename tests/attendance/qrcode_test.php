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

namespace local_apsolu\attendance;

use coding_exception;
use local_apsolu\attendance\qrcode;
use local_apsolu\core\attendancepresence;
use local_apsolu\core\attendancesession;
use local_apsolu\core\attendance\status;
use local_apsolu\core\course;
use moodle_exception;
use stdClass;

/**
 * Classe de tests pour local_apsolu\attendance\qrcode
 *
 * @package    local_apsolu
 * @category   test
 * @copyright  2025 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(qrcode::class)]
final class qrcode_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();

        $this->resetAfterTest();
    }

    /**
     * Teste delete().
     *
     * @return void
     */
    public function test_delete(): void {
        // Génère un nouveau cours.
        $data = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_course_data();
        $course = new course();
        $course->save($data);

        // Génère une session de cours.
        $session = new attendancesession();
        $session->courseid = $course->id;
        $session->name = 'attendancesession 1';
        $session->save();

        // Génère un QR code.
        $qrcode = new qrcode();
        $qrcode->keycode = qrcode::generate_keycode();
        $qrcode->settings = qrcode::get_default_settings();
        $qrcode->sessionid = $session->id;
        $qrcode->save();

        $countrecords = count(qrcode::get_records());
        $this->assertSame(1, $countrecords);

        // Supprime le QR code.
        $result = $qrcode->delete();
        $this->assertTrue($result);

        $countrecords = count(qrcode::get_records());
        $this->assertSame(0, $countrecords);
    }

    /**
     * Teste generate_keycode().
     *
     * @return void
     */
    public function test_generate_keycode(): void {
        $this->assertIsString(qrcode::generate_keycode());
    }

    /**
     * Teste get_default_settings().
     *
     * @return void
     */
    public function test_get_default_settings(): void {
        $this->assertIsObject(qrcode::get_default_settings());
        $this->assertCount(13, (array) qrcode::get_default_settings());
    }

    /**
     * Teste save().
     *
     * @return void
     */
    public function test_save(): void {
        // Génère un nouveau cours.
        $data = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_course_data();
        $course = new course();
        $course->save($data);

        // Génère une session de cours.
        $session1 = new attendancesession();
        $session1->courseid = $course->id;
        $session1->name = 'attendancesession 1';
        $session1->save();

        $session2 = new attendancesession();
        $session2->courseid = $course->id;
        $session2->name = 'attendancesession 2';
        $session2->save();

        $countrecords = count(qrcode::get_records());
        $this->assertSame(0, $countrecords);

        // Génère un QR code.
        $qrcode = new qrcode();
        $qrcode->keycode = qrcode::generate_keycode();
        $qrcode->settings = qrcode::get_default_settings();
        $qrcode->sessionid = $session1->id;
        $qrcode->save();
        $firstqrcodeid = $qrcode->id;

        $this->assertIsInt($qrcode->id);
        $countrecords = count(qrcode::get_records());
        $this->assertSame(1, $countrecords);

        // Génère un QR code pour une nouvelle session.
        $qrcode = new qrcode();
        $qrcode->keycode = qrcode::generate_keycode();
        $qrcode->settings = qrcode::get_default_settings();
        $qrcode->sessionid = $session2->id;
        $qrcode->id = $qrcode->save();

        $countrecords = count(qrcode::get_records());
        $this->assertSame(2, $countrecords);

        // Génère un QR code pour une session déjà associée à un autre QR code. À la fin, il ne doit pas avoir 3 QR codes en base.
        $qrcode = new qrcode();
        $qrcode->keycode = qrcode::generate_keycode();
        $qrcode->settings = qrcode::get_default_settings();
        $qrcode->sessionid = $session1->id;
        $qrcode->id = $qrcode->save();

        $this->assertNotEquals($qrcode->id, $firstqrcodeid);
        $countrecords = count(qrcode::get_records());
        $this->assertSame(2, $countrecords);

        // Met à jour le QR code.
        $qrcode->keycode = qrcode::generate_keycode();
        $qrcode->save();
        $updatedqrcodeid = $qrcode->id;

        $this->assertEquals($qrcode->id, $updatedqrcodeid);
        $countrecords = count(qrcode::get_records());
        $this->assertSame(2, $countrecords);

        // Tester avec de la data.
    }

    /**
     * Teste set_default_settings().
     *
     * @return void
     */
    public function test_set_default_settings(): void {
        $qrcode = new qrcode();
        $qrcode->settings = '';
        $qrcode->set_default_settings();
        $this->assertCount(13, (array) $qrcode->settings);

        $qrcode = new qrcode();
        $qrcode->settings = '{}';
        $qrcode->set_default_settings();
        $this->assertCount(13, (array) $qrcode->settings);

        try {
            $qrcode = new qrcode();
            $qrcode->settings = true;
            $qrcode->set_default_settings();

            $this->fail('Une exception "coding_exception" attendue parce que l\'attribut "settings" n\'est pas un objet.');
        } catch (coding_exception $exception) {
            $this->assertInstanceOf('coding_exception', $exception);
        }
    }

    /**
     * Teste sign().
     *
     * @return void
     */
    public function test_sign(): void {
        global $USER;

        // Génère un nouveau cours.
        $data = $this->getDataGenerator()->get_plugin_generator('local_apsolu')->get_course_data();
        $course = new course();
        $course->save($data);

        // Génère une session de cours.
        $session = new attendancesession();
        $session->courseid = $course->id;
        $session->name = 'attendancesession 1';
        $session->save();

        // Génère un QR code.
        $qrcode = new qrcode();
        $qrcode->keycode = qrcode::generate_keycode();
        $qrcode->settings = new stdClass();
        $qrcode->settings->starttime = 15 * MINSECS;
        $qrcode->settings->presentstatus = 1;
        $qrcode->settings->latetimeenabled = 1;
        $qrcode->settings->latetime = 15 * MINSECS;
        $qrcode->settings->latestatus = 2;
        $qrcode->settings->endtimeenabled = 1;
        $qrcode->settings->endtime = 30 * MINSECS;
        $qrcode->settings->automarkenabled = 1;
        $qrcode->settings->automarkstatus = 4;
        $qrcode->settings->automarktime = DAYSECS;
        $qrcode->settings->allowguests = 0;
        $qrcode->settings->autologout = 1;
        $qrcode->settings->rotate = 0;
        $qrcode->sessionid = $session->id;

        $statuses = status::get_records();

        // Teste l'erreur lorsque la validation est effectuée avec un étudiant non-inscrit.
        try {
            $qrcode->settings->allowguests = false;
            $qrcode->sign($session);

            $this->fail('Une exception "moodle_exception" attendue pour un utilisateur non-inscrit à ce cours.');
        } catch (moodle_exception $exception) {
            $this->assertInstanceOf('moodle_exception', $exception);
            $this->assertSame(
                get_string('you_do_not_have_any_active_enrolments_for_this_course', 'local_apsolu'),
                $exception->getMessage()
            );
        } finally {
            $qrcode->settings->allowguests = true;
        }

        // Teste l'erreur lorsque la prise de présences n'a pas débuté.
        try {
            $session->sessiontime = time() + DAYSECS;
            $session->save();

            $qrcode->sign($session);
            $this->fail('Une exception "moodle_exception" attendue pour une prise de présences non démarrée');
        } catch (moodle_exception $exception) {
            $this->assertInstanceOf('moodle_exception', $exception);
            $this->assertSame(
                get_string('the_attendance_recording_for_this_session_has_not_started_yet', 'local_apsolu'),
                $exception->getMessage()
            );
        }

        // Teste l'erreur lorsque la prise de présences est terminée (délai expiré).
        try {
            $session->sessiontime = time() - 61;
            $session->save();

            $qrcode->settings->endtime = 60;
            $qrcode->settings->endtimeenabled = 1;
            $qrcode->sign($session);
            $this->fail('Une exception "moodle_exception" attendue pour une prise de présences terminée (délai expiré)');
        } catch (moodle_exception $exception) {
            $this->assertInstanceOf('moodle_exception', $exception);
            $this->assertSame(
                get_string('the_attendance_recording_for_this_session_is_over', 'local_apsolu'),
                $exception->getMessage()
            );
        }

        // Teste l'erreur lorsque la prise de présences est terminée (session terminée).
        try {
            $session->sessiontime = time() - DAYSECS;
            $session->save();

            $qrcode->settings->endtimeenabled = 0;
            $qrcode->sign($session);
            $this->fail('Une exception "moodle_exception" attendue pour une prise de présences terminée (session terminée)');
        } catch (moodle_exception $exception) {
            $this->assertInstanceOf('moodle_exception', $exception);
            $this->assertSame(
                get_string('the_attendance_recording_for_this_session_is_over', 'local_apsolu'),
                $exception->getMessage()
            );
        }

        // Teste la validation d'une présence "normale".
        $session->sessiontime = time() - 60;
        $session->save();

        $a = new stdClass();
        $a->status = $statuses[$qrcode->settings->presentstatus]->longlabel;
        $a->time = userdate(time(), get_string('strftimetime'));
        $response = get_string('your_participation_has_been_recorded_X_for_this_session_the_X', 'local_apsolu', $a);

        $qrcode->settings->starttime = 0;
        $qrcode->settings->latetime = 120;
        $qrcode->settings->latetimeenabled = 1;
        $this->assertSame($response, $qrcode->sign($session));

        $presence = attendancepresence::get_record(['sessionid' => $session->id, 'studentid' => $USER->id], '*', MUST_EXIST);
        $presence->delete();

        // Teste la validation d'une présence sans le paramètre "en retard".
        $qrcode->settings->latetimeenabled = 0;

        $a = new stdClass();
        $a->status = $statuses[$qrcode->settings->presentstatus]->longlabel;
        $a->time = userdate(time(), get_string('strftimetime'));
        $response = get_string('your_participation_has_been_recorded_X_for_this_session_the_X', 'local_apsolu', $a);

        $this->assertSame($response, $qrcode->sign($session));

        $presence = attendancepresence::get_record(['sessionid' => $session->id, 'studentid' => $USER->id], '*', MUST_EXIST);
        $presence->delete();

        // Teste la validation d'une présence "en retard".
        $qrcode->settings->latetime = 30;
        $qrcode->settings->latetimeenabled = 1;

        $a = new stdClass();
        $a->status = $statuses[$qrcode->settings->latestatus]->longlabel;
        $a->time = userdate(time(), get_string('strftimetime'));
        $response = get_string('your_participation_has_been_recorded_X_for_this_session_the_X', 'local_apsolu', $a);

        $this->assertSame($response, $qrcode->sign($session));

        // Teste l'erreur lorsque la validation a déjà été effectuée précédemment.
        try {
            $qrcode->sign($session);

            $this->fail('Une exception "moodle_exception" attendue pour une validation déjà effectuée précédemment');
        } catch (moodle_exception $exception) {
            $presence = attendancepresence::get_record(['sessionid' => $session->id, 'studentid' => $USER->id]);

            $a = new stdClass();
            $a->status = $statuses[$presence->statusid]->longlabel;
            $a->datetime = userdate($presence->timecreated, get_string('strftimedatetime', 'local_apsolu'));

            $this->assertInstanceOf('moodle_exception', $exception);
            $this->assertSame(
                get_string('your_participation_has_already_been_recorded_X_for_this_session_the_X', 'local_apsolu', $a),
                $exception->getMessage()
            );
        }
    }
}
