define([], function() {
    return {
        setHonorability: function() {
            const select = document.getElementById('id_licensetype');

            if (!select) {
                // Le formulaire est probablement en lecture seule.
                return;
            }

            let honorability = 0;
            for (let i = 0; i < select.options.length; i++) {
                if (select.options[i].value == 'S') {
                    continue;
                }

                if (select.options[i].value == 'PSU') {
                    continue;
                }

                if (!select.options[i].selected) {
                    continue;
                }

                honorability = 1;
                break;
            }

            let honorabilityElements = document.getElementsByName('honorability');
            if (honorabilityElements[0]) {
                honorabilityElements[0].setAttribute('value', honorability);

                // Déclenche l'évènement pour que les hooks hideIf de Moodle fonctionnent.
                var event = new Event('change');
                honorabilityElements[0].dispatchEvent(event);
            }
        },
        initialise: function() {
            this.setHonorability();

            let select = document.getElementById('id_licensetype');
            if (select) {
                select.addEventListener("change", () => {
                    this.setHonorability();
                });
            }
        }
    };
});
