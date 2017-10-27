define(["jquery", "enrol_select/jquery.popupoverlay"], function($) {
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
                data.listid = $(this).data('listid');
                data.ueid = $(this).data('ueid');
                data.roleid = $(this).data('roleid');
                data.raid = $(this).data('raid');

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

                    $.ajax({
                        url: M.cfg.wwwroot+"/local/apsolu/attendance/ajax/edit_enrolment.php",
                        type: 'POST',
                        data: $('#apsolu-attendance-ajax-edit-enrolment form').serialize(),
                        dataType: 'json'
                    })
                    .done(function(result){
                        $('#apsolu-attendance-popup').html(result.form);

                        var list = $('.apsolu-attendance-status[data-ueid='+result.ueid+']');
                        if (list.length == 1) {
                            list.html(result.list);
                            $('.apsolu-attendance-edit-enrolments[data-ueid='+result.ueid+']').data('listid', result.listid);
                        }

                        var role = $('.apsolu-attendance-role[data-raid='+result.raid+']');
                        if (role.length == 1) {
                            role.html(result.role);
                            $('.apsolu-attendance-edit-enrolments[data-raid='+result.raid+']').data('roleid', result.roleid);
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
