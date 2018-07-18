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
        }
    };
});
