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
 * Module javascript.
 *
 * @todo       Description à compléter.
 *
 * @module     local_apsolu/federation_adhesion_health_quiz
 * @copyright  2023 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([], function() {
    return {
        initialise: function() {
            // Récupère tous les champs radio.
            var inputs = document.querySelectorAll('#healthquizquestionsform input[type=radio]');
            for (var i = 0; i < inputs.length; i++) {
                // Ajoute un écouteur sur chaque bouton radio.
                inputs[i].addEventListener('change', function() {
                    // Récupère la liste des boutons radio cochés.
                    var inputs = document.querySelectorAll('#healthquizquestionsform input[type=radio]:checked');

                    // Si tous les boutons radio sont cochés, on calcule le score.
                    if (inputs.length === 9) {
                        // On initialise le score à 0.
                        var healthquizvalue = document.getElementById('healthquizvalue');
                        healthquizvalue.value = 0;

                        for (var i = 0; i < inputs.length; i++) {
                            if (inputs[i].value === '0') {
                                continue;
                            }

                            // On positionne le score à 1 si l'utilisateur a répondu Oui à une question.
                            healthquizvalue.value = 1;
                            break;
                        }

                        // Déverrouille le bouton d'envoi du formulaire.
                        var submit = document.getElementById('healthquizsubmit');
                        submit.removeAttribute('disabled');
                    }
                });
            }
        }
    };
});
