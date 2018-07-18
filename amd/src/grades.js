define(['jquery', 'local_apsolu/jquery.tablesorter'], function($) {
    return {
        initialise : function(){

            // Gère les modifications de la checkbox.
            $("#apsolu-form-grades input[type='checkbox']").change(function(){
                var name = $(this).attr("name");
                var id = name.substring(3);

                if ($(this).prop("checked")) {
                    if (name.substring(0, 3) == "abj") {
                        //
                        $("input[name='abi"+id+"']").prop('checked', false);
                    } else {
                        $("input[name='abj"+id+"']").prop('checked', false);
                    }
                    $("input[name='grade"+id+"']").prop('disabled', true);
                } else {
                    $("input[name='grade"+id+"']").prop('disabled', false);
                }
            });

            // Gère les modifications de la checkbox.
            $("#apsolu-form-grades input[type='checkbox']").each(function(){
                var name = $(this).attr("name");
                var id = name.substring(3);

                if ($(this).prop("checked")) {
                    $("input[name='grade"+id+"']").prop('disabled', true);
                }
            });

            // Ajoute la possiblité de trier les tableaux.
            $(".table-sortable").tablesorter({
                headers: {
                    0: {sorter: false},
                    7: {sorter: false},
                    8: {sorter: false},
                    9: {sorter: false},
                    10: {sorter: false},
                    11: {sorter: false},
                    12: {sorter: false},
                    13: {sorter: false}
                }
            });
        }
    };
});