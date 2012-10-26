(function ($) {

    "use strict";

    var ModalPopup = function ($button, options) {

        var self = this;

        // initialization and self.settings
        self.settings = $.extend({
            'href': $button.attr('data-modal-url') || '#',
            'target': 'body',
            'duration': 1000,
            'width': null,
            'height': null,
            // ids
            'container': '#modal',
            'overlay': '#modal-overlay',
            'close': '#modal-close',
            'content': '#modal-content',
            // callbacks
            'onopen': function () {},
            'onload': function () {},
            'onclose': function () {}
        }, options || {});
        var
        $target,
        $container,
        $overlay,
        $close,
        $content
        publicMethod;

        function load() {
            $overlay.addClass('loading');
            $.ajax({
                'url': self.settings.href,
                'dataType': 'html',
                'error': function () {
                    $overlay.removeClass('loading');
                },
                'success': function (response) {
                    $overlay.removeClass('loading');
                    $content.html(response);
                    self.settings.onload(self.settings);
                }
            });
        }

        function open() {
            var documentHeight = $(document).height();
            $container.css('height', documentHeight + 'px');
            $overlay.css('height', documentHeight + 'px');
            
            $container.animate({
                opacity: 'show'
            }, self.settings.duration, function () {
                self.settings.onopen();
                load();
            });
        }

        function close() {
            $container.fadeOut(function() {
                $content.html('');
                $container.fadeOut();
                self.settings.onclose();
            });
        }

        function init() {
            if ($button.data('modalPopup')) {
                self.settings = $button.data('modalPopup').settings;
            }
            $target = $(self.settings.target);
            
            if (!$(self.settings.container).length) {
                var markup = '',
                inlineStyle = '';
                
                if(self.settings.width) {
                    inlineStyle += 'width:' + self.settings.width + 'px;left:50%;margin-left:-' + Math.ceil(self.settings.width/2) + 'px;';
                }
                
                if(self.settings.height) {
                    inlineStyle += 'height:' + self.settings.height + 'px;top:50%;margin-top:-' + Math.ceil(self.settings.height/2) + 'px;';
                }
                
                markup += '<div id="' + self.settings.container.replace('#', '') + '">';
                markup += '  <div id="' + self.settings.overlay.replace('#', '') + '"></div>';
                markup += '  <div id="' + self.settings.close.replace('#', '') + '"></div>';
                markup += '  <div id="' + self.settings.content.replace('#', '') + '" style="' + inlineStyle + '"></div>';
                markup += '</div>';
                $target.append(markup);
            }

            $container = $(self.settings.container);
            $overlay = $(self.settings.overlay);
            $close = $(self.settings.close);
            $content = $(self.settings.content);
            
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
        if(options == 'close'){
            close();
        }
    };

    // plugin creation
    window.$.fn.modalPopup = function (options) {
        $(this).each(function () {
            var $button = $(this);
            $button.data('modalPopup', new ModalPopup($button, options));
        });
    };

    window.$.fn.modalPopup.close = function () {
        console.log('close');
    };
}(jQuery, window));
