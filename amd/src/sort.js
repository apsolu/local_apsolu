define(["jquery", "local_apsolu/jquery.tablesorter"], function($) {
    return {
        initialise : function(){
            $(".table-sortable").tablesorter();
        }
    };
});
