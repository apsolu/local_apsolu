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

namespace local_apsolu\payment;

/**
 * Classe gérant les méthodes de paiement.
 *
 * @package    local_apsolu
 * @copyright  2026 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class method {
    /**
     * Initialise la configuration des méthodes de paiement.
     *
     * @return void
     */
    public static function init_config(): void {
        $enabled = 1;

        foreach (self::get_available_methods() as $stringid => $label) {
            $attribute = self::get_key_config($stringid);

            if (get_config('local_apsolu', $attribute) !== false) {
                // On n'écrase pas une variable qui serait déjà configurée.
                continue;
            }

            set_config($attribute, $enabled, 'local_apsolu');
        }
    }

    /**
     * Retourne la liste des méthodes de paiements disponibles dans APSOLU.
     *
     * @return array.
     */
    public static function get_available_methods(): array {
        return [
            'card' => get_string('method_card', 'local_apsolu'),
            'check' => get_string('method_check', 'local_apsolu'),
            'coins' => get_string('method_coins', 'local_apsolu'),
            'pass' => get_string('method_pass', 'local_apsolu'),
            'paybox' => get_string('method_paybox', 'local_apsolu'),
        ];
    }

    /**
     * Retourne la liste des méthodes de paiements activées dans APSOLU.
     *
     * @return array.
     */
    public static function get_enabled_methods(): array {
        $enabled = [];

        foreach (self::get_available_methods() as $stringid => $label) {
            $attribute = self::get_key_config($stringid);

            if (empty(get_config('local_apsolu', $attribute)) === true) {
                continue;
            }

            $enabled[$stringid] = $label;
        }

        return $enabled;
    }

    /**
     * Retourne l'identifiant de configuration d'une méthode de paiement à partir de la clé utilisée par la méthode
     * get_available_methods().
     *
     * @param string $stringid Identifiant de la méthode de paiement retourné par la méthode get_available_methods().
     *
     * @return string Retourne l'identifiant à utiliser avec la fonction get_config()/set_config().
     */
    public static function get_key_config(string $stringid): string {
        return sprintf('payment_method_%s', $stringid);
    }
}
