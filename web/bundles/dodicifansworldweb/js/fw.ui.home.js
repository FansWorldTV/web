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
        videoCategory: null,
        videoFeed: Routing.generate(appLocale + '_home_ajaxfilter'),
        imteStyle: {
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

            return true;
        },
        makePackery: function(videoCategory) {
            var that = this;
            var i = 0;
            var cnt = 0;
            var container = document.querySelector('section.highlights');
            var packery = new Packery(container, {
                itemSelector: '.video',
                gutter: ".gutter-sizer",
                columnWidth: ".grid-sizer"
            });

            var feed = Routing.generate(appLocale + '_home_ajaxfilter');

            $.ajax({url: that.options.videoFeed, data: {'vc': videoCategory}}).then(function(response){
                for(i in response.highlighted) {
                    if (response.highlighted.hasOwnProperty(i)) {
                        var video = response.highlighted[i];
                        $.when(templateHelper.htmlTemplate('video-home_element', video))
                        .then(function(response){
                            var $thumb = $(response).clone();
                            $thumb.addClass('video');
                            if(cnt === 1) {
                                $thumb.addClass('double');
                            }
                            cnt += 1;

                            $('section.highlights').append($thumb);
                            packery.appended($thumb);
                            packery.layout();
                        });
                    }
                }
            });
        },
        destroy: function() {
            var that = this;
            $(that.element).unbind("destroyed", that.teardown);
            that.teardown();
            return true;
        },
        teardown: function() {
            var that = this;
            $.removeData($(that.element)[0], that._name);
            $(that.element).removeClass(that._name);
            that.unbind();
            that.element = null;
            return that.element;
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
                    $.when(templateHelper.htmlTemplate('video-home_element', video))
                        .then(function(response){
                            $thumb = $(response).clone();
                            $container.append($thumb);
                        });
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
                    //console.log(video);
                    $thumb = $('<article class="video"><img width="220" src="' + video.image + '" title="' + video.title + '"/></article>');
                    $.when(templateHelper.htmlTemplate('video-home_element', video))
                        .then(function(response){
                            $thumb = $(response).clone();
                            $container.append($thumb);;
                    });
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
//            gutter: '25px',
            gutter: ".gutter-sizer",
            columnWidth: ".grid-sizer"
//            columnWidth: container.querySelector('.grid-sizer')
        });

        window.packery = packery;
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

                    $thumb = $('<article class="video"><img src="' + video.image + '" title="' + video.title + '"/></article>');

                    $.when(templateHelper.htmlTemplate('video-home_element', video))
                    .then(function(response){
                        $thumb = $(response).clone();

                        $thumb.addClass('video');
                        if(cnt === 1) {
                            $thumb.addClass('double');
                        }

                        $thumb.find('img').css({
                            'width': '100%',
                            'height': '100%'
                        });
                        cnt += 1;

                        $('section.highlights').append($thumb);
                        packery.appended($thumb);
                        packery.layout();
                    });
                }
            }
        });
    }

    var videoCategory = $('.filter-home').find('.active').attr('data-category-id');
    makePackery(8);
    appendFollowed(videoCategory);
    appendPopular(videoCategory);
});