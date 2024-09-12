define([], function() {
    return {
        initialise: function() {
            let counters = document.querySelectorAll('.table-row-counter');

            counters.forEach(function(counter) {
                // Détermine si l'attribut data-target existe.
                let targetname = counter.getAttribute('data-target');
                if (!targetname) {
                    return;
                }

                // Détermine si l'élément définit par data-target existe.
                let target = document.getElementById(targetname);
                if (!target) {
                    return;
                }

                // Observe tous les changements sur la cible.
                let observer = new MutationObserver(function() {
                    let count = target.querySelectorAll('tbody > tr:not(.filtered)');

                    let counterspan = document.querySelector('.table-row-counter[data-target='+target.id+']');
                    if (counterspan) {
                        // Met à jour le compteur de lignes.
                        counterspan.textContent = count.length;
                    }
                });

                observer.observe(target, { attributes: false, childList: true });
            });
        }
    };
});
