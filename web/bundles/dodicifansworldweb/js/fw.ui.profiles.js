/**
 * Created with JetBrains PhpStorm.
 * User: benjius
 * Date: 6/7/13
 * Time: 3:06 PM
 * To change this template use File | Settings | File Templates.
 */


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
        block: null,
        videoFeed: Routing.generate(appLocale + '_profile_ajaxgetprofiles'),
        selector: 'section.most-recent',
        itemSelector: '.profile',
        packery: null,
        container: null,
        queue: null,
        queueDelay: 10,
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
                if($.isNumeric(id)) {
                    that.options.type = type;
                    that.options.id = id;
                    $.when(that.removeAll()).then(function(){
                        that.hide();
                        var reqData = {};
                        reqData[that.options.type] = parseInt(that.options.id, 10);
                        $.when(that.makePackery(reqData)).then(function(){
                        }).progress(function() {
                            console.log("adding thumbnails to packery");
                        }).fail(function(error){
                            alert(error.message);
                        });
                    }).fail(function(error){
                            var reqData = {};
                            reqData[that.options.type] = parseInt(that.options.id, 10);
                            $.when(that.makePackery(reqData)).then(function(){
                            }).progress(function() {
                                console.log("adding thumbnails to packery");
                            }).fail(function(error){
                                alert(error.message);
                            });
                        });
                }
            };
            window.fansWorldEvents.addListener('onFilterChange', that.options.onFilterChange);
            that.options.packery = new Packery(that.options.container, {
                itemSelector: '.profile',
                gutter: ".gutter-sizer",
                columnWidth: ".grid-sizer"
            });
            var reqData =  {
                type: 'all',
                page: that.options.page,
                filterby: that.options.block
            };
            if(!isNaN(that.options.id)) {
                reqData.genre = that.options.id;
            }
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
            $.ajax({
                url: that.options.videoFeed,
                data: data || {
                    'vc': that.options.videoCategory
                }
            }).then(function(response) {
                    console.log(response)
                    var i = 0;
                    totalVideos = response.profiles.length;
                    if(totalVideos <= 0) {
                        deferred.reject(new Error("Video category does not contain any video"));
                    }
                    for(i in response.profiles) {
                        if (response.profiles.hasOwnProperty(i)) {
                            var profile = response.profiles[i];
                            $.when(templateHelper.htmlTemplate('profile-home_element', profile))
                                .then(function(response){
                                    var $thumb = $(response).clone();
                                    $thumb.addClass('profile');
                                    console.log(profile.highlight);
                                    if(profile.highlight == true) {
                                        $thumb.addClass('double');
                                    }
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
            var videos = $(that.options.selector).find('.profile');
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
        videoFeed: Routing.generate(appLocale + '_profile_ajaxgetprofiles'),
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
                    type: 'all',
                    page: that.options.page,
                    filterby: that.options.block
                };
                if(!isNaN(that.options.id)) {
                    filter.genre = that.options.id;
                }
                return filter;
            };
            $.when(that.insetThumbs(that.options.videoFeed, that.options.getFilter())).then(function(response){
            });

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
                $.when(that.insetThumbs(that.options.videoFeed, that.options.getFilter())).then(function(response){
                });
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
                    if(response.profiles.length < 1) {
                        $(that.element).parent().fadeOut('slow');
                    }
                    for(i in response.profiles) {
                        if (response.profiles.hasOwnProperty(i)) {
                            var addMore = response.addMore;
                            var profile = response.profiles[i];
                            $.when(templateHelper.htmlTemplate('profile-home_element', profile))
                                .then(function(response){
                                    var $thumb = $(response).clone();
                                    $thumb.find("[data-idolship-add]").fwIdolship({
                                        onAddIdol: function(plugin, data) {
                                            var self = $(plugin.element);
                                            self.addClass('disabled');
                                            self.removeClass('add');
                                            self.text("-");
                                            console.log(self.parent().find('.data-cant'))
                                            var fc = self.parent().find('.data-cant').text();
                                            fc += 1;
                                            self.parent().find('.data-cant').text(fc);
                                        },
                                        onRemoveIdol: function(plugin, data) {
                                            window.location.reload();
                                        }
                                    });
                                    $thumb.find('[data-teamship-add]').fwTeamship({
                                        onAddTeam: function(plugin, data) {
                                            var self = $(plugin.element);
                                            self.addClass('disabled');
                                            self.removeClass('add');
                                            self.text("-");
                                            self.parent().find('[data-cant]').text('KK');
                                        },
                                        onRemoveTeam: function(plugin, data) {
                                            window.location.reload();
                                        }
                                    });
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
// Attach plugin to all matching element                                     //
///////////////////////////////////////////////////////////////////////////////
$(document).ready(function () {
    "use strict";

    // Packery
    var type = $(".filter-home").find('.active').attr('data-entity-type');
    var id = parseInt($(".filter-home").find('.active').attr('data-entity-id'), 10);

    // Video Packery Gallery
    $('section.most-recent').fwHomePackery({
        type: type,
        id: id,
        selector: 'section.most-recent',
        block: 'popular'
    });

    // Video Grid
    $('section.popular > .profiles-container').fwHomeThumbs({
        type: type,
        id: id,
        block: 'popular'
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