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

use core_customfield\api as CustomfieldAPI;
use local_apsolu\core\federation\course as federationcourse;
use local_apsolu\customfields\course as CustomfieldsCourse;
use stdClass;

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
    public function delete(): bool {
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
    public static function get_custom_fields($coursetypeid): array {
        global $DB;

        $sql = "SELECT cf.*
                  FROM {customfield_field} cf
                  JOIN {apsolu_courses_fields} acf ON acf.customfieldid = cf.id
                 WHERE acf.coursetypeid = :coursetypeid
              ORDER BY cf.sortorder";
        return $DB->get_records_sql($sql, ['coursetypeid' => $coursetypeid]);
    }

    /**
     * Initialise les types de cours.
     *
     * Cette méthode est réservée pour le processus d'installation.
     *
     * @return void
     */
    public static function initialize_course_type(): void {
        global $DB;

        // Initialise un type de format "cours".
        $params = ['name' => 'APSOLU', 'component' => 'core_course', 'area' => 'course', 'itemid' => 0];
        $category = $DB->get_record('customfield_category', $params, '*', MUST_EXIST);

        $fields = [];
        foreach (CustomfieldAPI::get_categories_with_fields('core_course', 'course', 0) as $customcategory) {
            if ($customcategory->get('id') != $category->id) {
                continue;
            }

            foreach ($customcategory->get_fields() as $field) {
                $fields[$field->get('shortname')] = $field->get('id');
            }

            break;
        }

        $record = new stdClass();
        $record->shortname = strtolower(get_string('course'));
        $record->name = get_string('course');
        // Modèle du libellé de cours: [activité] [jour] [heure début] [heure fin] [niveau].
        $record->fullnametemplate = sprintf(
            '%%%02d %%%02d %%%02d %%%02d',
            $fields['category'],
            $fields['weekday'],
            $fields['timerange'],
            $fields['skill']
        );
        $record->color = '#f66151';
        $record->sortorder = 1;
        if ($DB->get_record('apsolu_courses_types', ['shortname' => $record->shortname]) === false) {
            $DB->insert_record('apsolu_courses_types', $record);
        }

        // Associe les champs personnalisés au type de format "cours".
        $params = ['name' => 'APSOLU', 'component' => 'core_course', 'area' => 'course', 'itemid' => 0];
        $category = $DB->get_record('customfield_category', $params, '*', MUST_EXIST);
        $coursetype = $DB->get_record('apsolu_courses_types', ['shortname' => strtolower(get_string('course'))], '*', MUST_EXIST);

        $coursefields = [
            'type' => ['showinadministration' => 1, 'showonpublicpages' => 1],
            'category' => ['showinadministration' => 1, 'showonpublicpages' => 1],
            'skill' => ['showinadministration' => 1, 'showonpublicpages' => 1],
            'location' => ['showinadministration' => 1, 'showonpublicpages' => 1],
            'weekday' => ['showinadministration' => 1, 'showonpublicpages' => 1],
            'timerange' => ['showinadministration' => 1, 'showonpublicpages' => 1],
            'federation' => ['showinadministration' => 1, 'showonpublicpages' => 0],
            'on_homepage' => ['showinadministration' => 1, 'showonpublicpages' => 0],
            'period' => ['showinadministration' => 1, 'showonpublicpages' => 0],
            'show_policy' => ['showinadministration' => 0, 'showonpublicpages' => 0],
            'information' => ['showinadministration' => 0, 'showonpublicpages' => 0],
        ];

        $federationcourse = new FederationCourse();
        if (empty($federationcourse->get_courseid()) === true) {
            unset($coursefields['federation']);
        }

        foreach (CustomfieldAPI::get_categories_with_fields('core_course', 'course', 0) as $customcategory) {
            if ($customcategory->get('id') != $category->id) {
                continue;
            }

            foreach ($customcategory->get_fields() as $field) {
                if (isset($coursefields[$field->get('shortname')]) === false) {
                    continue;
                }

                $record = new stdClass();
                $record->coursetypeid = $coursetype->id;
                $record->customfieldid = $field->get('id');
                $record->showinadministration = $coursefields[$field->get('shortname')]['showinadministration'];
                $record->showonpublicpages = $coursefields[$field->get('shortname')]['showonpublicpages'];

                $conditions = ['coursetypeid' => $record->coursetypeid, 'customfieldid' => $record->customfieldid];
                if ($DB->get_record('apsolu_courses_fields', $conditions) !== false) {
                    continue;
                }
                $DB->insert_record('apsolu_courses_fields', $record);
            }
        }
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
    public function save(?object $data = null, ?object $mform = null): void {
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
        foreach (CustomfieldsCourse::get_course_custom_fields() as $field) {
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
