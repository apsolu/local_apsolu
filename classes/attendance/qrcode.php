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
use local_apsolu\core\record;
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
    const TABLENAME = 'apsolu_attendance_qrcode';

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
     * Retourne le paramétrage par défaut, configuré dans l'administration.
     *
     * @return stdClass
     */
    public static function get_default_settings(): stdClass {
        $settings = new stdClass();
        $settings->starttime = get_config('local_apsolu', 'qrcode_starttime');
        $settings->presentstatus = get_config('local_apsolu', 'qrcode_presentstatus');
        $settings->latetime = get_config('local_apsolu', 'qrcode_latetime');
        $settings->latestatus = get_config('local_apsolu', 'qrcode_latestatus');
        $settings->endtime = get_config('local_apsolu', 'qrcode_endtime');
        $settings->automark = get_config('local_apsolu', 'qrcode_automark');
        $settings->automarkstatus = get_config('local_apsolu', 'qrcode_automarkstatus');
        $settings->allowguests = get_config('local_apsolu', 'qrcode_allowguests');
        $settings->autologout = get_config('local_apsolu', 'qrcode_autologout');
        $settings->rotate = get_config('local_apsolu', 'qrcode_rotate');

        return $settings;
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
        }

        // Démarre une transaction, si ce n'est pas déjà fait.
        if ($DB->is_transaction_started() === false) {
            $transaction = $DB->start_delegated_transaction();
        }

        if (empty($this->id) === true) {
            // TODO: event.
            $DB->delete_records(get_called_class()::TABLENAME, ['sessionid' => $this->sessionid]);

            // TODO: event.
            $eventclass = '\local_apsolu\event\session_created';
            $this->id = $DB->insert_record(get_called_class()::TABLENAME, $this);
        } else {
            // TODO: event.
            $eventclass = '\local_apsolu\event\session_updated';
            $DB->update_record(get_called_class()::TABLENAME, $this);
        }

        // Enregistre un évènement dans les logs.
        // TODO: event.
        /*
        $event = $eventclass::create([
            'objectid' => $this->id,
            'context' => context_course::instance($this->courseid),
            ]);
        $event->trigger();
         */

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
        if (isset($this->settings) === false || is_object($this->settings) === false) {
            throw new coding_exception('$this->settings must be an object for ' . __METHOD__ . '.');
        }

        $this->settings->starttime = get_config('local_apsolu', 'qrcode_starttime');
        $this->settings->presentstatus = get_config('local_apsolu', 'qrcode_presentstatus');
        $this->settings->latetime = get_config('local_apsolu', 'qrcode_latetime');
        $this->settings->latestatus = get_config('local_apsolu', 'qrcode_latestatus');
        $this->settings->endtime = get_config('local_apsolu', 'qrcode_endtime');
        $this->settings->automark = get_config('local_apsolu', 'qrcode_automark');
        $this->settings->automarkstatus = get_config('local_apsolu', 'qrcode_automarkstatus');
        $this->settings->allowguests = get_config('local_apsolu', 'qrcode_allowguests');
        $this->settings->autologout = get_config('local_apsolu', 'qrcode_autologout');
        $this->settings->rotate = get_config('local_apsolu', 'qrcode_rotate');
    }
}
