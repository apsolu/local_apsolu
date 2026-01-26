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

use moodle_exception;
use moodle_url;
use stdClass;

/**
 * Classe gérant les échanges avec la solution de paiement PayBox.
 *
 * @package    local_apsolu
 * @copyright  2022 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class paybox {
    /**
     * Nombre maximum de caractères autorisé pour le champ "prénom".
     */
    const MAX_LENGTH_FIRSTNAME = 30;

    /**
     * Nombre maximum de caractères autorisé pour le champ "nom".
     */
    const MAX_LENGTH_LASTNAME = 30;

    /**
     * Nombre maximum de caractères autorisé pour le champ "adresse 1".
     */
    const MAX_LENGTH_ADDRESS1 = 50;

    /**
     * Nombre maximum de caractères autorisé pour le champ "adresse 2".
     */
    const MAX_LENGTH_ADDRESS2 = 50;

    /**
     * Nombre maximum de caractères autorisé pour le champ "code postal".
     */
    const MAX_LENGTH_ZIPCODE = 16;

    /**
     * Nombre maximum de caractères autorisé pour le champ "ville".
     */
    const MAX_LENGTH_CITY = 50;

    /**
     * Nombre maximum de caractères autorisé pour le champ "pays".
     */
    const MAX_LENGTH_COUNTRYCODE = 3;

    /**
     * Retourne le nom de domaine du premier serveur PayBox disponible pour gérer la transaction.
     *
     * @param int|null|string $userid Identifiant de l'utilisateur.
     *
     * @return string|false Un nom de domaine ou false si aucun serveur n'est disponible.
     */
    public static function get_address($userid = null) {
        global $DB, $USER;

        if ($userid === null) {
            $userid = $USER->id;
        }

        $address = $DB->get_record('apsolu_payments_addresses', ['userid' => $userid]);

        if ($address === false) {
            $address = new stdClass();
            $address->id = 0;
            $address->firstname = $USER->firstname;
            $address->lastname = $USER->lastname;
            $address->address1 = '';
            $address->address2 = '';
            $address->zipcode = '';
            $address->city = '';
            $address->countrycode = 250;
            $address->timecreated = time();
            $address->timemodified = $address->timecreated;
            $address->userid = $userid;
        }

        return $address;
    }

    /**
     * Retourne le nom de domaine du premier serveur PayBox disponible pour gérer la transaction.
     *
     * @return string|false Un nom de domaine ou false si aucun serveur n'est disponible.
     */
    public static function get_server() {
        global $CFG;

        $payboxserver = false;

        $servers = explode(',', get_config('local_apsolu', 'paybox_servers_outgoing'));
        foreach ($servers as $server) {
            $server = trim($server);

            if (empty($server)) {
                continue;
            }

            // Gère les éventuels paramètres de proxy.
            $options = [];
            if (!empty($CFG->proxyhost) && !is_proxybypass('https://' . $server)) {
                if (!empty($CFG->proxyport)) {
                    $proxy = $CFG->proxyhost . ':' . $CFG->proxyport;
                } else {
                    $proxy = $CFG->proxyhost;
                }

                $options = [
                    'http' => [
                        'proxy' => $proxy,
                    ],
                    'https' => [
                        'proxy' => $proxy,
                    ],
                ];
            }

            // Création du contexte de transaction.
            $ctx = stream_context_create($options);

            // Récupération des données.
            $content = file_get_contents('https://' . $server . '/load.html', false, $ctx);
            if (strpos($content, '<div id="server_status" style="text-align:center;">OK</div>') !== false) {
                // Le serveur est prêt et les services opérationnels.
                $payboxserver = $server;
                break;
            }
            // La machine est disponible mais les services ne le sont pas.
        }

        return $payboxserver;
    }

    /**
     * Enregistre les coordonnées d'un porteur de carte de paiement.
     *
     * @param object          $data   Données issues du formulaire de saisie des coordonnées d'un porteur de carte de paiement.
     * @param int|null|string $userid Identifiant de l'utilisateur.
     *
     * @return string|false Un nom de domaine ou false si aucun serveur n'est disponible.
     */
    public static function save_address(object $data, $userid = null) {
        global $DB, $USER;

        if ($userid === null) {
            $userid = $USER->id;
        }

        $address = $DB->get_record('apsolu_payments_addresses', ['userid' => $userid]);

        if ($address === false) {
            $address = new stdClass();
            $address->id = 0;
            $address->timecreated = time();
        }

        $attributes = [];
        $attributes[] = 'firstname';
        $attributes[] = 'lastname';
        $attributes[] = 'address1';
        $attributes[] = 'address2';
        $attributes[] = 'zipcode';
        $attributes[] = 'city';
        $attributes[] = 'countrycode';

        $changed = false;
        foreach ($attributes as $attribute) {
            if (isset($data->{$attribute}) === false) {
                $link = new moodle_url('/local/apsolu/payment/index.php');

                throw new moodle_exception('invalidparameter', $module = 'debug', $link);
            }

            $data->{$attribute} = trim($data->{$attribute});
            if (isset($address->{$attribute}) === false || $address->{$attribute} !== $data->{$attribute}) {
                $address->{$attribute} = $data->{$attribute};
            }
        }

        $address->userid = $userid;

        if (empty($address->id) === true) {
            $DB->insert_record('apsolu_payments_addresses', $address);
        } else {
            $address->timemodified = time();
            $DB->update_record('apsolu_payments_addresses', $address);
        }
    }
}
