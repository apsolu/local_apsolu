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

/* jshint esversion: 6 */

/**
 * Initialise un filtre de recherche sur un élément.
 *
 * @module     local_apsolu/input-text-filter
 * @copyright  2021 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {
    return {
        /**
         * Initialise un filtre de recherche sur un élément.
         *
         * - positionne un évènement 'keyup' sur un élément 'input' (identifié par inputSelector)
         * - affiche ou masque les éléments (identifié par targetSelector) en fonction du texte saisie dans l'élément 'input'
         *
         * @param {String} inputSelector QuerySelector de l'élément contenant le texte à filtrer/rechercher.
         * @param {String} targetSelector QuerySelector des éléments devant être comparés avec le filtre de recherche.
         * @param {String} closestSelector Détermine sur quel élément le style doit s'appliquer.
         *  Si ce n'est pas précisé, le style sera appliqué directement sur les éléments targetSelector.
         * @param {String} displayStyle Détermine le style d'affichage à appliquer.
         *  Par défaut, c'est le style 'block' qui est appliqué.
         *
         * @returns {void}
         */
        initialise: function(inputSelector, targetSelector, closestSelector = '', displayStyle = 'block') {
            // Vérifie l'existence de l'élément inputSelector.
            var element = document.querySelector(inputSelector);
            if (element === null) {
                return;
            }

            // Positionne un évènement 'keyup' sur l'élément inputSelector.
            element.addEventListener('keyup', function() {
                // Récupère tous les éléments cibles (identifié par targetSelector).
                let elements = document.querySelectorAll(targetSelector);

                // Récupère le texte recherché.
                let searchString = document.querySelector(inputSelector).value.trim().toLowerCase();

                let parentNode;
                let style;

                for (let i = 0; i < elements.length; i++) {
                    // Si le champ de recherche inputSelector est vide ou que le texte recherché correspond à l'élement parcouru.
                    if (searchString == '' || elements[i].textContent.trim().toLowerCase().includes(searchString)) {
                        style = displayStyle;
                    } else {
                        style = 'none';
                    }

                    // Remonte à l'élément parent pour masquer le bloc complet.
                    if (closestSelector != '') {
                        parentNode = elements[i].closest(closestSelector);
                        if (parentNode === null) {
                            continue;
                        }
                    } else {
                        parentNode = elements[i];
                    }

                    // Applique le style à l'élément.
                    parentNode.style.display = style;
                }
            });
        }
    };
});
