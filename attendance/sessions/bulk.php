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
 * Page d'ajout en série de sessions de cours.
 *
 * @package    local_apsolu
 * @copyright  2026 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\attendancesession;
use local_apsolu\core\course;
use local_apsolu\core\holiday;
use local_apsolu\core\messaging;
use local_apsolu\form\attendance\session\bulk_edit;

defined('MOODLE_INTERNAL') || die;

$template = 'local_apsolu/attendance_sessions_form';
$PAGE->set_title(get_string('add_bulk_sessions', 'local_apsolu'));
$PAGE->set_url('/local/apsolu/attendance/index.php', ['page' => $page, 'action' => 'bulk', 'courseid' => $courseid]);

$apsolucourse = new course();
$apsolucourse->load($courseid, $required = true);

// Définit les valeurs par défaut.
$default = new stdClass();
$default->startdate = time();
$default->enddate = null;
$default->weekdays = [$apsolucourse->weekday => 1];
$default->excludeholidays = 1;
[$default->starthour, $default->startminute] = explode(':', $apsolucourse->starttime);
$default->duration = course::getDuration($apsolucourse->starttime, $apsolucourse->endtime);
$default->count = 1;
$default->durationbreak = 0;
$default->locationid = $apsolucourse->locationid;
$default->courseid = $courseid;
$default->submitted = optional_param('previewbutton', null, PARAM_ALPHA);

// Définit les jours de la semaine.
$weekdays = array_keys(course::get_weekdays());

// Définit les lieux d'activités.
$locations = [];
foreach ($DB->get_records('apsolu_locations', $conditions = null, $sort = 'name') as $location) {
    $locations[$location->id] = $location->name;
}

// Build form.
$customdata = ['default' => $default, 'weekdays' => $weekdays, 'locations' => $locations];
$mform = new bulk_edit($PAGE->url->out(false), $customdata);

if ($mdata = $mform->get_data()) {
    $collisions = [];
    $sessions = [];

    // Calcule l'intervalle de début et de fin pour ajouter des sessions.
    $startdate = $mdata->startdate;
    if (empty($mdata->enddate) === true) {
        $enddate = $startdate;
    } else {
        $enddate = $mdata->enddate;
    }

    // Calcule les jours.
    $n = 1;
    $days = [];
    foreach ($weekdays as $day) {
        if (isset($mdata->weekdays[$day]) === true && empty($mdata->weekdays[$day]) === false) {
            $days[$n] = 1;
        } else {
            $days[$n] = 0;
        }

        $n++;
    }

    // Récupère les jours fériés.
    $holidays = [];
    if (empty($mdata->excludeholidays) === false) {
        foreach (holiday::get_records() as $holiday) {
            $day = date('Ymd', $holiday->day);
            $holidays[$day] = $holiday;
        }
    }

    // Calcule les sessions à ajouter.
    $n = date('N', $startdate);
    while ($startdate <= $enddate) {
        if ($days[$n] === 0) {
            $n++;
            $startdate += DAYSECS;

            if ($n === 8) {
                $n = 1;
            }
            continue;
        }

        $startdatetime = $startdate + ($mdata->starthour * HOURSECS) + ($mdata->startminute * MINSECS);
        for ($i = 0; $i < $mdata->count; $i++) {
            $sessiontime = $startdatetime + ($i * $mdata->duration + $i * $mdata->breakduration);

            $day = date('Ymd', $sessiontime);
            if (isset($holidays[$day]) === true) {
                // On ignore les jours fériés.
                continue;
            }

            // Détecte les collisions de sessions.
            $sql = "SELECT s.*
                      FROM {apsolu_attendance_sessions} s
                     WHERE s.courseid = :courseid
                       AND (s.sessiontime BETWEEN :newstartime1 AND :newendtime1
                        OR s.sessiontime + s.duration BETWEEN :newstartime2 AND :newendtime2
                        OR :newstartime3 BETWEEN s.sessiontime AND s.sessiontime + s.duration
                        OR :newendtime3 BETWEEN s.sessiontime AND s.sessiontime + s.duration)";
            $records = $DB->get_records_sql($sql, [
                'courseid' => $apsolucourse->id,
                'newstartime1' => $sessiontime,
                'newendtime1' => $sessiontime + $mdata->duration,
                'newstartime2' => $sessiontime,
                'newendtime2' => $sessiontime + $mdata->duration,
                'newstartime3' => $sessiontime,
                'newendtime3' => $sessiontime + $mdata->duration,
            ]);

            if (count($records) !== 0) {
                $conflicts = [];
                foreach ($records as $record) {
                    $conflicts[] = get_string(
                        'the_session_from_the_X_to_the_Y',
                        'local_apsolu',
                        [
                            'starttime' => userdate($record->sessiontime, get_string('strftimedatetimefrom', 'local_apsolu')),
                            'endtime' => userdate(
                                $record->sessiontime + $record->duration,
                                get_string('strftimetime', 'local_apsolu')
                            ),
                        ]
                    );
                }

                $collisions[] = get_string(
                    'the_session_from_the_X_to_the_Y_collides_with_Z',
                    'local_apsolu',
                    [
                        'starttime' => userdate($sessiontime, get_string('strftimedatetimefrom', 'local_apsolu')),
                        'endtime' => userdate($sessiontime + $mdata->duration, get_string('strftimetime', 'local_apsolu')),
                        'sessions' => implode(', ', $conflicts),
                    ]
                );
                continue;
            }

            // Génère une nouvelle session pour l'afficher dans le message d'aperçu ou l'enregistrer.
            $session = new AttendanceSession();
            $session->name = get_string(
                'session_from_the_X_to_the_Y',
                'local_apsolu',
                [
                    'starttime' => userdate($sessiontime, get_string('strftimedatetimefrom', 'local_apsolu')),
                    'endtime' => userdate($sessiontime + $mdata->duration, get_string('strftimetime', 'local_apsolu')),
                ]
            );
            $session->sessiontime = $sessiontime;
            $session->duration = $mdata->duration;
            $session->locationid = $mdata->locationid;
            $session->courseid = $apsolucourse->id;

            $sessions[] = $session;
        }

        $n++;
        $startdate += DAYSECS;
        if ($n === 8) {
            $n = 1;
        }
    }

    if (isset($mdata->submitbutton) === true) {
        // Le bouton "enregistrer" a été utilisé.
        $count = 0;
        foreach ($sessions as $session) {
            $session->save();
            $count++;

            $items[] = $session->name;
        }

        if ($count === 0) {
            $notifications[] = $OUTPUT->notification(get_string('no_sessions_created', 'local_apsolu'), 'notifymessage');
        } else {
            $notifications[] = $OUTPUT->notification(get_string('X_sessions_created', 'local_apsolu', $count), 'notifysuccess');

            if (isset($mdata->notify_functional_contact) === true) {
                // Notifie le secrétariat.
                $subject = get_string('new_sessions_for_course_X', 'local_apsolu', $apsolucourse->fullname);
                $body = html_writer::tag('p', get_string(
                    'the_following_sessions_have_been_created_for_the_course_X',
                    'local_apsolu',
                    ['name' => $apsolucourse->fullname, 'url' => new moodle_url('/course/view.php', ['id' => $apsolucourse->id])]
                ));
                $body .= html_writer::alist($items, $attributes = [], $tag = 'ul');

                messaging::notify_functional_address($subject, $body);
            }
        }
    } else {
        // Le bouton "aperçu" a été utilisé.
        $count = count($sessions);
        if ($count === 0) {
            $notifications[] = $OUTPUT->notification(
                get_string('no_sessions_will_be_created_with_these_settings', 'local_apsolu'),
                'notifymessage'
            );
        } else {
            if ($count > 25) {
                $sessions = array_merge(array_slice($sessions, 0, 10), ['...'], array_slice($sessions, -10));
            }

            $items = [];
            foreach ($sessions as $session) {
                if ($session === '...') {
                    $items[] = '...';
                    continue;
                }

                $items[] = $session->name;
            }

            $message = html_writer::div(get_string('X_sessions_will_be_created', 'local_apsolu', $count));
            $message .= html_writer::alist($items, $attributes = [], $tag = 'ul');
            $notifications[] = $OUTPUT->notification($message, 'notifysuccess');
        }

        $count = count($collisions);
        if ($count !== 0) {
            if ($count > 25) {
                $collisions = array_merge(array_slice($sessions, 0, 10), ['...'], array_slice($sessions, -10));
            }

            $message = html_writer::tag('p', html_writer::tag('b', get_string('warning', 'local_apsolu')));
            $message .= html_writer::alist($collisions, $attributes = [], $tag = 'ul');
            $notifications[] = $OUTPUT->notification($message, 'notifyproblem');
        }
    }
}

// Display form.
$data->form = $mform->render();
