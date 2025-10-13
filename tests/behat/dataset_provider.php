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

namespace local_apsolu\tests\behat;

use context_block;
use context_helper;
use context_system;
use core\session\manager as session_manager;
use DateInterval;
use DatePeriod;
use DateTime;
use Exception;
use local_apsolu\core as Apsolu;
use local_apsolu\core\federation;
use local_apsolu_courses_categories_edit_form;
use stdClass;
use tool_langimport\controller as langimport;
use tool_usertours\local\filter\theme;
use tool_usertours\tour;
use testing_data_generator;

/**
 * Classe aidant à la génération d'un jeu de données.
 *
 * @package    local_apsolu
 * @copyright  2025 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dataset_provider {
    /**
     * Configure un environnement complet APSOLU avec un jeu de données initialisé.
     */
    public static function execute() {
        global $CFG;

        require_once($CFG->dirroot . '/cohort/lib.php');
        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/group/lib.php');
        require_once($CFG->dirroot . '/user/profile/lib.php');
        require_once($CFG->dirroot . '/lib/blocklib.php');
        require_once($CFG->dirroot . '/lib/testing/generator/data_generator.php');

        session_manager::init_empty_session();
        session_manager::set_user(get_admin());

        // Force l'utilisation du français, notamment pour avoir le bon jour de la semaine dans le nom des cours.
        $langimport = new langimport();
        $langimport->install_languagepacks('fr', $updating = false);
        force_current_language('fr');

        self::setup_blocks_and_theme();
        self::setup_config();
        self::setup_roles();
        self::setup_cohorts();
        self::setup_users();
        self::setup_periods();
        self::setup_calendars();
        self::setup_courses();
        self::setup_federation_course();
        self::setup_attendances();
        self::setup_gradebooks();
    }

    /**
     * Retourne l'année universtaire en cours.
     *
     * @return int Une année sur 4 chiffres. Ex: 2024 pour l'année 2024-2025.
     */
    private static function get_academic_year() {
        $academicyear = date('Y');

        if (date('m') < 8) {
            $academicyear--;
        }

        return $academicyear;
    }

    /**
     * Ajoute aléatoirement des présences.
     *
     * @return void
     */
    private static function setup_attendances() {
        global $DB;

        // Récupère l'utilisateur "lenseignante".
        $teacher = $DB->get_record('user', ['username' => 'lenseignante', 'deleted' => 0], $fields = '*', MUST_EXIST);

        // Génère les motifs de présence.
        Apsolu\attendance\status::generate_default_values();
        $statuses = Apsolu\attendance\status::get_records();

        $present = reset($statuses);
        for ($i = 0; $i < 20; $i++) {
            // Ajoute artificiellement un poid fort sur le motif "présent".
            $statuses[] = clone $present;
        }

        // Récupère la liste des cours de l'utilisateur "lenseignante".
        $sql = "SELECT DISTINCT e.courseid
                  FROM {enrol} e
                  JOIN {user_enrolments} ue ON e.id = ue.enrolid
                  WHERE ue.userid = :teacherid";
        $courses = $DB->get_records_sql($sql, ['teacherid' => $teacher->id]);
        foreach ($courses as $course) {
            // Récupère les méthodes d'inscription par voeux du cours.
            $enrolments = $DB->get_records('enrol', ['courseid' => $course->courseid, 'enrol' => 'select']);
            foreach ($enrolments as $enrolment) {
                // Récupère les utilisateurs inscrits avec cette méthode d'inscription.
                $enrolment->users = $DB->get_records(
                    'user_enrolments',
                    ['enrolid' => $enrolment->id, 'status' => ENROL_USER_ACTIVE],
                    $fields = 'userid'
                );
            }

            // Récupère tous les sessions du cours.
            $sessions = Apsolu\attendancesession::get_records(['courseid' => $course->courseid]);
            foreach ($sessions as $session) {
                if ($session->is_expired() === false) {
                    // Rappel: ne pas mettre une présence à une session future.
                    continue;
                }

                // Détermine à quelle méthode d'inscription correspond la session.
                $found = false;
                foreach ($enrolments as $enrolment) {
                    if ($session->sessiontime > $enrolment->customint8) {
                        continue;
                    }

                    $found = true;
                    break;
                }

                if ($found === false) {
                    // Ne devrait jamais arriver.
                    continue;
                }

                // Attribue une présence à chaque étudiant.
                foreach ($enrolment->users as $user) {
                    $status = $statuses[array_rand($statuses)];

                    $descriptions = array_fill(0, 10, '');
                    switch ($status->shortlabel) {
                        case 'R':
                            $descriptions[] = 'panne de réveil';
                            $descriptions[] = 'grève de bus';
                            $descriptions[] = 'pb de bus';
                            break;
                        case 'A':
                            $descriptions[] = 'affaires oubliées';
                            break;
                        case 'D':
                            $descriptions[] = 'départ en stage';
                            $descriptions[] = 'justificatif médical';
                            $descriptions[] = 'rdv médecin';
                            break;
                    }

                    $presence = new Apsolu\attendancepresence();
                    $presence->studentid = $user->userid;
                    $presence->teacherid = $teacher->id;
                    $presence->statusid = $status->id;
                    $presence->description = $descriptions[array_rand($descriptions)];
                    $presence->timecreated = $session->sessiontime;
                    $presence->timemodified = $session->sessiontime;
                    $presence->sessionid = $session->id;
                    $presence->save();
                }
            }
        }
    }

    /**
     * Configure les blocs, les méthodes d'inscription et le thème APSOLU.
     *
     * @return void
     */
    private static function setup_blocks_and_theme() {
        global $CFG, $DB;

        // Note: ne pas déplacer cette inclusion au début du fichier. Sinon, la CI indique que la variable $CFG n'existe pas lors de
        // la lecture du fichier.
        require_once($CFG->dirroot . '/my/lib.php');

        set_config('customfrontpageinclude', $CFG->dirroot . '/theme/apsolu/index.php');
        set_config('theme', 'apsolu');

        // Supprime la configuration actuelle du dashboard.
        $DB->delete_records('block_instances', ['parentcontextid' => 1, 'pagetypepattern' => 'my-index']);

        // Définit les blocks.
        $blocks = [];
        $blocks['calendar_month'] = 'side-pre';
        $blocks['apsolu_dashboard'] = 'content';

        $weights = [];
        $weights['content'] = 0;
        $weights['side-post'] = 0;
        $weights['side-pre'] = 0;

        // Enregistre les blocks du dashboard par défaut en base de données.
        foreach ($blocks as $blockname => $defaultregion) {
            if (isset($weights[$defaultregion]) === false) {
                throw new Exception(get_string('unknownblockregion', 'error', $defaultregion));
            }

            $blockinstance = new stdClass();
            $blockinstance->blockname = $blockname;
            $blockinstance->parentcontextid = 1;
            $blockinstance->showinsubcontexts = 0;
            $blockinstance->pagetypepattern = 'my-index';
            $blockinstance->subpagepattern = 2;
            $blockinstance->defaultregion = $defaultregion;
            $blockinstance->defaultweight = $weights[$defaultregion];
            $blockinstance->configdata = '';
            $blockinstance->timecreated = time();
            $blockinstance->timemodified = time();
            $blockinstance->id = $DB->insert_record('block_instances', $blockinstance);

            // Ensure the block context is created.
            context_block::instance($blockinstance->id);

            // If the new instance was created, allow it to do additional setup.
            $block = block_instance($blockname, $blockinstance);
            if ($block === false) {
                throw new Exception(get_string('cannotsaveblock', 'error'));
            }
            $block->instance_create();

            $weights[$defaultregion]++;
        }

        // Réinitialise tous les dashboards des utilisateurs.
        my_reset_page_for_all_users(MY_PAGE_PRIVATE, 'my-index');
    }

    /**
     * Configure les 2 calendriers (semestre 1 et semestre 2).
     *
     * @return void
     */
    private static function setup_calendars() {
        global $DB;

        $periods = [];
        foreach (Apsolu\period::get_records() as $record) {
            $name = trim((string) $record);
            $periods[$name] = $record;
        }

        foreach (['Semestre 1', 'Semestre 2'] as $type) {
            if (isset($periods[$type]) === false) {
                continue;
            }

            $weeks = explode(',', $periods[$type]->weeks);
            $startdate = new DateTime(current($weeks));
            $enddate = new DateTime(end($weeks));
            $enddate->add(new DateInterval('P7D'));

            $calendartype = new stdClass();
            $calendartype->name = $type;
            $calendartype->id = $DB->insert_record('apsolu_calendars_types', $calendartype);

            $instance = new stdClass();
            $instance->id = 0;
            $instance->name = $type;
            $instance->enrolstartdate = $startdate->getTimestamp();
            $instance->enrolenddate = $enddate->getTimestamp();
            $instance->coursestartdate = $startdate->getTimestamp();
            $instance->courseenddate = $enddate->getTimestamp();
            $instance->reenrolstartdate = 0;
            $instance->reenrolenddate = 0;
            $instance->gradestartdate = $startdate->getTimestamp();
            $instance->gradeenddate = $enddate->getTimestamp();
            $instance->typeid = $calendartype->id;

            $DB->insert_record('apsolu_calendars', $instance);
        }
    }

    /**
     * Configure les cohortes et les populations.
     *
     * 6 cohortes :
     *  - Ensemble Évalué (bonification) Femme
     *  - Ensemble Évalué (bonification) Homme
     *  - Ensemble Évalué (option) Femme
     *  - Ensemble Évalué (option) Homme
     *  - Ensemble Non évalué Femme
     *  - Ensemble Non évalue Homme
     *
     * 3 populations :
     *  - Population Évalué (bonification)
     *  - Population Évalué (option)
     *  - Population Non évalué
     *
     * @return void
     */
    private static function setup_cohorts() {
        global $DB;

        $cohorts = $DB->get_records('cohort', $conditions = null, $sort = '', $fields = 'idnumber, id');

        $roles = $DB->get_records('role', ['archetype' => 'student']);
        foreach ($roles as $role) {
            if (empty($role->name) === true || $role->shortname === 'ffsu') {
                continue;
            }

            $newcohorts = [];
            foreach (['Homme', 'Femme'] as $sex) {
                $idnumber = sprintf('%s_%s', $role->shortname, strtolower($sex));

                if (isset($cohorts[$idnumber]) === true) {
                    $newcohorts[] = $cohorts[$idnumber];
                    continue;
                }

                $cohort = new stdClass();
                $cohort->idnumber = $idnumber;
                $cohort->name = sprintf('Ensemble %s %s', $role->name, $sex);
                $cohort->contextid = 1;

                $cohort->id = cohort_add_cohort($cohort);

                $newcohorts[] = $cohort;
            }

            // Définit une population.
            $college = new stdClass();
            $college->id = 0;
            $college->name = sprintf('Population %s', $role->name);
            $college->maxwish = 2;
            $college->minregister = 0;
            $college->maxregister = 2;
            $college->userprice = 0;
            $college->institutionprice = 0;
            $college->roleid = $role->id;

            $college->id = $DB->insert_record('apsolu_colleges', $college);

            $DB->delete_records('apsolu_colleges_members', ['collegeid' => $college->id]);
            foreach ($newcohorts as $cohort) {
                $sql = "INSERT INTO {apsolu_colleges_members}(collegeid, cohortid) VALUES(?, ?)";
                $DB->execute($sql, [$college->id, $cohort->id]);
            }
        }
    }

    /**
     * Configure le paramétrage général de l'application.
     *
     * @return void
     */
    private static function setup_config() {
        global $DB;

        // Supprime le bouton de connexion anonyme.
        $oldvalue = get_config('core', 'guestloginbutton');
        add_to_config_log('guestloginbutton', $oldvalue, '0', 'core');
        set_config('guestloginbutton', 0);

        // Désactive tous les modèles d'analyse de données.
        $DB->set_field('analytics_models', 'enabled', '0');

        // Désactive toutes les visites guidées.
        $DB->set_field('tool_usertours_tours', 'enabled', '0');

        // Change la configuration de $CFG->enrol_plugins_enabled.
        $oldvalue = get_config('core', 'enrol_plugins_enabled');
        add_to_config_log('enrol_plugins_enabled', $oldvalue, 'manual,guest,self,select,meta,cohort', 'core');
        set_config('enrol_plugins_enabled', 'manual,guest,self,select,meta,cohort');

        set_config('functional_contact', 'contact@example.com', 'local_apsolu');
        set_config('technical_contact', 'contact@example.com', 'local_apsolu');
    }

    /**
     * Configure les créneaux et les inscriptions en fonction des données présentes dans le fichier tests/fixtures/dataset.csv.
     *
     * Cette fonction génère aussi la création des zones géographiques, la liste des activités, les niveaux de pratique, etc.
     *
     * @return void
     */
    private static function setup_courses() {
        global $CFG, $DB;

        // Note: ne pas déplacer cette inclusion au début du fichier. Sinon, la CI indique que la variable $CFG n'existe pas lors de
        // la lecture du fichier.
        require_once($CFG->dirroot . '/local/apsolu/courses/categories/edit_form.php');

        // Charge le fichier csv contenant le jeu de données.
        $file = $CFG->dirroot . '/local/apsolu/tests/fixtures/dataset.csv';

        $handle = fopen($file, 'r');
        if ($handle === false) {
            throw new Exception(sprintf('File "%s" not found', $file));
        }

        // Charge toutes les données présentes en base de données.
        $elements = [];
        $elements['cities'] = 'city';
        $elements['areas'] = 'area';
        $elements['locations'] = 'location';
        $elements['managers'] = 'manager';
        $elements['categories'] = 'category';
        $elements['groupings'] = 'grouping';
        $elements['skills'] = 'skill';
        $elements['periods'] = 'period';
        $elements['courses'] = 'course';

        foreach ($elements as $containers => $objectname) {
            ${$containers} = [];
            $classname = sprintf('local_apsolu\core\%s', $objectname);
            foreach ($classname::get_records() as $record) {
                $name = trim((string) $record);
                ${$containers}[$name] = $record;
            }
        }

        $paymentcenters = [];

        $weekdays = [];
        $weekdays['Lundi'] = 'monday';
        $weekdays['Mardi'] = 'tuesday';
        $weekdays['Mercredi'] = 'wednesday';
        $weekdays['Jeudi'] = 'thursday';
        $weekdays['Vendredi'] = 'friday';
        $weekdays['Samedi'] = 'saturday';
        $weekdays['Dimanche'] = 'sunday';

        $first = true;
        while (($data = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            if ($first === true) {
                // Ignore la 1ère ligne du fichier.
                $first = false;
                continue;
            }

            // Nettoie le fichier (au cas où...).
            $data = array_map('trim', $data);

            [$city, $area, $location, $manager, $category, $event, $grouping, $skill,
                $weekday, $starttime, $endtime, $period, $paymentcenter, $teachers] = $data;

            if (in_array($city, ['Châteauroux', 'Paris', 'Tahiti'], $strict = true) === false) {
                continue;
            }

            if (isset($weekdays[$weekday]) === true) {
                $weekday = $weekdays[$weekday];
            } else {
                $weekday = 'monday';
            }

            if (isset($cities[$city]) === false) {
                $record = new Apsolu\city();
                $record->name = $city;
                $record->save();
                $cities[$city] = $record;
            }

            if (isset($areas[$area]) === false) {
                $record = new Apsolu\area();
                $record->name = $area;
                $record->cityid = $cities[$city]->id;
                $record->save();
                $areas[$area] = $record;
            }

            if (isset($managers[$manager]) === false) {
                $record = new Apsolu\manager();
                $record->name = $manager;
                $record->save();
                $managers[$manager] = $record;
            }

            if (isset($locations[$location]) === false) {
                $record = new Apsolu\location();
                $record->name = $location;
                $record->areaid = $areas[$area]->id;
                $record->managerid = $managers[$manager]->id;
                $record->save();
                $locations[$location] = $record;
            }

            if (isset($groupings[$grouping]) === false) {
                $record = new Apsolu\grouping();
                $record->name = $grouping;
                $record->save();
                $groupings[$grouping] = $record;
            }

            if (isset($categories[$category]) === false) {
                // Data.
                $categorydata = new stdClass();
                $categorydata->id = 0;
                $categorydata->name = $category;
                $categorydata->parent = $groupings[$grouping]->id;
                $categorydata->description = '';
                $categorydata->descriptionformat = 0;
                $categorydata->url = '';

                // Form.
                $groupingdata = [$groupings[$grouping]->id => $groupings[$grouping]->name];
                $context = context_system::instance();
                $itemid = 0;

                $customdata = ['category' => $categorydata, 'groupings' => $groupingdata,
                    'context' => $context, 'itemid' => $itemid];
                $mform = new local_apsolu_courses_categories_edit_form(null, $customdata);

                $editor = file_prepare_standard_editor(
                    $categorydata,
                    'description',
                    $mform->get_description_editor_options(),
                    $context,
                    'coursecat',
                    'description',
                    $itemid
                );
                $mform->set_data($editor);

                $record = new Apsolu\category();
                $record->save($categorydata, $mform);
                $categories[$category] = $record;
            }

            if (isset($skills[$skill]) === false) {
                $record = new Apsolu\skill();
                $record->name = $skill;
                $record->shortname = $skill;
                $record->save();
                $skills[$skill] = $record;
            }

            if (isset($periods[$period]) === false) {
                $record = new Apsolu\period();
                $record->name = $period;
                $record->save();
                $periods[$period] = $record;
            }

            $paymentcenters[$paymentcenter] = $paymentcenter;

            // Génère le créneau.
            $fullname = Apsolu\course::get_fullname(
                $categories[$category]->name,
                $event,
                $weekday,
                $starttime,
                $endtime,
                $skills[$skill]->name
            );

            if (isset($courses[$fullname]) === true) {
                continue;
            }

            $coursedata = new stdClass();
            $coursedata->event = $event;
            $coursedata->weekday = $weekday;
            $coursedata->starttime = $starttime;
            $coursedata->endtime = $endtime;
            $coursedata->on_homepage = 1;
            $coursedata->showpolicy = 0;
            $coursedata->category = $categories[$category]->id;
            $coursedata->str_category = $categories[$category]->name;
            $coursedata->periodid = $periods[$period]->id;
            $coursedata->locationid = $locations[$location]->id;
            $coursedata->skillid = $skills[$skill]->id;
            $coursedata->str_skill = $skills[$skill]->name;

            $course = new Apsolu\course();
            $course->save($coursedata);
            $courses[$fullname] = $course;

            self::setup_enrolments($course, $period, $teachers);
        }

        // Définit les centres de paiements.
        self::setup_payments($courses, $paymentcenters);

        // Renomme la catégorie par défaut.
        $category = $DB->get_record('course_categories', ['id' => 1]);
        if ($category !== false) {
            $category->name = 'Multisports';
            $DB->update_record('course_categories', $category);
        }
    }

    /**
     * Configure les méthodes d'inscription et les inscriptions dans les cours.
     *
     * @param Apsolu\course $apsolucourse Cours dans lequel configurer les méthodes d'inscription et les inscriptions.
     * @param string        $period       Valeur possible: "Annuelle", "Semestre 1" ou "Semestre 2".
     * @param string        $teachers     Liste des identifiants des utilisateurs devant avoir le rôle "enseignant éditeur",
     *                                    séparés par une virgule.
     *
     * @return void
     */
    private static function setup_enrolments(Apsolu\course $apsolucourse, string $period, string $teachers): void {
        global $DB;

        $course = $DB->get_record('course', ['id' => $apsolucourse->id], $fields = '*', MUST_EXIST);
        $users = $DB->get_records('user', ['deleted' => 0], $sort = '', $fields = 'username, id');
        $roles = $DB->get_records('role', $conditions = null, $sort = '', $fields = 'shortname, id');
        $calendars = $DB->get_records('apsolu_calendars', $conditions = null, $sort = '', $fields = 'name, id, enrolstartdate,
            enrolenddate, coursestartdate, courseenddate');
        $enrolinstances = enrol_get_instances($course->id, $enabled = null);
        $cohorts = [];
        foreach ($DB->get_records('cohort') as $cohort) {
            if (str_starts_with($cohort->name, 'Ensemble ') === false) {
                continue;
            }
            $cohorts[$cohort->id] = $cohort;
        }

        // Récupère les méthodes d'inscription manuelle et par voeux.
        $manualinstance = null;
        $selectinstances = [];
        foreach ($enrolinstances as $instance) {
            if ($instance->enrol === 'manual') {
                $manualinstance = $instance;
                continue;
            }

            if ($instance->enrol === 'select') {
                $selectinstances[$instance->id] = $instance;
                continue;
            }
        }

        // Initialise la méthode d'inscription manuelle.
        $manualplugin = enrol_get_plugin('manual');
        if ($manualinstance === null) {
            $instanceid = $manualplugin->add_instance($course, $manualplugin->get_instance_defaults());
            $manualinstance = $DB->get_record('enrol', ['id' => $instanceid]);
        }

        // Initialise les méthodes d'inscription par voeux.
        if ($period === 'Annuelle') {
            $expectedinstances = ['Semestre 1', 'Semestre 2'];
        } else {
            $expectedinstances = [$period];
        }

        foreach ($selectinstances as $instance) {
            $key = array_search($instance->name, $expectedinstances, $strict = true);
            if ($key === false) {
                continue;
            }

            unset($expectedinstances[$key]);
        }

        $selectplugin = enrol_get_plugin('select');
        foreach ($expectedinstances as $instancename) {
            $data = $selectplugin->get_instance_defaults();
            $data['name'] = $instancename;
            $data['customchar1'] = $calendars[$instancename]->id; // CalendarID.
            $data['enrolstartdate'] = $calendars[$instancename]->enrolstartdate;
            $data['enrolenddate'] = $calendars[$instancename]->enrolenddate;
            $data['customint7'] = $calendars[$instancename]->coursestartdate;
            $data['customint8'] = $calendars[$instancename]->courseenddate;
            $data['customint3'] = 1; // Active les quotas.
            $data['customint1'] = 15; // Quota sur liste principale.
            $data['customint2'] = 10; // Quota sur liste complémentaire.
            $instanceid = $selectplugin->add_instance($course, $data);
            $selectinstances[$instanceid] = $DB->get_record('enrol', ['id' => $instanceid]);

            foreach ($cohorts as $cohortid => $cohort) {
                $filter = null;
                if (str_contains($course->fullname, 'Masculin') === true || str_contains($course->fullname, '(H)') === true) {
                    $filter = 'homme';
                } else if (str_contains($course->fullname, 'Féminin') === true || str_contains($course->fullname, '(F)') === true) {
                    $filter = 'femme';
                }

                if ($filter !== null) {
                    $parts = explode('_', $cohort->idnumber);
                    $sex = end($parts);
                    if ($filter !== $sex) {
                        continue;
                    }
                }

                $DB->execute('INSERT INTO {enrol_select_cohorts}(enrolid, cohortid) VALUES(?, ?)', [$instanceid, $cohortid]);
            }

            foreach ([$roles['option']->id, $roles['bonification']->id, $roles['libre']->id] as $roleid) {
                $DB->execute('INSERT INTO {enrol_select_roles}(enrolid, roleid) VALUES(?, ?)', [$instanceid, $roleid]);
            }

            foreach ([] as $cardid) {
                $DB->execute('INSERT INTO {enrol_select_cards}(enrolid, cardid) VALUES(?, ?)', [$instanceid, $cardid]);
            }
        }

        // Attribue les droits enseignants selon les informations du fichier csv.
        foreach (explode(',', $teachers) as $teacher) {
            if (isset($users[$teacher]) === false) {
                continue;
            }

            $user = $users[$teacher];
            $manualplugin->enrol_user(
                $manualinstance,
                $user->id,
                $teacherroleid = 3,
                $timestart = 0,
                $timeend = 0,
                $status = ENROL_USER_ACTIVE
            );
        }

        // Attribue les droits étudiants sur les cours de l'utilisateur "lenseignante".
        if ($teachers !== 'lenseignante') {
            return;
        }

        foreach ($selectinstances as $instance) {
            $i = 0;
            $enroled = [];

            if (str_contains($course->fullname, 'Athlétisme') === true) {
                $instance->customint2 -= 5; // On laisse 5 places vacantes.
            }

            $sql = "SELECT cm.*, c.idnumber
                      FROM {cohort_members} cm
                      JOIN {cohort} c ON c.id = cm.cohortid
                      JOIN {enrol_select_cohorts} esc ON cm.cohortid = esc.cohortid
                     WHERE esc.enrolid = :enrolid";
            $records = $DB->get_records_sql($sql, ['enrolid' => $instance->id]);
            shuffle($records);
            foreach ($records as $record) {
                if (isset($enroled[$record->userid]) === true) {
                    continue;
                }

                if ($record->userid === $users['letudiant']->id) {
                    // Bloque toutes inscriptions pour l'utilisateur "letudiant".
                    continue;
                }

                if ($DB->count_records('user_enrolments', ['userid' => $record->userid]) > 2) {
                    // Empêche un même étudiant d'être inscrit sur 20 créneaux.
                    continue;
                }

                $role = explode('_', $record->idnumber)[0];

                if ($i < $instance->customint1) {
                    $status = $selectplugin::ACCEPTED;
                } else if ($i < ($instance->customint1 + $instance->customint2)) {
                    $status = $selectplugin::WAIT;
                } else {
                    break;
                }

                $selectplugin->enrol_user(
                    $instance,
                    $record->userid,
                    $roles[$role]->id,
                    $timestart = 0,
                    $timeend = 0,
                    $status
                );
                $i++;

                $enroled[$record->userid] = $record->userid;
            }
        }
    }

    /**
     * Met en place un environnement avec cours FFSU configuré.
     *
     * @return void
     */
    private static function setup_federation_course() {
        global $CFG, $DB;

        // Récupère l'année universitaire en cours.
        $academicyear = self::get_academic_year();

        // Crée un type de calendrier APSOLU.
        $calendartype = new stdClass();
        $calendartype->name = 'Général';
        $calendartype->id = $DB->insert_record('apsolu_calendars_types', $calendartype);

        // Crée un calendrier APSOLU.
        $calendar = new stdClass();
        $calendar->name = 'Calendrier FFSU';
        $calendar->enrolstartdate = make_timestamp($academicyear, 8, 1); // 1er août N.
        $calendar->enrolenddate = make_timestamp($academicyear + 1, 8, 1); // 1er août N+1.
        $calendar->coursestartdate = $calendar->enrolstartdate;
        $calendar->courseenddate = $calendar->enrolenddate;
        $calendar->reenrolstartdate = 0;
        $calendar->reenrolenddate = 0;
        $calendar->gradestartdate = 0;
        $calendar->gradeenddate = 0;
        $calendar->typeid = $calendartype->id;
        $calendar->id = $DB->insert_record('apsolu_calendars', $calendar);

        // Crée une cohorte.
        $cohort = $DB->get_record('cohort', ['idnumber' => 'FFSU']);
        if ($cohort === false) {
            $cohort = new stdClass();
            $cohort->name = 'FFSU';
            $cohort->idnumber = 'FFSU';
            $cohort->contextid = 1;
            $cohort->id = cohort_add_cohort($cohort);
        }

        // Crée un rôle.
        $role = $DB->get_record('role', ['shortname' => 'ffsu']);
        if ($role === false) {
            $archetype = 'student';
            $role = new stdClass();
            $role->id = create_role('Pratique FFSU', 'ffsu', '', $archetype);
            $contextlevels = array_keys(context_helper::get_all_levels());
            $archetyperoleid = $DB->get_field('role', 'id', ['shortname' => $archetype, 'archetype' => $archetype]);
            $contextlevels = get_role_contextlevels($archetyperoleid);
            set_role_contextlevels($role->id, $contextlevels);
            foreach (['assign', 'override', 'switch', 'view'] as $type) {
                $rolestocopy = get_default_role_archetype_allows($type, $archetype);
                foreach ($rolestocopy as $tocopy) {
                    $functionname = "core_role_set_{$type}_allowed";
                    $functionname($role->id, $tocopy);
                }
            }
            $sourcerole = $DB->get_record('role', ['id' => $archetyperoleid], $fields = '*', MUST_EXIST);
            role_cap_duplicate($sourcerole, $role->id);
        }

        $apsolurole = new Apsolu\role();
        $apsolurole->id = $role->id;
        $apsolurole->color = 'cornflowerblue';
        $apsolurole->fontawesomeid = 'star';
        $apsolurole->save();

        // Crée un centre de paiement.
        $center = new stdClass();
        $center->name = 'Association des étudiants';
        $center->prefix = 'ffsu-';
        $center->idnumber = '107975626';
        $center->sitenumber = '1999888';
        $center->rank = '43';
        $center->hmac = str_repeat('0123456789ABCDEF', 8);
        $center->id = $DB->insert_record('apsolu_payments_centers', $center);

        // Crée un tarif de paiement.
        $card = new stdClass();
        $card->name = 'Carte FFSU';
        $card->fullname = 'Carte FFSU';
        $card->trial = 0;
        $card->price = 25.50;
        $card->centerid = $center->id;
        $card->id = $DB->insert_record('apsolu_payments_cards', $card);
        $DB->execute('INSERT INTO {apsolu_payments_cards_cohort}(cardid, cohortid) VALUES(?, ?)', [$card->id, $cohort->id]);
        $DB->execute('INSERT INTO {apsolu_payments_cards_roles}(cardid, roleid) VALUES(?, ?)', [$card->id, $role->id]);
        $DB->execute(
            'INSERT INTO {apsolu_payments_cards_cals}(cardid, calendartypeid, value) VALUES(?, ?, 0)',
            [$card->id, $calendartype->id]
        );

        // Crée l'espace-cours.
        $course = new stdClass();
        $course->fullname = 'Adhésion FFSU';
        $course->shortname = 'FFSU';
        $course->category = 1;
        $federationcourse = create_course($course);

        // Ajoute la méthode d'inscription.
        $plugin = enrol_get_plugin('select');
        $enrolid = $plugin->add_instance($federationcourse, $plugin->get_instance_defaults());
        $enrol = $DB->get_record('enrol', ['id' => $enrolid]);
        $enrol->customchar1 = $calendar->id;
        $enrol->customint3 = 0; // Désactive les quotas.
        $enrol->customchar3 = $plugin::ACCEPTED;
        $DB->update_record('enrol', $enrol);
        $DB->execute('INSERT INTO {enrol_select_cohorts}(enrolid, cohortid) VALUES(?, ?)', [$enrol->id, $cohort->id]);
        $DB->execute('INSERT INTO {enrol_select_roles}(enrolid, roleid) VALUES(?, ?)', [$enrol->id, $role->id]);
        if (defined('APSOLU_DEMO') === true) {
            // Active les paiements sur l'instance de démo. Lors de tests Behat, ils ne sont pas activés par défaut.
            $DB->execute('INSERT INTO {enrol_select_cards}(enrolid, cardid) VALUES(?, ?)', [$enrol->id, $card->id]);
        }

        set_config('federation_course', $federationcourse->id, 'local_apsolu');

        $sql = "INSERT INTO {apsolu_complements} (id, price, federation) VALUES(:id, 0, 1)";
        $DB->execute($sql, ['id' => $federationcourse->id]);

        // Génère les activités FFSU et les groupes de cours correspondant.
        Federation\activity::synchronize_database();

        $course = new Federation\course();
        $course->set_groups();

        // Définit un numéro d'association.
        $numbers = ['AB00' => 'ENC Paris', 'AC01' => 'IUT Paris', 'AD03' => 'U. Paris'];
        foreach ($numbers as $id => $value) {
            $number = new Federation\number();
            $number->number = $id;
            $number->field = 'institution';
            $number->value = $value;
            $number->save();
        }

        // Ajoute les utilisateurs "etudiant" dans la cohorte FFSU.
        foreach ($DB->get_records('user', ['deleted' => 0]) as $user) {
            if (str_starts_with($user->username, 'etudiant') === false && $user->username !== 'letudiant') {
                continue;
            }

            cohort_add_member($cohort->id, $user->id);
        }
    }

    /**
     * Ajoute aléatoirement des notes aux étudiants.
     *
     * @return void
     */
    private static function setup_gradebooks() {
        global $DB;

        // Rend les étudiants en rôle "libre" non évaluables.
        $context = context_system::instance();
        foreach (['student', 'libre', 'ffsu'] as $shortname) {
            $role = $DB->get_record('role', ['shortname' => $shortname], '*', MUST_EXIST);
            role_change_permission($role->id, $context, 'local/apsolu:gradable', CAP_PROHIBIT);
        }

        // Change la configuration de $CFG->gradepointmax.
        $oldvalue = get_config('core', 'gradepointmax');
        add_to_config_log('gradepointmax', $oldvalue, '20', 'core');
        set_config('gradepointmax', '20');

        // Change la configuration de $CFG->gradepointdefault.
        $oldvalue = get_config('core', 'gradepointdefault');
        add_to_config_log('gradepointdefault', $oldvalue, '20', 'core');
        set_config('gradepointdefault', '20');

        // Charge les différentes options.
        $options = ['calendarstypes' => [], 'roles' => []];

        foreach ($DB->get_records('apsolu_calendars_types') as $record) {
            $options['calendarstypes'][] = $record->id;
            $calendartypes[$record->name] = $record->id;
        }

        $roles = [];
        foreach (Apsolu\gradebook::get_gradable_roles() as $record) {
            $options['roles'][] = $record->id;
            $roles[$record->shortname] = $record->id;
        }

        // Ajoute des éléments de notation.
        $elements = ['option' => ['Pratique (option)', 'Théorie (option)'], 'bonification' => ['Pratique (bonification)']];
        foreach ($elements as $rolename => $items) {
            foreach ($items as $itemname) {
                foreach ($calendartypes as $calendartype => $calendartypeid) {
                    if (str_starts_with($calendartype, 'Semestre') === false) {
                        continue;
                    }

                    $data = new stdClass();
                    $data->name = $itemname;
                    $data->roleid = $roles[$rolename];
                    $data->calendarid = $calendartypeid;
                    $data->grademax = get_config('core', 'gradepointmax');
                    $data->publicationdate = 1;

                    $gradeitem = new Apsolu\gradeitem();
                    $gradeitem->save($data);
                }
            }
        }

        // Génère une plage de notes possibles avec un poid plus fort pour les notes entre 10 et 15.
        $availablegrades = array_merge(range(0, 20), range(5, 17), range(10, 15), ['ABI', 'ABJ', '', '', '']);

        // Charge le carnet de notes.
        $grades = [];
        $gradebook = Apsolu\gradebook::get_gradebook($options);
        foreach ($gradebook->users as $user) {
            foreach ($user->grades as $grade) {
                if ($grade === null) {
                    continue;
                }

                $finalgrade = $availablegrades[array_rand($availablegrades)];
                if (empty($finalgrade) === true) {
                    // Valeur utilisée pour afficher des notes manquantes dans le carnet de notes.
                    continue;
                }

                $grades[$grade->inputname] = $finalgrade;
            }
        }

        // Enregistre les notes.
        Apsolu\gradebook::set_grades($grades);
    }

    /**
     * Configure les centres de paiement et les tarifs.
     *
     * @param array $courses Un tableau contenant des objets Apsolu\course.
     * @param array $centers Un tableau contenant le nom des centres de paiements à créer.
     *
     * @return void
     */
    private static function setup_payments(array $courses, array $centers) {
        global $DB;

        // Configure les dates d'ouverture de paiements.
        $academicyear = self::get_academic_year();

        set_config('payments_startdate', mktime(0, 0, 0, 8, 1, $academicyear), 'local_apsolu');
        set_config('payments_enddate', mktime(0, 0, 0, 8, 1, $academicyear + 1), 'local_apsolu');

        // Configure les adresses des serveurs Paybox de préproduction.
        set_config('paybox_servers_incoming', '195.101.99.76', 'local_apsolu');
        set_config('paybox_servers_outgoing', 'preprod-tpeweb.paybox.com', 'local_apsolu');

        // Configure les centres de paiements.
        $paymentcenters = $DB->get_records('apsolu_payments_centers', $conditions = null, $sort = '', $fields = 'name, id');
        foreach ($centers as $centername) {
            if (isset($paymentcenters[$centername]) === true) {
                continue;
            }

            $record = new stdClass();
            $record->name = $centername;
            $record->prefix = '';
            $record->idnumber = '107975626';
            $record->sitenumber = '1999888';
            $record->rank = '43';
            $record->hmac = str_repeat('0123456789ABCDEF', 8);
            $record->id = $DB->insert_record('apsolu_payments_centers', $record);

            $paymentcenters[$centername] = $record->id;
        }

        // Applique un tarif pour le rôle libre et les cohortes associées.
        $role = $DB->get_record('role', ['shortname' => 'libre'], '*', MUST_EXIST);

        $cohorts = [];
        foreach ($DB->get_records('cohort') as $cohort) {
            if (str_starts_with($cohort->idnumber, 'libre_') === false) {
                continue;
            }

            $cohorts[] = $cohort;
        }

        $calendartypes = $DB->get_records('apsolu_calendars_types');

        // Configure les tarifs.
        $cards = [];
        foreach ($paymentcenters as $centerid) {
            $card = new stdClass();
            $card->name = 'Carte pratique personnelle';
            $card->fullname = 'Carte SPORT';
            $card->trial = 0;
            $card->price = '15.00';
            $card->centerid = $centerid;
            $card->id = $DB->insert_record('apsolu_payments_cards', $card);
            $cards[$card->id] = $card;

            $DB->execute('INSERT INTO {apsolu_payments_cards_roles}(cardid, roleid) VALUES(?, ?)', [$card->id, $role->id]);

            foreach ($cohorts as $cohort) {
                $DB->execute('INSERT INTO {apsolu_payments_cards_cohort}(cardid, cohortid) VALUES(?, ?)', [$card->id, $cohort->id]);
            }

            foreach ($calendartypes as $calendartype) {
                $DB->execute(
                    'INSERT INTO {apsolu_payments_cards_cals}(cardid, calendartypeid, value) VALUES(?, ?, 0)',
                    [$card->id, $calendartype->id]
                );
            }
        }

        // Applique les tarifs sur tous les cours.
        $courseids = [];
        foreach ($courses as $course) {
            $courseids[$course->id] = $course->id;
        }

        $enrolments = $DB->get_records('enrol', ['enrol' => 'select']);
        foreach ($enrolments as $enrolment) {
            if (isset($courseids[$enrolment->courseid]) === false) {
                continue;
            }

            foreach ($cards as $card) {
                $DB->execute('INSERT INTO {enrol_select_cards}(enrolid, cardid) VALUES(?, ?)', [$enrolment->id, $card->id]);
            }
        }
    }

    /**
     * Configure les périodes.
     *
     * @return void
     */
    private static function setup_periods() {
        // Récupère l'année universitaire en cours.
        $academicyear = self::get_academic_year();

        // Génère les jours fériés.
        for ($i = 0; $i < 2; $i++) {
            foreach (Apsolu\holiday::get_holidays($academicyear + $i) as $timestamp) {
                $holiday = new Apsolu\holiday();
                $holiday->day = $timestamp;
                $holiday->save();
            }
        }

        // Génère 3 périodes : S1, S2 et annuelle.
        $weeks = [];
        $weeks['Semestre 1'] = [];
        $weeks['Semestre 2'] = [];
        $weeks['Annuelle'] = [];

        // Semestre 1: du dernier lundi de juillet au 4ème lundi de décembre.
        $oneweekinterval = new DateInterval('P7D');
        $start = new DateTime(sprintf('last monday of july %s', $academicyear));
        $end = new DateTime(sprintf('last monday of december %s', $academicyear));

        $period = new DatePeriod($start, $oneweekinterval, $end);
        foreach ($period as $datetime) {
            $week = $datetime->format('Y-m-d');
            $weeks['Semestre 1'][] = $week;
            $weeks['Annuelle'][] = $week;
        }

        // Semestre 2: du dernier lundi de décembre au dernier lundi de juillet n+1.
        $start = new DateTime(sprintf('last monday of december %s', $academicyear));
        $start->add($oneweekinterval);
        $end = new DateTime(sprintf('first monday of august %s', $academicyear + 1));

        $period = new DatePeriod($start, $oneweekinterval, $end);
        foreach ($period as $datetime) {
            $week = $datetime->format('Y-m-d');
            $weeks['Semestre 2'][] = $week;
            $weeks['Annuelle'][] = $week;
        }

        // Enregistre les périodes.
        foreach ($weeks as $name => $range) {
            $period = new Apsolu\period();
            $period->id = 0;
            $period->name = $name;
            $period->generic_name = $name;
            $period->weeks = implode(',', $range);
            $period->save();
        }
    }

    /**
     * Configure 3 rôles : Évalué (option), Évalué (bonification) et Non évalué.
     *
     * @return void
     */
    private static function setup_roles() {
        global $DB;

        // Génère les rôles.
        $roles = [];
        $roles[] = (object) ['name' => 'Évalué (option)', 'shortname' => 'option', 'color' => 'green', 'shape' => 'certificate',
            'archetype' => 'student', 'description' => 'Étudiants en formation qualifiante.'];
        $roles[] = (object) ['name' => 'Évalué (bonification)', 'shortname' => 'bonification', 'color' => 'orange',
            'shape' => 'certificate', 'archetype' => 'student',
            'description' => 'Étudiants en formation qualif. Seules comptent les notes au dessus de 10.'];
        $roles[] = (object) ['name' => 'Non évalué', 'shortname' => 'libre', 'color' => 'purple', 'shape' => 'circle',
            'archetype' => 'student', 'description' => 'Étudiants en formation personnelle. Aucune évaluation n\'est attendue.'];

        foreach ($roles as $role) {
            $record = $DB->get_record('role', ['shortname' => $role->shortname]);
            if ($record !== false) {
                $role->id = $record->id;
                continue;
            }

            // Procédure recopiée de la méthode create_role() du fichier lib/testing/generator/data_generator.php.
            $role->id = create_role($role->name, $role->shortname, $role->description, $role->archetype);

            $contextlevels = array_keys(context_helper::get_all_levels());

            if (empty($role->archetype) === false) {
                // Copying from the archetype default role.
                $archetyperoleid = $DB->get_field('role', 'id', ['shortname' => $role->archetype, 'archetype' => $role->archetype]);
                $contextlevels = get_role_contextlevels($archetyperoleid);
            }
            set_role_contextlevels($role->id, $contextlevels);

            if (empty($role->archetype) === false) {
                // We copy all the roles the archetype can assign, override, switch to and view.
                $types = ['assign', 'override', 'switch', 'view'];
                foreach ($types as $type) {
                    $rolestocopy = get_default_role_archetype_allows($type, $role->archetype);
                    foreach ($rolestocopy as $tocopy) {
                        $functionname = "core_role_set_{$type}_allowed";
                        $functionname($role->id, $tocopy);
                    }
                }

                // Copying the archetype capabilities.
                $sourcerole = $DB->get_record('role', ['id' => $archetyperoleid]);
                role_cap_duplicate($sourcerole, $role->id);
            }
        }

        foreach ($roles as $role) {
            $apsolurole = new Apsolu\role();
            $apsolurole->id = $role->id;
            $apsolurole->color = $role->color;
            $apsolurole->fontawesomeid = $role->shape;
            $apsolurole->save();
        }

        // Modifie les permissions d'attribution de rôle.
        $rolesallowassign = [];
        $rolesallowassign['manager'] = ['option', 'bonification', 'libre'];
        $rolesallowassign['editingteacher'] = ['option', 'bonification', 'libre'];

        $context = context_system::instance();
        foreach ($rolesallowassign as $source => $targets) {
            $fromrole = $DB->get_record('role', ['shortname' => $source]);
            if ($fromrole === false) {
                continue;
            }

            foreach ($targets as $target) {
                $targetrole = $DB->get_record('role', ['shortname' => $target]);
                if ($targetrole === false) {
                    continue;
                }

                if ($DB->get_record('role_allow_assign', ['roleid' => $fromrole->id, 'allowassign' => $targetrole->id]) !== false) {
                    continue;
                }

                core_role_set_assign_allowed($fromrole->id, $targetrole->id);
                \core\event\role_allow_assign_updated::create([
                    'context' => $context,
                    'objectid' => $fromrole->id,
                    'other' => ['targetroleid' => $targetrole->id, 'allow' => true],
                    ])->trigger();
            }
        }

        // Modifie les permissions de visibilité de rôle.
        $rolesallowviewable = [];
        $rolesallowviewable['option'] = 'student';
        $rolesallowviewable['bonification'] = 'student';
        $rolesallowviewable['libre'] = 'student';

        foreach ($rolesallowviewable as $rolename => $archetypename) {
            $role = $DB->get_record('role', ['shortname' => $rolename]);
            $archetype = $DB->get_record('role', ['shortname' => $archetypename]);
            if ($role === false || $archetype === false) {
                continue;
            }

            // On autorise les rôles qui ont le droit de voir le rôle $archetypename de voir aussi le rôle $rolename.
            $rolecanbeviewedby = $DB->get_records('role_allow_view', ['allowview' => $role->id], $sort = '', $fields = 'roleid');
            $archetypecanbeviewedby = $DB->get_records('role_allow_view', ['allowview' => $archetype->id]);
            foreach ($archetypecanbeviewedby as $row) {
                if (isset($rolecanbeviewedby[$row->roleid]) === true) {
                    continue;
                }

                $fromroleid = $row->roleid;
                $targetroleid = $role->id;

                $record = new stdClass();
                $record->roleid = $fromroleid;
                $record->allowview = $targetroleid;
                $DB->insert_record('role_allow_view', $record);

                \core\event\role_allow_view_updated::create([
                    'context' => $context,
                    'objectid' => $fromroleid,
                    'other' => ['targetroleid' => $targetroleid, 'allow' => true],
                    ])->trigger();
            }

            // On autorise le rôle '{{ role.shortname }}' de voir les rôles visibles par le rôle '{{ role.archetype }}'.
            $rolecanview = $DB->get_records('role_allow_view', ['roleid' => $role->id], $sort = '', $fields = 'allowview');
            $archetypecanview = $DB->get_records('role_allow_view', ['roleid' => $archetype->id]);
            foreach ($archetypecanview as $row) {
                if (isset($rolecanview[$row->allowview]) === true) {
                    continue;
                }

                $fromroleid = $role->id;
                $targetroleid = $row->allowview;

                $record = new stdClass();
                $record->roleid = $fromroleid;
                $record->allowview = $targetroleid;
                $DB->insert_record('role_allow_view', $record);

                \core\event\role_allow_view_updated::create([
                    'context' => $context,
                    'objectid' => $fromroleid,
                    'other' => ['targetroleid' => $targetroleid, 'allow' => true],
                    ])->trigger();
            }
        }
    }

    /**
     * Configure différents utilisateurs et les affecte dans les cohortes.
     *
     * - 1 gestionnaire
     * - 15 enseignants
     * - 60 étudiants
     *
     * @return void
     */
    private static function setup_users() {
        global $DB;

        $generator = new testing_data_generator();

        // Cycles d'études.
        $cycles = ['L1', 'L2', 'L3', 'M1', 'M2'];

        // UFR -> Départements.
        $ufrs = [];
        $ufrs['Arts'] = ['Arts plastiques', 'Arts du spectacle', 'Histoire de l’art et archéologie', 'Musique'];
        $ufrs['Langues'] = ['Allemand', 'Anglais', 'Espagnol', 'Italien', 'Langues celtiques'];
        $ufrs['Mathématiques'] = ['Mathématiques'];
        $ufrs['Sciences de la vie et de l’environnement'] = ['Biologie', 'Écologie', 'Santé'];
        $ufrs['Sciences et techniques des activités physiques et sportives'] = ['STAPS'];
        $ufrs['Sciences Humaines'] = ['Psychologie', 'Sciences de l’éducation', 'Sociologie'];
        $ufrkeys = array_keys($ufrs);

        // Institutions (avec un poid fort pour la valeur U. Paris).
        $institutions = ['ENC Paris', 'IUT Paris', 'U. Paris', 'U. Paris', 'U. Paris', 'U. Paris', 'U. Paris'];

        // Types de profil.
        $types = ['student' => get_string('defaultcoursestudent'), 'employee' => get_string('employee', 'local_apsolu')];

        // Liste de noms.
        $lastnames = ['Andre', 'Bernard', 'Bertrand', 'Blanc', 'Bonnet', 'Boyer', 'Chevalier', 'Clement', 'David', 'Dubois',
            'Dupont', 'Faure', 'Fournier', 'Francois', 'Garcia', 'Garnier', 'Gauthier', 'Gautier', 'Girard', 'Guerin', 'Guerin',
            'Henry', 'Lambert', 'Lefebvre', 'Lefevre', 'Legrand', 'Leroy', 'Martin', 'Masson', 'Mathieu', 'Mercier', 'Michel',
            'Moreau', 'Morel', 'Morin', 'Muller', 'Perrin', 'Petit', 'Richard', 'Robert', 'Robin', 'Rousseau', 'Roussel', 'Roux',
            'Thomas', 'Vincent'];

        // Liste de prénoms par genre.
        $firstnames = [];
        $firstnames['F'] = ['Agnès', 'Amandine', 'Ameline', 'Anne-Cécile', 'Béatrice', 'Brigitte', 'Carine', 'Catherine', 'Cécilia',
            'Céline', 'Chantal', 'Christèle', 'Christine', 'Claire', 'Corinne', 'Émilie', 'Fabienne', 'Gwenaëlle', 'Isabelle',
            'Juliette', 'Laurianne', 'Lucie', 'Magali', 'Marianne', 'Marie', 'Maud', 'Mireille', 'Mylène', 'Nathalie', 'Nelly',
            'Nouraya', 'Pascale', 'Patricia', 'Séverine', 'Solenn', 'Sophie', 'Stéphanie', 'Sylvie', 'Valérie'];
        $firstnames['M'] = ['Abdellah', 'Bruno', 'Christian', 'Christophe', 'Cyril', 'Damien', 'David', 'Denis', 'Dominique',
            'Édouard', 'Fabien', 'François', 'Frédéric', 'Gilles', 'Guillaume', 'Guy', 'Gwendal', 'Gwenn', 'Jean-Charles',
            'Jean-Christophe', 'Jean-François', 'Jean-Louis', 'Jefferson', 'Julien', 'Matthieu', 'Maxime', 'Mikaël', 'Olivier',
            'Pascal', 'Philippe', 'Sébastien', 'Serge', 'Sofiene', 'Stéphane', 'Tanguy', 'Théo', 'Valentin', 'Yann'];

        // Génère des données pour les 3 utilisateurs de démonstration.
        $password = null;
        if (defined('APSOLU_DEMO') === true) {
            $password = 'apsolu';
        }

        $users = [];
        $users[] = ['username' => 'letudiant', 'password' => $password, 'idnumber' => '20160001', 'firstname' => 'Léo',
            'lastname' => 'Bobet', 'institution' => 'U. Paris', 'sex' => 'M', 'policyagreed' => 0, 'type' => 'student'];
        $users[] = ['username' => 'lenseignante', 'password' => $password, 'firstname' => 'Marguerite', 'lastname' => 'Broquedis',
            'institution' => 'U. Paris', 'sex' => 'F', 'policyagreed' => 1, 'type' => 'employee'];
        $users[] = ['username' => 'legestionnaire', 'password' => $password, 'firstname' => 'Bernard', 'lastname' => 'Moquette',
            'institution' => 'U. Paris', 'sex' => 'M', 'policyagreed' => 1, 'type' => 'employee'];

        // Génère des données avec un profil enseignant.
        for ($i = 1; $i < 15; $i++) {
            if ($i % 2 === 0) {
                $sex = 'M';
            } else {
                $sex = 'F';
            }
            $lastname = $lastnames[array_rand($lastnames)];
            $firstname = $firstnames[$sex][array_rand($firstnames[$sex])];

            $users[] = ['username' => sprintf('enseignant%s', $i), 'firstname' => $firstname, 'lastname' => $lastname,
                'sex' => $sex, 'institution' => 'U. Paris', 'policyagreed' => 1, 'type' => 'employee'];
        }

        // Génère des données avec un profil étudiant.
        $idnumbers = [];
        for ($i = 1; $i < 60; $i++) {
            if ($i % 2 === 0) {
                $sex = 'M';
            } else {
                $sex = 'F';
            }
            $lastname = $lastnames[array_rand($lastnames)];
            $firstname = $firstnames[$sex][array_rand($firstnames[$sex])];

            do {
                $idnumber = date('Y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            } while (isset($idnumbers[$idnumber]) === true);
            $idnumbers[$idnumber] = $idnumber;

            $users[] = ['username' => sprintf('etudiant%s', $i), 'firstname' => $firstname, 'lastname' => $lastname,
                'idnumber' => $idnumber, 'sex' => $sex, 'policyagreed' => 0, 'type' => 'student'];
        }

        // Crée les comptes des utilisateurs.
        foreach ($users as $i => $user) {
            $customfields = [];

            if ($user['type'] === 'student') {
                $ufr = $ufrkeys[array_rand($ufrkeys)];
                $department = $ufrs[$ufr][array_rand($ufrs[$ufr])];

                $user['department'] = $department;
            }

            if (isset($user['institution']) === false) {
                $user['institution'] = $institutions[array_rand($institutions)];
            }

            if (isset($user['password']) === false) {
                $user['password'] = $user['username'];
            }

            // Enregistre le compte.
            $record = $generator->create_user($user);
            $users[$i]['id'] = $record->id;

            // Enregistre les champs de profil.
            if ($user['type'] === 'student') {
                $customfields[] = (object) ['id' => $record->id, 'profile_field_apsolucycle' => $cycles[array_rand($cycles)]];
                $customfields[] = (object) ['id' => $record->id, 'profile_field_apsoluufr' => $ufr];
            }

            $customfields[] = (object) ['id' => $record->id, 'profile_field_apsolusex' => $user['sex']];
            $customfields[] = (object) ['id' => $record->id, 'profile_field_apsolusesame' => 1];
            $customfields[] = (object) ['id' => $record->id, 'profile_field_apsoluusertype' => $types[$user['type']]];

            foreach ($customfields as $customfield) {
                profile_save_data($customfield);
            }
        }

        // Attribue le rôle gestionnaire.
        $role = $DB->get_record('role', ['shortname' => 'manager'], $fields = '*', MUST_EXIST);
        $manager = $DB->get_record('user', ['username' => 'legestionnaire', 'deleted' => 0], $fields = '*', MUST_EXIST);
        $context = context_system::instance();
        role_assign($role->id, $manager->id, $context->id);

        // Affecte les étudiants dans les cohortes.
        $cohorts = $DB->get_records('cohort');
        foreach ($users as $user) {
            if (str_starts_with($user['username'], 'etudiant') === false && $user['username'] !== 'letudiant') {
                continue;
            }

            if ($user['sex'] === 'M') {
                $sex = 'homme';
            } else {
                $sex = 'femme';
            }

            foreach ($cohorts as $cohort) {
                if ($cohort->idnumber === null) {
                    continue;
                }

                if (str_ends_with($cohort->idnumber, $sex) === false) {
                    continue;
                }

                cohort_add_member($cohort->id, $user['id']);
            }
        }
    }
}
