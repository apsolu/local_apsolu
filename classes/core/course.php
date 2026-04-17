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

// phpcs:disable moodle.NamingConventions.ValidFunctionName.LowercaseMethod
// phpcs:disable moodle.NamingConventions.ValidVariableName.MemberNameUnderscore

namespace local_apsolu\core;

use coding_exception;
use context_block;
use context_course;
use core_course\customfield\course_handler;
use core_course_category;
use core_php_time_limit;
use grade_category;
use grade_item;
use moodle_exception;
use moodle_url;
use stdClass;

/**
 * Classe gérant les créneaux horaires APSOLU (cours Moodle).
 *
 * @package    local_apsolu
 * @copyright  2019 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course extends record {
    /**
     * Nom de la table de référence en base de données.
     */
    const TABLENAME = 'apsolu_courses';

    /** @var int|string Identifiant numérique du créneau horaire. */
    public $id = 0;

    /** @var string $shortname Nom abrégé du créneau horaire. */
    public $shortname = '';

    /** @var string $fullname Nom complet du créneau horaire. */
    public $fullname = '';

    /** @var string $idnumber Identifiant du créneau horaire. */
    public $idnumber = '';

    /** @var int|string $categoryid Entier représentant l'identifiant de l'activité sportive. */
    public $category = '';

    /** @var int|string $visible Entier booléen déterminant si le cours est visible ou non. */
    public $visible = '';

    /** @var array $customfields Liste de champs personnalisés. */
    public $customfields = [];

    /**
     * Affiche une représentation textuelle de l'objet.
     *
     * @return string.
     */
    public function __toString() {
        return $this->fullname;
    }

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

        // This might take a while. Raise the execution time limit.
        core_php_time_limit::raise();

        // Supprime les sessions du cours.
        foreach ($this->get_sessions() as $session) {
            $session->delete();
        }

        // We do this here because it spits out feedback as it goes.
        $course = $DB->get_record('course', ['id' => $this->id], $fields = '*', MUST_EXIST);
        $result = delete_course($course, $showfeedback = false);

        if ($result === false) {
            $link = new moodle_url('/local/apsolu/courses/index.php', ['tab' => 'courses']);

            throw new moodle_exception('cannotdeletecategorycourse', $module = '', $link, $parameter = $this->fullname);
        }

        // Supprime l'objet en base de données.
        $DB->delete_records(self::TABLENAME, ['id' => $this->id]);

        // TODO: supprimer les notes.

        // Update course count in categories.
        fix_course_sortorder();

        // Valide la transaction en cours.
        if (isset($transaction) === true) {
            $transaction->allow_commit();
        }

        return true;
    }

    /**
     * Récupère les champs personnalisés d'un cours.
     *
     * @param int $courseid Identifiant du cours pour lequel on souhaite récupérer les champs personnalisés.
     *
     * @return array
     */
    public static function get_customfield_records(int $courseid = 0): array {
        $handler = course_handler::create();

        $customfields = [];
        foreach ($handler->get_instance_data($courseid, $returnall = true) as $customdata) {
            $shortname = $customdata->get_field()->get('shortname');
            $customfields[$shortname] = $customdata;
        }

        return $customfields;
    }

    /**
     * Retourne l'id du cours de FFSU.
     *
     * @return int|false Retourne l'id du cours de FFSU ou false si il n'est pas défini.
     */
    public static function get_federation_courseid() {
        debugging(
            'Use of ' . __METHOD__ . ' is deprecated. Use local_apsolu\core\federation\course::get_courseid().',
            DEBUG_DEVELOPER
        );

        $federationcourse = get_config('local_apsolu', 'federation_course');

        if (empty($federationcourse) === true) {
            return false;
        }

        return $federationcourse;
    }

    /**
     * Calcule le nom complet du cours à partir des paramètres passés à la méthode.
     *
     * @param int|string $category  Identifiant ou nom de la catégorie d'activité sportive.
     * @param string     $event     Libellé complémentaire / spécialité.
     * @param string     $weekday   Jour de la semaine en anglais.
     * @param string     $starttime Heure de début du cours.
     * @param string     $endtime   Heure de fin du cours.
     * @param int|string $skill     Identifiant ou libellé du niveau de pratique.
     *
     * @return string Nom abrégé unique.
     */
    public static function get_fullname(object $data) {
        global $DB;

        $coursetype = $DB->get_record('apsolu_courses_types', ['id' => $data->customfield_type]);
        $fullname = $coursetype->fullnametemplate;

        preg_match_all('/%[0-9]{2}/', $coursetype->fullnametemplate, $matches);

        $customfields = [];
        foreach ($matches[0] as $code) {
            $fieldid = intval(substr($code, 1));
            $customfields[$fieldid] = $code;
        }

        // Remplace les %0x par le texte correspondant à l'identifiant du champ.
        foreach (self::get_customfield_records($data->id) as $customfield) {
            if (isset($customfields[$customfield->get('fieldid')]) === false) {
                continue;
            }

            $fullname = str_replace($customfields[$customfield->get('fieldid')], $customfield->export_value(), $fullname);
        }

        return $fullname;
    }

    /**
     * Vérifie le nom abrégé du cours.
     *
     * Si le nom abrégé passé en paramètre est déjà utilisé, un nouveau nom abrégé est généré.
     *
     * @param int|string $courseid  Identifiant du cours.
     * @param string     $shortname Nom abrégé du cours à contrôler.
     *
     * @return string Nom abrégé unique.
     */
    public static function get_shortname($courseid, $shortname) {
        global $DB;

        // Contrôle que le nom abrégé est bien unique.
        while (true) {
            $course = $DB->get_record('course', ['shortname' => $shortname]);
            if ($course === false) {
                break;
            }

            if ($courseid === $course->id) {
                break;
            }

            $shortname .= '.';
        }

        return $shortname;
    }

    /**
     * Retourne l'index du jour de la semaine donné en paramètre.
     * ex: monday=1, tuesday=2, etc.
     *
     * @throws coding_exception A coding exception is thrown when $data parameter is null.
     *
     * @param string $day Jour de la semaine en anglais ou dans la langue locale de Moodle.
     *
     * @return int Numéro du jour de la semaine.
     */
    public static function get_numweekdays($day) {
        switch ($day) {
            case 'monday':
                return 1;
            case 'tuesday':
                return 2;
            case 'wednesday':
                return 3;
            case 'thursday':
                return 4;
            case 'friday':
                return 5;
            case 'saturday':
                return 6;
            case 'sunday':
                return 7;
        }

        switch ($day) {
            case get_string('monday', 'local_apsolu'):
                return 1;
            case get_string('tuesday', 'local_apsolu'):
                return 2;
            case get_string('wednesday', 'local_apsolu'):
                return 3;
            case get_string('thursday', 'local_apsolu'):
                return 4;
            case get_string('friday', 'local_apsolu'):
                return 5;
            case get_string('saturday', 'local_apsolu'):
                return 6;
            case get_string('sunday', 'local_apsolu'):
                return 7;
        }

        throw new coding_exception('Invalid value (' . json_encode(['day' => $day]) . ' for ' . __METHOD__ . '.');
    }

    /**
     * Recherche et instancie des objets depuis la base de données.
     *
     * @see Se référer à la documentation de la méthode get_records() de la variable globale $DB.
     * @param array|null $conditions Critères de sélection des objets.
     * @param string     $sort       Champs par lesquels s'effectue le tri.
     * @param string     $fields     Liste des champs retournés.
     * @param int        $limitfrom  Retourne les enregistrements à partir de n+$limitfrom.
     * @param int        $limitnum   Nombre total d'enregistrements retournés.
     *
     * @return array Un tableau d'objets instanciés.
     */
    public static function get_raw_records(
        ?array $conditions = null,
        string $sort = '',
        string $fields = '*',
        int $limitfrom = 0,
        int $limitnum = 0
    ) {
        global $DB;

        $records = [];

        $select = [];
        $select[] = 'c.id';
        $select[] = 'c.shortname';
        $select[] = 'c.fullname';
        $select[] = 'c.visible';
        $select[] = 'cc.id AS category';
        $select[] = 'cc.name AS categoryname';
        $select[] = 'ccc.id AS grouping';
        $select[] = 'ccc.name AS groupingname';

        $join = [];
        $params = [];

        $customfields = customfields::get_course_custom_fields();
        foreach ($customfields as $name => $customfield) {
            $id = $customfield->id;
            $leftjoin = sprintf('LEFT JOIN {customfield_data} d%s ON d%s.contextid = ctx.id
                AND d%s.fieldid = :field%s', $id, $id, $id, $id);

            switch ($customfield->shortname) {
                case 'skill':
                    $select[] = sprintf('d%s.intvalue AS skill', $id);
                    $select[] = 'sk.name AS skillname';
                    $join[] = $leftjoin;
                    $join[] = sprintf('LEFT JOIN {apsolu_skills} sk ON d%s.intvalue = sk.id', $id);
                    $params[sprintf('field%s', $id)] = $id;
                    break;
                case 'location':
                    $select[] = sprintf('d%s.intvalue AS location', $id);
                    $select[] = 'loc.name AS locationname';
                    $select[] = 'area.id AS area';
                    $select[] = 'area.name AS areaname';
                    $select[] = 'city.id AS city';
                    $select[] = 'city.name AS cityname';
                    $join[] = $leftjoin;
                    $join[] = sprintf('LEFT JOIN {apsolu_locations} loc ON d%s.intvalue = loc.id', $id);
                    $join[] = 'LEFT JOIN {apsolu_areas} area ON area.id = loc.areaid';
                    $join[] = 'LEFT JOIN {apsolu_cities} city ON city.id = area.cityid';
                    $params[sprintf('field%s', $id)] = $id;
                    break;
                case 'period':
                    $select[] = sprintf('d%s.intvalue AS period', $id);
                    $select[] = 'p.name AS periodname';
                    $join[] = $leftjoin;
                    $join[] = sprintf('LEFT JOIN {apsolu_periods} p ON d%s.intvalue = p.id', $id);
                    $params[sprintf('field%s', $id)] = $id;
                    break;
                case 'type':
                    $select[] = sprintf('d%s.intvalue AS type', $id);
                    $select[] = 't.name AS typename';
                    $join[] = sprintf('JOIN {customfield_data} d%s ON d%s.contextid = ctx.id
                        AND d%s.fieldid = :field%s', $id, $id, $id, $id);
                    $join[] = sprintf('JOIN {apsolu_courses_types} t ON d%s.intvalue = t.id', $id);
                    $params[sprintf('field%s', $id)] = $id;
                    break;
                case 'activity':
                case 'event':
                case 'weekday':
                case 'start_time':
                case 'end_time':
                case 'start_date':
                case 'end_date':
                case 'federation':
                case 'on_homepage':
                case 'showpolicy':
                case 'information':
                default:
                    $select[] = sprintf('d%s.value AS %s', $id, $customfield->shortname);
                    $join[] = sprintf('LEFT JOIN {customfield_data} d%s ON d%s.contextid = ctx.id
                        AND d%s.fieldid = :field%s', $id, $id, $id, $id);
                    $params[sprintf('field%s', $id)] = $id;
            }
        }

        if ($fields === '*') {
            $fields = implode(', ', $select);
        }

        $jointures = implode(' ', $join);

        $where = '';
        if (empty($conditions) === false) {
            $i = 0;
            $where = [];
            foreach ($conditions as $field => $value) {
                $namedparam = sprintf('where%s', $i);
                $where[] = sprintf('%s = :%s', $field, $namedparam);
                $params[$namedparam] = $value;
                $i++;
            }
            $where = sprintf('WHERE %s', implode(' AND ', $where));
        }

        $order = '';
        if (empty($sort) === false) {
            $order = sprintf('ORDER BY %s', $sort);
        }

        $sql = sprintf("SELECT %s
                          FROM {course} c
                          JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = 50
                          JOIN {course_categories} cc ON cc.id = c.category
                          JOIN {course_categories} ccc ON ccc.id = cc.parent %s %s %s", $fields, $jointures, $where, $order);

        foreach ($DB->get_records_sql($sql, $params) as $data) {
            $record = new course();
            $record->set_vars($data);
            $records[$record->id] = $record;
        }

        return $records;
    }

    /**
     * Recherche et instancie des objets depuis la base de données.
     *
     * @see Se référer à la documentation de la méthode get_records() de la variable globale $DB.
     * @param array|null $conditions Critères de sélection des objets.
     * @param string     $sort       Champs par lesquels s'effectue le tri.
     * @param string     $fields     Liste des champs retournés.
     * @param int        $limitfrom  Retourne les enregistrements à partir de n+$limitfrom.
     * @param int        $limitnum   Nombre total d'enregistrements retournés.
     *
     * @return array Un tableau d'objets instanciés.
     */
    public static function get_records(
        ?array $conditions = null,
        string $sort = '',
        string $fields = 'id, shortname, fullname, idnumber, category, visible',
        int $limitfrom = 0,
        int $limitnum = 0
    ) {
        global $DB;

        $classname = __CLASS__;

        $records = [];
        foreach ($DB->get_records('course', $conditions, $sort, $fields, $limitfrom, $limitnum) as $data) {
            // Récupère les champs personnalisés de cours.
            $data->customfields = self::get_customfield_records($data->id);

            $record = new $classname();
            $record->set_vars($data);
            $records[$record->id] = $record;
        }

        return $records;
    }

    /**
     * Recherche et instancie des objets depuis la base de données.
     *
     * @see Se référer à la documentation de la méthode get_records() de la variable globale $DB.
     * @param array|null $conditions Critères de sélection des objets.
     * @param string     $sort       Champs par lesquels s'effectue le tri.
     * @param string     $fields     Liste des champs retournés.
     * @param int        $limitfrom  Retourne les enregistrements à partir de n+$limitfrom.
     * @param int        $limitnum   Nombre total d'enregistrements retournés.
     *
     * @return array Un tableau d'objets instanciés.
     */
    public static function get_records_by_course_type(int $coursetypeid): array {
        global $DB;

        $classname = __CLASS__;
        $handler = course_handler::create();

        $records = [];

        $sql = "SELECT c.id, c.shortname, c.fullname, c.idnumber, c.category, c.visible
                  FROM {course} c
                  JOIN {customfield_data} cd ON c.id = cd.instanceid
                  JOIN {customfield_field} cf ON cf.id = cd.fieldid
                 WHERE cf.type = 'apsolu_course_type'
                   AND cd.intvalue = :coursetypeid
              ORDER BY c.fullname";

        foreach ($DB->get_records_sql($sql, ['coursetypeid' => $coursetypeid]) as $data) {
            // Récupère les champs personnalisés de cours.
            $data->customfields = self::get_customfield_records($data->id);

            $record = new $classname();
            $record->set_vars($data);
            $records[$record->id] = $record;
        }

        return $records;
    }

    /**
     * Retourne le nombre de secondes écoulées entre le début de la semaine et le début du cours.
     *
     * @throws coding_exception Lève une exception lorsque la date de début du cours est mal formatée.
     *
     * @return int
     */
    public function get_session_offset() {
        if (preg_match('/^[0-9][0-9]:[0-9][0-9]$/', $this->starttime) !== 1) {
            throw new coding_exception('Unexpected value of starttime (' . $this->starttime . ') for ' . __METHOD__ . '.');
        }

        [$hours, $minutes] = explode(':', $this->starttime);

        $offset = 0;
        $offset += (($this->numweekday - 1) * 24 * 60 * 60);
        $offset += ($hours * 60 * 60);
        $offset += ($minutes * 60);

        return $offset;
    }

    /**
     * Retourne les sessions du cours.
     *
     * @return array Retourne un tableau d'objets attendancesession.
     */
    public function get_sessions() {
        return attendancesession::get_records(['courseid' => $this->id]);
    }

    /**
     * Retourne un tableau trié par jour de la semaine et indéxé par le nom du jour en anglais.
     *
     * @return array Tableau trié et indéxé par le jour de la semaine en anglais.
     */
    public static function get_weekdays() {
        $weekdays = [];
        $weekdays['monday'] = get_string('monday', 'local_apsolu');
        $weekdays['tuesday'] = get_string('tuesday', 'local_apsolu');
        $weekdays['wednesday'] = get_string('wednesday', 'local_apsolu');
        $weekdays['thursday'] = get_string('thursday', 'local_apsolu');
        $weekdays['friday'] = get_string('friday', 'local_apsolu');
        $weekdays['saturday'] = get_string('saturday', 'local_apsolu');
        $weekdays['sunday'] = get_string('sunday', 'local_apsolu');

        return $weekdays;
    }

    /**
     * Retourne la durée d'un cours en secondes à partir de son heure de début et son heure de fin.
     *
     * @param string $starttime Date de début du cours au format hh:mm.
     * @param string $endtime   Date de fin du cours au format hh:mm.
     *
     * @return int|false Durée en secondes du cours, ou false si une erreur est détectée.
     */
    public static function getDuration(string $starttime, string $endtime) {
        $times = [];
        $times['starttime'] = explode(':', $starttime);
        $times['endtime'] = explode(':', $endtime);

        foreach ($times as $key => $values) {
            if (count($values) !== 2) {
                debugging(__METHOD__ . ': 2 valeurs attendues pour la variable $' . $key, $level = DEBUG_DEVELOPER);

                return false;
            }

            if (ctype_digit($values[0]) === false || ctype_digit($values[1]) === false) {
                debugging(__METHOD__ . ': 2 entiers attendus pour la variable $' . $key, $level = DEBUG_DEVELOPER);

                return false;
            }

            $times[$key] = $values[0] * 60 * 60 + $values[1] * 60;
        }

        $duration = $times['endtime'] - $times['starttime'];

        if ($duration <= 0) {
            debugging(__METHOD__ . ': valeur nulle ou négative pour la variable $duration', $level = DEBUG_DEVELOPER);

            return false;
        }

        return $duration;
    }

    /**
     * Charge un objet à partir de son identifiant.
     *
     * @param int|string $recordid Identifiant de l'objet à charger.
     * @param bool       $required Si true, lève une exception lorsque l'objet n'existe pas.
                                   Valeur par défaut: false (pas d'exception levée).
     *
     * @return void
     */
    public function load($recordid, bool $required = false) {
        global $DB;

        $strictness = IGNORE_MISSING;
        if ($required) {
            $strictness = MUST_EXIST;
        }

        $record = $DB->get_record(
            'course',
            ['id' => $recordid],
            'id, shortname, fullname, idnumber, category, visible',
            $strictness
        );

        if ($record === false) {
            return;
        }

        // Récupère les champs personnalisés de cours.
        $record->customfields = self::get_customfield_records($record->id);

        $this->set_vars($record);
    }

    /**
     * Enregistre un objet en base de données.
     *
     * @throws coding_exception A coding exception is thrown when $data parameter is null.
     * @throws dml_exception A DML specific exception is thrown for any errors.
     *
     * @param object|null $data  StdClass représentant l'objet à enregistrer.
     * @param object|null $mform Mform représentant un formulaire Moodle nécessaire à la gestion d'un champ de type editor.
     *
     * @return void
     */
    public function save(?object $data = null, ?object $mform = null) {
        global $DB;

        if ($data === null) {
            throw new coding_exception('$data parameter cannot be null for ' . __METHOD__ . '.');
        }

        $this->set_vars($data);

        /*
        $this->numweekday = null;
        if (empty($this->weekday) === false) {
            $this->numweekday = self::get_numweekdays($this->weekday);
        }
        */

        // TODO: Set fullname.
        // $data->str_category = 'TODO(cat)';
        // $data->str_skill = 'TODO(skill)';
        $this->fullname = self::get_fullname($data);

        // TODO: Set shortname.
        $this->shortname = self::get_shortname($this->id, $this->fullname);

        if (isset($data->idnumber) === true) {
            $this->idnumber = $data->idnumber;
        }

        // TODO: controler que endtime n'est pas inférieur à startime.

        // Démarre une transaction, si ce n'est pas déjà fait.
        if ($DB->is_transaction_started() === false) {
            $transaction = $DB->start_delegated_transaction();
        }

        if (empty($this->id) === true) {
            // Créé le cours.
            $coursedata = $data;
            $coursedata->fullname = $this->fullname;
            $coursedata->shortname = $this->shortname;
            $coursedata->category = $data->customfield_category;

            $newcourse = create_course($coursedata);
            $this->id = $newcourse->id;

            // Ajoute une méthode d'inscription manuelle.
            $instance = $DB->get_record('enrol', ['enrol' => 'manual', 'courseid' => $this->id]);
            if ($instance === false) {
                $plugin = enrol_get_plugin('manual');

                $fields = $plugin->get_instance_defaults();

                $instance = new stdClass();
                $instance->id = $plugin->add_instance($newcourse, $fields);
            }

            // Ajoute le bloc apsolu_course.
            $blocktype = 'apsolu_course';
            $context = context_course::instance($this->id, MUST_EXIST);

            $blockinstance = new stdClass();
            $blockinstance->blockname = $blocktype;
            $blockinstance->parentcontextid = $context->id;
            $blockinstance->showinsubcontexts = 0;
            $blockinstance->pagetypepattern = 'course-view-*';
            $blockinstance->subpagepattern = null;
            $blockinstance->defaultregion = 'side-pre'; // Dans la colonne de gauche.
            $blockinstance->defaultweight = -1; // Avant le bloc "Paramètres du cours".
            $blockinstance->configdata = '';
            $blockinstance->timecreated = time();
            $blockinstance->timemodified = time();
            $blockinstance->id = $DB->insert_record('block_instances', $blockinstance);

            // Ensure the block context is created.
            context_block::instance($blockinstance->id);

            // If the new instance was created, allow it to do additional setup.
            $block = block_instance($blocktype, $blockinstance);
            $block->instance_create();

            // Génére les sessions de cours.
            if (empty($this->periodid) === false) {
                $this->set_sessions();
            }
        } else {
            $oldcourse = new course();
            $oldcourse->load($this->id, $required = true);

            $coursedata = $data;
            $coursedata->fullname = $this->fullname;
            $coursedata->shortname = $this->shortname;
            $coursedata->category = $data->customfield_category;

            update_course($coursedata);

            /*
            if ($this->typeid === '1') {
                // TODO: hack pour maintenir la compatibilité avec la table historique.
                $DB->update_record(self::TABLENAME, $this);
            }
             */

            // TODO: Vérifie que les informations liées aux sessions de cours n'ont pas été modifiées.
            if (false && empty($this->periodid) === false) {
                $sessionfields = ['locationid', 'weekday', 'numweekday', 'starttime', 'endtime', 'periodid'];
                foreach ($sessionfields as $field) {
                    if ($oldcourse->{$field} == $this->{$field}) {
                        continue;
                    }

                    // Génère les sessions de cours.
                    $this->set_sessions();
                    break;

                }
            }
        }

        // Trie les cours de la catégorie.
        $category = core_course_category::get((int) $data->customfield_category);
        if ($category->can_resort_courses()) {
            \core_course\management\helper::action_category_resort_courses($category, $sort = 'fullname');
        }

        // Valide la transaction en cours.
        if (isset($transaction) === true) {
            $transaction->allow_commit();
        }
    }

    /**
     * Génère ou met à jour le carnet de notes.
     *
     * @param bool $rescalegrades Témoin indiquant si la note doit être ajustée lorsque la note maximale attendue change.
     *
     * @return void
     */
    public function set_gradebook($rescalegrades = false) {
        global $DB;

        // Récupère tous les éléments de notation prévus pour ce cours.
        $sql = "SELECT agi.*
                  FROM {apsolu_grade_items} agi
                  JOIN {enrol} e ON agi.calendarid = e.customchar1
                 WHERE e.enrol = 'select'
                   AND e.courseid = :courseid";
        $coursegradeitems = $DB->get_records_sql($sql, ['courseid' => $this->id]);

        // Récupère tous les élements de notation actuels de ce cours.
        $gradeitems = grade_item::fetch_all(['courseid' => $this->id, 'iteminfo' => gradebook::NAME]);

        if ($gradeitems === false) {
            $gradeitems = [];
        }

        foreach ($gradeitems as $item) {
            [$apsolugradeid, $itemname] = explode('-', $item->itemname, 2);

            if (isset($coursegradeitems[$apsolugradeid]) === false) {
                // Supprime un élément de notation obsolète.
                $item->delete();
                continue;
            }

            $gradeitem = $coursegradeitems[$apsolugradeid];
            unset($coursegradeitems[$apsolugradeid]);

            // Met à jour le nom de l'élément de notation.
            $item->iteminfo = 'APSOLU';
            $item->itemname = $gradeitem->id . '-' . $gradeitem->name;
            $item->set_hidden($gradeitem->publicationdate);
            $oldmax = $item->grademax;
            $item->grademax = $gradeitem->grademax;
            if ($rescalegrades === true) {
                $oldmin = 0;
                $newmin = 0;
                $newmax = $item->grademax;
                $source = gradebook::SOURCE;
                $item->rescale_grades_keep_percentage($oldmin, $oldmax, $newmin, $newmax, $source);
            }
            $item->update();
        }

        // Enregistre les nouveaux éléments dans le carnet de notes.
        $rootcategory = grade_category::fetch_course_category($this->id);

        foreach ($coursegradeitems as $item) {
            $gradeitem = new grade_item(['id' => 0, 'courseid' => $this->id]);
            $gradeitem->iteminfo = gradebook::NAME;
            $gradeitem->itemname = $item->id . '-' . $item->name;
            $gradeitem->itemtype = 'manual';
            $gradeitem->categoryid = $rootcategory->id;
            $gradeitem->hidden = $gradeitem->hidden;
            $gradeitem->grademax = $gradeitem->grademax;
            $gradeitem->id = $gradeitem->insert();
        }
    }

    /**
     * Génère les sessions du cours.
     *
     * @return void
     */
    public function set_sessions() {
        // Récupère le nombre de secondes entre le début de la semaine et la date de début du cours.
        $offset = $this->get_session_offset();

        $sessions = [];

        // Récupère les sessions prévues pour cette période.
        $period = new period();
        $period->load($this->periodid);
        foreach ($period->get_sessions($offset) as $sessiontime => $session) {
            if ($session->has_started() === true && defined('APSOLU_DEMO') === false) {
                // On retire de la sélection toutes les sessions déjà passées.
                continue;
            }

            $sessions[$sessiontime] = $session;
        }

        // Récupère les sessions actuellement définies en base de données pour ce cours.
        foreach ($this->get_sessions() as $sessionid => $session) {
            $sessiontime = $session->sessiontime;

            if (isset($sessions[$sessiontime]) === true) {
                // La session existe déjà en base de données.
                $sessions[$sessiontime] = $session;
                continue;
            }

            if ($session->has_started() === true) {
                // On conserve toutes les sessions passées.
                $sessions[$sessiontime] = $session;
                continue;
            }

            // Toutes les autres sessions, on les supprime.
            $session->delete();
        }

        // On procède à l'enregistrement des nouvelles sessions.
        $count = 0;
        ksort($sessions);
        foreach ($sessions as $sessiontime => $session) {
            $count++;

            if ($session->has_started() === true && defined('APSOLU_DEMO') === false) {
                // On ne modifie jamais les sessions passées.
                continue;
            }

            $sessionid = $session->id;
            $sessionname = $session->name;

            $session->set_name($count);

            if ($sessionid !== 0 && $sessionname === $session->name && $session->locationid === $this->locationid) {
                // La session n'est pas nouvelle, le nom et le lieu sont identiques.
                continue;
            }

            $session->courseid = $this->id;
            $session->activityid = $this->category; // TODO: supprimer ce champ. Note: category ne semble pas être défini,
                                                    // provoquant une initialisation à 0 en base de données.
            $session->locationid = $this->locationid;
            if ($sessionid === 0) {
                $session->timecreated = time();
            }
            $session->timemodified = time();
            $session->save();
        }
    }

    /**
     * Trie les cours par ordre alphabétique.
     *
     * @param array $courses
     *
     * @return array
     */
    public static function sort(array $courses): array {
        uasort($courses, function ($a, $b) {
            $fields = [];
            $fields[] = 'category'; // Trie par activité.
            $fields[] = 'weekday'; // Trie par jour de la semaine.
            $fields[] = 'start_date'; // Trie par date de début.
            $fields[] = 'start_time'; // Trie par heure de début.
            $fields[] = 'location'; // Trie par lieu.
            $fields[] = 'skill'; // Trie par niveau.

            foreach ($fields as $field) {
                if (isset($a->customfields[$field], $b->customfields[$field]) === false) {
                    continue;
                }

                $return = strcmp($a->customfields[$field]->export_value(), $b->customfields[$field]->export_value());
                if ($return !== 0) {
                    return $return;
                }
            }

            return 0;
        });

        return $courses;
    }

    /**
     * Change la visibilité du cours.
     *
     * La valeur de retour 1 indique que le cours est visible à la fin de l'exécution de la fonction.
     * La valeur de retour 0 indique que le cours est masqué.
     *
     * @param int|string $courseid Identifiant du cours.
     *
     * @return int Retourne un entier représentant la visibilité du cours
     */
    public static function toggle_visibility($courseid) {
        $course = new \core_course_list_element(get_course($courseid));

        if (empty($course->visible) === true) {
            \core_course\management\helper::action_course_show($course);
            return 1;
        }

        \core_course\management\helper::action_course_hide($course);
        return 0;
    }
}
