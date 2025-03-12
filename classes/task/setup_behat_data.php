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

namespace local_apsolu\task;

use local_apsolu\tests\behat\dataset_provider;

/**
 * Classe représentant la tâche permettant d'initier un jeu de données pour les tests Behat.
 *
 * @package    local_apsolu
 * @copyright  2025 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setup_behat_data extends \core\task\adhoc_task {
    /**
     * Retourne le nom de la tâche (information utilisée dans les pages d'administration).
     *
     * @return string
     */
    public function get_name() {
        return get_string('setup_behat_data', 'local_apsolu');
    }

    /**
     * Execute la tâche.
     *
     * @throws coding_exception Lève une exception lorsque la tâche n'est pas exécute par Behat.
     *
     * @return void
     */
    public function execute() {
        global $CFG;

        if (defined('BEHAT_UTIL') === false) {
            throw new coding_exception('This task can be only used by Behat CLI tool');
        }

        require_once($CFG->dirroot.'/local/apsolu/tests/behat/dataset_provider.php');

        dataset_provider::execute();
    }
}
