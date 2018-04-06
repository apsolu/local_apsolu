define(['jquery', 'local_apsolu_courses/jquery.tablesorter'], function($) {
    return {
        initialise : function(){
            // Gère les checkboxes... blabla !
            $('.select_options').change(function(){
                var form = $(this).parents(':eq(5)');

                var checkboxes = $(this).parents(':eq(1)').find("input[type='checkbox']:checked");
                if (checkboxes.length > 0 && $(this).val() !== '') {
                    $(this).parents(':eq(1)').submit();
                }
            });

            $('.checkall').click(function(){
                var form = $(this).parents(':eq(5)');
                form.find("input[type='checkbox']").prop('checked', true);
                form.find('input[name="notify"]').prop('disabled', false);
            });

            $('.uncheckall').click(function(){
                var form = $(this).parents(':eq(5)');
                form.find("input[type='checkbox']").prop('checked', false);
                form.find('input[name="notify"]').prop('disabled', true);
            });

            $('.apsolu-select-manage-users-input-checkbox').change(function(){
                var form = $(this).parents(':eq(5)');
                if (form.find(".apsolu-select-manage-users-input-checkbox:checked").length == 0) {
                    form.find('input[name="notify"]').prop('disabled', true);
                } else {
                    form.find('input[name="notify"]').prop('disabled', false);
                }
            });

            $('input[name="notify"]').prop('disabled', true);

            // ajoute la possiblité de trier les tableaux
            $(".table-sortable").tablesorter({
                headers: {
                    0: {sorter: false},
                    1: {sorter: false}
                    // 6: {sorter: false}
                }
            });
        }
    };
});
