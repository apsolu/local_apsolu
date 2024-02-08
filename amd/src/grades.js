define(['jquery'], function($) {
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
        }
    };
});
