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

// WARNING GLOBAL VARIABLE
// EventEmitter is taken from packery but can be download from https://github.com/Wolfy87/EventEmitter
$(document).ready(function () {
    window.fansWorldEvents = new EventEmitter();
});

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

///////////////////////////////////////////////////////////////////////////////
// Plugin wrapper para galerias packery                                      //
///////////////////////////////////////////////////////////////////////////////
$(document).ready(function () {
    "use strict";
    var pluginName = "fwHomePackery";
    var defaults = {
        videoCategory: null,
        videoFeed: Routing.generate(appLocale + '_home_ajaxfilter'),
        selector: 'section.highlights',
        itemSelector: '.video',
        packery: null,
        container: null,
        queue: null,
        queueDelay: 100,
        newVideoCategoryEvent: null
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
            that.options.container = document.querySelector(that.options.selector);

            that.options.newVideoCategoryEvent = (function nvc(videoCategory){
                var vc = parseInt(videoCategory, 10);
                if($.isNumeric(vc)) {
                    that.options.videoCategory = vc;
                    fansWorldEvents.removeListener('newVideoCategory', that.options.newVideoCategoryEvent);
                    $.when(that.removeAll()).then(function(){
                        $.when(that.makePackery()).then(function(){
                            fansWorldEvents.addListener('newVideoCategory', that.options.newVideoCategoryEvent);
                        }).progress(function() {
                            //console.log("adding thumbnails to packery");
                        }).fail(function(error){
                            alert(error.message);
                            fansWorldEvents.addListener('newVideoCategory', that.options.newVideoCategoryEvent);
                        });
                    }).fail(function(error){
                        $.when(that.makePackery()).then(function(){
                            fansWorldEvents.addListener('newVideoCategory', that.options.newVideoCategoryEvent);
                        }).progress(function() {
                            //console.log("adding thumbnails to packery");
                        }).fail(function(error){
                            alert(error.message);
                            fansWorldEvents.addListener('newVideoCategory', that.options.newVideoCategoryEvent);
                        });
                    });;
                }
                return nvc;
            })(this);

            fansWorldEvents.addListener('newVideoCategory', that.options.newVideoCategoryEvent);
            that.options.packery = new Packery(that.options.container, {
                itemSelector: '.video',
                gutter: ".gutter-sizer",
                columnWidth: ".grid-sizer"
            });
            that.makePackery();
            return true;
        },
        makePackery: function() {
            var that = this;
            var i = 0;
            var cnt = 0;
            var totalVideos = 0;
            var queue = null;
            var deferred = new jQuery.Deferred();
            var itemElements = that.options.packery.getItemElements();
            var onAdd = function(pckryInstance, laidOutItems) {
                var items = pckryInstance.getItemElements();
                deferred.notify(laidOutItems);
                if(0 === queue.size() && totalVideos === laidOutItems.length) {
                    deferred.resolve();
                    pckryInstance.off('layoutComplete', onAdd);
                }
            };
            that.options.packery.on('layoutComplete', onAdd);
            queue = $.jqmq({
                // Queue items will be processed every queueDelay milliseconds.
                delay: that.options.queueDelay,
                // Process queue items one-at-a-time.
                batch: 1,
                // For each queue item, execute this function.
                callback: function( thumb ) {
                    $(that.options.selector).append(thumb);
                    that.options.packery.appended(thumb);
                    that.options.packery.layout();
                },
                // When the queue completes naturally, execute this function.
                complete: function(){
                }
            });
            $.ajax({
                url: that.options.videoFeed,
                data: {
                    'vc': that.options.videoCategory
                }
            }).then(function(response) {
                var i = 0;
                totalVideos = response.highlighted.length;
                if(totalVideos <= 0) {
                    deferred.reject(new Error("Video category does not contain any video"));
                }
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
                            queue.add($thumb);
                        });
                    }
                }
            });
            return deferred.promise();
        },
        removeAll: function() {
            var that = this;
            var deferred = new jQuery.Deferred();
            var itemElements = that.options.packery.getItemElements();
            var onRemove = function(pckryInstance, removedItems) {
                var items = pckryInstance.getItemElements();
                deferred.notify(removedItems);
                if(items.length <= 0) {
                    pckryInstance.off('removeComplete', onRemove);
                    deferred.resolve();
                }
            };
            that.options.packery.on('removeComplete', onRemove);
            var queue = $.jqmq({
                // Queue items will be processed every queueDelay milliseconds.
                delay: that.options.queueDelay,
                // Process queue items one-at-a-time.
                batch: 1,
                // For each queue item, execute this function.
                callback: function( thumb ) {
                    that.options.packery.remove(thumb);
                },
                // When the queue completes naturally, execute this function.
                complete: function(){

                }
            });
            var videos = $(that.options.selector).find('.video');
            if(videos.length > 0) {
                videos.each(function(elem){
                    queue.add($(this));
                });
            } else {
                deferred.reject(new Error("Video container is empty !"));
            }
            return deferred.promise();
        },
        destroy: function() {
            var that = this;
            $(that.element).unbind("destroyed", that.teardown);
            that.teardown();
            return true;
        },
        teardown: function() {
            var that = this;
            fansWorldEvents.removeListener('newVideoCategory', that.options.newVideoCategoryEvent);
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

///////////////////////////////////////////////////////////////////////////////
// Plugin wrapper para galerias semantic grid                                //
///////////////////////////////////////////////////////////////////////////////
$(document).ready(function () {
    "use strict";
    var pluginName = "fwHomeThumbs";
    var defaults = {
        videoCategory: null,
        videoFeed: Routing.generate(appLocale + '_home_ajaxfilter'),
        page: 1,
        block: null,
        newEvent: null
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
            that.clearThumbs();
            that.appendThumbs();

            that.options.newVideoCategory = (function nvc(videoCategory){
                var vc = parseInt(videoCategory, 10);
                if($.isNumeric(vc)) {
                    that.options.videoCategory = vc;
                }
                that.clearThumbs();
                that.appendThumbs();
                return nvc;
            })(this);

            fansWorldEvents.addListener('newVideoCategory', that.options.newVideoCategory);

            $('section.' + that.options.block + ' > .add-more').on('click', function(event) {
                that.addMoreThumbs(event);
            });
            return true;
        },
        clearThumbs: function() {
            var that = this;
            //$(that.element).parent().hide('slow');
            $(that.element).parent().slideUp('slow', function() {})
            $(that.element).empty();
        },
        addMoreThumbs: function(event) {
            var that = this;
            var button = $(event.srcElement);
            that.options.page += 1;
            button.addClass('rotate');
            $.when(that.appendThumbs()).then(function(response){
                button.removeClass('rotate');
            });
        },
        appendThumbs: function() {
            var that = this;
            var i = 0;
            var deferred = new jQuery.Deferred();
            $.ajax({
                url: that.options.videoFeed,
                data: {
                    paginate:{
                        page: that.options.page,
                        block: that.options.block,
                        vc: that.options.videoCategory
                    }
                }
            }).then(function(response) {
                    for(i in response.videos) {
                        if (response.videos.hasOwnProperty(i)) {
                            var video = response.videos[i];
                            $.when(templateHelper.htmlTemplate('video-home_element', video))
                                .then(function(response){
                                    var $thumb = $(response).clone();
                                    $thumb.hide().appendTo(that.element).fadeIn('slow');
                                });
                        }
                    }
                    if(response.videos.length > 0 ) {
                        $(that.element).parent().slideDown('slow', function() {})
                    }
                    return response.videos;
                }).done(function(videos){
                    deferred.resolve(videos);
                }).fail(function(error){
                    deferred.reject(new Error(error));
                });
            return deferred.promise();
        },
        destroy: function() {
            var that = this;
            $(that.element).unbind("destroyed", that.teardown);
            that.teardown();
            return true;
        },
        teardown: function() {
            var that = this;
            fansWorldEvents.removeListener('newVideoCategory', that.options.newVideoCategory);
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

///////////////////////////////////////////////////////////////////////////////
// Plugin generador de tags                                                  //
///////////////////////////////////////////////////////////////////////////////
$(document).ready(function () {
    "use strict";
    var pluginName = "fwHomeTags";
    var defaults = {
        tagSource: Routing.generate(appLocale + '_tag_ajaxgetusedinvideos'),
        channel: null,
        filter: null,
        maxTags: 4,
        page: 1

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
            that.makeTags();
            return true;
        },
        makeTags: function() {
            var that = this;
            $.ajax({
                url: that.options.tagSource,
                data: {
                    channel: that.options.channel,
                    filter: that.options.filter,
                    page: that.options.page
                }
            }).then(function(response){
                    var i = 0;
                    var tags = response.tags;
                    $(that.element).empty();
                    for(i in tags){
                        if (tags.hasOwnProperty(i)) {
                            $(that.element).append("<li>"+tags[i].title+"</li>");
                            if(i >= that.options.maxTags) {
                                break;
                            }
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

///////////////////////////////////////////////////////////////////////////////
// Attach plugin to all matching element                                     //
///////////////////////////////////////////////////////////////////////////////
$(document).ready(function () {
    "use strict";

    // Packery
    $('section.highlights').fwHomePackery({
        videoCategory: $('.filter-home').find('.active').attr('data-category-id'),
    })
    // Semantic
    $('section.popular > .videos-container').fwHomeThumbs({
        videoCategory: $('.filter-home').find('.active').attr('data-category-id'),
        block: 'popular'
    });
    $('section.followed > .videos-container').fwHomeThumbs({
        videoCategory: $('.filter-home').find('.active').attr('data-category-id'),
        block: 'followed'
    });
    // Tags
    $('section.popular-tags > ul').fwHomeTags({
        channel: $('.filter-home').find('.active').attr('data-category-id'),
        filter: 'popular'
    });
    $('section.followed-tags > ul').fwHomeTags({
        channel: $('.filter-home').find('.active').attr('data-category-id'),
        filter: 'follow'
    });
});

$(document).ready(function () {


    $('.filter-home > li').on('click', function(){
        $(this).parent().find('.active').removeClass('active');
        //$(this).toggleClass('active', 125);
        $(this).addClass('active');
        var videoCategory = $(this).attr('data-category-id');

        // Get a plugin handler
        //var popularThumbs = $('section.popular > .videos-container').data('fwHomeThumbs');
        //var followedThumbs = $('section.followed > .videos-container').data('fwHomeThumbs');
        //var highlightsThumbs = $('section.highlights').data('fwHomePackery');

        // Clear semantic grid thumbs
        //popularThumbs.clearThumbs();
        //followedThumbs.clearThumbs();

        // Set the internal variable TODO: refactor ! (DONE)
        //highlightsThumbs.options.videoCategory = popularThumbs.options.videoCategory = followedThumbs.options.videoCategory = videoCategory;

        //popularThumbs.appendThumbs();
        //followedThumbs.appendThumbs();

        //highlightsThumbs.removeAll();
        //highlightsThumbs.makePackery();
        ///////////////////////////////////////////////////////////////////////
        // Decoupled EventTrigger                                            //
        ///////////////////////////////////////////////////////////////////////
        window.fansWorldEvents.emitEvent('newVideoCategory', [videoCategory]);
    });
});