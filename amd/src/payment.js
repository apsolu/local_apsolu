define(["jquery"], function($) {
    return {
        initialise : function() {
            $('.apsolu-payment-forms').submit(function(){
                $('input[type=submit]').prop('disabled', true);
            });
        }
    };
});
