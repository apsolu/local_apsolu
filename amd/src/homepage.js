define(["local_apsolu/lib_smoothscroll", "jquery"], function(smoothScroll, $) {
    return {
        initialise : function() {
            smoothScroll.init();

            // Active le focus si une page est chargée avec un lien contenant une ancre.
            if (window.location.hash) {

                var menuid = '';
                switch(window.location.hash) {
                    case '#accueil':
                        menuid = 'apsolu-home-a';
                        break;
                    case '#activites':
                        menuid = 'apsolu-activities-a';
                        break;
                    case '#inscription':
                        menuid = 'apsolu-signup-a';
                        break;
                }

                if (menuid != '') {
                    var menu = document.getElementById(menuid);
                    if (menu) {
                        menu.focus();
                    }
                }
            }

            $('.apsolu-activities-description-div, .apsolu-slots-table').css('display', 'none');
            $('#apsolu-activities-content-legend-div').css('display', 'none');
            $('.apsolu-activities-list-h4').addClass('apsolu-activities-list-h4-link btn btn-default');
            $('#apsolu-activities-list-ul > li').addClass('col-xs-6 col-md-3');

            $('.apsolu-activities-list-h4').click(function(){
                // Supprime la classe qui donne un effet "lien cliquable".
                $('.apsolu-activities-list-h4').removeClass('apsolu-activities-list-h4-link');
                $('.apsolu-activities-list-h4').addClass('apsolu-activities-list-h4-link-back');

                // Supprime la liste des créneaux préalablement affichée.
                $('#apsolu-visible-content-div').remove();

                // Ajoute la liste des créneaux dans la div principale.
                $('#apsolu-activities-content-div').append($(this).parent().clone());
                $('#apsolu-activities-content-div > .apsolu-activity-content-div').attr('id', 'apsolu-visible-content-div');

                // Ajoute un bouton ferme après le titre de l'activité.
                $('#apsolu-visible-content-div .apsolu-activities-description-div').fadeIn();
                $('#apsolu-visible-content-div .apsolu-slots-table').fadeIn();
                $('#apsolu-activities-list-ul').css('display', 'none');

                // Ajoute la légende
                $('#apsolu-activities-content-legend-div').fadeIn();

                $('html,body').animate({ scrollTop: $('#activites').offset().top }, 200);
                //window.scrollTo(0,$('#activites').offset().top);

                // Ajoute un évènement sur le bouton de fermeture pour revenir à l'affichage précédent.
                $('#apsolu-visible-content-div .apsolu-activities-list-h4-link-back').click(function(){
                    $(this).parent().fadeOut('fast', function() {
                        $('.apsolu-activities-list-h4').addClass('apsolu-activities-list-h4-link');
                        $('.apsolu-activities-list-h4').removeClass('apsolu-activities-list-h4-link-back');
                        $(this).remove();
                        $('#apsolu-activities-list-ul').css('display', 'block');
                    });
                    $('#apsolu-activities-content-legend-div').fadeOut('fast');
                });
            });

            // Affiche au besoin la liste des activités au lieu des créneaux lorsqu'on clique sur le bouton "activités" du menu
            $('#apsolu-activities-a').click(function(){
                if ($('#apsolu-activities-list-ul').css('display') === 'none') {
                    $('#apsolu-visible-content-div').remove();
                    $('.apsolu-activities-list-h4').addClass('apsolu-activities-list-h4-link');
                    $('.apsolu-activities-list-h4').removeClass('apsolu-activities-list-h4-link-back');
                    $('#apsolu-activities-list-ul').css('display', 'block');
                }
            });
        }
    };
});
