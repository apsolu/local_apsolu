/*
 * Permet de masquer / afficher des colonnes des tables
 * Les tables doivent avoir une balise thead et tbody
 * Compatible avec jquery.tablesorter
 * Utilisation
 * - Encapsuler la table avec une balise ayant comme classe 'apsolu-mask-wrapper'
 * - Pour ne pas permettre de masquer une colonne, rajouter la classe 'no-mask' à la balise th
 * - Pour sauvegarder la configuration de la table et la partager dans le document,
 *   Rajouter l'attribut 'data-mask-name="<nom>"' à la table et à la colonne
 */

define(['jquery', "local_apsolu/preference", 'core/notification'], function($, UserRepository, Notification) {
    return {
        initialise: function(force) {
            // If table mask was already initialized, return.
            if(typeof apsolu_table_mask_preferences !== 'undefined' && !force) { return; }

            // N'exécute pas ce script si l'utilisateur n'est pas authentifié (sinon l'appel à getUserPreferences() redirige vers la
            // page d'authentification ; exemple: la page de présentation des créneaux : /local/apsolu/presentation/summary.php).
            if (document.body.classList.contains("notloggedin") === true) {
                return;
            }

            var apsolu_table_mask_preferences = {};

            let restoreHtml = '<a class="restore-columns btn btn-default btn-condensed">'
            + 'Colonnes cachées : cliquez ici pour les restaurer</a>';

            $(".apsolu-mask-wrapper > table").before(restoreHtml);
            $(".apsolu-mask-wrapper > table").after(restoreHtml);
            $(".restore-columns").hide();

            UserRepository.getUserPreferences('apsolu_maskable_config').then((value) => {
                if(!value || !value.preferences[0].value) { return; }

                apsolu_table_mask_preferences = JSON.parse(value.preferences[0].value);

                $('.apsolu-mask-wrapper').each(function () {
                    let $wrapper = $(this);
                    let wrapperName = $wrapper.data("mask-name");
                    if(!wrapperName || !apsolu_table_mask_preferences[wrapperName]) { return; }

                    apsolu_table_mask_preferences[wrapperName].forEach(function(columnName) {
                        let $cell = $wrapper.find("th[data-mask-name=" + columnName + "]");
                        if($cell.length <= 0) { return; }

                        let colIndex = $($cell[0]).data("column");
                        let useHeaderChildIndex = false;

                        if(colIndex === undefined || colIndex === undefined) { // no tablesorter data
                            colIndex = $cell[0].cellIndex;
                            useHeaderChildIndex = true;
                        }

                        // find and hide col index
                        $wrapper.find("tbody tr td:nth-child(" + (colIndex + 1) + ")").addClass('hide-col');

                        // Hide the header
                        if(useHeaderChildIndex) {
                            $wrapper.find("thead tr th:nth-child(" + (colIndex + 1) + ")").addClass('hide-col');
                        } else {
                            $wrapper.find("thead tr th[data-column=" + colIndex + "]").addClass('hide-col');
                            $wrapper.find("thead tr td[data-column=" + colIndex + "]").addClass('hide-col');
                        }

                        // show restore links
                        $wrapper.find(".restore-columns").show();

                    });

                });

            }).catch(Notification);
            $('.apsolu-mask-wrapper thead th:not(".no-mask")').each(function() {
                let content = $(this).html();
                $(this).html("<div class='d-flex m-0 p-0'><div class='flex-grow-1'>" + content + "</div>"
                    + "<div class='hide-column-container'>"
                    + "<button class='btn btn-default btn-condensed hide-column p-0' title='Cacher la colonne'>"
                    + "<i class='fa fa-eye-slash'></i></button></div></div>");
            });

            $('.hide-column').click(function() {
                let $cell = $(this).closest('th,td');
                let $wrapper = $(this).closest('.apsolu-mask-wrapper');
                let columnName = $cell.data("mask-name");
                let wrapperName = $wrapper.data("mask-name");
                if(wrapperName) {
                    $wrapper = $('[data-mask-name=' + wrapperName + ']');
                }

                let colIndex = $($cell[0]).data("column");

                let useHeaderChildIndex = false;

                if(colIndex === undefined || colIndex === undefined) { // no tablesorter data
                    colIndex = $cell[0].cellIndex;
                    useHeaderChildIndex = true;
                }

                // find and hide col index
                $wrapper.find("tbody tr td:nth-child(" + (colIndex + 1) + ")").addClass('hide-col');

                // Hide the header
                if(useHeaderChildIndex){
                    $wrapper.find("thead tr th:nth-child(" + (colIndex + 1) + ")").addClass('hide-col');
                } else {
                    $wrapper.find("thead tr th[data-column=" + colIndex + "]").addClass('hide-col');
                    $wrapper.find("thead tr td[data-column=" + colIndex + "]").addClass('hide-col');
                }


                // show restore footer
                $wrapper.find(".restore-columns").show();

                if(columnName && wrapperName)
                {
                    // Add column to wrapper config and save
                    if(!apsolu_table_mask_preferences[wrapperName]) { apsolu_table_mask_preferences[wrapperName] = []; }

                    let index = apsolu_table_mask_preferences[wrapperName].indexOf(columnName);
                    if(index < 0) { apsolu_table_mask_preferences[wrapperName].push(columnName); }
                    UserRepository.setUserPreference('apsolu_maskable_config', JSON.stringify(apsolu_table_mask_preferences))
                        .catch(Notification);
                }

                return false;
            });

            $(".restore-columns").click(function() {
                let $wrapper = $(this).closest('.apsolu-mask-wrapper');
                let wrapperName = $wrapper.data("mask-name");
                if(wrapperName) {
                    $wrapper = $('[data-mask-name=' + wrapperName + ']');

                    apsolu_table_mask_preferences[wrapperName] = [];
                    UserRepository.setUserPreference('apsolu_maskable_config', JSON.stringify(apsolu_table_mask_preferences))
                        .catch(Notification);
                }
                $wrapper.find(".restore-columns").hide();
                $wrapper.find("th, td").removeClass('hide-col');
                return false;
            });
        }
    };
});
