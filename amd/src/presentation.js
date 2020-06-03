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
 * Module javascript pour l'offre de formations.
 *
 * @module     local_apsolu/presentation
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'enrol_select/select2'], function($) {
    return {
        initialise: function() {
            /**
             * Fonction permettant de filtrer l'offre de formations.
             *
             */
            function filter() {
                // Filtre le tableau de l'offre de formations.

                // Réaffiche toutes les lignes pour mieux les masquer ensuite.
                $('#apsolu-enrol-select tbody tr').css('display', 'table-row');

                $('.apsolu-enrol-selects').each(function() {
                    var values = $(this).select2('data');
                    var target = $(this).attr('name');
                    if (values.length === 0) {
                        // Ne pas parcourir le tableau entier, si il n'y a aucun filtre pour cette colonne.
                        return;
                    }

                    var header;
                    var headerChildren;

                    $('#apsolu-enrol-select tbody tr').each(function() {
                        if ($(this).children().length === 1) {
                            // On ne traite pas les <th>, mais on les masque si elles ne servent à rien.
                            if (headerChildren === 0) {
                                header.css('display', 'none');
                            }

                            header = $(this);
                            headerChildren = 0;

                            return;
                        }

                        if ($(this).css('display') === 'none') {
                            // Ne pas parcourir le tableau entier, si la ligne est déjà masquée.
                            return;
                        }

                        // Pour chaque valeur sélectionnée dans le widget select2...
                        for (var i = 0; i < values.length; i++) {
                            var value = $.trim($(this).children('[data-column="' + target + '"]').attr('data-value'));
                            if (value.indexOf(values[i].text) !== -1) {
                                // Si la valeur correspond au filtre, on continue...
                                headerChildren++;
                                return;
                            }
                        }

                        // On masque la ligne si la colonne ne correspond à aucun critère de recherche.
                        $(this).css('display', 'none');
                    });

                    if (headerChildren === 0) {
                        header.css('display', 'none');
                    }
                });
            }

            // Initialise les entrées HTML du formulaire contenant les filtres.
            $('.apsolu-enrol-selects').select2({
                allowClear: true,
                width: '18em'
                });

            // Appelle la focntion filter() à chaque changement de valeurs dans les entrées du formulaire.
            $('.apsolu-enrol-selects').on('change.select2', function() {
                filter();
            });

            // Initialise les filtres.
            filter();

            /**
             * Fonction permettant d'alterner l'affichage du bloc de filtres.
             *
             */
            function toggleFilterBlock() {
                var filters = document.getElementById('apsolu-offerings-filters-aside');
                if (filters) {
                    if (filters.style.display == 'block') {
                        filters.style.display = 'none';
                    } else {
                        filters.style.display = 'block';
                    }
                }
            }

            if (document.getElementsByClassName('select2-selection__choice').length > 0) {
                // Affiche le bloc de filtres, si il y a déjà une selection en cours.
                toggleFilterBlock();
            }

            var toggleFiltersButton = document.getElementById('toggle-filters-button');
            if (toggleFiltersButton) {
                // Positionne un évènement lors d'un clic sur le bouton d'affichage des filtres.
                toggleFiltersButton.addEventListener('click', toggleFilterBlock);
            }
        }
    };
});
