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
 * Search municipality selector module.
 *
 * @module local_apsolu/federation_adhesion_municipalities_form
 * @copyright 2025 Université Rennes 2
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax'], function($, Ajax) {
    return {
        processResults: function(selector, results) {
            var municipalities = [];
            $.each(results, function(index, municipality) {
                municipalities.push({
                    value: municipality.inseecode,
                    label: municipality.name + ' (' + municipality.departmentid + ')'
                });
            });
            return municipalities;
        },

        transport: function(selector, query, success, failure) {
            var promise;

            // Search within specific course if known and if the 'search within' dropdown is set
            // to search within course or activity.
            var args = {query: query};

            // Call AJAX request.
            promise = Ajax.call([{methodname: 'local_apsolu_get_relevant_municipalities', args: args}]);

            // When AJAX request returns, handle the results.
            promise[0].then(function(results) {
                var promises = [];

                // Render label with name and departmentid.
                $.each(results, function(index, municipality) {
                    promises.push(municipality.name + ' (' + municipality.departmentid + ')');
                });

                // Apply the label to the results.
                return $.when.apply($.when, promises).then(function() { // eslint-disable-line promise/no-nesting
                    var args = arguments;
                    var i = 0;
                    $.each(results, function(index, municipality) {
                        municipality._label = args[i++];
                    });
                    success(results);
                    return;
                });

            }).fail(failure);
        }
    };
});
