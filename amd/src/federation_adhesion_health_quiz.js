define([], function() {
    return {
        initialise: function() {
            // Récupère tous les champs radio.
            let inputs = document.querySelectorAll('#healthquizquestionsform input[type=radio]');
            for (let i = 0; i < inputs.length; i++) {
                // Ajoute un écouteur sur chaque bouton radio.
                inputs[i].addEventListener('change', function() {
                    // Récupère la liste des boutons radio cochés.
                    let allinputs = document.querySelectorAll('#healthquizquestionsform input[type=radio]');
                    let inputs = document.querySelectorAll('#healthquizquestionsform input[type=radio]:checked');

                    // Si tous les boutons radio sont cochés, on calcule le score.
                    // La variable allinputs.length contient les checkboxes oui/non. Donc, on divise par 2.
                    if (inputs.length === (allinputs.length / 2)) {
                        // On initialise le score à 0.
                        let healthquizvalue = document.getElementById('healthquizvalue');
                        healthquizvalue.value = 0;

                        for (let i = 0; i < inputs.length; i++) {
                            if (inputs[i].value === '0') {
                                continue;
                            }

                            // On positionne le score à 1 si l'utilisateur a répondu Oui à une question.
                            healthquizvalue.value = 1;
                            break;
                        }

                        // Déverrouille le bouton d'envoi du formulaire.
                        let submit = document.getElementById('healthquizsubmit');
                        submit.removeAttribute('disabled');
                    }
                });
            }
        }
    };
});
