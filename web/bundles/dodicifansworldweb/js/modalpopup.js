(function ($) {

    "use strict";
    var publicMethod;

    var ModalPopup = function ($button, options) {

        var self = this;

        // initialization and self.settings
        self.settings = $.extend({
            'href': $button.attr('data-modal-url') || '#',
            'id': $button.attr('id'),
            'target': 'body',
            'duration': 1000,
            'width': null,
            'height': null,
            // ids
            'container': '#modal',
            'overlay': '#modal-overlay',
            'close': '#modal-close',
            'content': '#modal-content',
            // Optionals for open without button
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
                    $(self.settings.container + ' [data-wall]').wall();

                    $(document).bind('keydown',function(e){
                        if ( e.which == 27 ) {
                            close();
                        };
                    });
                    
                    $close = $(self.settings.close);
                    $close.on('click', function (e) {
                        close();
                        e.preventDefault();
                        return false;
                    });

                    $('body').tooltip({
                        selector:'[rel="tooltip"]'
                    });

                    $(".report").fwModalDialog({
                        modal: {
                            backdrop: false, 
                            width: 290
                        }
                    });
                    $("[data-edit='video']").fwModalDialog({
                        modal: {
                            'deleteButton': true
                        }
                    });
                    self.settings.onload(self.settings);

                    // Bind play prev~next video arrows
                    $('.modal-template').find('button.playPrev').on('click', function (e) {
                        playPrevVideo();
                        return false;
                    });
                    $('.modal-template').find('button.playNext').on('click', function (e) {
                        playNextVideo();
                        return false;
                    });
                    //Add share button bindings
                    share.init();
                }
            });
        }

        function playNextVideo() {
            var myId = self.settings.id;
            var ids = [];
            $(".isotope_container").data('isotope').$filteredAtoms.each(function(el) {
                ids.push($(this).attr('id'));
            });
            //$($(".isotope_container").data('isotope').$filteredAtoms[4]).attr('id')
            //$($(".isotope_container").data('isotope').$filteredAtoms[4]).find('a[data-modal-url]').first()
            var index = $.inArray(myId,ids) ? $.inArray(myId,ids) : false;
            if(index) {
                var link = $(".isotope_container").data('isotope').$filteredAtoms[index + 1];
                $(link).find('a[data-modal-url]').first().trigger('click');
            }
        }
        function playPrevVideo() {
            var myId = self.settings.id;
            var ids = [];
            $(".isotope_container").data('isotope').$filteredAtoms.each(function(el) {
                ids.push($(this).attr('id'));
            });
            //$($(".isotope_container").data('isotope').$filteredAtoms[4]).attr('id')
            //$($(".isotope_container").data('isotope').$filteredAtoms[4]).find('a[data-modal-url]').first()
            var index = $.inArray(myId,ids) ? $.inArray(myId,ids) : false;
            if(index) {
                var link = $(".isotope_container").data('isotope').$filteredAtoms[index - 1];
                $(link).find('a[data-modal-url]').first().trigger('click');
            }
        }
        function open() {
            var windowHeight = $(window).height();
            $container.css('height', windowHeight + 'px');
            $overlay.css('height', windowHeight + 'px');

            console.log("POPUP-ID")
            console.log(self.settings.id)

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
                $(document).unbind('keydown');
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

                markup += '<div id="modal">';
                markup += '  <div id="modal-overlay"></div>';
                markup += '  <div id="modal-close"></div>';
                markup += '  <div id="modal-content" style="' + inlineStyle + '"></div>';
                markup += '</div>';
                $target.append(markup);
            }

            $container = $(self.settings.container);
            $overlay = $(self.settings.overlay);
            $content = $(self.settings.content);
            

            if($button.attr('data-open-modal')){
                open();
            }else{
                $button.on('click', function (e) {
                    e.preventDefault();
                    open();
                    return false;
                });
            }
        }

        init();
        if(options == 'close'){
            close();
        }
    };

    publicMethod = $.fn['modalPopup'] = $['modalPopup'] = function(options){
        new ModalPopup($('body').attr('data-open-modal', 'true'), options);
    };

    // plugin creation
    window.$.fn.modalPopup = function (options) {
        $(this).each(function () {
            var $button = $(this);
            $button.data('modalPopup', new ModalPopup($button, options));
        });
    };
}(jQuery, window));
