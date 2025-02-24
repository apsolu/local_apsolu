define(["jquery", "local_apsolu/table-mask", "local_apsolu/jquery.tablesorter"], function($, tableMask) {
    return {
        initialise: function(options = {}) {
            if (options.hasOwnProperty("widgets") && options.widgets.includes("stickyHeaders")) {
                let ignoredpages = ["page-local-apsolu-presentation-activity", "page-local-apsolu-presentation-summary"];
                if (ignoredpages.includes(document.body.id) === false) {
                    // Modifie le style de <body> afin de faire fonctionner le stickyHeaders de TableSorter.
                    document.body.style.height = 'auto';
                }
            }

            $(".table-sortable").tablesorter(options);
            tableMask.initialise();

        }
    };
});
