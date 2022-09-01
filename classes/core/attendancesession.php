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
 * Classe gérant les sessions/séances de cours.
 *
 * @package    local_apsolu
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_apsolu\core;

use context_course;
use stdClass;

/**
 * Classe gérant les sessions/séances de cours.
 *
 * @package    local_apsolu
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attendancesession extends record {
    /**
     * Nom de la table de référence en base de données.
     */
    const TABLENAME = 'apsolu_attendance_sessions';

    /** @var int|string Identifiant numérique de la session de cours. */
    public $id = 0;

    /** @var string $name Nom de la session de cours. */
    public $name = '';

    /** @var int|string $sessiontime Timestamp Unix représentant la date de début de la session. */
    public $sessiontime = '';

    /** @var int|string $courseid Identifiant du cours auquel est rattachée la session. */
    public $courseid = '';

    /** @var int|string $activityid Identifiant numérique de la catégorie du cours (activité sportive). TODO: champ à supprimer ? */
    public $activityid = '';

    /** @var int|string $timecreated Timestamp Unix de création de la session. */
    public $timecreated = '';

    /** @var int|string $timemodified Timestamp Unix de modification de la session. */
    public $timemodified = '';

    /** @var int|string $locationid Identifiant numérique du lieu de pratique. */
    public $locationid = '';

    /**
     * Supprime un objet en base de données.
     *
     * @throws dml_exception A DML specific exception is thrown for any errors.
     *
     * @return bool true.
     */
    public function delete() {
        global $DB;

        // Démarre une transaction, si ce n'est pas déjà fait.
        if ($DB->is_transaction_started() === false) {
            $transaction = $DB->start_delegated_transaction();
        }

        // Supprime les présences.
        $presences = attendancepresence::get_records(array('sessionid' => $this->id));
        foreach ($presences as $presence) {
            $presence->delete();
        }

        // Supprime l'objet en base de données.
        $DB->delete_records(self::TABLENAME, array('id' => $this->id));

        // Enregistre un évènement dans les logs.
        $event = \local_apsolu\event\session_deleted::create(array(
            'objectid' => $this->id,
            'context' => context_course::instance($this->courseid),
            ));
        $event->trigger();

        // Valide la transaction en cours.
        if (isset($transaction) === true) {
            $transaction->allow_commit();
        }

        return true;
    }

    /**
     * Indique si la session est passée.
     *
     * @return bool
     */
    public function is_expired() {
        $now = getdate();
        $today = make_timestamp($now['year'], $now['mon'], $now['mday'], $now['hours'], $now['minutes'], $now['seconds']);

        return $today > $this->sessiontime;
    }

    /**
     * Enregistre un objet en base de données.
     *
     * @throws dml_exception A DML specific exception is thrown for any errors.
     *
     * @param object|null $data  StdClass représentant l'objet à enregistrer.
     * @param object|null $mform Mform représentant un formulaire Moodle nécessaire à la gestion d'un champ de type editor.
     *
     * @return void
     */
    public function save(object $data = null, object $mform = null) {
        global $DB;

        if ($data !== null) {
            $this->set_vars($data);
        }

        // Démarre une transaction, si ce n'est pas déjà fait.
        if ($DB->is_transaction_started() === false) {
            $transaction = $DB->start_delegated_transaction();
        }

        if (empty($this->id) === true) {
            $eventclass = '\local_apsolu\event\session_created';
            $this->id = $DB->insert_record(get_called_class()::TABLENAME, $this);
        } else {
            $eventclass = '\local_apsolu\event\session_updated';
            $DB->update_record(get_called_class()::TABLENAME, $this);
        }

        // Enregistre un évènement dans les logs.
        $event = $eventclass::create(array(
            'objectid' => $this->id,
            'context' => context_course::instance($this->courseid),
            ));
        $event->trigger();

        // Valide la transaction en cours.
        if (isset($transaction) === true) {
            $transaction->allow_commit();
        }
    }

    /**
     * Définit le nom de la session.
     *
     * @param int $count Numéro de la session de cours.
     *
     * @return void
     */
    public function set_name(int $count) {
        $params = new stdClass();
        $params->count = $count;
        $params->strdatetime = userdate($this->sessiontime, get_string('strftimedaydatetime'));

        $this->name = get_string('session_:count:_of_the_:strdatetime:', 'local_apsolu', $params); // Exemple: Cours n°2 du mercredi 12 septembre à 18h30.
    }
}
