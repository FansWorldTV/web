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
// Plugin wrapper para galerias Hero                                      //
///////////////////////////////////////////////////////////////////////////////
$(document).ready(function () {
    "use strict";
    var pluginName = "fwHomeHero";
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
        onVideoCategoryEvent: null,
        maxVideos: 8
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
            console.log("fwHomeHero init()")
            var that = this;
            var self = $(that.element);
            self.bind("destroyed", $.proxy(that.teardown, that));
            self.addClass(that._name);

            that.options.container = document.querySelector(that.options.selector);

            that.options.onFilterChange = function (type, id, vc){
                id = parseInt(id, 10);
                vc = parseInt(vc, 10);
                that.options.videoFeed = Routing.generate(appLocale + '_home_ajaxfilter');
                that.options.getFilter = function() {
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
                    return reqData;
                };
                //that.clearThumbs();
                that.insetThumbs(that.options.videoFeed, that.options.getFilter());
            };
            window.fansWorldEvents.addListener('onFilterChange', that.options.onFilterChange);


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
            that.hide();
            var deferred = new jQuery.Deferred();
            $.ajax({
                url: feed,
                data: data
            }).then(function(response) {
                var i = 0;
                var total = Object.keys(response.highlighted).length;
                // If no more videos then hide the addMore button
                if(total < that.options.maxVideos) {
                    console.log("hay pocos videos: " + response.highlighted.length)
                    that.show();
                    return;
                }

                var leftMax = 1; //$(that.element).find('.span2').length;
                $(that.element).find('.span2').empty();
                for(i = 0; i <= leftMax; i += 1) {
                    if (response.highlighted.hasOwnProperty(i)) {
                        var video = response.highlighted[i];
                        $.when(templateHelper.htmlTemplate('video-home_element', video))
                        .then(function(response){
                            console.log("appending to span2")
                            var $thumb = $(response).clone();
                            $thumb.addClass('video');
                            $(that.element).find('.span2').append($thumb);
                        });
                    }
                }

                leftMax += 1;

                $.when(templateHelper.htmlTemplate('video-home_element', response.highlighted[leftMax]))
                .then(function(response){
                    console.log("appending to span4")
                    $(that.element).find('.span4').empty();
                    var $thumb = $(response).clone();
                    $thumb.addClass('video');
                    $(that.element).find('.span4').append($thumb);
                });

                leftMax += 1;

                var rightMax = 5;
                $(that.element).find('.span6').empty();
                for(i = 0; i <= rightMax; i += 1) {
                    if (response.highlighted.hasOwnProperty(i)) {
                        var video = response.highlighted[leftMax + i];
                        $.when(templateHelper.htmlTemplate('video-home_element', video))
                        .then(function(response){
                            console.log("appending to span6")
                            var $thumb = $(response).clone();
                            $thumb.addClass('video');
                            $(that.element).find('.span6').append($thumb);
                        });
                    }
                }

                var il = new ImagesLoaded(that.element);
                il.done(function () {
                    console.log("All images loaded !")
                    that.show();
                });
                il.progress(function (image, isBroken) {
                    console.log("image loaded !")
                });
                il.fail(function(instance){
                    console.log('FAIL - all images loaded, at least one is broken');
                    that.show();
                });

                return response.highlighted;
            }).done(function(highlighted){
                deferred.resolve(highlighted);
            }).fail(function(error){
                deferred.reject(new Error(error));
            });
            return deferred.promise();
        },
        hide: function() {
            var that = this;
            //$(that.element).parent().fadeOut(function() {});
            window.fansWorldEvents.emitEvent('onContenNeedsLoad', [that]);
        },
        show: function() {
            var that = this;
            //$(that.element).removeClass('hidden');
            //$(that.element).parent().fadeIn(function() {});
            window.fansWorldEvents.emitEvent('onContentLoaded', [that]);
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
                    $.when(that.insetThumbs(that.options.videoFeed, that.options.getFilter()))
                    .then(function(response){
                        $(that.element).removeClass('hidden');
                    });
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
            $(that.element).empty();
            $(that.element).parent().find('.spinner').removeClass('hidden');
            $(that.element).parent().find('.add-more').hide();
            $(that.element).parent().find('.spinner').show();
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
            that.hide();
            var deferred = new jQuery.Deferred();
            $.ajax({
                url: feed,
                data: data
            }).then(function(response) {
                var i = 0;
                // If no more videos then hide the addMore button
                if(response.videos.length < 1) {
                    $(that.element).parent().find('.add-more').hide();
                    that.show();
                }
                for(i in response.videos) {
                    if (response.videos.hasOwnProperty(i)) {
                        var addMore = response.addMore;
                        var video = response.videos[i];
                        $.when(templateHelper.htmlTemplate('video-home_element', video))
                        .then(function(response){
                            var $thumb = $(response).clone();
                            $thumb.hide().appendTo(that.element).fadeIn('slow');
                            $thumb.find('img').load(function() {
                                if(addMore) {
                                    $(that.element).parent().find('.add-more').show();
                                } else {
                                    $(that.element).parent().find('.add-more').hide();
                                }
                                $(that.element).append($thumb);
                            });
                        });
                    }
                }
                var il = new ImagesLoaded(that.element);
                il.done(function () {
                    console.log("All images loaded !")
                    that.show();
                });
                il.progress(function (image, isBroken) {
                    console.log("image loaded !")
                });
                il.fail(function(instance){
                    console.log('FAIL - all images loaded, at least one is broken');
                    that.show();
                });

                return response.videos;
            }).done(function(videos){
                deferred.resolve(videos);
            }).fail(function(error){
                deferred.reject(new Error(error));
            });
            return deferred.promise();
        },
        hide: function() {
            var that = this;
            //$(that.element).fadeOut(function() {});
            window.fansWorldEvents.emitEvent('onContenNeedsLoad', [that]);
        },
        show: function() {
            var that = this;
            //$(that.element).removeClass('hidden');
            //$(that.element).fadeIn(function() {});
            window.fansWorldEvents.emitEvent('onContentLoaded', [that]);
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
                delay: 0,
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
                    $(that.element).append(tag);
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
        hide: function() {
            var that = this;
            window.fansWorldEvents.emitEvent('onContenNeedsLoad', [that]);
        },
        show: function() {
            var that = this;
            window.fansWorldEvents.emitEvent('onContentLoaded', [that]);
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
    $('section.highlighteds').fwHomeHero({
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

    /*
    // Comentado por juan, no mas total counts!!

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

    */

    $('[data-menu-edit="true"]').on('click', function(event){
        if($(this).hasClass('active')) {
            $('.category-menu').hide();
            $(this).removeClass('active');
            return;
        }
        $(this).addClass('active');
        $('.category-menu').show();
    });


    var heroMenu = '.filter-home';
    var heroEdit = '.hero-editor';

    $('body').on("click", heroMenu + ":not('.editing') > li:not('[data-override]')", function(event) {
        if ($(this).hasClass('active')) {
            return;
        }
        $(this).parent().find('.active').removeClass('active');
        $(this).addClass('active');

        var type = $(this).attr('data-entity-type');
        var id = parseInt($(this).attr('data-entity-id'), 10);
        var vc = $(this).attr('data-video-category');

        $('.filter-container ul.hero-submenu')
            .each(function() {
            if (parseInt($(this)
                .attr('data-parent-entity-id'), 10) == id && $(this)
                .attr('data-entity-type') == type) {
                if (!$(".filter-container").is(':visible')) {
                    $(".filter-container").slideDown();
                }
                $(this).addClass('active').removeClass('hidden');
            } else {
                $(this).removeClass('active').addClass('hidden');
            }
        });
        $('.filter-container').removeClass('hidden').slideDown().find(heroEdit).addClass('hidden');

        if ($(".filter-container [data-parent-entity-id=" + id + "][data-entity-type=" + type + "]").length < 1) {
            $(".filter-container").slideUp();
        }

        /*
        // Video Packery Gallery
        $('section.highlighteds').
        // Video Grid
        $('section.popular > .videos-container').
        $('section.followed > .videos-container').
        */
        ///////////////////////////////////////////////////////////////////////
        // Decoupled EventTrigger //
        ///////////////////////////////////////////////////////////////////////
        window.fansWorldEvents.emitEvent('onFilterChange', [type, id]);
    });
});

$(document).ready(function () {
    "use strict";
    var max = 0;
    var total = 0;
    function onContenNeedsLoad() {
        max += 1;
        total += 1;
        console.log("onContenNeedsLoad(): " + max);
    }
    function onContentLoaded() {
        total -= 1;
        if(total == 0) {

            $('.highlights-container').removeClass('hidden');
            $('.highlights-container').show();
            setTimeout(function(){
                if($('section.followed > .videos-container').find('.video').length > 0) {
                    $('section.followed').removeClass('hidden');
                    $('section.popular').show();
                }
                if($('section.popular > .videos-container').find('.video').length > 0) {
                    $('section.popular').removeClass('hidden');
                    $('section.popular').show();
                }
                $('.spinner').addClass('hidden').hide();
            }, 10);
        }
        console.log("contenido cargado: " + total + " max: " + max);
    }
    function onFilterChange() {
        total = max = 0;

        $('section.popular').addClass('hidden');
        $('section.followed').addClass('hidden');
        $('.highlights-container').addClass('hidden');
        $('.spinner').removeClass('hidden').show();
        console.log("onFilterChange(): " + total);
    }
    window.fansWorldEvents.addListener('onContenNeedsLoad', onContenNeedsLoad);
    window.fansWorldEvents.addListener('onContentLoaded', onContentLoaded);
    window.fansWorldEvents.addListener('onFilterChange', onFilterChange);
    //window.fansWorldEvents.addListener('onFindVideosByTag', onFilterChange);

});