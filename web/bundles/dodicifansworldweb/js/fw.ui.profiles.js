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

// v1.2

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
        limitProfilesHome: 20,  // Asoc. en ProfileController.php con LIMIT_PROFILES_HOME
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
                console.log(id)
                that.options.type = type == 'null' ? 'type' : type;
                that.options.id = id == 'null' ? 'all' : id;
                that.options.page = 1;
                that.options.videoFeed = Routing.generate(appLocale + '_profile_ajaxgetprofiles');
                that.options.getFilter = function() {
                    var filter = {
                        page: that.options.page,
                    };
                    filter[that.options.type] = that.options.id;
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
                $('body').find('.spinner').removeClass('hidden');
                $(that.element).parent().find('.add-more').hide();
                $('body').find('.spinner').show();
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
                    var addMore = response.addMore || false;
                    if(typeof response.profiles === 'object' && Object.keys(response.profiles).length < 1) {
                        $(that.element).parent().fadeOut('slow');
                    } else if(typeof response.profiles === 'object' && Object.keys(response.profiles).length >= that.options.limitProfilesHome) {
                        addMore = true;
                    }
                    function render_profile(profile) {
                        $.when(templateHelper.htmlTemplate('profile-home_element', profile))
                        .then(function(response){
                            var $thumb = $(response).clone();
                            var fanBtnClass = null;
                            if(!profile.isFan) {
                                fanBtnClass = "befun-small";
                                $thumb.find("[data-idolship-add]").addClass(fanBtnClass);
                                $thumb.find('[data-teamship-add]').addClass(fanBtnClass);
                            } else {
                                fanBtnClass = "unfan-small";
                                $thumb.find("[data-idolship-add]").addClass(fanBtnClass);
                                $thumb.find('[data-teamship-add]').addClass(fanBtnClass);
                            }
                                $thumb.find("[data-idolship-add]").fwIdolship({
                                    onAddIdol: function(plugin, data) {
                                        var self = $(plugin.element);
                                        self.addClass('unfan-small');
                                        self.removeClass('befun-small');
                                        self.get(0).lastChild.nodeValue = "";
                                    },
                                    onRemoveIdol: function(plugin, data) {
                                        var self = $(plugin.element);
                                        self.addClass('befun-small');
                                        self.removeClass('unfan-small');
                                        self.get(0).lastChild.nodeValue = "";
                                    }
                                });
                                $thumb.find('[data-teamship-add]').fwTeamship({
                                    onAddTeam: function(plugin, data) {
                                        var self = $(plugin.element);
                                        self.addClass('unfan-small');
                                        self.removeClass('befun-small');
                                        self.get(0).lastChild.nodeValue = "";
                                    },
                                    onRemoveTeam: function(plugin, data) {
                                        var self = $(plugin.element);
                                        self.addClass('befun-small');
                                        self.removeClass('unfan-small');
                                        self.get(0).lastChild.nodeValue = "";
                                    }
                                });
                            $(that.element).append($thumb);
                        });
                    }
                    for(i in response.profiles) {
                        if (response.profiles.hasOwnProperty(i)) {
                            var profile = response.profiles[i];
                            // Check Assets
                            if(profile.splash)
                            render_profile(profile);
                        }
                    }
                    var il = new ImagesLoaded(that.element);
                    il.done(function () {
                        $('body').find('.spinner').addClass('hidden');
                        $('body').find('.spinner').hide();
                        $(that.element).parent().removeClass('hidden');
                        $(that.element).parent().fadeIn('slow', function() {
                            if(addMore) {
                                $(that.element).parent().find('.add-more').fadeIn('slow');
                            } else {
                                $(that.element).parent().find('.add-more').hide();
                            }
                        });
                    });
                    il.progress(function (image, isBroken) {
                        console.log("image loaded !")
                    });
                    il.fail(function(instance){
                        $('body').find('.spinner').addClass('hidden');
                        $('body').find('.spinner').hide();
                        $(that.element).parent().removeClass('hidden');
                        $(that.element).parent().fadeIn('slow', function() {
                            if(addMore) {
                                $(that.element).parent().find('.add-more').fadeIn('slow');
                            } else {
                                $(that.element).parent().find('.add-more').hide();
                            }
                        });
                    });
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
    var id = $(".filter-home").find('.active').attr('data-entity-id');

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
        var id = $(this).attr('data-entity-id');

        ///////////////////////////////////////////////////////////////////////
        // Decoupled EventTrigger                                            //
        ///////////////////////////////////////////////////////////////////////
        window.fansWorldEvents.emitEvent('onFilterChange', [type, id]);
    });

});