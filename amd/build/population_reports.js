define(["jquery","core/notification","core/ajax","local_apsolu/datatables.net","local_apsolu/datatables.net-buttons","local_apsolu/datatables.net-bs4","buttons.bootstrap4","buttons.html5"],function(i,t,a,e){return{init:function(e){i(document).ready(function(){"0"!=i("#id_reportid").val()&&i("#id_reportid").trigger("change")}),i("#id_reportid").change(function(){var s=".report-enrolList-table";i.fn.dataTable.isDataTable(s)&&(table=i(s).DataTable(),table.destroy(),i(s).empty()),"0"!=this.value&&(i("#spinner").css("visibility","visible"),a.call([{methodname:"local_apsolu_get_reportdataset",args:{classname:"population",reportid:this.value}}])[0].done(function(n){n.tooltip&&console.log(n.tooltip);var e=JSON.parse(n.orders),t=[];for(var a in e)t.push([a,e[a]]);var o={data:JSON.parse(n.data),columns:JSON.parse(n.columns),order:t,buttons:["csvHtml5"],dom:'<"top"Bfi>rt<"bottom"lp><"clear">',pageLength:30,language:{sEmptyTable:"Aucune donnée disponible dans le tableau",sInfo:"Affichage de l'élément _START_ à _END_ sur _TOTAL_ éléments",sInfoEmpty:"Affichage de l'élément 0 à 0 sur 0 élément",sInfoFiltered:"(filtré à partir de _MAX_ éléments au total)",sInfoPostFix:"",sInfoThousands:",",sLengthMenu:"Afficher _MENU_ éléments",sLoadingRecords:"Chargement...",sProcessing:"Traitement...",sSearch:"Rechercher :",sZeroRecords:"Aucun élément correspondant trouvé",oPaginate:{sFirst:"Premier",sLast:"Dernier",sNext:"Suivant",sPrevious:"Précédent"},oAria:{sSortAscending:": activer pour trier la colonne par ordre croissant",sSortDescending:": activer pour trier la colonne par ordre décroissant"},select:{rows:{_:"%d lignes sélectionnées",0:"Aucune ligne sélectionnée",1:"1 ligne sélectionnée"}}},initComplete:function(e,t){if(i("#spinner").css("visibility","hidden"),n.filters){var a=JSON.parse(n.filters);i(s+" thead tr").clone(!1).appendTo(s+" thead"),i(s+" thead tr:eq(1) th").removeAttr("class").html(""),a.input&&this.api().columns(a.input).every(function(){var t=this;if(t.visible()){var e=i(t.header()).html();i('<input type="text" class="column_search" placeholder=" '+e+'" />').appendTo(i(s+" thead tr:eq(1) th").eq(t.index()).empty()).on("keyup change",function(){var e=i.fn.dataTable.util.escapeRegex(i(this).val());t.search(e).draw()})}}),a.select&&this.api().columns(a.select).every(function(){var t=this;if(t.visible()){var a=i('<select><option value=""></option></select>').appendTo(i(s+" thead tr:eq(1) th").eq(t.index()).empty()).on("change",function(){var e=i.fn.dataTable.util.escapeRegex(i(this).val());t.search(e?"^"+e+"$":"",!0,!1).draw()});t.data().unique().sort().each(function(e,t){a.append('<option value="'+e+'">'+e+"</option>")})}})}}};i(s).DataTable(o).unique()}).fail(t.exception))})}}});