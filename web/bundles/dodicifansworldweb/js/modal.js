(function ($) {

    "use strict";

    var ModalPopup = function ($button, options) {

        // initialization and settings
        var settings = $.extend({
            'href': $button.attr('data-modal-url') || '#',
            'target': 'body',
            'duration': 1000,
            // ids
            'container': '#modal',
            'overlay': '#modal-overlay',
            'close': '#modal-close',
            'content': '#modal-content',
            // callbacks
            'onopen': function () {},
            'onload': function () {},
            'onclose': function () {}
        }, options || {}),
            $target,
            $container,
            $overlay,
            $close,
            $content;

        function load() {
            $overlay.addClass('loading');
            $.ajax({
                'url': settings.href,
                'dataType': 'html',
                'error': function () {
                    $overlay.removeClass('loading');
                },
                'success': function (response) {
                    $overlay.removeClass('loading');
                    $content.html(response);
                    settings.onload();
                }
            });
        }

        function open() {
            var documentHeight = $(document).height();
            $container.css('height', documentHeight + 'px');
            $overlay.css('height', documentHeight + 'px');
            
            $container.animate({
                opacity: 'show'
            }, settings.duration, function () {
                settings.onopen();
                load();
            });
        }

        function close() {
            $container.fadeOut(function() {
                $content.html('');
                $container.fadeOut();
                settings.onclose();
            });
        }

        function init() {
            $target = $(settings.target);
            if (!$(settings.container).length) {
                var markup = '';
                markup += '<div id="' + settings.container.replace('#', '') + '">';
                markup += '  <div id="' + settings.overlay.replace('#', '') + '"></div>';
                markup += '  <div id="' + settings.close.replace('#', '') + '"></div>';
                markup += '  <div id="' + settings.content.replace('#', '') + '"></div>';
                markup += '</div>';
                $target.append(markup);
            }

            $container = $(settings.container);
            $overlay = $(settings.overlay);
            $close = $(settings.close);
            $content = $(settings.content);

            $close.on('click', function (e) {
                close();
                e.preventDefault();
                return false;
            });

            $button.on('click', function (e) {
                e.preventDefault();
                open();
                return false;
            });
        }

        init();

    };

    // plugin creation
    $.fn.modalPopup = function (options) {
        $(this).each(function () {
            var $button = $(this);

            if ($button.data('modalPopup')) {
                return;
            }
            $button.data('modalPopup', new ModalPopup($button, options));
        });

    };

}(jQuery));
