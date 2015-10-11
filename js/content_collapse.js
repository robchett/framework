(function(defaults, $, window, document, undefined) {
	'use strict';
	$.fn.extend({
		collapseText : function(options) {
			options = $.extend({}, defaults, options);
			return $(this).each(function() {
				var $ths = $(this);
				var height = $(this).data('collapse-height') || options.height;
				var showText = $(this).data('collapse-show-text') || options.showText;
				var hideText = $(this).data('collapse-hide-text') || options.hideText;				
				var allowHide = $(this).data('collapse-allow-hide') || options.allowHide;

				$(this).css({"height": "auto"}).find('collapse-button').remove();
				if ($(this).children().eq(0).hasClass('collapse-container')) {
					$(this).html($(this).children().eq(0).html());
				}
				if ($(this).height() > parseInt(height)) {
					var $inner = $(this).wrapInner('<div class="collapse-container"></div>').children(".collapse-container");
					$inner.css({"height": height, "overflow": "hidden"});
					var $button1 = $('<a class="btn collapse-button collapse-button-show"/></a>').html(showText);
					if (allowHide) {
						var $button2 = $('<a class="btn collapse-button collapse-button-hide"></a>').html(hideText).hide();
						$button1.click(function() {$(this).hide(); $button2.show(); $inner.css({height: "auto"});});
						$button2.click(function() {$(this).hide(); $button1.show(); $inner.css({height: height});});
						$(this).append($button1).append($button2);
					} else {
						button1.click(function() {$(this).hide(); $inner.css({height: "auto"});});
						$(this).append($button1);
					}
				}
			});
		}
	});
})({
	height : "200px",
	showText : "Show more",
	hideText: "Show less",
	allowHide: true
}, jQuery, window, document);