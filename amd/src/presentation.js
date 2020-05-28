define(['jquery', 'enrol_select/select2'], function($) {
    return {
        initialise : function() {
            function filter() {
                // Filtre le tableau de l'offre de formations.

                // Réaffiche toutes les lignes pour mieux les masquer ensuite.
                $('#apsolu-enrol-select tbody tr').css('display', 'table-row');

                $('.apsolu-enrol-selects').each(function(index) {
                    values = $(this).select2('data');
                    target = $(this).attr('name');
                    if (values.length === 0) {
                        // Ne pas parcourir le tableau entier, si il n'y a aucun filtre pour cette colonne.
                        return;
                    }

                    header_children = undefined;

                    $('#apsolu-enrol-select tbody tr').each(function() {
                        if ($(this).children().length === 1) {
                            // On ne traite pas les <th>, mais on les masque si elles ne servent à rien.
                            if (header_children === 0) {
                                header.css('display', 'none');
                            }

                            header = $(this);
                            header_children = 0;

                            return;
                        }

                        if ($(this).css('display') === 'none') {
                            // Ne pas parcourir le tableau entier, si la ligne est déjà masquée.
                            return;
                        }
                        console.log(target);
                        // Pour chaque valeur sélectionnée dans le widget select2...
                        for (var i = 0 ; i < values.length ; i++) {
                            value = $.trim($(this).children('[data-column="'+target+'"]').attr('data-value'));
                            // if (value === values[i].text) {
                            if (value.indexOf(values[i].text) !== -1) {
                                // Si la valeur correspond au filtre, on continue...
                                header_children++;
                                return;
                            }
                        }

                        // On masque la ligne si la colonne ne correspond à aucun critère de recherche.
                        $(this).css('display', 'none');
                    });

                    if (header_children === 0) {
                        header.css('display', 'none');
                    }
                });
            }

            /*
            elements_options = new Array();
            for (var i = 0; i < $('#apsolu-enrol-select tbody tr:eq(2) td').length ; i++) {
                elements_options[i] = new Array();
            }

            $('#apsolu-enrol-select tbody tr').each(function() {
                if ($(this).children().length === 1) {
                    // On ignore les <th>.
                    // elements_options[10].push($.trim($(this).text()));
                    return;
                }

                $(this).children().each(function(index) {
                    var text = $.trim($(this).text());
                    if (elements_options[index].indexOf(text) === -1) {
                        elements_options[index].push(text);
                    }
                });
            });

            var tr = document.createElement('tr');
            for (var i = 0; i < $('#apsolu-enrol-select tbody tr:eq(2) td').length ; i++) {
                elements_options[i].sort();

                var select = document.createElement('select');
                select.setAttribute('class', 'apsolu-enrol-selects');
                select.setAttribute('multiple', 'multiple');
                for (var j = 0 ; j < elements_options[i].length ; j++) {
                    var option = document.createElement('option');
                    var text = document.createTextNode(elements_options[i][j]);
                    option.append(text);
                    select.append(option);
                }

                var td = document.createElement('td');
                td.append(select);
                tr.append(td);
            }

            $('#apsolu-enrol-select thead').append(tr);
            */
            // $('.apsolu-enrol-selects').select2({width: 'resolve'});
            $('.apsolu-enrol-selects').select2({
                allowClear: true,
                width: '18em'
                // width: 'resolve'
                });
            // $('.apsolu-enrol-selects').select2();
            $('.apsolu-enrol-selects').on('change.select2', function() {
                filter();
            });

            // Initialise les filtres.
            filter();
        }
    };
});
