define([], function() {
    window.requirejs.config({
        paths: {
            "dot/doT": M.cfg.wwwroot + '/local/apsolu/lib/jquery/doT/doT.min',
            "jquery-extendext": M.cfg.wwwroot + '/local/apsolu/lib/jquery/jQuery.extendext/jQuery.extendext.min',
            "query-builder": M.cfg.wwwroot + '/local/apsolu/lib/jquery/jQuery-QueryBuilder/js/query-builder.min',
            "query-builder.fr": M.cfg.wwwroot + '/local/apsolu/lib/jquery/jQuery-QueryBuilder/i18n/query-builder.fr',
            "jszip": M.cfg.wwwroot + '/local/apsolu/lib/jquery/DataTables/JSZip-2.5.0/jszip.min',
            "pdfmake": M.cfg.wwwroot + '/local/apsolu/lib/jquery/DataTables/pdfmake-0.1.36/pdfmake.min',
            "vfs_fonts": M.cfg.wwwroot + '/local/apsolu/lib/jquery/DataTables/pdfmake-0.1.36/vfs_fonts',
            "datatables.net": M.cfg.wwwroot + '/local/apsolu/lib/jquery/DataTables/DataTables-1.10.22/js/jquery.dataTables.min',
            "datatables.net-buttons": M.cfg.wwwroot + '/local/apsolu/lib/jquery/DataTables/Buttons-1.6.5/js/dataTables.buttons.min',
            "datatables.net-bs4":
                M.cfg.wwwroot + '/local/apsolu/lib/jquery/DataTables/DataTables-1.10.22/js/dataTables.bootstrap4.min',
            "buttons.bootstrap4": M.cfg.wwwroot + '/local/apsolu/lib/jquery/DataTables/Buttons-1.6.5/js/buttons.bootstrap4.min',
            "buttons.html5": M.cfg.wwwroot + '/local/apsolu/lib/jquery/DataTables/Buttons-1.6.5/js/buttons.html5.min',
            "buttons.colVis": M.cfg.wwwroot + '/local/apsolu/lib/jquery/DataTables/Buttons-1.6.5/js/buttons.colVis.min',
            "bootstrap-datepicker": M.cfg.wwwroot + '/local/apsolu/lib/jquery/bootstrap-datepicker/js/bootstrap-datepicker.min',
            "bootstrap-datetimepicker":
                M.cfg.wwwroot + '/local/apsolu/lib/jquery/bootstrap-datetimepicker/js/bootstrap-datetimepicker',
            "moment": M.cfg.wwwroot + '/local/apsolu/lib/jquery/moment'
        },
    });
});
