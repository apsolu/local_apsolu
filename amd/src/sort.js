define(["jquery", "local_apsolu/jquery.tablesorter"], function($) {
    return {
        initialise: function(options = {}) {
            if (options.hasOwnProperty("widgets") && options.widgets.includes("stickyHeaders")) {
                // Modifie le style de <body> afin de faire fonctionner le stickyHeaders de TableSorter.
                document.body.style.height = 'auto';
            }

            $(".table-sortable").tablesorter(options);
        }
    };
});
