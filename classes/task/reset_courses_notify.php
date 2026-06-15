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

use Throwable;
use local_apsolu\core\reset;
use local_apsolu\task\reset_courses as resetTask;
use local_apsolu\core\messaging as mailer;
use core\output\html_writer;
use core\context\system as context_system;
use stdClass;
use core\task\manager;
use DateTime;

/**
 * Classe représentant la tâche permettant d'envoyer les emails de rappel avant la réinitalisation annuelle de la plateforme.
 *
 * Elle utilise les variables enregistrées dans la table de configuration du plugin local_apsolu.
 *
 * @package   local_apsolu
 * @copyright 2026 Université Rennes 2
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reset_courses_notify extends \core\task\adhoc_task {
    /**
     * Retourne le nom de la tâche.
     *
     * @return string
     */
    public function get_name(): string {
        // Shown in admin screens.
        return get_string('reset_courses_notify', 'local_apsolu');
    }

    /**
     * Positionne la valeur du nextruntime de la tâche de notifications : à partir de la date d'exécution de la tâche
     * de réinitialisation, on calcule le runtime correspondant à l'envoi des mails soit le jour précédent l'exécution, à 08h00.
     *
     * @param mixed $resetruntime la date d'exécution de la prochaine réinitialisation.
     * @return void
     */
    public function set_notify_runtime($resetruntime) {
        $notifyruntime = new DateTime();
        $daybefore = $resetruntime - (24 * 3600); // Jour de l'exécution - 24H.
        $notifyruntime->setTimestamp($daybefore)->setTime(8, 0); // On passe l'heure à 08h00.
        $this->set_next_run_time($notifyruntime->getTimeStamp());
    }

     /**
      * Execute la tâche.
      *
      * @return void
      */
    public function execute(): void {
        global $CFG, $DB;
        try {
            // Récupère la configuration.
            $resetconf = new reset();

            // On vérifie si la tâche de réinitilisation existe bien et on récupère sa date d'exécution.
            $resettask = manager::get_queued_adhoc_task_record(new resetTask());
             // On vérifie les paramètres qui seront testés lors de l'exécution (statut et date programmée).
            if (
                false !== $resettask &&
                $resetconf->load_default_settings() != false &&
                $resetconf->nextactive != false &&
                $resetconf->nextdatetime != null
            ) {
                $rundatetime = userdate($resettask->nextruntime, get_string('strftimedatetimewithyear', 'local_apsolu'));
                $taskstatus =
                    rtrim(get_string('reset_is_activated', 'local_apsolu', $rundatetime), '.') .
                    get_string('reset_settings_for_activation', 'local_apsolu', '');

                // Début du mail (la prochaine réinitialisation sera exécutée le ... avec les paramètres suivants : ).
                $htmlstatus = html_writer::tag('p', $taskstatus);

                // Rappel des paramètres utilisé pour la tâche.
                $htmllist = $resetconf->get_settings_html_list("", ['nextdatetime', 'nextactive'], [], true);

                // Envoie du mail.
                mailer::notify_users_by_capability(
                    context_system::instance(),
                    'local/apsolu:resetsettings',
                    get_string('settings_reset_courses_notify', 'local_apsolu'),
                    $htmlstatus . $htmllist
                );

                mtrace('Envoi d\'un email de notification en vue de l\'exécution de la tâche ' .
                'de réinitialisation des espaces cours qui sera exécutée le ' . $rundatetime);
            } else {
                mtrace('La procédure de réinitialisation des espaces-cours (rentrée ' . date('Y', time()) . ') ne sera pas ' .
                'correctement exécutée car la configuration actuelle n\'est pas valide. Les utilisateurs n\'ont pas été notifiés.');
            }
        } catch (Throwable $exception) {
            mtrace($exception->getMessage());
            throw $exception;
        }
    }
}
