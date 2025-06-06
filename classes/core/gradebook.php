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

use context_course;
use context_system;
use csv_export_writer;
use Exception;
use grade_grade;
use grade_item;
use MoodleExcelFormat;
use MoodleExcelWorkbook;
use PhpOffice\PhpSpreadsheet\Style\Border;
use stdClass;

/**
 * Classe gérant les carnets de notes.
 *
 * @package    local_apsolu
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gradebook {
    /**
     * Valeur utilisée pour cacher un élément de notation.
     */
    const GRADE_ITEM_HIDDEN = '1';

    /**
     * Valeur utilisée pour rendre visible un élément de notation.
     */
    const GRADE_ITEM_VISIBLE = '0';

    /**
     * Nom de la catégorie contenant les éléments d'évaluation d'APSOLU dans chaque cours.
     */
    const NAME = 'APSOLU';

    /**
     * Nom utilisé dans le champ source pour l'historique des modifications.
     */
    const SOURCE = 'apsolu-gradebook';

    /** @var array $caneditgrades Contient un tableau indexé par identifiant de cours, indiquant si l'utilisateur pour
                                  éditer les notes du cours. */
    public static $caneditgrades = [];

    /**
     * Indique si l'utilisateur est autorisé à éditer les notes pour un cours donné.
     *
     * @param int|string $courseid       Identifiant du cours.
     * @param bool       $calendarlocked Indique si l'édition des notes n'est pas hors-délai par rapport au calendrier.
     *
     * @return bool True si l'utilisateur peut éditer la note de ce cours, false si il n'a pas les droits.
     */
    public static function can_edit_grades($courseid, bool $calendarlocked) {
        if (isset(self::$caneditgrades[$courseid]) === true) {
            return self::$caneditgrades[$courseid];
        }

        self::$caneditgrades[$courseid] = has_capability('local/apsolu:editgradesafterdeadline', context_system::instance());

        if (self::$caneditgrades[$courseid] === true) {
            // L'utilisateur peut éditer les notes n'importe quand.
            return self::$caneditgrades[$courseid];
        }

        self::$caneditgrades[$courseid] = has_capability('local/apsolu:editgrades', context_course::instance($courseid));
        if (self::$caneditgrades[$courseid] === false) {
            // L'utilisateur n'a pas le droit de modifier la note dans ce contexte.
            return self::$caneditgrades[$courseid];
        }

        // On vérifie que l'utilisateur est dans les temps pour noter l'étudiant.
        self::$caneditgrades[$courseid] = ($calendarlocked === false);

        return self::$caneditgrades[$courseid];
    }

    /**
     * Retourne les cours où des évaluations sont en cours.
     *
     * @param int $contextlevel Si le contextlevel correspond à la constante CONTEXT_SYSTEM, tous les cours sont renvoyés
     *                          aux gestionnaires. Sinon, seuls les cours de l'enseignant sont renvoyés.
     *
     * @return array
     */
    public static function get_courses(int $contextlevel = CONTEXT_COURSE) {
        global $DB, $USER;

        $syscontext = context_system::instance();

        if ($contextlevel !== CONTEXT_SYSTEM) {
            // Bascule par défaut sur le contexte de cours.
            $contextlevel = CONTEXT_COURSE;
        }

        if ($contextlevel === CONTEXT_SYSTEM && has_capability('local/apsolu:viewallgrades', $syscontext) === false) {
            // Bascule par défaut sur le contexte de cours quand l'utilisateur n'a pas le droit de voir toutes les notes.
            $contextlevel = CONTEXT_COURSE;
        }

        // Récupère la liste des rôles pouvant être évaluer.
        $gradableroles = array_keys(self::get_gradable_roles());
        if (count($gradableroles) === 0) {
            return [];
        }

        $params = [];
        $params['context_course'] = CONTEXT_COURSE;

        if ($contextlevel === CONTEXT_SYSTEM) {
            // Requête pour les gestionnaires.
            $sql = "SELECT DISTINCT c.*".
                " FROM {course} c".
                " JOIN {course_categories} cc ON cc.id = c.category".
                " JOIN {enrol} e ON c.id = e.courseid".
                " JOIN {apsolu_courses} ac ON ac.id = c.id".
                " JOIN {context} ctx ON c.id = ctx.instanceid AND ctx.contextlevel = :context_course".
                " JOIN {role_assignments} ra ON ctx.id = ra.contextid AND ra.roleid IN (".implode(',', $gradableroles).")".
                " WHERE e.enrol = 'select'".
                " AND e.status = 0".
                " ORDER BY cc.name, ac.numweekday, ac.starttime";
        } else {
            // Récupère la liste des rôles pouvant évaluer.
            $graderroles = array_keys(get_roles_with_capability('local/apsolu:viewgrades', $permission = CAP_ALLOW, $syscontext));
            if (count($gradableroles) === 0) {
                return [];
            }

            // Requête pour les enseignants.
            $sql = "SELECT DISTINCT c.*".
                " FROM {course} c".
                " JOIN {course_categories} cc ON cc.id = c.category".
                " JOIN {enrol} e ON c.id = e.courseid".
                " JOIN {apsolu_courses} ac ON ac.id = c.id".
                " JOIN {context} ctx ON c.id = ctx.instanceid AND ctx.contextlevel = :context_course".
                " JOIN {role_assignments} ra ON ctx.id = ra.contextid AND ra.roleid IN (".implode(',', $gradableroles).")".
                " WHERE e.enrol = 'select'".
                " AND e.status = 0".
                " AND ctx.id IN (SELECT contextid
                                   FROM {role_assignments}
                                  WHERE userid = :userid
                                    AND roleid IN (".implode(',', $graderroles)."))".
                " ORDER BY cc.name, ac.numweekday, ac.starttime";
            $params['userid'] = $USER->id;
        }

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Retourne un tableau contenant la liste des rôles qui peuvent être notés dans APSOLU.
     *
     * @return array
     */
    public static function get_gradable_roles() {
        $capability = 'local/apsolu:gradable';
        $permission = CAP_ALLOW;
        $context = context_system::instance();

        $roles = get_roles_with_capability($capability, $permission, $context);

        return role_fix_names($roles);
    }

    /**
     * Retourne un tableau contenant l'intégralité du carnet de notes.
     *
     * @param array $options Liste des options d'affichage du carnet de notes (seulement les évalués en option, seulement
     *                       certaines activités, etc).
     * @param array $fields  Liste des champs à retourner.
     *
     * @return array
     */
    public static function get_gradebook(array $options, array $fields = []) {
        global $DB, $OUTPUT;

        // Indexe les champs dans un tableau associatif.
        foreach ($fields as $key => $value) {
            $fields[$value] = true;
            unset($fields[$key]);
        }

        // Calcule les permissions de l'utilisateur.
        $capability = new stdClass();
        $capability->editgrades = has_capability('local/apsolu:editgrades', context_system::instance());
        $capability->editgradesafterdeadline = has_capability('local/apsolu:editgradesafterdeadline', context_system::instance());
        $capability->viewallgrades = has_capability('local/apsolu:viewallgrades', context_system::instance());

        // Contrôle que les options obligatoires sont présentes.
        if (isset($options['courses']) === false) {
            $options['courses'] = [];
        }

        if (isset($options['calendarstypes']) === false) {
            throw new Exception(get_string('fieldrequired', 'error', get_string('calendars_types', 'local_apsolu')));
        }

        if (isset($options['roles']) === false) {
            throw new Exception(get_string('fieldrequired', 'error', get_string('roles')));
        }

        // Récupère les calendriers APSOLU.
        $now = time();
        $options['calendars'] = [];
        $calendars = $DB->get_records('apsolu_calendars');
        foreach ($calendars as $calendarid => $calendar) {
            if (in_array($calendar->typeid, $options['calendarstypes'], $strict = true) === false) {
                // Ignore les calendriers n'ayant pas le bon type.
                continue;
            }

            // Vérifie que l'édition des notes n'est pas hors-délai.
            $canedit = ((empty($calendar->gradestartdate) || $now > $calendar->gradestartdate) &&
                (empty($calendar->gradeenddate) || $now < $calendar->gradeenddate));
            $calendar->locked = ($canedit === false);

            $options['calendars'][] = $calendar->id;
        }

        // On récupère la liste des notes attendues en fonction des options passées en paramètre.
        $gradeitems = [];
        foreach (gradeitem::get_records($conditions = null, $sort = 'name') as $item) {
            if (isset($options['gradeitems']) === true && in_array($item->id, $options['gradeitems'], $strict = true) === false) {
                // Ignore les éléments de notation non sélectionnés par le filtre.
                continue;
            }

            if (is_array($options['roles']) === false) {
                $options['roles'] = [$options['roles']];
            }

            if (in_array($item->roleid, $options['roles'], $strict = true) === false) {
                continue;
            }

            if (is_array($options['calendars']) === false) {
                $options['calendars'] = [$options['calendars']];
            }

            if (in_array($item->calendarid, $options['calendars'], $strict = true) === false) {
                continue;
            }

            if (isset($calendars[$item->calendarid]) === false) {
                continue;
            }

            $item->calendar_locked = $calendars[$item->calendarid]->locked;
            $gradeitems[$item->id] = $item;
        }

        // Récupération des enseignants.
        $teachers = [];
        if (isset($options['teachers']) === true || isset($fields['teachers']) === true) {
            $sql = "SELECT c.id AS courseid, u.*".
                " FROM {user} u".
                " JOIN {role_assignments} ra ON u.id = ra.userid".
                " JOIN {context} ctx ON ctx.id = ra.contextid".
                " JOIN {apsolu_courses} c ON ctx.instanceid = c.id".
                " WHERE ra.roleid = 3". // Enseignant.
                " ORDER BY u.lastname, u.firstname";
            $recordset = $DB->get_recordset_sql($sql);
            foreach ($recordset as $record) {
                $courseid = $record->courseid;
                unset($record->courseid);

                if (isset($teachers[$courseid]) === false) {
                    $teachers[$courseid] = [];
                }

                $teachers[$courseid][$record->id] = fullname($record);
            }
            $recordset->close();
        }

        // Récupération des notes.
        $grades = [];
        $sql = "SELECT gi.itemname, gi.grademax, gg.userid, gg.finalgrade, gg.feedback, gi.courseid, gi.iteminfo,".
            " u.firstname, u.lastname, u.lastnamephonetic, u.firstnamephonetic, u.middlename, u.alternatename".
            " FROM {grade_items} gi".
            " JOIN {grade_grades} gg ON gi.id = gg.itemid".
            " LEFT JOIN {user} u ON u.id = gg.usermodified";
        $recordset = $DB->get_recordset_sql($sql);
        foreach ($recordset as $grade) {
            if ($grade->iteminfo !== self::NAME) {
                // On garde uniquement les éléments de notation dont l'iteminfo est APSOLU.
                // Note: il n'y a pas d'index sur le champ grade_items.iteminfo. On traite cette info côté PHP.
                continue;
            }

            // On stocke toutes les notes attendues.
            list($apsolugradeitemid, $name) = explode('-', $grade->itemname, 2);
            if (isset($gradeitems[$apsolugradeitemid]) === false) {
                continue;
            }

            // On récupère toutes les notes par étudiant et cours.
            if (isset($grades[$grade->userid]) === false) {
                $grades[$grade->userid] = [];
            }

            if (isset($grades[$grade->userid][$grade->courseid]) === false) {
                $grades[$grade->userid][$grade->courseid] = [];
            }

            if (empty($grade->feedback) === false) {
                $grade->finalgrade = $grade->feedback;
            }

            $gradeitems[$apsolugradeitemid]->grademax = (float) $grade->grademax;

            $value = new stdClass();
            $value->grade = $grade->finalgrade;
            $value->grader = fullname($grade);
            $grades[$grade->userid][$grade->courseid][$apsolugradeitemid] = $value;
        }
        $recordset->close();

        // Récupération des utilisateurs.
        $customfields = customfields::getCustomFields();
        $gradableroles = self::get_gradable_roles();

        $conditions = [];

        $params = [];
        $params[] = $customfields['apsoluufr']->id;
        $params[] = $customfields['apsolucycle']->id;
        $params[] = $customfields['apsolusex']->id;
        $params[] = CONTEXT_COURSE;

        // Filtres.
        $filters = [];

        if (defined('APSOLU_GRADES_COURSE_SCOPE') === false) {
            define('APSOLU_GRADES_COURSE_SCOPE', CONTEXT_COURSE);
        }

        // Extraction des catégories de cours.
        $options['categories'] = [];
        foreach ($options['courses'] as $key => $value) {
            list($categoryid, $courseid) = explode('-', $value);
            if ($courseid === '0') {
                // Traite une activité.
                $options['categories'][$categoryid] = $categoryid;
                unset($options['courses'][$key]);
            } else {
                // Traite un créneau.
                $options['courses'][$key] = $courseid;
            }
        }

        if (APSOLU_GRADES_COURSE_SCOPE === CONTEXT_COURSE) {
            // L'utilisateur est seulement enseignant. On force à ne récupérer que ses cours.
            $courses = self::get_courses();

            // Contrôle les droits d'accès sur les activités.
            if ($options['categories'] !== []) {
                // Pour chaque catégorie sélectionnée, ajoute tous les cours auxquels l'utilisateur peut accéder.
                foreach ($courses as $course) {
                    if (isset($options['categories'][$course->category]) === false) {
                        continue;
                    }

                    $options['courses'][] = $course->id;
                }
            }

            foreach ($options['courses'] as $key => $courseid) {
                if (isset($courses[$courseid]) === true) {
                    // L'utilisateur est autorisé à voir ce cours.
                    continue;
                }

                // L'utilisateur n'a pas le droit d'accéder à ce cours.
                unset($options['courses'][$key]);
            }

            if ($options['courses'] === []) {
                // L'utilisateur a sélectionné tous les cours.
                foreach ($courses as $course) {
                    $options['courses'][] = $course->id;
                }
            }
        } else if (APSOLU_GRADES_COURSE_SCOPE === CONTEXT_SYSTEM) {
            if ($options['categories'] !== []) {
                // Gère la sélection des catégories.
                foreach ($DB->get_records('course') as $course) {
                    if (isset($options['categories'][$course->category]) === false) {
                        continue;
                    }

                    $options['courses'][] = $course->id;
                }
            }

            if ($options['courses'] === []) {
                // Gère le cas où le gestionnaire demande tous les cours.
                unset($options['courses']);
            }
        }

        $filters['courses'] = " AND c.id IN (%s)";
        $filters['roles'] = " AND ra.roleid IN (%s)";
        $filters['calendars'] = " AND aca.id IN (%s)";
        $filters['cities'] = " AND act.id IN (%s)";

        foreach ($filters as $optionname => $sql) {
            if (isset($options[$optionname]) === false) {
                continue;
            }

            if (is_array($options[$optionname]) === false) {
                $options[$optionname] = [$options[$optionname]];
            }

            $count = 0;
            foreach ($options[$optionname] as $option) {
                if (ctype_digit($option) === false) {
                    continue;
                }

                $count++;
                $params[] = $option;
            }

            if ($count === 0) {
                continue;
            }

            $conditions[] = sprintf($sql, substr(str_repeat('?,', $count), 0, -1));
        }

        if (isset($options['fullnameuser']) === true && empty($options['fullnameuser']) === false) {
            $params[] = '%'.$options['fullnameuser'].'%';
            $conditions[] = sprintf(" AND %s LIKE ? ", $DB->sql_fullname('u.firstname', 'u.lastname'));
        }

        $sql = "SELECT u.id, u.lastname, u.firstname, u.email, u.idnumber, uid3.data AS sex, act.name AS city,
                       u.institution, uid1.data AS ufr, u.department,
                       u.lastnamephonetic, u.firstnamephonetic, u.middlename, u.alternatename, u.picture, u.imagealt,
                       uid2.data AS cycle, aca.id AS calendarid, aca.name AS calendar, c.id AS courseid, c.fullname AS course,
                       cc.name AS category, cc2.name AS grouping, ra.roleid".
            " FROM {user} u".
            " LEFT JOIN {user_info_data} uid1 ON u.id = uid1.userid AND uid1.fieldid = ?".
            " LEFT JOIN {user_info_data} uid2 ON u.id = uid2.userid AND uid2.fieldid = ?".
            " LEFT JOIN {user_info_data} uid3 ON u.id = uid3.userid AND uid3.fieldid = ?".
            " JOIN {user_enrolments} ue ON u.id = ue.userid AND ue.status = 0".
            " JOIN {enrol} e ON e.id = ue.enrolid AND e.enrol = 'select' AND e.status = 0".
            " JOIN {apsolu_calendars} aca ON aca.id = e.customchar1".
            " JOIN {course} c ON c.id = e.courseid".
            " JOIN {course_categories} cc ON cc.id = c.category".
            " JOIN {course_categories} cc2 ON cc2.id = cc.parent".
            " JOIN {apsolu_courses} ac ON ac.id = c.id".
            " JOIN {apsolu_locations} al ON al.id = ac.locationid".
            " JOIN {apsolu_areas} aa ON aa.id = al.areaid".
            " JOIN {apsolu_cities} act ON act.id = aa.cityid".
            " JOIN {context} ctx ON c.id = ctx.instanceid AND ctx.contextlevel = ?".
            " JOIN {role_assignments} ra ON ctx.id = ra.contextid AND ra.itemid = e.id AND ra.userid = u.id".
            " WHERE u.deleted = 0".implode(' ', $conditions).
            " ORDER BY u.lastname, u.firstname, u.institution, u.department";
        $recordset = $DB->get_recordset_sql($sql, $params);

        // Construction du carnet de notes.
        $gradebooks = new stdClass();
        $gradebooks->headers = [];
        if (isset($fields['pictures']) === true) {
            $gradebooks->headers[] = get_string('pictureofuser');
        }
        $gradebooks->headers[] = get_string('idnumber');
        $gradebooks->headers[] = get_string('lastname');
        $gradebooks->headers[] = get_string('firstname');
        if (isset($fields['sexes']) === true) {
            $gradebooks->headers[] = get_string('sex', 'local_apsolu');
        }
        if (isset($fields['emails']) === true) {
            $gradebooks->headers[] = get_string('email');
        }
        if (isset($fields['cities']) === true) {
            $gradebooks->headers[] = get_string('city', 'local_apsolu');
        }
        if (isset($fields['institutions']) === true) {
            $gradebooks->headers[] = get_string('institution');
        }
        if (isset($fields['ufrs']) === true) {
            $gradebooks->headers[] = get_string('ufr', 'local_apsolu');
        }
        if (isset($fields['departments']) === true) {
            $gradebooks->headers[] = get_string('department');
        }
        if (isset($fields['cycles']) === true) {
            $gradebooks->headers[] = get_string('cycle', 'local_apsolu');
        }
        if (isset($fields['calendars']) === true) {
            $gradebooks->headers[] = get_string('calendar', 'local_apsolu');
        }
        if (isset($fields['groupings']) === true) {
            $gradebooks->headers[] = get_string('grouping', 'local_apsolu');
        }
        if (isset($fields['categories']) === true) {
            $gradebooks->headers[] = get_string('activity', 'local_apsolu');
        }
        if (isset($fields['courses']) === true) {
            $gradebooks->headers[] = get_string('course');
        }
        if (isset($fields['roles']) === true) {
            $gradebooks->headers[] = get_string('role');
        }
        if (isset($fields['teachers']) === true) {
            $gradebooks->headers[] = get_string('teacher', 'local_apsolu');
        }
        foreach ($gradeitems as $item) {
            $gradebooks->headers[] = $item->name;
        }

        $gradebooks->users = [];
        foreach ($recordset as $user) {
            if (isset($options['institutions']) === true &&
                in_array($user->institution, $options['institutions'], $strict = true) === false) {
                continue;
            }

            if (isset($options['ufrs']) === true && in_array($user->ufr, $options['ufrs'], $strict = true) === false) {
                continue;
            }

            if (isset($options['departments']) === true &&
                in_array($user->department, $options['departments'], $strict = true) === false) {
                continue;
            }

            if (isset($options['cycles']) === true && in_array($user->cycle, $options['cycles'], $strict = true) === false) {
                continue;
            }

            if (isset($options['idnumber']) === true && $user->idnumber !== $options['idnumber']) {
                continue;
            }

            if (isset($options['teachers']) === true) {
                if (isset($teachers[$user->courseid]) === false) {
                    // Il n'y a pas d'enseignants dans ce cours.
                    continue;
                }

                $found = false;
                foreach ($teachers[$user->courseid] as $teacherid => $teacher) {
                    if (in_array((string) $teacherid, $options['teachers'], $strict = true) === true) {
                        $found = true;
                        break;
                    }
                }

                if ($found === false) {
                    // Aucun enseignant de ce cours ne correspond au filtre.
                    continue;
                }
            }

            if (isset($gradableroles[$user->roleid]) === false) {
                // Ce utilisateur n'est pas évaluable.
                continue;
            }

            $gradebook = new stdClass();
            $gradebook->profile = [];
            if (isset($fields['pictures']) === true) {
                $gradebook->picture = $OUTPUT->user_picture($user);
            }
            $gradebook->profile[] = $user->idnumber;
            $gradebook->profile[] = $user->lastname;
            $gradebook->profile[] = $user->firstname;

            if (isset($fields['sexes']) === true) {
                $gradebook->profile[] = $user->sex;
            }
            if (isset($fields['emails']) === true) {
                $gradebook->profile[] = $user->email;
            }
            if (isset($fields['cities']) === true) {
                $gradebook->profile[] = $user->city;
            }
            if (isset($fields['institutions']) === true) {
                $gradebook->profile[] = $user->institution;
            }
            if (isset($fields['ufrs']) === true) {
                $gradebook->profile[] = $user->ufr;
            }
            if (isset($fields['departments']) === true) {
                $gradebook->profile[] = $user->department;
            }
            if (isset($fields['cycles']) === true) {
                $gradebook->profile[] = $user->cycle;
            }
            if (isset($fields['calendars']) === true) {
                $gradebook->profile[] = $user->calendar;
            }
            if (isset($fields['groupings']) === true) {
                $gradebook->profile[] = $user->grouping;
            }
            if (isset($fields['categories']) === true) {
                $gradebook->profile[] = $user->category;
            }
            if (isset($fields['courses']) === true) {
                $gradebook->profile[] = $user->course;
            }
            if (isset($fields['roles']) === true) {
                $gradebook->profile[] = $gradableroles[$user->roleid]->localname;
            }
            if (isset($fields['teachers']) === true) {
                if (isset($teachers[$user->courseid]) === true) {
                    $gradebook->profile[] = implode(', ', $teachers[$user->courseid]);
                } else {
                    $gradebook->profile[] = '';
                }
            }
            $gradebook->grades = [];
            $needagrade = false;
            foreach ($gradeitems as $apsolugradeitemid => $item) {
                $grade = null;
                if ($user->roleid === $item->roleid && $user->calendarid === $item->calendarid) {
                    $needagrade = true;

                    $grade = new stdClass();
                    $grade->locked = (self::can_edit_grades($user->courseid, $item->calendar_locked) === false);
                    $grade->abi = false;
                    $grade->abj = false;
                    $grade->value = null;
                    $grade->max = $item->grademax;
                    $grade->inputname = $user->id.'-'.$user->courseid.'-'.$apsolugradeitemid;

                    if (isset($grades[$user->id][$user->courseid][$apsolugradeitemid]) === true) {
                        $value = $grades[$user->id][$user->courseid][$apsolugradeitemid]->grade;
                        if ($value === 'ABI') {
                            $grade->abi = true;
                            $grade->value = $value;
                        } else if ($value === 'ABJ') {
                            $grade->abj = true;
                            $grade->value = $value;
                        } else if (empty($value) === false) {
                            $grade->value = number_format($value, 2);
                        }

                        if (isset($fields['graders']) === true) {
                            $grade->grader = $grades[$user->id][$user->courseid][$apsolugradeitemid]->grader;
                        }
                    }
                }

                $gradebook->grades[] = $grade;
            }

            if ($needagrade === true) {
                // Ajoute seulement les utilisateurs ayant une note attendue.
                $gradebooks->users[] = $gradebook;
            }
        }
        $recordset->close();

        return $gradebooks;
    }

    /**
     * Retourne un tableau contenant l'intégralité du carnet de notes.
     *
     * @param array $options Liste des options d'affichage du carnet de notes (seulement les évalués en option, seulement certaines
     *                       activités, etc).
     * @param array $fields  Liste des champs à retourner.
     * @param string $format Format de fichier de l'exportation. Valeur gérée : csv ou xls.
     *
     * @return void
     */
    public static function export(array $options, array $fields = [], string $format = 'csv') {
        global $CFG;

        $filename = 'extraction_des_notes';
        $gradebook = self::get_gradebook($options, $fields);

        switch ($format) {
            case 'xls':
                require_once($CFG->libdir . '/excellib.class.php');

                // Définit les entêtes.
                $workbook = new MoodleExcelWorkbook("-");
                $workbook->send($filename);
                $myxls = $workbook->add_worksheet();
                $properties = ['border' => Border::BORDER_THIN];
                $excelformat = new MoodleExcelFormat($properties);
                foreach ($gradebook->headers as $position => $value) {
                    $myxls->write_string(0, $position, $value, $excelformat);
                }

                // Définit les données.
                $line = 1;
                foreach ($gradebook->users as $user) {
                    $column = 0;
                    foreach ($user->profile as $value) {
                        $myxls->write_string($line, $column++, $value, $excelformat);
                    }

                    foreach ($user->grades as $grade) {
                        $value = $grade->value;
                        if ($value === null) {
                            $value = get_string('not_applicable', 'local_apsolu');
                        }

                        if (is_numeric($value) === true) {
                            $myxls->write_number($line, $column++, $value, ['num_format' => '0.00']);
                        } else {
                            $myxls->write_string($line, $column++, $value, $excelformat);
                        }
                    }
                    $line++;
                }

                // MDL-83543: positionne un cookie pour qu'un script js déverrouille le bouton submit après le téléchargement.
                setcookie('moodledownload_' . sesskey(), time());

                // Transmet le fichier au navigateur.
                $workbook->close();
                exit();
            case 'csv':
                require_once($CFG->libdir . '/csvlib.class.php');

                $csvexport = new csv_export_writer();
                $csvexport->set_filename($filename);

                $csvexport->add_data($gradebook->headers);

                foreach ($gradebook->users as $user) {
                    $data = $user->profile;
                    foreach ($user->grades as $grade) {
                        if ($grade === null) {
                            $data[] = get_string('not_applicable', 'local_apsolu');
                        } else {
                            $data[] = $grade->value;
                        }
                    }
                    $csvexport->add_data($data);
                }

                $csvexport->download_file();
                exit();
            default:
                throw new coding_exception(get_string('unknowformat', 'error', $format));
        }
    }

    /**
     * Enregistre les notes.
     *
     * @param array $grades Listes des notes.
     *
     * @return void
     */
    public static function set_grades(array $grades) {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        $gradeitems = [];

        foreach ($grades as $gradename => $value) {
            list($userid, $courseid, $apsolugradeitemid) = explode('-', $gradename, 3);

            if (isset($gradeitems[$courseid]) === false) {
                $gradeitems[$courseid] = [];
                foreach (grade_item::fetch_all(['courseid' => $courseid, 'iteminfo' => self::NAME]) as $item) {
                    $id = explode('-', $item->itemname);
                    if (isset($id[0]) === false) {
                        continue;
                    }

                    if (ctype_digit($id[0]) === false) {
                        continue;
                    }

                    $gradeitems[$courseid][$id[0]] = $item;
                }
            }

            if (isset($gradeitems[$courseid][$apsolugradeitemid]) === false) {
                // Hmm, y'a un souci... visiblement, l'élément de notation n'existe pas pour ce cours.
                continue;
            }

            $item = $gradeitems[$courseid][$apsolugradeitemid];

            $grade = new stdClass();
            $grade->userid = $userid;
            if (in_array(strtoupper($value), ['ABI', 'ABJ'], $strict = true) === true) {
                $grade->finalgrade = null;
                $grade->feedback = strtoupper($value);
            } else {
                $grade->finalgrade = str_replace(',', '.', $value);
                $grade->feedback = null;

                if (is_numeric($grade->finalgrade) === false) {
                    continue;
                }
            }

            $currentgrade = grade_grade::fetch(['itemid' => $item->id, 'userid' => $userid]);
            if ($currentgrade !== false && grade_floats_different($currentgrade->finalgrade, $grade->finalgrade) === false &&
                $currentgrade->feedback === $grade->feedback) {
                // La note n'a pas changé, on continue.
                continue;
            }

            // On met à jour la note.
            if ($item->update_final_grade($grade->userid, $grade->finalgrade, $src = 'local_apsolu', $grade->feedback) === false) {
                $transaction->rollback(new Exception(get_string('an_error_occurred_while_saving_your_grades', 'local_apsolu')));
                return false;
            }
        }

        $transaction->allow_commit();
        return true;
    }
}
