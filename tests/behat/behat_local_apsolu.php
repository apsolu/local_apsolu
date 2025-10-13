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

use Behat\Behat\Context\Step\Given;
use Behat\Behat\Context\Step\When;
use Behat\Gherkin\Node\TableNode;
use local_apsolu\tests\behat\dataset_provider;

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');
require_once(__DIR__ . '/../../../../cohort/lib.php');

/**
 * Classe gérant de nouvelles instructions behat.
 *
 * @package    local_apsolu
 * @copyright  2023 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_local_apsolu extends behat_base {
    /**
     * Simule l'étape d'inscription à un créneau APSOLU.
     *
     * @When /^I click on "(?P<activity_string>[^"]*)" "(?P<weekday_string>[^"]*)" "(?P<time_string>[^"]*)" course$/
     *
     * @param string $activity Element we look for
     * @param string $weekday The type of what we look for
     * @param string $time The type of what we look for
     * @return void
     */
    public function i_click_on_course(string $activity, string $weekday, string $time) {
        $activityliteral = behat_context_helper::escape($activity);
        $weekdayliteral = behat_context_helper::escape($weekday);
        $timeliteral = behat_context_helper::escape($time);

        $tableid = behat_context_helper::escape('apsolu-activities-table');

        $xpathcontains = [];
        $xpathcontains[] = sprintf('contains(normalize-space(.), %s)', $activityliteral);
        $xpathcontains[] = sprintf('contains(normalize-space(.), %s)', $weekdayliteral);
        $xpathcontains[] = sprintf('contains(normalize-space(.), %s)', $timeliteral);

        $xpath = sprintf("//table[@id=%s]//tr[%s]/td[1]/a", $tableid, implode(' and ', $xpathcontains));
        $this->find('xpath', $xpath)->click();
    }

    /**
     * Configure un environnement complet APSOLU avec un jeu de données initialisé.
     *
     * @Given /^I setup an environment for APSOLU$/
     */
    public function i_setup_an_environment_for_apsolu() {
        global $CFG;

        require_once($CFG->dirroot . '/local/apsolu/tests/behat/dataset_provider.php');

        dataset_provider::execute();
    }

    /**
     * Met en place un environnement avec cours FFSU configuré.
     *
     * @Given /^I setup a federation environment$/
     */
    public function i_setup_a_federation_environment() {
        $this->i_setup_an_environment_for_apsolu();
    }
}
