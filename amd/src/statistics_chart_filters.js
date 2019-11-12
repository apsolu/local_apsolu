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
    'core/templates',
  ], function($, str, Chartjs, Ajax, Builder, Output, OutputTable, Mustache, Templates) {

    $.extend({ 
      resetChart : function (id,args) {
        Chartjs.helpers.each(Chartjs.instances, function(instance){
          if (instance.canvas.parentNode.getAttribute("aria-describedby") == "chart-table-data-" + id){
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
                  alert (JSON.parse(data.chartdata));
                }
              
              },
              fail: Notification.exception
            }]);
          }
        })
      },
      
      actionCriterias : function (uniqid,reportid,criteriatype) {
        $("button[id^='btn-"+criteriatype+"-"+uniqid+"']").each(function(i){
          $(this).unbind("click");
          $(this).click(function(){
            // Set active button
            $("button[id^='btn-"+criteriatype+"-"+uniqid+"']").each(function(i){
              $(this).removeClass('active');
            });
            if ($(this).attr('value') != "resetCriterias") {
              $(this).addClass('active');
            }
            
            // build search criterias
            var criterias = {};
            var classname = $(this).attr('data-type');
            $("button.active[id*='-"+uniqid+"-']").each(function(i){
              var criteria = $(this).attr('data-criteria');
              var id = $(this).attr('value');
              criterias[criteria] = [{"id":id,"active":true}];
            });
            args = {'options':{"classname":classname,"reportid": reportid,"criterias":criterias}};
    
            // reset chart from criterias
            $.resetChart(uniqid,args);
            
          });
        });  
      },
      
      renderCriteria : function (key,uniqid,options) {
        var label;
        var strings = [
            {key: 'statistics_chart_criteria_'+key,component: 'local_apsolu'},
        ];
        str.get_strings(strings).then(function (results) {
          Templates.render('local_apsolu/statistics_chart_filters_criteria', {'btnid':uniqid,'label':results[0]+' : ','criteriatype':key,'options':options.criterias[key],'classname':options.classname})
              .then(function(html, js) {
                  Templates.appendNodeContents('#chart-filters-'+uniqid, html, js);
                  $.actionCriterias(uniqid,options.reportid,key);
              });
        });
  
      },
      
    }); 
    
    $.fn.extend({
      renderCriterias : function (uniqid,options) {
        if (options.criterias) {
          if (options.criterias.cities) {
            $.renderCriteria("cities",uniqid,options);
          }
          if (options.criterias.calendarstypes) {
            $.renderCriteria("calendarstypes",uniqid,options);
          }
          if (options.criterias.complementaries) {
            $.renderCriteria("complementaries",uniqid,options);          
          }  
        }  
      },
    });
  }
);

