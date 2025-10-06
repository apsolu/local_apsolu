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

namespace local_apsolu\core\federation;

use local_apsolu\core\federation\course as FederationCourse;
use local_apsolu\core\record;
use stdClass;

/**
 * Classe gérant la correspondance entre le nom des activités FFSU et le nom des activités APSOLU.
 *
 * @package    local_apsolu
 * @copyright  2022 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity extends record {
    /**
     * Nom de la table de référence en base de données.
     */
    const TABLENAME = 'apsolu_federation_activities';

    /** @var int|string $id Identifiant numérique de la correspondance d'activités. */
    public $id = 0;

    /** @var string $code Code officiel de l'activité sportive côté FFSU. */
    public $code = '';

    /** @var string $name Nom officiel de l'activité sportive côté FFSU. */
    public $name = '';

    /** @var int|string $restriction Indique si le sport est à contrainte (1: oui, 0: non). */
    public $restriction = '';

    /** @var int|string|null $categoryid Identifiant numérique de la catégorie APSOLU décrivant
                                         une activité sportive (table {apsolu_categories}). */
    public $categoryid = null;

    /**
     * Retourne la liste des noms officiels des activités FFSU.
     *
     * @return array
     */
    public static function get_activity_data() {
        $data = [];
        $data[] = ['id' => 2, 'code' => 'ATHLETISME', 'name' => 'ATHLÉTISME', 'restriction' => 0];
        $data[] = ['id' => 3, 'code' => 'AVIRON', 'name' => 'AVIRON', 'restriction' => 0];
        $data[] = ['id' => 4, 'code' => 'BADMINTON', 'name' => 'BADMINTON', 'restriction' => 0];
        $data[] = ['id' => 5, 'code' => 'BASEBALL', 'name' => 'BASEBALL / SOFTBALL', 'restriction' => 0];
        $data[] = ['id' => 6, 'code' => 'BASKET', 'name' => 'BASKET-BALL / BASKET 3X3', 'restriction' => 0];
        $data[] = ['id' => 7, 'code' => 'BIATHLON', 'name' => 'BIATHLON', 'restriction' => 1];
        $data[] = ['id' => 8, 'code' => 'BOWLING', 'name' => 'BOWLING', 'restriction' => 0];
        $data[] = ['id' => 9, 'code' => 'BOXEEDU', 'name' => 'BOXE ÉDUCATIVE ASSAUT', 'restriction' => 0];
        $data[] = ['id' => 10, 'code' => 'BOXE', 'name' => 'BOXE COMBAT', 'restriction' => 1];
        $data[] = ['id' => 11, 'code' => 'BRIDGE', 'name' => 'BRIDGE', 'restriction' => 0];
        $data[] = ['id' => 12, 'code' => 'CANOE', 'name' => 'CANOË-KAYAK', 'restriction' => 0];
        $data[] = ['id' => 13, 'code' => 'CHEERLEADING', 'name' => 'CHEERLEADING', 'restriction' => 0];
        $data[] = ['id' => 14, 'code' => 'COURSE', 'name' => 'COURSE D\'ORIENTATION', 'restriction' => 0];
        $data[] = ['id' => 15, 'code' => 'CYCLISME', 'name' => 'CYCLISME', 'restriction' => 0];
        $data[] = ['id' => 16, 'code' => 'DANSE', 'name' => 'DANSE', 'restriction' => 0];
        $data[] = ['id' => 17, 'code' => 'ECHECS', 'name' => 'ÉCHECS', 'restriction' => 0];
        $data[] = ['id' => 18, 'code' => 'EQUITATION', 'name' => 'ÉQUITATION', 'restriction' => 0];
        $data[] = ['id' => 19, 'code' => 'ESCALADE', 'name' => 'ESCALADE', 'restriction' => 0];
        $data[] = ['id' => 20, 'code' => 'ESCRIME', 'name' => 'ESCRIME', 'restriction' => 0];
        $data[] = ['id' => 21, 'code' => 'FITNESS', 'name' => 'FITNESS', 'restriction' => 0];
        $data[] = ['id' => 22, 'code' => 'FOOT', 'name' => 'FOOTBALL / FUTSAL', 'restriction' => 0];
        $data[] = ['id' => 23, 'code' => 'FOOTAME', 'name' => 'FOOTBALL AMÉRICAIN', 'restriction' => 0];
        $data[] = ['id' => 24, 'code' => 'FORCE', 'name' => 'FORCE ATHLÉTIQUE', 'restriction' => 0];
        $data[] = ['id' => 25, 'code' => 'GOLF', 'name' => 'GOLF', 'restriction' => 0];
        $data[] = ['id' => 26, 'code' => 'GYM', 'name' => 'GYMNASTIQUE', 'restriction' => 0];
        $data[] = ['id' => 27, 'code' => 'HALTERO', 'name' => 'HALTÉROPHILIE / MUSCULATION', 'restriction' => 0];
        $data[] = ['id' => 28, 'code' => 'HANDBALL', 'name' => 'HANDBALL', 'restriction' => 0];
        $data[] = ['id' => 29, 'code' => 'HOCKEY', 'name' => 'HOCKEY', 'restriction' => 0];
        $data[] = ['id' => 30, 'code' => 'JUDO', 'name' => 'JUDO', 'restriction' => 0];
        $data[] = ['id' => 31, 'code' => 'KARATE', 'name' => 'KARATÉ', 'restriction' => 0];
        $data[] = ['id' => 33, 'code' => 'KICKBOXING', 'name' => 'KICKBOXING COMBAT', 'restriction' => 1];
        $data[] = ['id' => 34, 'code' => 'LUTTE', 'name' => 'LUTTE / SAMBO', 'restriction' => 0];
        $data[] = ['id' => 35, 'code' => 'NAGE', 'name' => 'NAGE AVEC PALMES', 'restriction' => 0];
        $data[] = ['id' => 36, 'code' => 'NATATION', 'name' => 'NATATION', 'restriction' => 0];
        $data[] = ['id' => 37, 'code' => 'PATINAGE', 'name' => 'PATINAGE', 'restriction' => 0];
        $data[] = ['id' => 38, 'code' => 'PELOTE', 'name' => 'PELOTE BASQUE', 'restriction' => 0];
        $data[] = ['id' => 39, 'code' => 'PENTATHLON', 'name' => 'PENTATHLON MODERNE AVEC TIR', 'restriction' => 1];
        $data[] = ['id' => 40, 'code' => 'PETANQUE', 'name' => 'PÉTANQUE', 'restriction' => 0];
        $data[] = ['id' => 41, 'code' => 'ROLLERHOCKEY', 'name' => 'ROLLER HOCKEY', 'restriction' => 0];
        $data[] = ['id' => 42, 'code' => 'RUGBY', 'name' => 'RUGBY', 'restriction' => 0];
        $data[] = ['id' => 43, 'code' => 'SAUVETAGE', 'name' => 'SAUVETAGE SPORTIF', 'restriction' => 0];
        $data[] = ['id' => 44, 'code' => 'SAVATEBOXE', 'name' => 'SAVATE BOXE FRANÇAISE', 'restriction' => 0];
        $data[] = ['id' => 45, 'code' => 'SKATEBOARD', 'name' => 'SKATEBOARD / TROTTINETTE', 'restriction' => 0];
        $data[] = ['id' => 46, 'code' => 'SKI', 'name' => 'SKI ALPIN', 'restriction' => 0];
        $data[] = ['id' => 47, 'code' => 'SQUASH', 'name' => 'SQUASH', 'restriction' => 0];
        $data[] = ['id' => 48, 'code' => 'SURF', 'name' => 'SURF / STAND UP PADDLE', 'restriction' => 0];
        $data[] = ['id' => 49, 'code' => 'TAEKWONDO', 'name' => 'TAEKWONDO POOMSAE', 'restriction' => 0];
        $data[] = ['id' => 50, 'code' => 'TENNISTABLE', 'name' => 'TENNIS DE TABLE', 'restriction' => 0];
        $data[] = ['id' => 51, 'code' => 'TENNIS', 'name' => 'TENNIS / PADEL', 'restriction' => 0];
        $data[] = ['id' => 52, 'code' => 'TIRARC', 'name' => 'TIR À L\'ARC', 'restriction' => 0];
        $data[] = ['id' => 53, 'code' => 'TIRSPORTIF', 'name' => 'TIR SPORTIF', 'restriction' => 1];
        $data[] = ['id' => 54, 'code' => 'TRIATHLON', 'name' => 'TRIATHLON ET DISCIPLINES ENCHAINÉES', 'restriction' => 0];
        $data[] = ['id' => 55, 'code' => 'ULTIMATE', 'name' => 'ULTIMATE', 'restriction' => 0];
        $data[] = ['id' => 56, 'code' => 'VOILE', 'name' => 'VOILE / KITE SURF', 'restriction' => 0];
        $data[] = ['id' => 57, 'code' => 'VOLLEY', 'name' => 'VOLLEY', 'restriction' => 0];
        $data[] = ['id' => 58, 'code' => 'WATERPOLO', 'name' => 'WATER-POLO', 'restriction' => 0];
        $data[] = ['id' => 60, 'code' => 'ESPORT', 'name' => 'ESPORT', 'restriction' => 0];
        $data[] = ['id' => 61, 'code' => 'KICKBOXINGLIGHT', 'name' => 'KICKBOXING LIGHT / PANCRACE', 'restriction' => 0];
        $data[] = ['id' => 62, 'code' => 'FLECHETTES', 'name' => 'FLÉCHETTES', 'restriction' => 0];
        $data[] = ['id' => 63, 'code' => 'TAEKWONDOCOMBAT', 'name' => 'TAEKWONDO COMBAT', 'restriction' => 1];
        $data[] = ['id' => 64, 'code' => 'RUGBYXIII', 'name' => 'RUGBY À XIII', 'restriction' => 0];
        $data[] = ['id' => 2501, 'code' => 'BABYFOOT', 'name' => 'BABY-FOOT', 'restriction' => 0];
        $data[] = ['id' => 2502, 'code' => 'BILLARD', 'name' => 'BILLARD', 'restriction' => 0];
        $data[] = ['id' => 2503, 'code' => 'PENTATHLON_LASER', 'name' => 'PENTATHLON MODERNE AVEC LASER RUN', 'restriction' => 0];
        $data[] = ['id' => 2504, 'code' => 'SKINORDIQUE', 'name' => 'SKI NORDIQUE', 'restriction' => 0];
        $data[] = ['id' => 2055, 'code' => 'SNOWBOARD', 'name' => 'SNOWBOARD', 'restriction' => 0];

        return $data;
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
    public function save(?object $data = null, ?object $mform = null) {
        global $DB;

        // Démarre une transaction, si ce n'est pas déjà fait.
        if ($DB->is_transaction_started() === false) {
            $transaction = $DB->start_delegated_transaction();
        }

        // Réinitialise le champ license de la table apsolu_courses si l'activité FFSU n'est plus associée à une catégorie APSOLU.
        if (empty($this->categoryid) === false) {
            if (isset($data->categoryid) === true && $data->categoryid !== $this->categoryid) {
                $sql = "UPDATE {apsolu_courses} SET license = 0 WHERE id IN (SELECT id FROM {course} WHERE category = :categoryid)";
                $DB->execute($sql, ['categoryid' => $this->categoryid]);
            }
        }

        // Enregistre l'objet en base de données.
        parent::save($data, $mform);

        // Valide la transaction en cours.
        if (isset($transaction) === true) {
            $transaction->allow_commit();
        }
    }

    /**
     * Synchronise la table 'apsolu_federation_activities' avec le référentiel FFSU.
     *
     * - ajoute les nouvelles activités
     * - met à jour les libellés
     * - génère les groupes
     *
     * @throws dml_exception A DML specific exception is thrown for any errors.
     *
     * @return void
     */
    public static function synchronize_database() {
        global $CFG, $DB;

        require_once($CFG->dirroot.'/group/lib.php');

        $federationcourse = new FederationCourse();
        $federationcourseid = $federationcourse->get_courseid();
        $federationgroups = [];
        if ($federationcourseid !== false) {
            $fields = 'name, id, courseid, timecreated, timemodified';
            $federationgroups = $DB->get_records('groups', ['courseid' => $federationcourseid], $sort = '', $fields);
        }

        $activities = $DB->get_records('apsolu_federation_activities');
        foreach (Activity::get_activity_data() as $data) {
            if (isset($activities[$data['id']]) !== false) {
                // Met à jour l'activité FFSU.
                $activity = $activities[$data['id']];

                if ($activity->name !== $data['name'] ||
                    $activity->restriction != $data['restriction']) {
                    // Met à jour l'enregistrement dans la table apsolu_federation_activities.
                    $sql = "UPDATE {apsolu_federation_activities}
                               SET code = :code, name = :name, restriction = :restriction
                             WHERE id = :id";
                    $DB->execute($sql, $data);

                    // Met à jour le nom du groupe dans le cours FFSU.
                    if (isset($federationgroups[$activity->name]) === true) {
                        $federationgroups[$activity->name]->name = $data['name'];
                        $federationgroups[$activity->name]->timemodified = time();
                        $DB->update_record('groups', $federationgroups[$activity->name]);
                    }
                }

                unset($activities[$data['id']]);
                continue;
            }

            // Insère une nouvelle activité FFSU.
            $sql = "INSERT INTO {apsolu_federation_activities} (id, code, name, restriction, categoryid)
                                                        VALUES (:id, :code, :name, :restriction, NULL)";
            $DB->execute($sql, $data);

            // Ajoute le groupe dans le cours FFSU.
            if ($federationcourseid !== false) {
                $group = new stdClass();
                $group->name = $data['name'];
                $group->courseid = $federationcourseid;
                $group->timecreated = time();
                $group->timemodified = $group->timecreated;
                groups_create_group($group);
            }
        }

        // Supprime les activités FFSU obsolètes.
        foreach ($activities as $activity) {
            $DB->delete_records('apsolu_federation_activities', ['id' => $activity->id]);

            if (isset($federationgroups[$activity->name]) === true) {
                groups_delete_group($federationgroups[$activity->name]->id);
            }
        }
    }
}
