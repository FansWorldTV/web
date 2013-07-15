/*global
 $,
 jQuery,
 error,
 success,
 endless,
 ajax,
 templateHelper,
 EventEmitter,
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
    "use strict";
    window.fansWorldEvents = window.fansWorldEvents || new EventEmitter();
});

$(document).ready(function () {
    "use strict";
    var pluginName = "fwHomeGallery";
    var defaults = {
        videoCategory: null,
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
        videoGenre: null,
        type: null,
        id: null,
        videoFeed: Routing.generate(appLocale + '_home_ajaxfilter'),
        selector: 'section.highlights',
        itemSelector: '.video',
        packery: null,
        container: null,
        queue: null,
        queueDelay: 100,
        onVideoCategoryEvent: null
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
            that.hide();

            that.options.container = document.querySelector(that.options.selector);

            that.options.onFilterChange = function (type, id){
                id = parseInt(id, 10);
                var reqData = {};
                if(!isNaN(id)) {
                    that.options.type = type;
                    that.options.id = id;
                    reqData[that.options.type] = that.options.id;
                } else {
                    that.options.type = "";
                    that.options.id = "";
                }
                $.when(that.removeAll()).then(function(){
                    that.hide();
                    $.when(that.makePackery(reqData)).then(function(){
                    }).progress(function() {
                        //console.log("adding thumbnails to packery");
                    }).fail(function(error){
                        that.hide();
                    });
                }).fail(function(error){
                    var reqData = {};
                    $.when(that.makePackery(reqData)).then(function(){
                    }).progress(function() {
                        //console.log("adding thumbnails to packery");
                    }).fail(function(error){
                        that.hide();
                    });
                });
            };
            window.fansWorldEvents.addListener('onFilterChange', that.options.onFilterChange);
            that.options.packery = new Packery(that.options.container, {
                itemSelector: '.video',
                gutter: ".gutter-sizer",
                columnWidth: ".grid-sizer"
            });
            var reqData = {};
            reqData[that.options.type] = parseInt(that.options.id, 10);
            that.makePackery(reqData);

            return true;
        },
        makePackery: function(data) {
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
                that.show();
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
            queue.pause();
            $.ajax({
                url: that.options.videoFeed,
                data: data || {
                    'vc': that.options.videoCategory
                }
            }).then(function(response) {
                var i = 0;
                var loadedImages = 0;
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
                            $thumb.find('img').load(function() {                                
                                if(cnt >= totalVideos) { 
                                    queue.start();
                                }
                            })
                            .error(function(error){
                                console.log("error al cargar imagen")
                                queue.start();
                            })
                            /*.onabort(function(){
                                queue.start();
                            })*/
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
        hide: function() {
            var that = this;
            $(that.element).fadeOut(function() {
                $(that.element).parent().find('.spinner').removeClass('hidden');
                $(that.element).parent().find('.spinner').show();
            });
        },
        show: function() {
            var that = this;
            $(that.element).removeClass('hidden');
            $(that.element).fadeIn(function() {
                $(that.element).parent().find('.spinner').addClass('hidden');
                $(that.element).parent().find('.spinner').hide();
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
            window.fansWorldEvents.removeListener('onFilterChange', that.options.onFilterChange);
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
        videoGenre: null,
        type: null,
        id: null,
        videoFeed: Routing.generate(appLocale + '_home_ajaxfilter'),
        page: 1,
        block: null,
        newEvent: null,
        getFilter: function() {}
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
            that.options.getFilter = function() {
                var filter = {
                    paginate: {
                        page: that.options.page,
                        block: that.options.block
                    }
                };
                filter.paginate[that.options.type] = parseInt(that.options.id, 10);
                return filter;
            };
            that.insetThumbs(Routing.generate(appLocale + '_home_ajaxfilter'), that.options.getFilter());

            that.options.onFindVideosByTag = function(tag, filter){
                if(filter === that.options.block) {
                    var url = Routing.generate(appLocale + "_video_ajaxsearchbytag");
                    that.options.videoFeed = Routing.generate(appLocale + "_video_ajaxsearchbytag");
                    that.options.getFilter = function() {
                        return {
                            id: tag.id,
                            entity: tag.type,
                            page: that.options.page
                        };
                    };
                    that.clearThumbs();
                    that.insetThumbs(that.options.videoFeed, that.options.getFilter());
                }
            };
            that.options.onFilterChange = function(type, id) {
                id = parseInt(id, 10);
                that.options.type = type;
                that.options.id = id;
                that.options.page = 1;
                that.options.videoFeed = Routing.generate(appLocale + '_home_ajaxfilter');
                that.options.getFilter = function() {
                    var filter = {
                        paginate: {
                            page: that.options.page,
                            block: that.options.block
                        }
                    };
                    filter.paginate[type] = parseInt(id, 10);
                    return filter;
                };
                that.clearThumbs();
                that.insetThumbs(that.options.videoFeed, that.options.getFilter());
            };

            window.fansWorldEvents.addListener('onFindVideosByTag', that.options.onFindVideosByTag);
            window.fansWorldEvents.addListener('onFilterChange', that.options.onFilterChange);

            $('section.' + that.options.block + ' > .add-more').on('click', function(event) {
                that.addMoreThumbs(event);
            });
            return true;
        },
        clearThumbs: function() {
            var that = this;
            $(that.element).parent().fadeOut(function() {
                $(that.element).empty();
                $(that.element).parent().find('.spinner').removeClass('hidden');
                $(that.element).parent().find('.add-more').hide();
                $(that.element).parent().find('.spinner').show();
            });
        },
        addMoreThumbs: function(event) {
            var that = this;
            var button = $(event.srcElement);
            that.options.page += 1;
            button.addClass('rotate');

            $.when(that.insetThumbs(that.options.videoFeed, that.options.getFilter())).then(function(response){
                button.removeClass('rotate');
            });
        },
        insetThumbs: function(feed, data) {
            var that = this;
            var deferred = new jQuery.Deferred();
            $.ajax({
                url: feed,
                data: data
            }).then(function(response) {
                var i = 0;
                if(response.videos.length < 1) {
                    $(that.element).parent().fadeOut('slow');
                }
                for(i in response.videos) {
                    if (response.videos.hasOwnProperty(i)) {
                        var addMore = response.addMore;
                        var video = response.videos[i];
                        $.when(templateHelper.htmlTemplate('video-home_element', video))
                        .then(function(response){
                            var $thumb = $(response).clone();
                            $thumb.find('img').load(function() {
                                $(that.element).parent().find('.spinner').addClass('hidden');
                                $(that.element).parent().find('.spinner').hide();
                                $(that.element).parent().removeClass('hidden');
                                $(that.element).parent().fadeIn('slow');
                                if(addMore) {
                                    $(that.element).parent().find('.add-more').show();
                                } else {
                                    $(that.element).parent().find('.add-more').hide();
                                }
                                $thumb.hide().appendTo(that.element).fadeIn('slow');
                            });
                        });
                    }
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
            window.fansWorldEvents.removeListener('onFindVideosByTag', that.options.onFindVideosByTag);
            window.fansWorldEvents.removeListener('onFilterChange', that.options.onFilterChange);
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
            var reqData = {
                filter: that.options.filter,
                page: that.options.page
            };
            reqData[that.options.type] = parseInt(that.options.id, 10);
            that.makeTags(reqData);

            that.options.onFilterChange = function(type, id) {
                id = parseInt(id, 10);
                that.options.type = type;
                that.options.id = id;
                var reqData = {
                    filter: that.options.filter,
                    page: that.options.page
                };
                reqData[that.options.type] = that.options.id;
                that.makeTags(reqData);
            };
            window.fansWorldEvents.addListener('onFilterChange', that.options.onFilterChange);
            return true;
        },
        makeTags: function(data) {
            var that = this;
            var queue = $.jqmq({
                // Queue items will be processed every queueDelay milliseconds.
                delay: 125,
                // Process queue items one-at-a-time.
                batch: 1,
                // For each queue item, execute this function.
                callback: function( videoTag ) {
                    var fragment = document.createDocumentFragment();
                    var tag = document.createElement('li');
                    tag.innerText = videoTag.title;
                    tag.setAttribute('id', videoTag.id);
                    tag.setAttribute('data-list-filter-type', videoTag.type);
                    tag.setAttribute('data-id', videoTag.id);
                    $(tag).on('click', function(event){
                        if($(this).hasClass('active')) {
                            return;
                        }
                        $(this).parent().find('.active').removeClass('active');
                        $(this).addClass('active');
                        window.fansWorldEvents.emitEvent('onFindVideosByTag', [videoTag, that.options.filter]);
                    });
                    $(tag).hide().appendTo(that.element).fadeIn('slow');
                },
                // When the queue completes naturally, execute this function.
                complete: function(){
                }
            });
            $.ajax({
                url: that.options.tagSource,
                data: data
            }).then(function(response){
                var i = 0;
                var tags = response.tags;
                $(that.element).empty();
                for(i in tags){
                    if (tags.hasOwnProperty(i)) {
                        queue.add(tags[i]);
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
// Show count plugins                                                        //
///////////////////////////////////////////////////////////////////////////////
$(document).ready(function () {
    "use strict";
    var pluginName = "fwShowCount";
    var defaults = {
        filter: null,
        type: null,
        id: null,
        feed: Routing.generate(appLocale + '_home_ajaxfilter')
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

            that.options.onFilterChange = function(type, id) {
                that.options.type = type;
                that.options.id = id;
                var reqData = {};
                reqData[that.options.type] = parseInt(that.options.id, 10);
                that.getTotal(that.options.feed, reqData);
                return;
            };
            window.fansWorldEvents.addListener('onFilterChange', that.options.onFilterChange);
            var reqData = {};
            reqData[that.options.type] = parseInt(that.options.id, 10);
            that.getTotal(that.options.feed, reqData);
            return true;
        },
        getTotal: function(feed, data) {
            var that  = this;
            $.ajax({
                url: feed,
                data: data
            }).then(function(response) {
                var total = response.totals[that.options.filter];
                $(that.element).fadeOut(function() {
                    $(this).text(total);
                }).fadeIn();
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
    var type = $(".filter-home").find('.active').attr('data-entity-type');
    var id = parseInt($(".filter-home").find('.active').attr('data-entity-id'), 10);

    var videoCategory = 0;
    var videoGenre = 0;

    // explore hashtag
    if(location.hash !== ''){
        var hash = location.hash;
        hash = window.location.hash.slice(1).toLowerCase().split('_');
        console.log(hash)
        /*
        setTimeout(function(){
            $('[data-entity-type="'+ hash[0] +'"][data-entity-id="'+ hash[1] +'"]').click();
        }, 2500)
        */
        //return;
        type = hash[0];
        id = hash[1];
        $(".filter-home").find('.active').removeClass('active');
        $('[data-entity-type="'+ hash[0] +'"][data-entity-id="'+ hash[1] +'"]').addClass('active');
    }
    // Video Packery Gallery
    $('section.highlights').fwHomePackery({
        videoCategory: videoCategory,
        videoGenre: videoGenre,
        type: type,
        id: id
    });
    // Video Grid
    $('section.popular > .videos-container').fwHomeThumbs({
        videoCategory: videoCategory,
        videoGenre: videoGenre,
        type: type,
        id: id,
        block: 'popular'
    });
    $('section.followed > .videos-container').fwHomeThumbs({
        videoCategory: videoCategory,
        videoGenre: videoGenre,
        type: type,
        id: id,
        block: 'followed'
    });
    // Video Tags
    $('section.popular-tags > ul').fwHomeTags({
        videoCategory: videoCategory,
        videoGenre: videoGenre,
        type: type,
        id: id,
        filter: 'popular'
    });
    $('section.followed-tags > ul').fwHomeTags({
        videoCategory: videoCategory,
        videoGenre: videoGenre,
        type: type,
        id: id,
        filter: 'followed'
    });

    // Video Counters
    $('[data-total-followed]').fwShowCount({
        type: type,
        id: id,
        filter: 'followed'
    });

    $('[data-total-popular]').fwShowCount({
        type: type,
        id: id,
        filter: 'popular'
    });
});

$(document).ready(function () {
    $(".filter-home > li").on('click', function(){
        if($(this).hasClass('active')) {
            return;
        }
        $(this).parent().find('.active').removeClass('active');
        $(this).addClass('active');
        var type = $(this).attr('data-entity-type');
        var id = parseInt($(this).attr('data-entity-id'), 10);

        ///////////////////////////////////////////////////////////////////////
        // Decoupled EventTrigger                                            //
        ///////////////////////////////////////////////////////////////////////
        window.fansWorldEvents.emitEvent('onFilterChange', [type, id]);
    });

});
