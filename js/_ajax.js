$(document).ready(function () {

    function get_form_data($this) {
        var data = {};
        $this.find(':input').each(function () {
            var name = $(this).attr('name');
            if ($(this).attr('type') == 'checkbox') {
                if ($(this).is(':checked')) {
                    if (name.match(/\[\]/)) {
                        data[name] = data[name] || [];
                        data[name].push($(this).val());
                    } else {
                        data[name] = $(this).val();
                    }
                }
            } else
                data[name] = $(this).val();
        });
        return data;
    }

    var $body = $('body');
    $body.on('click', null, function (event) {
        var $target = $(event.target);
        if (!$target.attr('disabled') && !$target.hasClass('disabled')) {
            if ($target.attr('data-ajax-click') || $target.parent('a').attr('data-ajax-click')) {
                if (!$target.attr('data-ajax-click')) {
                    $target = $target.parent('a');
                }
                event.preventDefault();
                var arr = $target.data('ajax-click').split(':');
                var module = arr[0];
                var act = arr[1];
                var options = {};
                if ($target.data('ajax-shroud')) {
                    options.loading_target = $target.data('ajax-shroud');
                }
                var data = $target.data('ajax-post') || {};
                data['origin'] = $target.attr('id');
                $.fn.ajax_factory(module, act, data, options);
            } else if ($.fn.ajax_factory.defaults.load_pages_ajax && ($target.is('a') || ($target = $target.parent('a')).length)) {
                var href = $target.attr('href');
                var rel = $target.attr('rel');
                if (typeof href != "undefined" && href != '#' && (typeof rel == 'undefined' || rel != 'external')) {
                    if (!href.match('http')) {
                        event.preventDefault();
                        var $page = $("div[data-url='" + href + "']");
                        if ($page.length) {
                            window.history.pushState($.fn.ajax_factory.get_state(href), '', href);
                            if (typeof page_handeler != 'undefined') {
                                page_handeler.toggle_page($page);
                                var state = $.fn.ajax_factory.get_state(href);
                                if (typeof state != "undefined" && typeof state.actions != undefined) {
                                    page_handeler.perform_page_actions($.fn.ajax_factory.get_state(href).actions, href);
                                }
                            }
                        } else {
                            var post = {module: 'core', act: 'load_page'};
                            var options = {call_as_uri: href, loading_target: '#main'};
                            $.fn.ajax_factory('core', 'load_page', post, options);
                        }
                    }
                }
            }
        }
    });
    $body.on('change', ':input', function (event) {
        var $target = $(event.target);
        if (!$target.attr('disabled') && !$target.hasClass('disabled')) {
            if ($target.attr('data-ajax-change')) {
                var options = {};
                event.preventDefault();
                var arr = $target.attr('data-ajax-change').split(':');
                var module = arr[0];
                var act = arr[1];
                var data = $target.data('ajax-post') || {};
                if ($target.attr('type') === 'checkbox') {
                    data.value = ($target.is(':checked') ? 1 : 0 );
                } else {
                    data.value = $target.val();
                }
                data['origin'] = $target.attr('id');
                if ($target.data('ajax-shroud')) {
                    options.loading_target = $target.data('ajax-shroud');
                }
                $.fn.ajax_factory(module, act, data, options);
            } else {
                var $parent = $target.parents('form').eq(0);
                if ($parent.data('ajax-change')) {
                    var arr = $parent.attr('data-ajax-change').split(':');
                    var module = arr[0];
                    var act = arr[1];
                    var ajax_shroud = $parent.attr('data-ajax-shroud');
                    var data = get_form_data($parent);
                    var options = {loading_target: ajax_shroud};
                    data.ajax_origin = $parent.id;
                    $.fn.ajax_factory(module, act, data, options);
                    return false;
                }
            }
        }
    });

    $body.on('click', 'form .submit', function (e) {
        $(this).parents('form').eq(0).submit();
        e.preventDefault();
        return false;
    });

    $body.on('submit', 'form.ajax', function (e) {
        e.preventDefault();
        var arr = $(this).attr('action').split(':');
        var module = arr[0];
        var act = arr[1];
        var ajax_shroud = $(this).attr('data-ajax-shroud');
        var data = get_form_data($(this));
        var options = $.fn.extend($(this).data(), {loading_target: ajax_shroud});
        data.ajax_origin = $(e.target)[0].id;
        $.fn.ajax_factory(module, act, data, options);
        return false;
    });
    $body.on('submit', 'form.noajax', function () {
        var ajax_shroud = $(this).attr('data-ajax-shroud');
        var div = add_loading_shroud(ajax_shroud);
        if ($(this).data('ajax-socket')) {
            var socketId = add_socket_io($(this).data('ajax-socket'), div);
            if (!$(this).find('input[name=data-socket]').length) {
                var input = document.createElement('input');
                input.className = 'hidden';
                input.name = 'data-socket';
                input.type = 'hidden';
                input.value = socketId;
                $(this).prepend(input);
            }
        }
    });
    $.fn.ajax_factory = function (module, act, post, options) {
        options = options || {};
        post = post || {};
        var div = add_loading_shroud(options.loading_target);
        if (options.ajaxSocket) {
            post['data-socket'] = add_socket_io(options.ajaxSocket, div);
        }
        post['module'] = module;
        post['act'] = act;
        $(".error_message").remove();
        $.ajax({
            url: options.call_as_uri || window.location,
            global: false,
            async: true,
            type: 'POST',
            dataType: 'json',
            cache: false,
            data: post,
            success: handle_json_response
        });
    };
    $.fn.ajax_factory.defaults = {
        complete: ['initMlink'],
        load_pages_ajax: false
    };
    $.fn.ajax_factory.states = [];
    $.fn.ajax_factory.get_state = function (state) {
        return this.states[state];
    };

    initMlink();
});

function initMlink() {
    $('select[multiple=multiple]').each(function () {
        if (!$(this).siblings('select').length) {
            var id = $(this).attr('name');
            $(this).hide();
            $(this).after('<select id="' + id + '_select" class="' + $(this).attr('class') + '" onchange="addMlink(\'' + id + '\',this.value)"><option value=\'-1\'>Select Another</option></select><ul id="' + id + '_selected" class="mlink_selected_wrapper"></ul>');
            $(this).find('option:not(optgroup > option) ,optgroup').each(function () {
                $(this).clone().appendTo($('#' + id + '_select'));
                if ($(this).is(':selected')) {
                    addMlink(id, $(this).val());
                }
            })
        }
    });
}

function addMlink(id, value) {
    if (value != -1 && value != 0) {
        var $option = $('#' + id + '_select option[value=' + value + ']');
        var title = $option.html();
        $option.attr('disabled', 'disabled');
        $('select[name="' + id + '"] option[value=' + value + ']').attr('selected', 'selected');
        $('#' + id + '_select').val(-1);
        $('#' + id + '_selected').append('<li data-value="' + value + '">' + title + '<a onclick="removeMlink(\'' + id + '\',\'' + value + '\')">Remove</a></li>');
    }
}

function removeMlink(id, value) {
    if (value != -1 && value != 0) {
        var $option = $('#' + id + '_select option[value=' + value + ']');
        $option.removeAttr('disabled');
        $('select[name="' + id + '"] option[value=' + value + ']').removeAttr('selected');
        $('select[name="' + id + '"]').trigger('change');
        $('#' + id + '_selected [data-value=' + value + ']').remove();

    }
}

function handle_json_response(json) {
    $('.loading_shroud').remove();
    json.pre_inject.each(function (inj) {
        if (inj.over != '') {
            $(inj.over).remove();
        }
        switch (inj.pos) {
            case 'append':
                $(inj.id).append(inj.html);
                break;
            case 'prepend':
                $(inj.id).prepend(inj.html);
                break;
            case 'before':
                $(inj.id).before(inj.html);
                break;
            case 'after':
                $(inj.id).after(inj.html);
                break;
        }
    });
    json.update.each(function (upd) {
        $(upd.id).html(upd.html);
    });
    json.inject.each(function (inj) {
        if (inj.over != '') {
            $(inj.over).remove();
        }
        switch (inj.pos) {
            case 'append':
                $(inj.id).append(inj.html);
                break;
            case 'prepend':
                $(inj.id).prepend(inj.html);
                break;
            case 'before':
                $(inj.id).before(inj.html);
                break;
            case 'after':
                $(inj.id).after(inj.html);
                break;
        }
    });
    if (typeof json.push_state != "undefined") {
        $.fn.ajax_factory.states[json.push_state.url] = json.push_state.data;
        if (json.push_state.push) {
            window.history.pushState(json.push_state.data, json.push_state.title, json.push_state.url);
        } else if (json.push_state.replace) {
            window.history.replaceState(json.push_state.data, json.push_state.title, json.push_state.url);
        }
    }
    if ($.fn.ajax_factory.defaults.complete) {
        $.fn.ajax_factory.defaults.complete.each(function (method, i, json) {
            if (typeof method == 'function') {
                method(json);
            } else {
                window[method](json);
            }
        }, json);
    }
}

Array.prototype.each = function (callback, context) {
    for (var i in this) {
        if (this.hasOwnProperty(i)) {
            callback(this[i], i, context);
        }
    }
};
Array.prototype.count = function () {
    return this.length - 2;
};
String.prototype.isNumber = function () {
    return !isNaN(parseFloat(this)) && isFinite(this);
};

function add_loading_shroud(ajax_shroud) {
    if (typeof ajax_shroud != 'undefined') {
        var div = document.createElement('div');
        div.className = 'loading_shroud';
        div.style.width = $(ajax_shroud).outerWidth() + 'px';
        div.style.height = $(ajax_shroud).outerHeight() + 'px';
        div.style.left = 0;
        div.style.top = 0;
        if ($(ajax_shroud).css('position') != 'absolute' || $(ajax_shroud).css('position') != 'relative') {
            $(ajax_shroud).css({'position': 'relative'});
        }
        $(ajax_shroud).prepend(div);
        return div;
    }
    return false;
}
var socketId = false;
function add_socket_io(ajaxSocket, write_element) {
    if (!socketId) {
        socketId = randomString(32, 'aA#');
        if (write_element) {
            $.getScript(window.location.origin + ':8000/socket.io/socket.io.js', function () {
                var socket = io.connect(window.location.origin + ':8000');
                socket.emit('set nickname', socketId);
                socket.on('message', function (data) {
                    $(write_element).append('<code>' + data + '</code>');
                });
            });
        }
    }
    return socketId;
}