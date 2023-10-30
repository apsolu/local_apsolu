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
 * Classe gérant de nouvelles instructions behat.
 *
 * @package    local_apsolu
 * @copyright  2023 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Behat\Context\Step\Given as Given;
use Behat\Behat\Context\Step\When as When;
use Behat\Gherkin\Node\TableNode as TableNode;
use local_apsolu\core\federation\activity as Activity;
use local_apsolu\core\federation\number as Number;

/**
 * Classe gérant de nouvelles instructions behat.
 *
 * @package    local_apsolu
 * @copyright  2023 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_local_apsolu extends behat_base {
    /**
     * Configure les blocs, les méthodes d'inscription et le thème APSOLU.
     *
     * @Given /^I setup APSOLU blocks and theme$/
     */
    public function i_setup_apsolu_blocks_and_theme() {
        global $CFG, $DB;

        // Charge l'API pour gérer le tableau de bord.
        require_once($CFG->dirroot . '/my/lib.php');
        require_once($CFG->libdir . '/blocklib.php');

        set_config('customfrontpageinclude', $CFG->dirroot . '/theme/apsolu/index.php');
        set_config('debug', '30719');
        set_config('manual,select', 'enrol_plugins_enabled');
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

            $blockinstance = new \stdClass();
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
     * Met en place un environnement avec cours FFSU configuré.
     *
     * @Given /^I setup a federation environment$/
     */
    public function i_setup_a_federation_environment() {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/cohort/lib.php');
        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/group/lib.php');

        // Crée un type de calendrier APSOLU.
        $calendartype = new stdClass();
        $calendartype->name = 'Général';
        $calendartype->id = $DB->insert_record('apsolu_calendars_types', $calendartype);

        // Crée un calendrier APSOLU.
        $calendar = new stdClass();
        $calendar->name = 'Calendrier FFSU';
        $calendar->enrolstartdate = time() - 24 * 60 * 60;
        $calendar->enrolenddate = time() + 24 * 60 * 60;
        $calendar->coursestartdate = $calendar->enrolstartdate;
        $calendar->courseenddate = time() + 48 * 60 * 60;
        $calendar->reenrolstartdate = 0;
        $calendar->reenrolenddate = 0;
        $calendar->gradestartdate = 0;
        $calendar->gradeenddate = 0;
        $calendar->typeid = $calendartype->id;
        $calendar->id = $DB->insert_record('apsolu_calendars', $calendar);

        // Crée une cohorte.
        $cohort = new stdClass();
        $cohort->name = 'FFSU';
        $cohort->contextid = 1;
        $cohort->id = cohort_add_cohort($cohort);

        // Crée un rôle.
        $archetype = 'student';
        $roleid = create_role('adhérent ffsu', 'ffsu', '', $archetype);
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
        foreach (Activity::get_records() as $activity) {
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
        $number = new Number();
        $number->number = 'AB00';
        $number->field = 'department';
        $number->value = 'mathematics';
        $number->save();

        // Ajoute l'étudiant "student2" dans la cohorte FFSU.
        $user = $DB->get_record('user', ['username' => 'student2'], $fields = '*', MUST_EXIST);
        cohort_add_member($cohort->id, $user->id);
    }
}
