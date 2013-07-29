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
        queueDelay: 0,
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

            that.options.onFilterChange = function (type, id, vc){
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
                vc = parseInt(vc, 10);
                if(!isNaN(vc)) {
                    that.options.vc = vc;
                    reqData.vc = that.options.vc;                
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

            var il = new ImagesLoaded(that.options.container);       
            il.done(function () {
                that.show();
                setTimeout(function() {
                    that.options.packery = new Packery(that.options.container, {
                        itemSelector: '.video',
                        gutter: ".gutter-sizer",
                        columnWidth: ".grid-sizer",
                        transitionDuration: '0.1s'
                    });
                }, 500
                );
                setTimeout(function() { that.options.packery.layout(); }, 500)
            });
            il.progress(function (image, isBroken) {
                console.log("image loaded !")
            });
            return;
            // Below load images disabled (home is now preloaded with data)
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
            // Disable - Enable preload 
            /*
            that.clearThumbs();
            that.insetThumbs(Routing.generate(appLocale + '_home_ajaxfilter'), that.options.getFilter());
            */

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
            that.options.onFilterChange = function(type, id, vc) {
                id = parseInt(id, 10);
                vc = parseInt(vc, 10);
                that.options.videoFeed = Routing.generate(appLocale + '_home_ajaxfilter');
                that.options.getFilter = function() {
                    var filter = {
                        paginate: {
                            page: that.options.page,
                            block: that.options.block
                        }
                    };
                    if(!isNaN(id)) {
                        that.options.type = type;
                        that.options.id = id;
                        that.options.page = 1;
                        filter.paginate[that.options.type] = that.options.id;
                    }
                    if(!isNaN(vc)) {
                        that.options.vc = vc;
                        filter.paginate.vc = that.options.vc;
                    }
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
                // If no more videos then hide the addMore button
                if(response.videos.length < 1) {
                    $(that.element).parent().find('.add-more').hide();
                }
                for(i in response.videos) {
                    if (response.videos.hasOwnProperty(i)) {
                        var addMore = response.addMore;
                        var video = response.videos[i];
                        $.when(templateHelper.htmlTemplate('video-home_element', video))
                        .then(function(response){
                            var $thumb = $(response).clone();
                            $thumb.hide().appendTo(that.element).fadeIn('slow');
                            /*
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
                            */
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

    $('[data-menu-edit="true"]').on('click', function(event){
        if($(this).hasClass('active')) {
            $('.category-menu').hide();
            $(this).removeClass('active');
            return;
        }
        $(this).addClass('active');
        $('.category-menu').show();
    });
});

$(document).ready(function () {
    /*
    $('body').on('click', '.subfilter-home', function(event){
        if($(event.target).hasClass('active')) {
            return;
        }
        $(event.target).parent().find('.active').removeClass('active');
        $(event.target).addClass('active');
        var type = $(event.target).attr('data-entity-type');
        var id = parseInt($(event.target).attr('data-entity-id'), 10);
        var vc = $(event.target).attr('data-video-category');
        ///////////////////////////////////////////////////////////////////////
        // Decoupled EventTrigger                                            //
        ///////////////////////////////////////////////////////////////////////
        window.fansWorldEvents.emitEvent('onFilterChange', [type, id, vc]);
    });
    */
    /*
    $(".filter-home > li:not('[data-override]')").on('click', function(){
        if($(this).hasClass('active')) {
            return;
        }
        $(this).parent().find('.active').removeClass('active');
        $(this).addClass('active');
        var type = $(this).attr('data-entity-type');
        var id = parseInt($(this).attr('data-entity-id'), 10);
        var vc = $(this).attr('data-video-category');

        $('.category-menu').find('ul').each(function(){
            if((parseInt($(this).attr('data-parent-entity-id'), 10) == id) && $(this).attr('data-entity-type') === type){
                $(this).show();
                $(this).removeClass('hidden');
                $(this).find('li').each(function(){
                    $(this).removeClass('active');
                });
            } else {
                $(this).hide();
                $(this).addClass('hidden');
            }
        });
        $('.category-menu').show();

        ///////////////////////////////////////////////////////////////////////
        // Decoupled EventTrigger                                            //
        ///////////////////////////////////////////////////////////////////////
        window.fansWorldEvents.emitEvent('onFilterChange', [type, id]);
    });
    */
});

///////////////////////////////////////////////////////////////////////////////
// Hero Editable Menu                                                        //
///////////////////////////////////////////////////////////////////////////////
$(document).ready(function () {

    var heroMenu = '.filter-home';
    var heroEdit = '.hero-menu-editor';
    ///////////////////////////////////////////////////////////////////////////////
    // Add a remove button to all menu elements                                  //
    ///////////////////////////////////////////////////////////////////////////////
    $('.filter-home').find('li').each(function(){
        //$(this).append("<i class='remove icon-remove-sign'></i>");
    });
    ///////////////////////////////////////////////////////////////////////////////
    // Preselect all matching hero menu elements in filter editor                //
    ///////////////////////////////////////////////////////////////////////////////    
    $('.filter-home').find('li').each(function(){
        //$(this).append("<i class='remove icon-remove-sign'></i>");
    });    
    ///////////////////////////////////////////////////////////////////////////////
    // Bind remove event                                                         //
    ///////////////////////////////////////////////////////////////////////////////
    $('body').on('click', heroMenu +" .remove", function(event){
        // Disable Bubbling
        event.preventDefault();
        // Get real target
        var self = $($(this).parent()[0]);
        // Get source attributes
        var type = self.attr('data-entity-type');
        var id = parseInt(self.attr('data-entity-id'), 10);
        // Get surce screen offset
        var orgOffset = self.offset();
        // Clone surce element
        var elem = self.clone();
        // Make a temporal div which will use to provide visual feedback
        var tempItem = $("<div style='position: absolute; opacity: 0.5' class='tempItem'>" + elem.text() + "</div>");
        // Translate object to surce screen coordinates
        tempItem.offset(orgOffset);
        // Make surce invisible
        self.css('opacity', 0.25);
        // Append visual helper
        $('body').append(tempItem)
        // Calculate destination offset
        var destOffset = $(heroEdit + '.editing li:last').offset();
        // If surce exists on target then fly there else append as new
        if(!isNaN(id) && $(heroEdit + ' [data-entity-type="' + type + '"][data-entity-id="' + id + '"]').length > 0) {
            // Matched target
            destOffset = $(heroEdit + ' [data-entity-type="' + type + '"][data-entity-id="' + id + '"]').offset();
        } else {
            // Append new target
            $(heroMenu + '.editing').append("<li style='opacity: 0'><i class='option unchecked'></i>" + elem.text() + "</li>");
            // Calculate offset
            destOffset = $(heroMenu + '.editing li:last').offset();
        }
        // Translate the helper        
        $(tempItem).css('-webkit-transform', 'translate('+ (destOffset.left - orgOffset.left) +'px, '+ (destOffset.top - orgOffset.top) +'px)');
        // Wait till animation stops
        $(tempItem).one('webkitTransitionEnd otransitionend oTransitionEnd msTransitionEnd transitionend', function(event){
            if(!isNaN(id) && $('#sortable2 [data-entity-type="' + type + '"][data-entity-id="' + id + '"]').length > 0) {
                $(heroEdit + ' [data-entity-type="' + type + '"][data-entity-id="' + id + '"]').removeClass('selected').find('i').removeClass().addClass("option unchecked");
                $(heroEdit + ' [data-entity-type="' + type + '"][data-entity-id="' + id + '"]').draggable( "option", "disabled", false );
            } else {
                $(heroMenu + '.editing li:last').css('opacity', 1);
            }
            tempItem.remove();
            $(self).hide(250, function () {
                $(this).remove();
            });                
        })
    });
    ///////////////////////////////////////////////////////////////////////////////
    // Add Element                                                               //
    ///////////////////////////////////////////////////////////////////////////////
    $('body').on('click', ".filter-container.editing ul" + heroEdit + ":not('.inUse') li:not('.selected')", function(event) {
        var self = $(this);
        var type = self.attr('data-entity-type');
        var id = parseInt(self.attr('data-entity-id'), 10);            
        var orgOffset = {};
        var destOffset = {};

        event.preventDefault();
        self.addClass("selected");
        self.parent().addClass('inUse');

        var div = document.createElement('div');
        div.textContent = self.text();
        div.classList.add("tempItem");
        var tempItem = $(div);

        $(heroMenu).append("<li style='opacity: 0.25'>" + $(this).text() + "</li>");
        $(heroMenu + ' li:last').attr('data-entity-type', type);
        $(heroMenu + ' li:last').attr('data-entity-id', id);
        var orgOffset = $(this).offset();
        destOffset = $(heroMenu + ' li:last').offset();
        tempItem.offset(orgOffset);
        $('body').append(tempItem);
        var xPos = orgOffset.left > destOffset.left ? -(orgOffset.left - destOffset.left) : (destOffset.left - orgOffset.left);
        var yPos = orgOffset.top > destOffset.top ? -(orgOffset.top - destOffset.top) : (destOffset.top - orgOffset.top);

        // Disable item & change icon
        self.draggable( "option", "disabled", true );
        self.find('i').removeClass('unchecked').addClass("checked");
        // Bug in transitions
        setTimeout(function() {
            tempItem.css('transform', 'translate(' + xPos + 'px, ' + yPos + 'px)');
        }, 15);

        $(tempItem).one('webkitTransitionEnd otransitionend oTransitionEnd msTransitionEnd transitionend', function(event){
            $(heroMenu + ' li:last').css('opacity', 1).append("<i class='remove icon-remove-sign'></i>");
            tempItem.remove();                
            self.parent().removeClass('inUse');                
        });
    });
    ///////////////////////////////////////////////////////////////////////////////
    // Remove element                                                            //
    ///////////////////////////////////////////////////////////////////////////////
    $('body').on('click', ".filter-container.editing ul" + heroEdit + ":not('.inUse') li.selected", function(event) {
        // Get the real target
        var self = $($(this).parent()[0]);
        var self = $(this);
        // Get source attributes
        var type = self.attr('data-entity-type');
        var id = parseInt(self.attr('data-entity-id'), 10);
        // Prevent event bubbling
        event.preventDefault();
        // Get surce screen offset
        var destOffset = self.offset();
        // Calculate destination offset
        var orgOffset = $(heroMenu + ' [data-entity-type="' + type + '"][data-entity-id="' + id + '"]').offset();            
        // Clone surce element
        var elem = self.clone();
        // Make a temporal div which will use to provide visual feedback
        var tempItem = $("<div style='position: absolute; opacity: 0.5' class='tempItem'>" + elem.text() + "</div>");
        // Translate object to surce screen coordinates
        tempItem.offset(orgOffset);            
        // Make surce invisible
        $(heroMenu + ' [data-entity-type="' + type + '"][data-entity-id="' + id + '"]').css('opacity', 0.25);
        // Append visual helper
        $('body').append(tempItem);
        // Translate the helper        
        var xPos = orgOffset.left > destOffset.left ? -(orgOffset.left - destOffset.left) : (destOffset.left - orgOffset.left);
        var yPos = orgOffset.top > destOffset.top ? -(orgOffset.top - destOffset.top) : (destOffset.top - orgOffset.top);

        // Bug in transitions
        setTimeout(function() {
            tempItem.css('transform', 'translate(' + xPos + 'px, ' + yPos + 'px)');
        }, 15)
        // Wait till animation stops
        $(tempItem).one('webkitTransitionEnd otransitionend oTransitionEnd msTransitionEnd transitionend', function(event){
            if(!isNaN(id) && $(heroEdit + ' [data-entity-type="' + type + '"][data-entity-id="' + id + '"]').length > 0) {
                $(heroMenu + ' [data-entity-type="' + type + '"][data-entity-id="' + id + '"]').removeClass('selected').find('i').removeClass().addClass("option unchecked");
                $(heroMenu + ' [data-entity-type="' + type + '"][data-entity-id="' + id + '"]').draggable( "option", "disabled", false );
            }
            $(heroMenu +' [data-entity-type="' + type + '"][data-entity-id="' + id + '"]').hide(250, function () {
                $(this).remove();
            });
            tempItem.remove();
        })
    });
    ///////////////////////////////////////////////////////////////////////////////
    //
    ///////////////////////////////////////////////////////////////////////////////
    $(heroMenu + " li").each(function(index, element){
        var type = $(this).attr('data-entity-type');
        var id = parseInt($(this).attr('data-entity-id'), 10);
        $(heroEdit + ' [data-entity-type="' + type + '"][data-entity-id="' + id + '"]').addClass("selected").find('i').removeClass('unchecked').addClass("checked");
    });
    ///////////////////////////////////////////////////////////////////////////////
    // Edit button                                                               //
    ///////////////////////////////////////////////////////////////////////////////
    $(".edit-filters").on('click', function(event) {
        if($(this).hasClass('active')) {
            $(this).removeClass('active');          
            $(this).find('i').removeClass('icon-white');
            $(heroEdit + " li i.option").hide();
            $(".filter-container").removeClass('editing');
            $(heroEdit + ".editing li").draggable("destroy");
            //$(".filter-container").removeClass('editing').find(".filter-menu").removeClass('editing').find("li").draggable("destroy").find("i.option").removeClass('hidden').show();
            $(heroMenu).removeClass('editing').sortable("destroy").find('i.remove').remove();
            if($(heroEdit).is(':visible')) {
                $(heroEdit).slideUp().addClass('hidden');
            }
            return;
        }
        $(this).addClass('active');
        $(this).find('i').addClass('icon-white');
        //$(".filter-menu li i.option").removeClass('hidden').show();
        $(".filter-container").addClass('editing').find(heroEdit + ".active").addClass('editing').find("li i.option").removeClass('hidden').show();
        if(!$(heroEdit).is(':visible')) {
            $(heroEdit).removeClass('hidden').slideDown();
        }
        
        // Make dragable items
        $(heroEdit + ".editing li").draggable({ 
            connectToSortable: "#sortable1",
            cursor: "move",
            opacity: 0.75,
            revert: "invalid", 
            helper: function( event ) {
                var helper = $(this).clone();
                helper.removeClass().addClass("dragging").find('i').removeClass();
                return helper;
            }           
        });
        // Make main menu sortable (accepts draggable elements too)
        $(heroMenu).sortable({
            connectWith: ".connectedSortable",
            //Custom placeholder HACKY
            placeholder: {
                element: function(currentItem) {
                    return $("<li class='placeholder'><i class='icon-arrow-down'></i><i class='icon-arrow-up'></i></li>")[0];
                },
                update: function(container, p) {
                    return;
                }
            },    
            receive: function(event, ui) {
                ui.item.addClass("active").find('i').removeClass('unchecked').addClass("checked");
                ui.item.draggable( "option", "disabled", true );
            },
            stop: function(event, ui) {
                ui.item.css({border: '0', 'border-radius': '2px'}).find('i').remove();
                ui.item.append("<i class='remove icon-remove-sign'></i>");
            }
        }).addClass('editing').find('li').append("<i class='remove icon-remove-sign'></i>");

        $("#sortable1 li").each(function(index, element){
            var type = $(this).attr('data-entity-type');
            var id = parseInt($(this).attr('data-entity-id'), 10);
            $('.filter-menu [data-entity-type="' + type + '"][data-entity-id="' + id + '"]').addClass("selected").find('i').removeClass('unchecked').addClass("checked");
            $('.filter-menu [data-entity-type="' + type + '"][data-entity-id="' + id + '"]').draggable( "option", "disabled", true );
        });
    });
    ///////////////////////////////////////////////////////////////////////////////
    // Hanlde filter menu selection when not editing                             //
    ///////////////////////////////////////////////////////////////////////////////
    $('body').on('click', ".filter-container:not('.editing') " + heroEdit + " li", function(event){
        if($(this).hasClass('active')) {
            return;
        }
        // remove previous button
        $(this).parent().find('.active').removeClass('active');
        $(this).addClass('active');
        var type = $(this).attr('data-entity-type');
        var id = parseInt($(this).attr('data-entity-id'), 10);
        var vc = $(this).attr('data-video-category');
        var vc = $(this).attr('data-video-category');

        console.log("filter menu: type: %s, id: %s, vc: %s", type, id, vc);

        ///////////////////////////////////////////////////////////////////////
        // Decoupled EventTrigger                                            //
        ///////////////////////////////////////////////////////////////////////
        window.fansWorldEvents.emitEvent('onFilterChange', [type, id, vc]);        
    });
    ///////////////////////////////////////////////////////////////////////////////
    // Hanlde hero menu selection                                                 //
    ///////////////////////////////////////////////////////////////////////////////
    $('body').on("click", heroMenu + ":not('.editing') > li:not('[data-override]')", function(event){
        if($(this).hasClass('active')) {
            return;
        }
        $(this).parent().find('.active').removeClass('active');
        $(this).addClass('active');
        var type = $(this).attr('data-entity-type');
        var id = parseInt($(this).attr('data-entity-id'), 10);
        var vc = $(this).attr('data-video-category');

        $('.filter-container').find('ul').each(function(){
            if(parseInt($(this).attr('data-parent-entity-id'), 10) == id) {
                if(!$(".filter-container").is(':visible')) {
                    $(".filter-container").slideDown();
                }
                $(this).addClass('active').removeClass('hidden');
            } else {
                $(this).removeClass('active').addClass('hidden');
            }
        });
        if($(".filter-container [data-parent-entity-id="+ id +"]").length < 1) {
            $(".filter-container").slideUp();
        }

        ///////////////////////////////////////////////////////////////////////
        // Decoupled EventTrigger                                            //
        ///////////////////////////////////////////////////////////////////////
        window.fansWorldEvents.emitEvent('onFilterChange', [type, id]);
    });
});