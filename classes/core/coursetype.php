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

namespace local_apsolu\core;

/**
 * Classe gérant les types de format d'activités.
 *
 * @package   local_apsolu
 * @copyright 2026 Université Rennes 2
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class coursetype extends record {
    /**
     * Nom de la table de référence en base de données.
     */
    const TABLENAME = 'apsolu_courses_types';

    /** @var int|string Identifiant numérique du type d'activité. */
    public $id = 0;

    /** @var string $shortname Identifiant alphanumérique du type d'activité. */
    public $shortname = '';

    /** @var string $name Nom du type d'activité. */
    public $name = '';

    /** @var string $fullnametemplate Modèle du libellé de cours. */
    public $fullnametemplate = '';

    /** @var string $color Couleur en hexadécimal représentant le type d'activité. */
    public $color = '#f66151';

    /** @var int|string Ordre d'affichage parmi les autres types d'activité. */
    public $sortorder;

    /** @var array $fields Liste des champs personnalisés indexés par leur identifiant. */
    public $fields = [];

    /**
     * Supprime un objet en base de données.
     *
     * @throws moodle_exception A moodle exception is thrown when moodle course cannot be delete.
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

        // Supprime les associations dans la table apsolu_courses_fields.
        $DB->delete_records('apsolu_courses_fields', ['coursetypeid' => $this->id]);

        // Supprime l'objet en base de données.
        $DB->delete_records(self::TABLENAME, ['id' => $this->id]);

        // Valide la transaction en cours.
        if (isset($transaction) === true) {
            $transaction->allow_commit();
        }

        return true;
    }

    /**
     * Récupère la liste des champs personnalisés pour un type de format de cours.
     *
     * @param int|string $coursetypeid Identifiant du type de cours.
     *
     * @return array
     */
    public static function get_custom_fields($coursetypeid) {
        global $DB;

        $sql = "SELECT cf.*
                  FROM {customfield_field} cf
                  JOIN {apsolu_courses_fields} acf ON acf.customfieldid = cf.id
                 WHERE acf.coursetypeid = :coursetypeid
              ORDER BY cf.sortorder";
        return $DB->get_records_sql($sql, ['coursetypeid' => $coursetypeid]);
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

        // Enregistre le type de l'activité.
        if (empty($this->id) === true) {
            $this->sortorder = $DB->count_records(get_called_class()::TABLENAME);
            $this->id = $DB->insert_record(get_called_class()::TABLENAME, $this);
        } else {
            $DB->update_record(get_called_class()::TABLENAME, $this);
        }

        // Enregistre les champs personnalisés associés.
        $customfields = [];
        foreach (customfields::get_course_custom_fields() as $field) {
            $customfields[$field->id] = $field;

            if (in_array($field->shortname, ['category', 'type'], $strict = true) === true) {
                // Les champs "category" (activité) et "type" sont obligatoires.
                $data->fields[$field->shortname] = $field->id;
            }
        }

        $fields = $DB->get_records('apsolu_courses_fields', ['coursetypeid' => $this->id], $sort = '', 'customfieldid, id');

        foreach ($data->fields as $value) {
            $fieldid = $value;
            $showinadministration = 1;
            $showonpublicpages = 1;

            if (isset($value['fieldid']) === true) {
                $fieldid = $value['fieldid'];

                if (isset($value['admin']) === false) {
                    $showinadministration = 0;
                }

                if (isset($value['public']) === false) {
                    $showonpublicpages = 0;
                }
            }

            if (isset($customfields[$fieldid]) === false) {
                // Le fieldid n'est pas valide.
                continue;
            }

            // On enregistre l'association.
            $coursefield = new coursefield();
            if (isset($fields[$fieldid]) === true) {
                $coursefield->id = $fields[$fieldid]->id;
            }
            $coursefield->coursetypeid = $this->id;
            $coursefield->customfieldid = $fieldid;
            $coursefield->showinadministration = $showinadministration;
            $coursefield->showonpublicpages = $showonpublicpages;
            $coursefield->save();

            unset($fields[$fieldid]);
        }

        foreach ($fields as $field) {
            // On supprime les associations obsolètes.
            $DB->delete_records('apsolu_courses_fields', ['id' => $field->id]);
        }

        // Valide la transaction en cours.
        if (isset($transaction) === true) {
            $transaction->allow_commit();
        }
    }
}
