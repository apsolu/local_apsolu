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
use local_apsolu\core\federation as Federation;
use local_apsolu_courses_categories_edit_form;
use stdClass;
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

        require_once($CFG->dirroot.'/cohort/lib.php');
        require_once($CFG->dirroot.'/course/lib.php');
        require_once($CFG->dirroot.'/group/lib.php');
        require_once($CFG->dirroot.'/lib/blocklib.php');
        require_once($CFG->dirroot.'/lib/testing/generator/data_generator.php');

        session_manager::init_empty_session();
        session_manager::set_user(get_admin());

        self::setup_blocks_and_theme();
        self::setup_config();
        self::setup_roles();
        self::setup_cohorts();
        self::setup_users();
        self::setup_periods();
        self::setup_calendars();
        self::setup_courses();
        self::setup_federation_course();
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
            $instance->gradestartdate = 0;
            $instance->gradeenddate = 0;
            $instance->typeid = $calendartype->id;

            $DB->insert_record('apsolu_calendars', $instance);
        }
    }

    /**
     * Configure les cohortes et les populations.
     *
     * 6 cohortes :
     *  - Évalué (bonification/sport facultatif) Femme
     *  - Évalué (bonification/sport facultatif) Homme
     *  - Évalué (option) Femme
     *  - Évalué (option) Homme
     *  - Libre Femme
     *  - Libre Homme
     *
     * 3 populations :
     *  - Population Évalué (bonification/sport facultatif)
     *  - Population Évalué (option)
     *  - Population Libre
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
                $cohort->name = sprintf('%s %s', $role->name, $sex);
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

        set_config('debug', '30719');

        // Supprime le bouton de connexion anonyme.
        $oldvalue = get_config('core', 'guestloginbutton');
        add_to_config_log('guestloginbutton', $oldvalue, '0', 'core');
        set_config('guestloginbutton', 0);

        // Désactive tous les modèles d'analyse de données.
        $DB->set_field('analytics_models', 'enabled', '0');

        // Désactive toutes les visites guidées.
        $DB->set_field('tool_usertours_tours', 'enabled', '0');

        // Change la configuration de $CFG->gradepointmax.
        $oldvalue = get_config('core', 'gradepointmax');
        add_to_config_log('gradepointmax', $oldvalue, '20', 'core');
        set_config('gradepointmax', '20');

        // Change la configuration de $CFG->gradepointdefault.
        $oldvalue = get_config('core', 'gradepointdefault');
        add_to_config_log('gradepointdefault', $oldvalue, '20', 'core');
        set_config('gradepointdefault', '20');

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

            list($city, $area, $location, $manager, $category, $event, $grouping, $skill,
                $weekday, $starttime, $endtime, $period, $paymentcenter, $teachers, $students) = $data;

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

                $editor = file_prepare_standard_editor($categorydata, 'description', $mform->get_description_editor_options(),
                    $context, 'coursecat', 'description', $itemid);
                $mform->set_data($editor);

                $record = new Apsolu\category();
                $record->save($categorydata, $mform);
                $categories[$category] = $record;
            }

            if (isset($skills[$skill]) === false) {
                $record = new Apsolu\skill();
                $record->name = $skill;
                $record->save();
                $skills[$skill] = $record;
            }

            if (isset($periods[$period]) === false) {
                $record = new Apsolu\period();
                $record->name = $period;
                $record->save();
                $periods[$period] = $record;
            }

            // Génère le créneau.
            $fullname = Apsolu\course::get_fullname($categories[$category]->name, $event,
                $weekday, $starttime, $endtime, $skills[$skill]->name);

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

            self::setup_enrolments($course, $period, $teachers, $students);
        }

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
     * @param string        $students     Liste des identifiants des utilisateurs devant avoir le rôle "évalué (option)", séparés
     *                                    par une virgule.
     *
     * @return void
     */
    private static function setup_enrolments(Apsolu\course $apsolucourse, string $period, string $teachers,
            string $students): void {
        global $DB;

        $course = $DB->get_record('course', ['id' => $apsolucourse->id], $fields = '*', MUST_EXIST);
        $users = $DB->get_records('user', $conditions = null, $sort = '', $fields = 'username, id');
        $roles = $DB->get_records('role', $conditions = null, $sort = '', $fields = 'shortname, id');
        $cohorts = $DB->get_records('cohort');
        $calendars = $DB->get_records('apsolu_calendars', $conditions = null, $sort = '', $fields = 'name, id');
        $enrolinstances = enrol_get_instances($course->id, $enabled = null);

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
            $instanceid = $selectplugin->add_instance($course, $data);
            $selectinstances[$instanceid] = $DB->get_record('enrol', ['id' => $instanceid]);

            foreach (array_keys($cohorts) as $cohortid) {
                $DB->execute('INSERT INTO {enrol_select_cohorts}(enrolid, cohortid) VALUES(?, ?)', [$instanceid, $cohortid]);
            }

            foreach ([$roles['option']->id, $roles['bonification']->id, $roles['libre']->id] as $roleid) {
                $DB->execute('INSERT INTO {enrol_select_roles}(enrolid, roleid) VALUES(?, ?)', [$instanceid, $roleid]);
            }

            foreach ([] as $cardid) {
                $DB->execute('INSERT INTO {enrol_select_cards}(enrolid, cardid) VALUES(?, ?)', [$instanceid, $cardid]);
            }
        }

        // Attribue les droits enseignants.
        foreach (explode(',', $teachers) as $teacher) {
            if (isset($users[$teacher]) === false) {
                continue;
            }

            $user = $users[$teacher];
            $manualplugin->enrol_user($manualinstance, $user->id, $teacherroleid = 3,
                $timestart = 0, $timeend = 0, $status = ENROL_USER_ACTIVE);
        }

        // Attribue les droits étudiants.
        foreach (explode(',', $students) as $student) {
            if (isset($users[$student]) === false) {
                continue;
            }

            $user = $users[$student];
            foreach ($selectinstances as $instance) {
                $selectplugin->enrol_user($instance, $user->id, $roles['option']->id,
                    $timestart = 0, $timeend = 0, $status = ENROL_USER_ACTIVE);
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
        $calendar->enrolenddate = make_timestamp($academicyear + 1, 8, 1);; // 1er août N+1.
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
        if ($role !== false) {
            $roleid = $role->id;
        } else {
            $archetype = 'student';
            $roleid = create_role('Pratique FFSU', 'ffsu', '', $archetype);
            $contextlevels = array_keys(context_helper::get_all_levels());
            $archetyperoleid = $DB->get_field('role', 'id', ['shortname' => $archetype, 'archetype' => $archetype]);
            $contextlevels = get_role_contextlevels($archetyperoleid);
            set_role_contextlevels($roleid, $contextlevels);
            foreach (['assign', 'override', 'switch', 'view'] as $type) {
                $rolestocopy = get_default_role_archetype_allows($type, $archetype);
                foreach ($rolestocopy as $tocopy) {
                    $functionname = "core_role_set_{$type}_allowed";
                    $functionname($roleid, $tocopy);
                }
            }
            $sourcerole = $DB->get_record('role', ['id' => $archetyperoleid], $fields = '*', MUST_EXIST);
            role_cap_duplicate($sourcerole, $roleid);
        }

        // Crée un centre de paiement.
        $center = new stdClass();
        $center->name = 'Association des étudiants';
        $center->prefix = '';
        $center->idnumber = '';
        $center->sitenumber = '';
        $center->rank = '';
        $center->hmac = '';
        $center->id = $DB->insert_record('apsolu_payments_cards', $center);

        // Crée un tarif de paiement.
        $card = new stdClass();
        $card->name = 'Carte FFSU';
        $card->fullname = 'Carte FFSU';
        $card->trial = 0;
        $card->price = 25.50;
        $card->centerid = $center->id;
        $card->id = $DB->insert_record('apsolu_payments_cards', $card);
        $DB->execute('INSERT INTO {apsolu_payments_cards_cohort}(cardid, cohortid) VALUES(?, ?)', [$card->id, $cohort->id]);
        $DB->execute('INSERT INTO {apsolu_payments_cards_roles}(cardid, roleid) VALUES(?, ?)', [$card->id, $roleid]);
        $DB->execute('INSERT INTO {apsolu_payments_cards_cals}(cardid, calendartypeid, value) VALUES(?, ?, 0)',
            [$card->id, $calendartype->id]);

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
        $DB->execute('INSERT INTO {enrol_select_cohorts}(enrolid, cohortid) VALUES(?, ?)', [$enrol->id, $cohort->id]);
        $DB->execute('INSERT INTO {enrol_select_roles}(enrolid, roleid) VALUES(?, ?)', [$enrol->id, $roleid]);
        $DB->execute('INSERT INTO {enrol_select_cards}(enrolid, cardid) VALUES(?, ?)', [$enrol->id, $card->id]);

        set_config('federation_course', $federationcourse->id, 'local_apsolu');

        $sql = "INSERT INTO {apsolu_complements} (id, price, federation) VALUES(:id, 0, 1)";
        $DB->execute($sql, ['id' => $federationcourse->id]);

        // Génère les groupes correspondant aux activités FFSU.
        $groups = $DB->get_records('groups', ['courseid' => $federationcourse->id], $sort = '', $fields = 'name');
        foreach (Federation\activity::get_records() as $activity) {
            if (isset($groups[$activity->name]) === true) {
                continue;
            }

            $group = new stdClass();
            $group->name = $activity->name;
            $group->courseid = $federationcourse->id;
            $group->timecreated = time();
            $group->timemodified = $group->timecreated;
            groups_create_group($group);
        }

        // Définit un numéro d'association.
        $number = new Federation\number();
        $number->number = 'AB00';
        $number->field = 'department';
        $number->value = 'mathematics';
        $number->save();

        // Ajoute les utilisateurs "etudiant" dans la cohorte FFSU.
        foreach ($DB->get_records('user') as $user) {
            if (str_starts_with($user->username, 'etudiant') === false && $user->username !== 'letudiant') {
                continue;
            }

            $user->department = 'mathematics';
            $DB->update_record('user', $user);

            cohort_add_member($cohort->id, $user->id);
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
        for ($i = 0; $i < 1; $i++) {
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

        // Semestre 1: du 2nd lundi de septembre au 2nd lundi de décembre.
        $interval = new DateInterval('P7D');
        $start = (new DateTime(sprintf('%s-09-01 00:00:00', $academicyear)))->modify('second monday');
        $end = (new DateTime(sprintf('%s-12-01 00:00:00', $academicyear)))->modify('second monday');

        $period = new DatePeriod($start, $interval, $end);
        foreach ($period as $datetime) {
            $week = $datetime->format('Y-m-d');
            $weeks['Semestre 1'][] = $week;
            $weeks['Annuelle'][] = $week;
        }

        // Semestre 2: du 2nd lundi de janvier au 2nd lundi de juillet.
        $start = (new DateTime(sprintf('%s-01-01 00:00:00', $academicyear + 1)))->modify('second monday');
        $end = (new DateTime(sprintf('%s-07-01 00:00:00', $academicyear + 1)))->modify('second monday');

        $period = new DatePeriod($start, $interval, $end);
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
     * Configure 3 rôles : Évalué (option), Évalué (bonification/sport facultatif) et Libre.
     *
     * @return void
     */
    private static function setup_roles() {
        global $DB;

        // Génère les rôles.
        $roles = [];
        $roles[] = (object) ['name' => 'Évalué (option)', 'shortname' => 'option', 'color' => 'green', 'archetype' => 'student',
            'description' => 'Étudiants en formation qualifiante.'];
        $roles[] = (object) ['name' => 'Évalué (bonification/sport facultatif)', 'shortname' => 'bonification', 'color' => 'orange',
            'archetype' => 'student', 'description' => 'Étudiants en formation qualif. Seules comptent les notes au dessus de 10.'];
        $roles[] = (object) ['name' => 'Libre', 'shortname' => 'libre', 'color' => 'purple', 'archetype' => 'student',
            'description' => 'Étudiants en formation personnelle. Aucune évaluation n\'est attendue.'];

        foreach ($roles as $role) {
            if ($DB->get_record('role', ['shortname' => $role->shortname]) !== false) {
                continue;
            }

            // Procédure recopiée de la méthode create_role() du fichier lib/testing/generator/data_generator.php.
            $newroleid = create_role($role->name, $role->shortname, $role->description, $role->archetype);

            $contextlevels = array_keys(context_helper::get_all_levels());

            if (empty($role->archetype) === false) {
                // Copying from the archetype default role.
                $archetyperoleid = $DB->get_field('role', 'id', ['shortname' => $role->archetype, 'archetype' => $role->archetype]);
                $contextlevels = get_role_contextlevels($archetyperoleid);
            }
            set_role_contextlevels($newroleid, $contextlevels);

            if (empty($role->archetype) === false) {
                // We copy all the roles the archetype can assign, override, switch to and view.
                $types = ['assign', 'override', 'switch', 'view'];
                foreach ($types as $type) {
                    $rolestocopy = get_default_role_archetype_allows($type, $role->archetype);
                    foreach ($rolestocopy as $tocopy) {
                        $functionname = "core_role_set_{$type}_allowed";
                        $functionname($newroleid, $tocopy);
                    }
                }

                // Copying the archetype capabilities.
                $sourcerole = $DB->get_record('role', ['id' => $archetyperoleid]);
                role_cap_duplicate($sourcerole, $newroleid);
            }

            $apsolurole = new Apsolu\role();
            $apsolurole->id = $newroleid;
            $apsolurole->color = $role->color;
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

        $password = 'apsolu';

        $users = [];
        $users[] = ['username' => 'lenseignante', 'password' => $password, 'firstname' => 'Marguerite', 'lastname' => 'Broquedis'];
        for ($i = 1; $i < 15; $i++) {
            $value = sprintf('enseignant%s', $i);
            $users[] = ['username' => $value, 'password' => $value];
        }

        $users[] = ['username' => 'letudiant', 'password' => $password];
        for ($i = 1; $i < 60; $i++) {
            $value = sprintf('etudiant%s', $i);
            $users[] = ['username' => $value, 'password' => $value];
        }

        $generator = new testing_data_generator();
        foreach ($users as $user) {
            $generator->create_user($user);
        }

        $manager = ['username' => 'legestionnaire', 'password' => $password, 'firstname' => 'Bernard', 'lastname' => 'Moquette'];
        $generator->create_user($manager);

        $role = $DB->get_record('role', ['shortname' => 'manager'], $fields = '*', MUST_EXIST);
        $manager = $DB->get_record('user', ['username' => 'legestionnaire'], $fields = '*', MUST_EXIST);
        $context = context_system::instance();
        role_assign($role->id, $manager->id, $context->id);

        // Affectation aux cohortes.
        $cohorts = $DB->get_records('cohort');
        foreach ($DB->get_records('user') as $user) {
            if (str_starts_with($user->username, 'etudiant') === false && $user->username !== 'letudiant') {
                continue;
            }

            $index = array_search($user->firstname, $generator->firstnames, $strict = true);
            if ($index === false) {
                continue;
            }

            if (intval($index / 5) % 2 === 0) {
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

                cohort_add_member($cohort->id, $user->id);
            }
        }
    }
}
