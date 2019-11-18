define(
  [
    "jquery",
    'core/notification',    
    "core/ajax",
    "local_apsolu/moment",
    "local_apsolu/datatables.net",
    "local_apsolu/datatables.net-buttons",
    "local_apsolu/datatables.net-bs4",
    "buttons.bootstrap4",
    "buttons.html5",
  ],
  function($, notification, Ajax, moment) {
    return {
      init: function(selector) {

        moment.locale('fr');

        $(document).ready(function(){
            var reportid = $( "#id_reportid" ).val(); 
            if (reportid != "0") {
              $( "#id_reportid" ).trigger("change");
            } 
        });
        
        $( "#id_reportid" ).change(function() {
          var requests = [];
          var selector = '.report-enrolList-table';
          
          // Destroy results table        
          if ( $.fn.dataTable.isDataTable( selector ) ) {
            table = $(selector).DataTable();
            table.destroy();
            $(selector).empty(); 
          } 
          
          if (this.value != "0") {
            $("#spinner").css('visibility', 'visible');
            
            // Get Report value
            var requests = Ajax.call([{
              methodname: 'local_apsolu_get_reportdataset',
              args: {'classname':'population','reportid':this.value}
            }]);
            
            requests[0].done(function(enrols) {
              // Display tooltip
              if (enrols.tooltip) {
                console.log (enrols.tooltip)
              }
            
              // Get ordering
              var json_order = JSON.parse(enrols.orders);
              var order = [];
              for(var i in json_order)
                  order.push([i, json_order[i]]);
                  
              // Build results table
              var options = {
                data : JSON.parse(enrols.data),
                columns: JSON.parse(enrols.columns, function (key, value) {
                    if (value && (typeof value === 'string') && value.indexOf("function") === 0) {
                        eval("var jsFunc = " + value);
                        return jsFunc;
                    }
                    return value;
                }),                
                order: order,
                buttons: ['csvHtml5'],
                dom: '<"top"Bfi>rt<"bottom"lp><"clear">', //'dom': 'lfrtip',
                pageLength:30,
                language: {
                      "sEmptyTable":     "Aucune donnée disponible dans le tableau",
                      "sInfo":           "Affichage de l'élément _START_ à _END_ sur _TOTAL_ éléments",
                      "sInfoEmpty":      "Affichage de l'élément 0 à 0 sur 0 élément",
                      "sInfoFiltered":   "(filtré à partir de _MAX_ éléments au total)",
                      "sInfoPostFix":    "",
                      "sInfoThousands":  ",",
                      "sLengthMenu":     "Afficher _MENU_ éléments",
                      "sLoadingRecords": "Chargement...",
                      "sProcessing":     "Traitement...",
                      "sSearch":         "Rechercher :",
                      "sZeroRecords":    "Aucun élément correspondant trouvé",
                      "oPaginate": {
                          "sFirst":    "Premier",
                          "sLast":     "Dernier",
                          "sNext":     "Suivant",
                          "sPrevious": "Précédent"
                      },
                      "oAria": {
                          "sSortAscending":  ": activer pour trier la colonne par ordre croissant",
                          "sSortDescending": ": activer pour trier la colonne par ordre décroissant"
                      },
                      "select": {
                              "rows": {
                                  "_": "%d lignes sélectionnées",
                                  "0": "Aucune ligne sélectionnée",
                                  "1": "1 ligne sélectionnée"
                              } 
                      }              
                    },
                initComplete: function(settings, json) {
                  $("#spinner").css('visibility', 'hidden');                          
                          
                  // Build columns filters
                  if (enrols.filters) {
                    var filters = JSON.parse(enrols.filters);
                    
                    $(selector+' thead tr').clone(false).appendTo( selector+' thead' );
                    $(selector+' thead tr:eq(1) th').removeAttr('class').html('');
                    
                    if (filters.input) {
                      this.api().columns(filters.input).every( function () {
                        var column = this;
                        if (column.visible()) {
                          
                          var title = $(column.header()).html();
                          var input = $('<input type="text" class="column_search" placeholder=" '+title+'" />')
                              .appendTo( $(selector+' thead tr:eq(1) th').eq(column.index()).empty() )
                              .on( 'keyup change', function () {
                                  var val = $.fn.dataTable.util.escapeRegex(
                                      $(this).val()
                                  );
                                  column
                                          .search(val)
                                          .draw();
                              } );
                        }
                      });
                    }
                    if (filters.select) {
                      this.api().columns(filters.select).every( function () {
                        var column = this;
                        if (column.visible()) {
                          var select = $('<select><option value=""></option></select>')
                              .appendTo( $(selector+' thead tr:eq(1) th').eq(column.index()).empty() )
                              .on( 'change', function () {
                                  var val = $.fn.dataTable.util.escapeRegex(
                                      $(this).val()
                                  );
           
                                  column
                                      .search( val ? '^'+val+'$' : '', true, false )
                                      .draw();
                              } );
           
                          column.data().unique().sort().each( function ( d, j ) {
                            var title = $(column.header()).html();
                            if (title != "Jour")
                            {                      
                              select.append( '<option value="'+d+'">'+d+'</option>' )
                            } else {
                              select.append( '<option value="'+moment.weekdays()[d]+'">'+moment.weekdays()[d]+'</option>' )
                            }
                          });                                                 
                        }
                      });
                    }
                  }
                  
                },
                
                
              };
                      
              var table = $(selector).DataTable(options);
              table.unique(); // Remove doublons
              
              
            }).fail(notification.exception);     
            
            
          }            
        });

      }
    };
  }
);

