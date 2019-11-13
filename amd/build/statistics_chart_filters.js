define(["jquery","core/str","core/chartjs","core/ajax","core/chart_builder","core/chart_output_chartjs","core/chart_output_htmltable","core/mustache","core/templates"],function(n,e,a,r,s,o,d,t,c){n.extend({resetChart:function(c,t){a.helpers.each(a.instances,function(i){i.canvas.parentNode&&i.canvas.parentNode.getAttribute("aria-describedby")=="chart-table-data-"+c&&r.call([{methodname:"local_apsolu_get_chartdataset",args:t,done:function(t){var e=n("#chart-area-"+c),a=e.find(".chart-table-data"),r=e.find("canvas");if(t.success){e.show(),i.destroy();t=JSON.parse(t.chartdata);s.make(t).then(function(t){new o(r,t),new d(a,t)})}else e.hide(),alert(JSON.parse(t.chartdata))},fail:Notification.exception}])})},actionCriterias:function(e,a,i){n("button[id^='btn-"+i+"-"+e+"']").each(function(t){n(this).unbind("click"),n(this).click(function(){n("button[id^='btn-"+i+"-"+e+"']").each(function(t){n(this).removeClass("active")}),"resetCriterias"!=n(this).attr("value")&&n(this).addClass("active");var r={},t=n(this).attr("data-type");n("button.active[id*='-"+e+"-']").each(function(t){var e=n(this).attr("data-criteria"),a=n(this).attr("value");r[e]=[{id:a,active:!0}]}),args={options:{classname:t,reportid:a,criterias:r}},n.resetChart(e,args)})})},renderCriteria:function(a,r,i){var t=[{key:"statistics_chart_criteria_"+a,component:"local_apsolu"}];e.get_strings(t).then(function(t){c.render("local_apsolu/statistics_chart_filters_criteria",{btnid:r,label:t[0]+" : ",criteriatype:a,options:i.criterias[a],classname:i.classname}).then(function(t,e){c.appendNodeContents("#chart-filters-"+r,t,e),n.actionCriterias(r,i.reportid,a)})})}}),n.fn.extend({renderCriterias:function(t,e){e.criterias&&(e.criterias.cities&&n.renderCriteria("cities",t,e),e.criterias.calendarstypes&&n.renderCriteria("calendarstypes",t,e),e.criterias.complementaries&&n.renderCriteria("complementaries",t,e))}})});