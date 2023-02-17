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
 * @module     local_apsolu/sort_courses
 * @copyright  2018 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(["jquery", "local_apsolu/jquery.tablefilter"], function($, TableFilter) {
    return {
        initialise: function() {
            var tfConfig = {
                base_path: "./../tablefilter/",
                alternate_rows: true,
                rows_counter: {
                    text: "Créneaux: "
                },
                btn_reset: true,
                // btn_reset_text: "Clear",
                loader: true,
                no_results_message: true,
                clear_filter_text: "-",

                // Data types.
                col_types: [
                    "number", // Identifiants des cours.
                    "string", // Groupements d'activités.
                    "string", // Activités sportives.
                    "string", // Niveaux.
                    "string", // Jours.
                    "string", // Horaires.
                    "string", // Lieux.
                    "string", // Périodes.
                    "string" // Enseignants.
                ],

                // Columns data filter types.
                col_1: "select", // Groupements d'activités.
                col_2: "select", // Activités sportives.
                col_3: "select", // Niveaux.
                col_4: "select", // Jours.
                col_5: "select", // Horaires.
                col_6: "select", // Lieux.
                col_7: "select", // Périodes.
                col_9: "none", // Actions.

                enable_default_theme: true,
                help_instructions: false,

                // Sort extension: in this example the column data types are provided by the
                // "col_types" property. The sort extension also has a "types" property
                // defining the columns data type for column sorting. If the "types"
                // property is not defined, the sorting extension will fallback to
                // the "col_types" definitions.
                extensions: [{name: "sort"}]
            };

            if (document.querySelectorAll("#table-courses-sortable thead tr th").length == 11) {
                // Ajoute un filtre pour la colonne FFSU, présente uniquement sur l'instance de Rennes.
                tfConfig.col_types.push("string");

                tfConfig.col_8 = "select"; // Ajoute un filtre de type select sur la colonne FFSU.
                delete tfConfig.col_9; // Le type de filtre sur la colonne devenue "enseignants".
                tfConfig.col_10 = "none"; // Ajoute un filtre de type none sur la colonne "actions".
            }

            var tf = new TableFilter.TableFilter(document.querySelector("#table-courses-sortable"), tfConfig);
            tf.init();
        }
    };
});
