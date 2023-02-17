// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Module javascript.
 *
 * @todo       Description à compléter.
 *
 * @module     local_apsolu/config
 * @copyright  2019 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
            "bootstrap-datepicker":
                M.cfg.wwwroot + '/local/apsolu/lib/jquery/bootstrap-datepicker/js/bootstrap-datepicker.min',
            "bootstrap-datetimepicker":
                M.cfg.wwwroot + '/local/apsolu/lib/jquery/bootstrap-datetimepicker/js/bootstrap-datetimepicker',
            "moment": M.cfg.wwwroot + '/local/apsolu/lib/jquery/moment'
        },
    });
});
