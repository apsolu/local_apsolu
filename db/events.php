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
 * Définition des observateurs.
 *
 * @package   local_apsolu
 * @copyright 2021 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$observers = [
    // Gère les déplacements de cours depuis l'interface Administration du site > Cours > Gestion des cours et catégories.
    [
        'eventname'   => '\core\event\course_updated',
        'callback'    => '\local_apsolu\observer\course::updated',
        'includefile' => null,
        'internal'    => true,
        'priority'    => 9999,
    ],
    // Gère la suppression de cours depuis l'interface Administration du site > Cours > Gestion des cours et catégories.
    [
        'eventname'   => '\core\event\course_deleted',
        'callback'    => '\local_apsolu\observer\course::deleted',
        'includefile' => null,
        'internal'    => true,
        'priority'    => 9999,
    ],
    // Gère les déplacements de catégories depuis l'interface Administration du site > Cours > Gestion des cours et catégories.
    [
        'eventname'   => '\core\event\course_category_updated',
        'callback'    => '\local_apsolu\observer\course_category::updated',
        'includefile' => null,
        'internal'    => true,
        'priority'    => 9999,
    ],
    // Gère la suppression de catégories depuis l'interface Administration du site > Cours > Gestion des cours et catégories.
    [
        'eventname'   => '\core\event\course_category_deleted',
        'callback'    => '\local_apsolu\observer\course_category::deleted',
        'includefile' => null,
        'internal'    => true,
        'priority'    => 9999,
    ],
    // Gère la suppression des cohortes depuis l'interface Administration du site > Utilisateurs > Comptes > Cohortes.
    [
        'eventname'   => '\core\event\cohort_deleted',
        'callback'    => '\local_apsolu\observer\cohort::deleted',
        'includefile' => null,
        'internal'    => true,
        'priority'    => 9999,
    ],
];
