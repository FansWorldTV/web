/*global
 $,
 jQuery,
 error,
 success,
 endless,
 ajax,
 templateHelper,
 Routing,
 appLocale,
 exports,
 module,
 require,
 define
 */
/*jslint nomen: true */                 /* Tolerate dangling _ in identifiers */
/*jslint vars: true */           /* Tolerate many var statements per function */
/*jslint white: true */                       /* tolerate messy whithe spaces */
/*jslint browser: true */
/*jslint devel: true */                         /* Assume console, alert, ... */
/*jslint windows: true */               /* Assume window object (for browsers)*/

/*******************************************************************************
 * Class dependencies:                                                         *
 *      jquery > 1.8.3                                                         *
 *      isotope                                                                *
 *      jsrender                                                               *
 *      jsviews                                                                *
 * external dependencies:                                                      *
 *      templateHelper                                                         *
 *      base genericAction                                                     *
 *      ExposeTranslation                                                      *
 *      FOS Routing                                                            *
 ******************************************************************************/

$(document).ready(function () {
    "use strict";
    var pluginName = "fwHomeGallery";
    var defaults = {
        style: {
            'width': '16%',
            'height': '160px',
            'margin-top': '5px',
            'margin-bottom': '5px',
            'border': '1px solid #333',
            'border-radius': '4px',
            'overflow': 'hidden'
        },
        itemSelector: '.video',
        feedSource: '',
        feedfilter: {}
    };
    function Plugin(element, options) {
        this.element = element;
        this.options = $.extend({}, defaults, options);
        this._defaults = defaults;
        this._name = pluginName;
        this.init();
    }
    Plugin.prototype = {
        init: function () {
            var that = this;
            var self = $(that.element);
            self.bind("destroyed", $.proxy(that.teardown, that));
            self.addClass(that._name);

            that.appendVideos(2)

            return;
            // initialize Isotope
            self.isotope({
                // options...
                itemSelector: '.video',
                resizable: false, // disable normal resizing
                // set columnWidth to a percentage of container width
                masonry: { columnWidth: self.width() / 6 }
            });

            var $container = $('section.highlights');
            $(that.options.itemSelector).css(that.options.style);

            $(that.options.itemSelector + '.double').css({
                'width': '32.5%',
                'height': '332px'
            });
            $(that.options.itemSelector + ' img').css({
                'width': '100%',
                'height': '100%'
            });
            // initialize Isotope
            $container.isotope({
                // options...
                itemSelector: '.video',
                resizable: false, // disable normal resizing
                // set columnWidth to a percentage of container width
                masonry: { columnWidth: $container.width() / 6 }
            });

            // update columnWidth on window resize
            $(window).smartresize(function(){
                $container.isotope({
                    // update columnWidth to a percentage of container width
                    masonry: { columnWidth: $container.width() / 6 }
                });
            });

            return;
            // Attach to window resize event
//            $(window).smartresize(function(){
//                var cells = that.getMaxSections($container);
//                self.find(that.options.itemSelector).each(function(i, item){
//                    var $this = $(this);
//                    $this.css('width', ((100 / cells) - 1) + "%");
//                });
//                $container.isotope({
//                    // update columnWidth to a percentage of container width
//                    masonry: { columnWidth: $container.width() / cells }
//                });
//                $container.isotope('reLayout');
//            });

            // update columnWidth on window resize
//            $(window).smartresize(function(){
//                $container.isotope({
//                    // update columnWidth to a percentage of container width
//                    masonry: { columnWidth: self.width() / 6 }
//                });
//            });
        },
        appendVideos: function(videoCategory) {
            var that = this;
            var i = 0;
            // ajax.genericAction('home_ajaxfilter', {paginate:{'page':1, 'block':'popular','vc': 6}}, function(r){console.log(r);});
            // $.ajax({url: Routing.generate('es_home_ajaxfilter'), data: {'vc': 1}}).then(function(r){console.log(r)})
            var feed = Routing.generate(appLocale + '_home_ajaxfilter');
            $.ajax({url: feed, data: {'vc': videoCategory}}).then(function(response){
                for(i in response.highlighted) {
                    if (response.highlighted.hasOwnProperty(i)) {
                        console.log(response.highlighted[i]);
                        var video = response.highlighted[i];
                        var thumb = document.createElement('article');
                        var image = document.createElement('img');
                        //thumb.className('video');
                        $(that.element).append('<article class="video"><img src="' + video.image + '" /></article>');
                    }
                }
                $(that.element).append()
            });
        },
        getMaxSections: function(container) {
            var that = this;
            var $container = $(that.element);
            var cells = 0;
            var width = parseInt($container.width(), 10);

            if(width <= 600) {
                cells = 1;
            } else if (width <= 800) {
                cells = 1;
            } else if (width <= 1000) {
                cells = 2;
            } else if (width <= 1200) {
                cells = 3;
            } else if (width <= 1200) {
                cells = 4;
            } else if (width <= 1600) {
                cells = 4;
            } else if (width > 1600) {
                cells = 5;
            }
            return cells;
        },
        destroy: function() {
            var that = this;
            $(that.element).unbind("destroyed", that.teardown);
            that.teardown();
        },
        teardown: function() {
            var that = this;
            $.removeData($(that.element)[0], that._name);
            $(that.element).removeClass(that._name);
            that.unbind();
            that.element = null;
        },
        bind: function() { },
        unbind: function() { }
    };
    $.fn[pluginName] = function (options) {
        return this.each(function () {
            if (!$.data(this, pluginName)) {
                $.data(this, pluginName, new Plugin(this, options));
            }
        });
    };
});

//Attach plugin to all matching element
$(document).ready(function () {
    "use strict";
    //$('section.highlights').fwHomeGallery({});
});

$(document).ready(function () {


    var $container = $('section.highlights');

    function createIsotope(x) {
        $container.find('.video').css({
            'width': '16%',
            'height': '160px',
            'margin-top': '5px',
            'margin-bottom': '5px',
            'border': '1px solid #333',
            'border-radius': '4px',
            'overflow': 'hidden'
        });
        $container.find('.video.double').css({
            'width': '32.5%',
            'height': '332px'
        });
        $container.find('.video img').css({
            'width': '100%',
            'height': '100%'
        });

        // initialize Isotope
        $container.isotope({
            // options...
            itemSelector: '.video',
            resizable: false, // disable normal resizing
            animationOptions: {
                duration: 2750,
                easing: 'linear',
                queue: false
            },
            // set columnWidth to a percentage of container width
            masonry: { columnWidth: $container.width() / 6 }
        });

        // update columnWidth on window resize
        $(window).smartresize(function(){
            $container.isotope({
                // update columnWidth to a percentage of container width
                masonry: { columnWidth: $container.width() / 6 }
            });
        });
    }
    function appendVideos(videoCategory) {
        var i = 0;
        var cnt = 0;
        createIsotope();
        // ajax.genericAction('home_ajaxfilter', {paginate:{'page':1, 'block':'popular','vc': 6}}, function(r){console.log(r);});
        // $.ajax({url: Routing.generate('es_home_ajaxfilter'), data: {'vc': 1}}).then(function(r){console.log(r)})
        var feed = Routing.generate(appLocale + '_home_ajaxfilter');
        $.ajax({url: feed, data: {'vc': videoCategory}}).then(function(response){
            for(i in response.highlighted) {
                if (response.highlighted.hasOwnProperty(i)) {
                    var video = response.highlighted[i];
                    var thumb = document.createElement('article');
                    thumb.classList.add('video');
                    
                    var image = document.createElement('img');
                    //thumb.className('video');
                    $thumb = $('<article class="video"><img src="' + video.image + '" title="' + video.title + '"/></article>');

                    $thumb.css({
                        'width': '16%',
                        'height': '160px',
                        'margin-top': '5px',
                        'margin-bottom': '5px',
                        'border': '1px solid #333',
                        'border-radius': '4px',
                        'overflow': 'hidden'
                    });
                    if(cnt === 1) {
                        $thumb.css({
                            'width': '32.5%',
                            'height': '332px'
                        });
                    }
                    $thumb.find('.video img').css({
                        'width': '100%',
                        'height': '100%'
                    });
                    cnt += 1;
                    $container.append($thumb).isotope('appended', $thumb);
                }
            }
            $container.isotope('reLayout');
            $container.isotope('reloadItems');
            $container.isotope({
                // update columnWidth to a percentage of container width
                masonry: { columnWidth: $container.width() / 6 }
            });
        });
    }

    function appendFollowed(videoCategory) {
        var i = 0;
        var cnt = 0;
        var $container = $('section.followed > .videos-container');
        $container.empty();
        var feed = Routing.generate(appLocale + '_home_ajaxfilter');
        $.ajax({url: feed, data: {'vc': videoCategory}}).then(function(response){
            for(i in response.followed) {
                if (response.followed.hasOwnProperty(i)) {
                    var video = response.followed[i];
                    $thumb = $('<article class="video"><img width="220" src="' + video.image + '" title="' + video.title + '"/></article>');
                    $container.append($thumb);
                }
            }
        });
    }

    function appendPopular(videoCategory) {
        var i = 0;
        var cnt = 0;
        var $container = $('section.popular > .videos-container');
        $container.empty();
        var feed = Routing.generate(appLocale + '_home_ajaxfilter');
        $.ajax({url: feed, data: {'vc': videoCategory}}).then(function(response){
            for(i in response.popular) {
                if (response.popular.hasOwnProperty(i)) {
                    var video = response.popular[i];
                    console.log(video);
                    $thumb = $('<article class="video"><img width="220" src="' + video.image + '" title="' + video.title + '"/></article>');
                    $container.append($thumb);
                }
            }
        });
    }

    function makePackery(videoCategory) {
        var i = 0;
        var cnt = 0;
        var container = document.querySelector('section.highlights');
        var packery = new Packery( container, {
            itemSelector: '.video',
            columnWidth: 15
        });
        // ajax.genericAction('home_ajaxfilter', {paginate:{'page':1, 'block':'popular','vc': 6}}, function(r){console.log(r);});
        // $.ajax({url: Routing.generate('es_home_ajaxfilter'), data: {'vc': 1}}).then(function(r){console.log(r)})
        var feed = Routing.generate(appLocale + '_home_ajaxfilter');
        $.ajax({url: feed, data: {'vc': videoCategory}}).then(function(response){
            for(i in response.highlighted) {
                if (response.highlighted.hasOwnProperty(i)) {
                    var video = response.highlighted[i];


                    var thumb = document.createElement('article');
                    thumb.classList.add('video');

                    var image = document.createElement('img');
                    //thumb.className('video');

                    $thumb = $('<article class="video"><img src="' + video.image + '" title="' + video.title + '"/></article>');

                    $thumb.css({
                        'width': '16%',
                        'height': '160px',
                        'margin-top': '5px',
                        'margin-bottom': '5px',
                        'border': '1px solid #333',
                        'border-radius': '4px',
                        'overflow': 'hidden'
                    });
                    if(cnt === 1) {
                        $thumb.css({
                            'width': '32.5%',
                            'height': '332px'
                        });
                    }
                    $thumb.find('.video img').css({
                        'width': '100%',
                        'height': '100%'
                    });
                    cnt += 1;

                    //container.append($thumb);
                    $('section.highlights').append($thumb);
                    packery.appended($thumb);
                }
            }
        });
    }

    console.log("home javascript")
    //appendVideos(8);
    makePackery(8);
    appendFollowed(8);
    appendPopular(8);
});