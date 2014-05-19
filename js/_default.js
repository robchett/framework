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

function randomString(length, chars) {
    var mask = '';
    if (chars.indexOf('a') > -1) mask += 'abcdefghijklmnopqrstuvwxyz';
    if (chars.indexOf('A') > -1) mask += 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    if (chars.indexOf('#') > -1) mask += '0123456789';
    if (chars.indexOf('!') > -1) mask += '~`!@#$%^&*()_+-={}[]:";\'<>?,./|\\';
    var result = '';
    for (var i = length; i > 0; --i) result += mask[Math.round(Math.random() * (mask.length - 1))];
    return result;
}