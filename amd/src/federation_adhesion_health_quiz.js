define([], function() {
    return {
        initialise : function() {
            // Récupère tous les champs radio.
            var inputs = document.querySelectorAll('#healthquizquestionsform input[type=radio]');
            for (var i = 0; i < inputs.length; i++) {
                // Ajoute un écouteur sur chaque bouton radio.
                inputs[i].addEventListener('change', function(evnt) {
                    // Récupère la liste des boutons radio cochés.
                    var inputs = document.querySelectorAll('#healthquizquestionsform input[type=radio]:checked');

                    // Si tous les boutons radio sont cochés, on calcule le score.
                    if (inputs.length === 9) {
                        // On initialise le score à 0.
                        var healthquizvalue = document.getElementById('healthquizvalue');
                        healthquizvalue.value = 0;

                        for (var i = 0; i < inputs.length; i++) {
                            if (inputs[i].value === '0') {
                                continue;
                            }

                            // On positionne le score à 1 si l'utilisateur a répondu Oui à une question.
                            healthquizvalue.value = 1;
                            break;
                        }

                        // Déverrouille le bouton d'envoi du formulaire.
                        var submit = document.getElementById('healthquizsubmit');
                        submit.removeAttribute('disabled');
                    }
                });
            }
        }
    }
});
