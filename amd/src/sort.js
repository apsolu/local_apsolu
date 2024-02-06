define(["jquery", "local_apsolu/jquery.tablesorter"], function($) {
    return {
        initialise: function(options = {}) {
            $(".table-sortable").tablesorter(options);
        }
    };
});
