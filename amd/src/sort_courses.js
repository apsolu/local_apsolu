define(["jquery", "local_apsolu/jquery.tablefilter"], function($, TableFilter) {
    return {
        initialise : function() {
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
                    "string"  // Enseignants.
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
                extensions: [{ name: "sort" }]
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
