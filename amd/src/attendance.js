define(["jquery", "enrol_select/jquery.popupoverlay"], function($) {
    return {
        initialise : function() {
            // Créé un bouton pour cocher toutes les présences non définies.
            $('#apsolu-attendance-table').before('<div id="apsolu-attendance-check-javascript-helper" class="text-right"><p>Pour les présences <u>non saisies</u> :<ul class="list-inline">'+
                '<li class="list-inline-item"><span class="btn btn-sm btn-primary" id="apsolu-check-radio-present">Cocher "présent" pour tous</span></li>'+
                '<li class="list-inline-item"><span class="btn btn-sm btn-primary" id="apsolu-check-radio-absent">Cocher "absent" pour tous</span></li>'+
                '<li class="list-inline-item"><span class="btn btn-sm btn-dark" id="apsolu-check-radio-uncheck">Décocher toutes les cases</span></li>'+
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

            // Ajoute un évènement sur le bouton "décocher" créé précédemment.
            $('#apsolu-check-radio-uncheck').click(function(){
                $('#apsolu-attendance-table tbody tr').each(function() {
                    // Recherche si un des 4 boutons radio est coché.
                    if ($(this).find('input[type=radio]:checked').length != 0) {
                        // Décoche toutes les boutons.
                        $(this).find('input[type=radio]').prop('checked', false);
                    }
                });
            });

            // Ajoute un évènement pour permettre de désélectionner un bouton radio.
            $('#apsolu-attendance-table tbody tr input[type=radio]').click(function() {
                // Supprime la propriété hasbeenpreviouslychecked sur tous les éléments radio, sauf l'élément courant.
                var name = $(this).attr('name');
                $('#apsolu-attendance-table tbody tr input[name="'+name+'"]').not(this).removeProp('hasbeenpreviouslychecked');

                if (this.hasbeenpreviouslychecked) {
                    // Décoche le bouton radio qui était déjà coché avant.
                    this.checked = false;
                    $(this).removeProp('hasbeenpreviouslychecked');
                } else {
                    // Place une propriété indiquant que le bouton radio est déjà coché.
                    this.hasbeenpreviouslychecked = true;
                }
            });

            // Ajoute un évènement sur le bouton "modifier l'inscription".
            $('.apsolu-attendance-edit-enrolments').click(function(event) {
                event.preventDefault();

                var popup = $('#apsolu-attendance-popup');
                if (popup.length == 0) {
                    $('#page-footer').append('<div id="apsolu-attendance-popup"></div>');
                    popup = $('#apsolu-attendance-popup');
                }

                data = new Object();
                data.userid = $(this).data('userid');
                data.courseid = $(this).data('courseid');
                data.enrolid = $(this).data('enrolid');
                data.statusid = $(this).data('statusid');
                data.roleid = $(this).data('roleid');

                // Build form.
                $.ajax({
                    url: M.cfg.wwwroot+"/local/apsolu/attendance/ajax/edit_enrolment.php",
                    type: 'POST',
                    data: data,
                    dataType: 'json'
                })
                .done(function(result){
                    try {
                        $('#apsolu-attendance-popup').html(result.form);
                        apsolu_attendance_handle_edit_enrolments_form();
                    } catch(e) {
                        console.log(e);
                        // $('#apsolu-enrol-form').html(result);
                    }
                });

                popup.css({backgroundColor: '#EEEEEE', padding: '.5em', cursor: 'default', maxWidth: '50%', textAlign: 'justify'});
                popup.popup('show');
            });

            function apsolu_attendance_handle_edit_enrolments_form() {
                // Submit data.
                $('#apsolu-attendance-ajax-edit-enrolment form').submit(function(event) {
                    event.preventDefault();

                    // Supprime le précédent popup afin de donner un indice visuel en cas de renvoi du formulaire.
                    var popup = document.querySelector("#apsolu-attendance-popup .alert");
                    if (popup !== null) {
                        popup.remove();
                    }

                    $.ajax({
                        url: M.cfg.wwwroot+"/local/apsolu/attendance/ajax/edit_enrolment.php",
                        type: 'POST',
                        data: $('#apsolu-attendance-ajax-edit-enrolment form').serialize(),
                        dataType: 'json'
                    })
                    .done(function(result){
                        $('#apsolu-attendance-popup').html(result.form);

                        // Mets à jour la colonne "Liste d'inscription".
                        var status = $('.apsolu-attendance-status[data-userid='+result.userid+']');
                        if (status.length == 1) {
                            status.html(result.status);
                            $('.apsolu-attendance-edit-enrolments[data-userid='+result.userid+']').data('statusid', result.statusid);
                        }

                        // Mets à jour la colonne "Type d'inscription".
                        var role = $('.apsolu-attendance-role[data-userid='+result.userid+']');
                        if (role.length == 1) {
                            role.html(result.role);
                            $('.apsolu-attendance-edit-enrolments[data-userid='+result.userid+']').data('roleid', result.roleid);
                        }

                        apsolu_attendance_handle_edit_enrolments_form();
                    });
                });

                // Close form.
                $('#apsolu-attendance-ajax-edit-enrolment form .cancel').click(function(event) {
                    event.preventDefault();

                    var popup = $('#apsolu-attendance-popup');
                    popup.popup('hide');
                });
            }
        }
    };
});
