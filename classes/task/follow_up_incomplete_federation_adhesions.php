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

use core_user;
use local_apsolu\core\federation\adhesion as Adhesion;
use local_apsolu\core\federation\course as FederationCourse;

/**
 * Classe représentant la tâche pour relancer inscriptions incomplètes à la FFSU.
 *
 * Une fois par jour, on recherche tous les utilisateurs ayant un dossier d'adhésion complet, mais qui n'ont pas encore fait la
 * demande de licence FFSU (cliqué sur le dernier bouton du formulaire).
 *
 * Afin de ne pas spammer trop régulièrement, on se limite à une notification par semaine.
 * Exemple :
 *    - l'utilisateur a modifié pour la dernière fois son dossier un mardi, tous les jeudis, il recevra une notification de rappel
 *    jusqu'à ce qu'il fasse sa demande d'adhésion.
 *
 * @package    local_apsolu
 * @copyright  2023 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class follow_up_incomplete_federation_adhesions extends \core\task\scheduled_task {
    /**
     * Retourne le nom de la tâche.
     *
     * @return string
     */
    public function get_name() {
        // Shown in admin screens.
        return get_string('task_for_follow_up_incomplete_federation_adhesions', 'local_apsolu');
    }

    /**
     * Execute la tâche.
     *
     * @return void
     */
    public function execute() {
        global $CFG, $DB;

        $federationcourse = new FederationCourse();
        $federationcourseid = $federationcourse->get_course();
        if ($federationcourseid === false) {
            return;
        }

        $subject = $SITE->shortname.' : '.get_string('membership_of_the_sports_association', 'local_apsolu');

        // Traite le système précedent, où les utilisateurs devaient faire une demande explicite de leur licence, après le paiement.
        $namefields = 'u.' . implode(', u.', core_user\fields::get_name_fields());
        $sql = "SELECT u.id, {$namefields}, u.email, afa.id AS adhesionid
                  FROM {user} u
                  JOIN {apsolu_federation_adhesions} afa ON u.id = afa.userid
                 WHERE u.deleted = 0
                   AND u.suspended = 0
                   AND afa.federationnumberrequestdate IS NULL
                   AND afa.federationnumber IS NULL
              ORDER BY u.lastname, u.firstname";
        $users = $DB->get_records_sql($sql);

        foreach ($users as $user) {
            $adhesion = new Adhesion();
            $adhesion->load($user->adhesionid);

            if (empty($adhesion->id) === true) {
                // Ne devrait jamais arriver...
                continue;
            }

            if ($adhesion->can_request_a_federation_number() === false) {
                // L'utilisateur ne remplit pas toutes les conditions pour faire une demande de licence.
                continue;
            }

            if ($adhesion->medicalcertificatestatus === Adhesion::MEDICAL_CERTIFICATE_STATUS_PENDING) {
                // L'utilisateur doit déposer un nouveau certificat.
                continue;
            }

            mtrace(sprintf('relance #%s %s %s (%s)', $user->id, $user->firstname, $user->lastname, $user->email));

            $url = $CFG->wwwroot . '/local/apsolu/federation/adhesion/index.php?step=6';
            $params = ['firstname' => $user->firstname, 'lastname' => $user->lastname, 'url' => $url];
            $message = get_string('follow_up_incomplete_federation_adhesions_message', 'local_apsolu', $params);
            email_to_user($user, core_user::get_noreply_user(), $subject, $message);
        }
    }
}
