var page_handeler = {
    defaults: {
        complete: []
    },

    init: function () {
        $('body').on('click', 'a', function (e) {
            if (typeof $(this).data('page-post') != 'undefined') {
                e.preventDefault();
                this.page($(this).attr('href'), $(this).data('page-post'));
            }
        });

        $.fn.ajax_factory.defaults.complete.push(function (json) {page_handeler.page_callback(json)});
        $.fn.ajax_factory.defaults.load_pages_ajax = true;
    },

    page_callback: function (json) {
        if (json && json.push_state) {
            var $id = $(json.push_state.data.id);
            if($id.length) {
                this.toggle_page($id);
            }
        }
    },

    toggle_page: function ($page) {
        if ($page.css('z-index') != 2) {
            var $main = $('#main');
            $page.hide();
            $main.stop(true, true).addClass('flipped');
            var $children = $main.children('div');
            setTimeout(function () {
                $children.hide();
                $page.show();
                $main.removeClass('flipped');

                $("a").removeClass('sel').parent('li').removeClass('sel');
                var $links = $('a[href="' + $page.data('url') + '"]');
                $links.addClass('sel').parent('li').addClass('sel');
                $main.animate({scrollTo: 0});

                if (page_handeler.defaults.complete) {
                    page_handeler.defaults.complete.each(function (method) {
                        if (typeof method == 'function') {
                            method();
                        } else {
                            window[method]();
                        }
                    });
                }
            }, 300);
        }
    },

    page: function (url, post, is_popped) {
        var module = post.module;
        var act = post.act;
        post.is_popped = is_popped || 0;
        var $page = $("div[data-url='" + url + "']");
        if ($page.length) {
            if (!is_popped) {
                window.history.pushState(post, '', url);
            }
            this.toggle_page($page);
            this.perform_page_actions(post.actions, url);
        } else {
            delete post.module;
            delete post.act;
            post.url = url;
            $.fn.ajax_factory(module, act, post);
        }
    },

    perform_page_actions: function (actions, url) {
        if (typeof actions != 'undefined') {
            actions.each(function (element) {
                var options = element[3] || {};
                options.post_as_url = url;
                $.fn.ajax_factory(element[0], element[1], element[2] || {}, options);
            });
        }
    }
};

$(document).ready(function () {
    page_handeler.init();
});
