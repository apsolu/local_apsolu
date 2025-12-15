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
use context_course;
use core_useragent;
use local_apsolu\core\attendancepresence;
use local_apsolu\core\attendancesession;
use local_apsolu\core\attendance\status as attendancestatus;
use local_apsolu\core\record;
use local_apsolu\event\qrcode_created;
use local_apsolu\event\qrcode_deleted;
use local_apsolu\event\qrcode_updated;
use moodle_exception;
use stdClass;

/**
 * Classe gérant les QR code pour la prise de présences.
 *
 * @package    local_apsolu
 * @copyright  2025 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qrcode extends record {
    /**
     * Nom de la table de référence en base de données.
     */
    const TABLENAME = 'apsolu_attendance_qrcodes';

    /** @var int|string Identifiant numérique de la session de cours. */
    public $id = 0;

    /** @var string $keycode Code aléatoire unique servant d'identifiant pour la session. */
    public $keycode = '';

    /** @var string $settings Paramètrage du QR code au format JSON. */
    public $settings = '';

    /** @var int|string $timecreated Timestamp Unix de création du QR code. */
    public $timecreated = '';

    /** @var int|string $sessionid Identifiant numérique de la session. */
    public $sessionid = '';

    /**
     * Supprime un objet en base de données.
     *
     * @throws dml_exception A DML specific exception is thrown for any errors.
     *
     * @return bool true.
     */
    public function delete() {
        global $DB;

        // Supprime l'objet en base de données.
        $DB->delete_records(get_called_class()::TABLENAME, ['id' => $this->id]);

        // Génère un évènement de suppression.
        $session = attendancesession::get_record(['id' => $this->sessionid], '*', MUST_EXIST);

        $event = qrcode_deleted::create([
            'objectid' => $this->id,
            'context' => context_course::instance($session->courseid),
            ]);
        $event->trigger();

        return true;
    }

    /**
     * Génère une valeur pour le champ keycode.
     *
     * @return string
     */
    public static function generate_keycode(): string {
        return base64_encode(uniqid(time(), $moreentropy = true));
    }

    /**
     * Retourne le paramétrage par défaut, configuré dans l'administration.
     *
     * @return stdClass
     */
    public static function get_default_settings(): stdClass {
        $settings = new stdClass();

        foreach (self::get_json_setting_names() as $name) {
            $settings->$name = get_config('local_apsolu', sprintf('qrcode_%s', $name));
        }

        return $settings;
    }

    /**
     * Retourne la liste des noms des paramètres JSON.
     *
     * @return array
     */
    public static function get_json_setting_names(): array {
        $names = [];
        $names[] = 'starttime';
        $names[] = 'presentstatus';
        $names[] = 'latetimeenabled';
        $names[] = 'latetime';
        $names[] = 'latestatus';
        $names[] = 'endtimeenabled';
        $names[] = 'endtime';
        $names[] = 'automarkenabled';
        $names[] = 'automarkstatus';
        $names[] = 'automarktime';
        $names[] = 'allowguests';
        $names[] = 'autologout';
        $names[] = 'rotate';

        return $names;
    }

    /**
     * Enregistre un objet en base de données.
     *
     * @throws coding_exception Une erreur coding_exception est levée si la variable $data->settings n'est pas un objet.
     * @throws dml_exception A DML specific exception is thrown for any errors.
     *
     * @param object|null $data  StdClass représentant l'objet à enregistrer.
     * @param object|null $mform Mform représentant un formulaire Moodle nécessaire à la gestion d'un champ de type editor.
     *
     * @return void
     */
    public function save(?object $data = null, ?object $mform = null) {
        global $DB;

        if ($data !== null) {
            if (isset($data->settings) === false || is_object($data->settings) === false) {
                throw new coding_exception('$data->settings must be an object for ' . __METHOD__ . '.');
            }

            $data->settings = json_encode($data->settings);
            $this->set_vars($data);
        } else if (is_object($this->settings) === true) {
            $this->settings = json_encode($this->settings);
        }

        // Démarre une transaction, si ce n'est pas déjà fait.
        if ($DB->is_transaction_started() === false) {
            $transaction = $DB->start_delegated_transaction();
        }

        $session = attendancesession::get_record(['id' => $this->sessionid], '*', MUST_EXIST);
        if (empty($this->id) === true) {
            // Supprime tous les QR codes qui seraient associés à cette session de cours.
            $qrcodes = self::get_records(['sessionid' => $this->sessionid]);
            foreach ($qrcodes as $qrcode) {
                $qrcode->delete();
            }

            $eventclass = '\local_apsolu\event\qrcode_created';
            $this->timecreated = time();
            $this->id = $DB->insert_record(get_called_class()::TABLENAME, $this);
        } else {
            $eventclass = '\local_apsolu\event\qrcode_updated';
            $DB->update_record(get_called_class()::TABLENAME, $this);
        }

        // Enregistre un évènement dans les logs.
        $event = $eventclass::create([
            'objectid' => $this->id,
            'context' => context_course::instance($session->courseid),
            ]);
        $event->trigger();

        // Valide la transaction en cours.
        if (isset($transaction) === true) {
            $transaction->allow_commit();
        }
    }

    /**
     * Définit le paramétrage par défaut, configuré dans l'administration.
     *
     * @return void
     */
    public function set_default_settings(): void {
        if ($this->settings === '') {
            $this->settings = new stdClass();
        } else if (is_string($this->settings) === true) {
            $this->settings = json_decode($this->settings);
        }

        if (is_object($this->settings) === false) {
            throw new coding_exception('$this->settings must be an object for ' . __METHOD__ . '.');
        }

        foreach (self::get_json_setting_names() as $name) {
            $this->settings->$name = get_config('local_apsolu', sprintf('qrcode_%s', $name));
        }
    }

    /**
     * Marque une présence et retourne le message de confirmation.
     *
     * @param attendancesession $session Session pour laquelle la présence doit être marquée.
     *
     * @return string
     */
    public function sign(attendancesession $session): string {
        global $USER;

        $now = time();

        // Contrôle que l'utilisateur n'a pas déjà été noté présent.
        $presence = attendancepresence::get_record(['sessionid' => $session->id, 'studentid' => $USER->id]);
        if ($presence !== false) {
            $status = attendancestatus::get_record(['id' => $presence->statusid], '*', MUST_EXIST);

            $a = new stdClass();
            $a->status = $status->longlabel;
            $a->datetime = userdate($presence->timecreated, get_string('strftimedatetime', 'local_apsolu'));
            throw new moodle_exception(
                'your_participation_has_already_been_recorded_X_for_this_session_the_X',
                'local_apsolu',
                $link = '',
                $a
            );
        }

        // Si le QR code n'autorise pas les non-inscrits, contrôle que l'utilisateur est bien inscrit au cours.
        $coursecontext = context_course::instance($session->courseid, MUST_EXIST);
        if (
            empty($this->settings->allowguests) === true &&
            is_enrolled($coursecontext, $user = null, $withcapability = '', $onlyactive = true) === false
        ) {
            throw new moodle_exception('you_do_not_have_any_active_enrolments_for_this_course', 'local_apsolu');
        }

        // Détermine si la prise de présences a débuté.
        if ($now < $session->sessiontime - $this->settings->starttime) {
            throw new moodle_exception('the_attendance_recording_for_this_session_has_not_started_yet', 'local_apsolu');
        }

        // Détermine si la prise de présences n'est pas expirée.
        $endtime = $session->get_duration();

        if (empty($this->settings->endtimeenabled) === false) {
            // Utilise la durée définie par le paramétrage d'arrêt des prises de présences.
            $endtime = $this->settings->endtime;
        }

        if ($now > $session->sessiontime + $endtime) {
            throw new moodle_exception('the_attendance_recording_for_this_session_is_over', 'local_apsolu');
        }

        // Définit le type de présence à appliquer.
        $statusid = $this->settings->presentstatus;

        // Vérifie si il ne faut pas appliquer le second type de présence.
        if (empty($this->settings->latetimeenabled) === false && $now > $session->sessiontime + $this->settings->latetime) {
            $statusid = $this->settings->latestatus;
        }

        // Enregistre la présence.
        $status = attendancestatus::get_record(['id' => $statusid], '*', MUST_EXIST);

        $presence = new attendancepresence();
        $presence->studentid = $USER->id;
        $presence->teacherid = $USER->id;
        $presence->statusid = $statusid;
        $presence->timecreated = $now;
        $presence->timemodified = $now;
        $presence->sessionid = $session->id;

        $presence->fingerprint = hash('sha256', getremoteaddr() . core_useragent::get_user_agent_string());
        $presence->save();

        $a = new stdClass();
        $a->status = $status->longlabel;
        $a->time = userdate($presence->timecreated, get_string('strftimetime'));

        return get_string('your_participation_has_been_recorded_X_for_this_session_the_X', 'local_apsolu', $a);
    }
}
