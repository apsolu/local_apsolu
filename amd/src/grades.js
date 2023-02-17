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
 * @module     local_apsolu/grades
 * @copyright  2018 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'local_apsolu/jquery.tablesorter'], function($) {
    return {
        initialise: function() {
            // Gère les modifications de la checkbox.
            $("#apsolu-form-grades input[type='checkbox']").change(function() {
                var name = $(this).attr("name");
                var id = name.substring(3);

                if ($(this).prop("checked")) {
                    if (name.substring(0, 3) == "abj") {
                        $("input[name='grades" + id + "']").val('ABJ');
                        $("input[name='abi" + id + "']").prop('checked', false);
                    } else {
                        $("input[name='grades" + id + "']").val('ABI');
                        $("input[name='abj" + id + "']").prop('checked', false);
                    }
                    $("input[name='grades" + id + "']").prop('readonly', true);
                } else {
                    $("input[name='grades" + id + "']").prop('readonly', false);
                    $("input[name='grades" + id + "']").val('');
                }
            });

            // Gère les modifications de la checkbox.
            $("#apsolu-form-grades input[type='checkbox']").each(function() {
                var name = $(this).attr("name");
                var id = name.substring(3);

                if ($(this).prop("checked")) {
                    $("input[name='grades" + id + "']").prop('readonly', true);
                }
            });

            // Ajoute la possiblité de trier les tableaux.
            $(".table-sortable").tablesorter({
                headers: {
                    "0": {sorter: false},
                }
            });
        }
    };
});
