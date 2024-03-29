define([], function() {
    return {
        initialise: function() {
            let elements = document.querySelectorAll("input[data-apsolu=colorpicker]");
            elements.forEach(function(element) {
                element.setAttribute("type", "color");
                if (element.classList.contains("form-control")) {
                    element.classList.remove("form-control");
                }
            });
        }
    };
});
