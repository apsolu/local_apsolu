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
 * Cache definitions.
 *
 * It contains the components that are requried in order to use caching.
 *
 * // Mode de cache: APPLICATION, SESSION ou REQUEST.
 *  'mode' => cache_store::MODE_APPLICATION,
 * // True si le cache utilisera seulement des noms de clés simples [a-zA-Z0-9_] pour ses éléments.
 *  'simplekeys' => true,
 * // True si le cache utilisera des données scalaires (int, float, string ou bool) ou des tableaux de valeurs scalaires.
 * // Si True, la donnée sera stockée en l'état, sinon les données seront d'abord sérialisées.
 *  'simpledata' => true,
 * // Tableau d'identifiants utilisés pour initialiser le cache à sa création.
 *  'requireidentifiers' => [],
 * // True si seuls les entrepôts de cache prenant en charge la garantie des données peuvent être utilisés.
 *  'requiredataguarantee' => true,
 * // True si seuls les entrepôts de cache prenant en charge les identifiants multiples peuvent être utilisés.
 *  'requiremultipleidentifiers' => false,
 * // Deprecated: 'requirelockingread' => false.
 * // Deprecated: 'requirelockingwrite' => false.
 * // True si un verrou doit être obtenu avant d'écrire dans le cache.
 *  'requirelockingbeforewrite' => false,
 * // True si le cache doit être initialisé une seule fois par requête.
 *  'staticacceleration' => true,
 * // Nombre maximum d'entrées dans le cache statique.
 *  'staticaccelerationsize' => null,
 * // Durée de vie du cache.
 *  'ttl' => 0,
 * // Nombre maximum d'entrées dans le cache.
 *  'maxsize' => null,
 * // True si le cache peut être local. False si il doit être partagé avec différents frontend.
 *  'canuselocalstore' => false,
 * // Chaîne indiquant le nom de la classe devant gérée le cache à la place de la classe par défaut.
 *  'overrideclass' => null,
 * // Chaîne indiquant le fichier définissant la classe définie par overrideclass.
 *  'overrideclassfile' => null,
 * // Chaîne pouvant indiquer la classe représentant ce cache. Utile si le cache n'utilise pas des valeurs scalaires.
 *  'datasource' => null,
 * // Chaîne indiquant le fichier définissant la classe définie par datasource.
 *  'datasourcefile' => null,
 * // Tableau d'évènements causant l'invalidation du cache. Note that these are NOT normal moodle events and predates the
 * // Events API. Instead these are arbitrary strings which can be used by cache_helper::purge_by_event('changesincoursecat');
 * // to mark multiple caches as invalid at once without the calling code knowing which caches are affected.
 *  'invalidationevents' => [],
 * // True pour utiliser uniquement les entrepôts de cache déjà utilisés ?
 *  'mappingsonly' => false,
 * // Définition des options pour le partage de cache.
 *  'sharingoptions' => cache_definition::SHARING_DEFAULT,
 * // Définition des valeurs par défaut pour le partage de cache.
 *  'defaultsharing' => cache_definition::SHARING_DEFAULT,
 *
 * Source: https://moodledev.io/docs/5.0/apis/subsystems/muc
 *
 * @package    local_apsolu
 * @copyright  2026 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$definitions = [
    // Contient toutes les données décrivant un créneau horaire, indexées par courseid.
    'courserenderer' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
    ],
    // Contient toutes les données des champs personnalisés des cours, indexées par courseid.
    'coursecustomfields' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
    ],
];
