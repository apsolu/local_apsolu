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

/**
 * Classe gérant les options de messagerie.
 *
 * @package    local_apsolu
 * @copyright  2022 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class messaging {
    /**
     * Option désactivant l'adresse de réponse.
     */
    const DISABLE_REPLYTO_ADDRESS = '0';

    /**
     * Option forçant l'utilisation de l'adresse de réponse.
     */
    const FORCE_REPLYTO_ADDRESS = '1';

    /**
     * Option laissant le choix à l'enseignant d'utiliser ou non l'adresse de réponse.
     */
    const ALLOW_REPLYTO_ADDRESS_CHOICE = '2';

    /**
     * Option permettant de choisir par défautdésactivant l'adresse de réponse.
     */
    const DO_NOT_USE_REPLYTO_ADDRESS = '0';

    /**
     * Option forçant l'utilisation de l'adresse de réponse.
     */
    const USE_REPLYTO_ADDRESS = '1';

    /**
     * Retourne la liste des options possible dans le choix d'adresse de réponse.
     *
     * @return array
     */
    public static function get_replyto_options() {
        $options = [];
        $options[self::DISABLE_REPLYTO_ADDRESS] = get_string('disable_replyto', 'local_apsolu');
        $options[self::FORCE_REPLYTO_ADDRESS] = get_string('force_replyto', 'local_apsolu');
        $options[self::ALLOW_REPLYTO_ADDRESS_CHOICE] = get_string('allow_to_choose_or_not_a_replyto_address', 'local_apsolu');

        return $options;
    }

    /**
     * Retourne la liste des options possible dans le choix par défaut d'adresse de réponse.
     *
     * @return array
     */
    public static function get_default_replyto_options() {
        $options = [];
        $options[self::DO_NOT_USE_REPLYTO_ADDRESS] = get_string('do_not_use_replyto_address', 'local_apsolu');
        $options[self::USE_REPLYTO_ADDRESS] = get_string('use_replyto_address', 'local_apsolu');

        return $options;
    }
}
