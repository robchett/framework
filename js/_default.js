$(document).ready(function () {
    recreate_checkboxes();
    $.fn.ajax_factory.defaults.complete.push('recreate_checkboxes')
});

recreate_checkboxes = function () {
    var $checkboxes = $(".checkbox_replace");
    $checkboxes.each(function () {
        var $this = $(this);
        var $input = $this.children("input");
        if ($input.prop('checked')) {
            $this.addClass("checked");
        }
    });
    $checkboxes.click(function () {
        var $this = $(this);
        var $input = $this.children("input");
        if (!$input.prop('checked')) {
            $this.addClass("checked");
            $input.prop('checked', true);
        } else {
            $this.removeClass("checked");
            $input.prop('checked', false);
        }
    });
};

window.onpopstate = function (event) {
    if (typeof page_handeler != 'undefined' && event && event.state) {
        page_handeler.page(event.state.url, event.state, 1);
    }
};