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
 * @module     local_apsolu/statistics_chart_filters
 * @copyright  2019 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(
  [
    'jquery',
    'core/str',
    'core/chartjs',
    'core/ajax',
    'core/chart_builder',
    'core/chart_output_chartjs',
    'core/chart_output_htmltable',
    'core/mustache',
    'core/notification',
    'core/templates',
  ], function($, str, Chartjs, Ajax, Builder, Output, OutputTable, Mustache, Notification, Templates) {

    $.extend({
      resetChart: function(id, args) {
        Chartjs.helpers.each(Chartjs.instances, function(instance) {
          if (instance.canvas.parentNode) {
          if (instance.canvas.parentNode.getAttribute("aria-describedby") == "chart-table-data-" + id) {
            Ajax.call([{
              methodname: 'local_apsolu_get_chartdataset',
              args: args,
              done: function(data) {
                var chartArea = $('#chart-area-' + id),
                chartTable = chartArea.find('.chart-table-data'),
                chartCanvas = chartArea.find('canvas');

                if (data.success) {
                  chartArea.show();
                  instance.destroy();
                  var data = JSON.parse(data.chartdata);
                  Builder.make(data).then(function(ChartInst) {
                    new Output(chartCanvas, ChartInst);
                    new OutputTable(chartTable, ChartInst);
                  });
                } else {
                  chartArea.hide();
                }
              },
              fail: Notification.exception
            }]);
          }
          }
        });
      },

      actionCriterias: function(uniqid, reportid, criteriatype) {
        $("button[id^='btn-" + criteriatype + "-" + uniqid + "']").each(function() {
          $(this).unbind("click");
          $(this).click(function() {
            // Set active button
            $("button[id^='btn-" + criteriatype + "-" + uniqid + "']").each(function() {
              $(this).removeClass('active');
            });
            if ($(this).attr('value') != "resetCriterias") {
              $(this).addClass('active');
            }

            // Build search criterias.
            var criterias = {};
            var classname = $(this).attr('data-type');
            $("button.active[id*='-" + uniqid + "-']").each(function() {
              var criteria = $(this).attr('data-criteria');
              var id = $(this).attr('value');
              criterias[criteria] = [{"id": id, "active": true}];
            });
            let args = {'options': {"classname": classname, "reportid": reportid, "criterias": criterias}};

            // Reset chart from criterias.
            $.resetChart(uniqid, args);

          });
        });
      },

      renderCriteria: function(key, uniqid, options) {
        var strings = [
            {key: 'statistics_chart_criteria_' + key, component: 'local_apsolu'},
        ];
        str.get_strings(strings).then(function(results) {
          Templates.render('local_apsolu/statistics_chart_filters_criteria', {
              'btnid': uniqid,
              'label': results[0] + ' : ',
              'criteriatype': key,
              'options': options.criterias[key],
              'classname': options.classname}
          ).then(function(html, js) {
                  Templates.appendNodeContents('#chart-filters-' + uniqid, html, js);
                  $.actionCriterias(uniqid, options.reportid, key);
                  return true;
              }).fail();
            return true;
        }).fail();
      },
    });

    $.fn.extend({
      renderCriterias: function(uniqid, options) {
        if (options.criterias) {
          if (options.criterias.cities) {
            $.renderCriteria("cities", uniqid, options);
          }
          if (options.criterias.calendarstypes) {
            $.renderCriteria("calendarstypes", uniqid, options);
          }
          if (options.criterias.complementaries) {
            $.renderCriteria("complementaries", uniqid, options);
          }
        }
      },
    });
  }
);
