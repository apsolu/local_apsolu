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
 * Classe gérant les périodes.
 *
 * @package    local_apsolu
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_apsolu\core;

/**
 * Classe gérant les périodes.
 *
 * @package    local_apsolu
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class period extends record {
    /**
     * Nom de la table de référence en base de données.
     */
    const TABLENAME = 'apsolu_periods';

    /** @var int|string Identifiant numérique de la période. */
    public $id = 0;

    /** @var string $name Nom unique. */
    public $name = '';

    /** @var string $generic_name Nom affiché. */
    public $generic_name = '';

    /** @var string $weeks Liste des semaines séparées par des virgules. */
    public $weeks = '';

    /**
     * Affiche une représentation textuelle de l'objet.
     *
     * @return string.
     */
    public function __tostring() {
        return $this->generic_name;
    }

    /**
     * Retourne la liste des sessions correspondant à la période.
     *
     * @param int $offset Nombre de secondes à ajouter pour obtenir la session à partir de la première heure de la semaine.
     * Exemples:
     *    - (12 * 60 * 60) retournera toutes les sessions de la période pour le lundi à 12h00.
     *    - ((3 * 24 * 60 * 60) + (16 * 60 * 60)) retournera toutes les sessions de la période pour le mercredi à 16h00.
     *
     * @return array Retourne un tableau d'objets attendancesession indéxé par le timestamp unix de la session.
     */
    public function get_sessions(int $offset) {
        $sessions = array();

        $holidays = array();
        foreach (holiday::get_records() as $holiday) {
            $key = strftime('%F', $holiday->day);
            $holidays[$key] = $holiday;
        }

        $weeks = explode(',', $this->weeks);
        foreach ($weeks as $week) {
            if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $week) !== 1) {
                continue;
            }

            // Calcule le timestamp.
            list($year, $month, $day) = explode('-', $week);
            $sessiontime = make_timestamp($year, $month, $day);
            $sessiontime += $offset;

            // Contrôle si il s'agit d'un jour férié.
            $key = strftime('%F', $sessiontime);
            if (isset($holidays[$key]) === true) {
                // On ignore les jours fériés.
                continue;
            }

            $session = new attendancesession();
            $session->sessiontime = $sessiontime;
            $sessions[$sessiontime] = $session;
        }

        return $sessions;
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

        // Démarre une transaction, si ce n'est pas déjà fait.
        if ($DB->is_transaction_started() === false) {
            $transaction = $DB->start_delegated_transaction();
        }

        if ($data !== null) {
            $this->set_vars($data);
        }

        if (empty($this->id) === true) {
            $this->id = $DB->insert_record(get_called_class()::TABLENAME, $this);
        } else {
            $oldperiod = new period();
            $oldperiod->load($this->id, $required = true);

            $DB->update_record(get_called_class()::TABLENAME, $this);

            // On régénère les sessions.
            if ($oldperiod->weeks !== $this->weeks) {
                $courses = course::get_records(array('periodid' => $this->id));
                foreach ($courses as $course) {
                    $course->set_sessions();
                }
            }
        }

        // Valide la transaction en cours.
        if (isset($transaction) === true) {
            $transaction->allow_commit();
        }
    }
}
