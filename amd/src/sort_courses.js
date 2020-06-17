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
                    "string", // FFSU.
                    "string"  // Enseignants.
                ],

                // Columns data filter types.
                col_1: "select",
                col_2: "select",
                col_3: "select",
                col_4: "select",
                col_5: "select",
                col_6: "select",
                col_7: "select",
                col_8: "select",
                col_10: "none",

                enable_default_theme: true,
                help_instructions: false,

                // Sort extension: in this example the column data types are provided by the
                // "col_types" property. The sort extension also has a "types" property
                // defining the columns data type for column sorting. If the "types"
                // property is not defined, the sorting extension will fallback to
                // the "col_types" definitions.
                extensions: [{ name: "sort" }]
            };

            var tf = new TableFilter.TableFilter(document.querySelector("#table-courses-sortable"), tfConfig);
            tf.init();
        }
    };
});
