define(["jquery"], function($) {
    return {
        initialise : function() {
            // Créé un bouton pour cocher toutes les présences non définies.
            $('#apsolu-attendance-table').before('<div id="apsolu-attendance-check-javascript-helper" class="text-right"><p>Pour les présences <u>non saisies</u> :<ul class="list-inline">'+
                '<li><span class="btn btn-xs btn-primary" id="apsolu-check-radio-present">Cocher "présent" pour tous</span></li>'+
                '<li><span class="btn btn-xs btn-primary" id="apsolu-check-radio-absent">Cocher "absent" pour tous</span></li>'+
                '</ul></div>');

            // Ajoute un évènement sur le bouton "présent" créé précédemment.
            $('#apsolu-check-radio-present').click(function(){
                $('#apsolu-attendance-table tbody tr').each(function() {
                    // Recherche si un des 4 boutons radio est coché.
                    if ($(this).find('input[type=radio]:checked').length == 0) {
                        // Coche le premier bouton radio de la liste.
                        $(this).find('input[type=radio]').first().prop('checked', true);
                    }
                });
            });

            // Ajoute un évènement sur le bouton "absent" créé précédemment.
             $('#apsolu-check-radio-absent').click(function(){
                $('#apsolu-attendance-table tbody tr').each(function() {
                    // Recherche si un des 4 boutons radio est coché.
                    if ($(this).find('input[type=radio]:checked').length == 0) {
                        // Coche le dernier bouton radio de la liste.
                        $(this).find('input[type=radio]').last().prop('checked', true);
                    }
                });
            });
        }
    };
});
