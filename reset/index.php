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
 * Configuration de la campagne annuelle de réinitialisation des espaces cours
 *
 * @package    local_apsolu
 * @copyright  2026 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/reset_form.php');

use local_apsolu\core\reset;
use local_apsolu\task\reset_courses as resetTask;
use core\task\manager;

// Setup admin access requirement.
admin_externalpage_setup('local_apsolu_reset_next');

// Vérification des permissions.
if (has_capability('local/apsolu:resetsettings', context_system::instance()) === false) {
    // Cet utilisateur n'a pas les droits nécessaires.
    throw new moodle_exception('nopermissions', 'error', '', get_capability_string('local/apsolu:resetsettings'));
}

// Charge les variables qui sont présentes en DB (valeurs en cache).
$reset = new reset();
$reset->load_default_settings();

// Délai minimum pour activer la réinitialisation. On charge le timestamp correspondat au moment où le formulaire est affiché
// pour comparer la valeur envoyée avec celle chargée par défaut (durée du délai +/- 1mn pour ne pas tenir compte des secondes).
$minimumdatetime = reset::get_minimum_datetime() - 60;

$customdata = [$reset, $minimumdatetime];
$mform = new local_apsolu_reset_form(null, $customdata);

$notifications = ['notify' => []];

// Données envoyées par le formulaire.
if ($mform->is_submitted()) {
    // Validation du formulaire.
    if ($data = $mform->get_data()) {
        $reset = new reset();
        $reset->set_datas($data);

        // Effectue les modifications en DB, return false s'il n'y a aucun changement dans les paramètres de réinitialisation.
        $reset->save_settings($updatedsettings);

        // Message indiquant qu'il y a eu des modifications.
        if (empty($updatedsettings) == false) {
            $notifications['notify'][] = get_string('reset_updated', 'local_apsolu');
        }
    } else {
        // Validation échouée : message d'erreur en haut de page.
        $notifications['warn'] = [get_string('reset_update_failed', 'local_apsolu')];
    }
}

// Date de la dernière exécution.
$latest = reset::get_latest_runtime();
if (empty($latest) == false) {
    $title = userdate($latest, get_string('strftimedateshort', 'local_apsolu'));
}

// Tâche adhoc existante en DB ?
$task = manager::get_queued_adhoc_task_record(new resetTask());
$datetime = 0;
// S'il y a une tâche adhoc programmée on indique que la réinitialisation est active même si la configuration indique autre chose.
if ($task !== false) {
    $datetime = (int) $task->nextruntime;
}
$notifications['notify'][] = $reset::get_activation_notification($datetime);

echo $OUTPUT->header();

// Messages d'information sur le statut de la réinitalisation :
// - A déjà été exécutée cette année ? si oui date de la dernière activation.
if (empty($title) == false) {
    echo $OUTPUT->heading(get_string('reset_last_activation', 'local_apsolu', $title));
}

if (empty($notifications) == false) {
    if (isset($notifications['warn'])) {
        // Notifications (warning) :
        // - La modification a échoué (l'enregistrement du formulaire n'a pas été validé) ?
        foreach ($notifications['warn'] as $w) {
            echo $OUTPUT->notification($w, 'warning');
        }
    } else if (isset($notifications['notify'])) {
        // Notifications (notify) :
        // - A été modifiée à l'instant (grâce au formulaire) ou la modification a échouée ?
        // - Activée (une tâche est programmée) ? si oui date de la prochaine activation.
        foreach ($notifications['notify'] as $n) {
            echo $OUTPUT->notification($n, 'notifymessage');
        }
    }
}

$mform->display();
echo $OUTPUT->footer();
