/*global $, jQuery, alert, console, error, success, endless, ajax, templateHelper, Routing, appLocale, exports, module, require, define*/
/*jslint nomen: true */ /* Tolerate dangling _ in identifiers */
/*jslint vars: true */ /* Tolerate many var statements per function */
/*jslint white: true */
/*jslint browser: true */
/*jslint devel: true */ /* Assume console, alert, ... */
/*jslint windows: true */ /* Assume Windows */
/*jslint maxerr: 100 */ /* Maximum number of errors */


/*
 * Class dependencies:
 *      jquery > 1.8.3
 *      isotope
 *      jsrender
 *      jsviews
 * external dependencies:
 *      templateHelper
 *      base genericAction
 */

// fansWorld TV Class Module 1.0

(function (root, factory) {
    "use strict";
    if (typeof exports === 'object') {
        // Node. Does not work with strict CommonJS, but
        // only CommonJS-like enviroments that support module.exports,
        // like Node.
        module.exports = factory(require('Routing'), require('templateHelper'), require('ajax'));
    } else if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jQuery', 'Routing', 'templateHelper', 'ajax'], factory);
    } else {
        // Browser globals (root is window)
        root.TV = factory(root.jQuery, root.Routing, root.templateHelper, root.ajax);
    }
}(this, function (jQuery, Routing, templateHelper, ajax) {
    "use strict";
    var TV = (function() {
        function TV(filtersList, channelsList) {
            //////////////
            // Internal //
            //////////////
            this.jQuery = jQuery;
            this.name = filtersList;
            this.channelsList = channelsList;
            this.channel = $('#filter-channels').closest('ul').find("li.active").attr("data-channel-id");
            this.filter = $('#list-filters ul').find('li.active').attr('data-list-filter-type');
            this.page = 1;
            this.contentContainer = $('.ranking-widget .content-container');
            this.isotopeContainer = $(this.contentContainer).find('.isotope_container').last();
            this.defaultTags = [
                {filter: 'day', title: 'dia'},
                {filter: 'week', title: 'semana'},
                {filter: 'month', title: 'mes'}
            ];

            $(this.isotopeContainer).attr('data-feed-source', 'teve_ajaxexplore');
            $(this.isotopeContainer).empty();

            //////////////
            // Bindings //
            //////////////
            // lists
            $('[data-list]').list();
            // modalPopup
            $('#montage-video-list a').modalPopup();
            // subscribe buttons
            $('[data-subscribe-channel]').click(function () {
                this.subscribe($(this));
            });
            // Handle channel list
            this.channelToggle();
            // Handle Filter tabs (popular, views, latest)
            this.filterToggle();

            ////////////////////
            // New TV Gallery //
            ////////////////////
            // load isotope gallery
            this.loadGallery();
            // Tags
            this.myTags();
            // custom tags
            if(this.filter === 'views') {
                this.addCustomTags(this.defaultTags);
            }
        }
        TV.prototype.loadGallery = function() {
            this.channel = $('#filter-channels').closest('ul').find("li.active").attr("data-channel-id");
            this.filter = $('#list-filters ul').find('li.active').attr('data-list-filter-type');
            this.page  = 1;
            $(this.isotopeContainer).fwGalerizer({
                normalize: false,
                endless: true,
                feedfilter: {'channel': this.channel, 'filter': this.filter, 'page': this.page},
                onEndless: function( plugin ) {
                    plugin.options.feedfilter.page += 1;
                    console.log("loading video gallery for channel %s with filter %s page: %s", plugin.options.feedfilter.channel, plugin.options.feedfilter.filter, plugin.options.feedfilter.page);
                    return plugin.options.feedfilter;
                },
                onDataReady: function(videos) {
                    var i, outp = [];
                    var normalize = function(video) {
                        var href = Routing.generate(appLocale + '_video_show', {
                            'id': video.id,
                            'slug': video.slug
                        });
                        var hrefModal = Routing.generate(appLocale + '_modal_media', {
                            'id': video.id,
                            'type': 'video'
                        });
                        var authorUrl = Routing.generate(appLocale + '_user_land', {
                            'username': video.author.username
                        });
                        return {
                                'type': 'video',
                                'date': video.createdAt,
                                'href': href,
                                'hrefModal': hrefModal,
                                'image': video.image,
                                'slug': video.slug,
                                'title': video.title,
                                'author': video.author.username,
                                'authorHref': authorUrl,
                                'authorImage': video.author.image
                        };
                    };
                    for(i in videos.videos) {
                        if (videos.videos.hasOwnProperty(i)) {
                            outp.push(normalize(videos.videos[i]));
                        }
                    }
                    return outp;
                }
            });
            this.page += 1;
        };
        TV.prototype.channelToggle = function() {
            var that = this;
            $('#filter-channels').closest('ul').find("li").click(function(e){
                var i;
                var selectedChannel = $(this).first().attr('data-list-target');
                $(".channels-widget .content .active").removeClass('active');
                $('#filter-channels').closest('ul').find("li.active").removeClass('active');
                $(this).first().addClass('active');

                $(".channels-widget .content #" + selectedChannel).addClass('active');

                // Update channel
                that.channel = $('#filter-channels').closest('ul').find("li.active").attr("data-channel-id");
                // update tags
                that.myTags(that.channel, that.filter);
                // custom tags
                if(that.filter === 'views') {
                    that.addCustomTags(that.defaultTags);
                }
                // Destroy gallery items
                that.destroyGallery();
                // Load new gallery
                that.loadGallery();
            });
        };
        TV.prototype.customFilterToggler = function(element) {
            var that = this;
            element.click(function(e){
                console.log("toggle custom filter")
                that.filter = $('this').attr('data-list-filter-type');
                // update tags
                that.myTags(that.channel, that.filter);
                // create custom UI filter elements
                that.addCustomTags(that.defaultTags);
                // Destroy gallery items
                that.destroyGallery();
                // Load new gallery
                that.loadGallery();
            });
        };
        TV.prototype.filterToggle = function() {
            var that = this;
            $('#list-filters').find("li").click(function(e){
                var i;
                var selectedFilter = $(this).first().attr('data-list-filter-type');
                $('#list-filters').find("li.active").removeClass('active');
                $(this).first().addClass('active');

                that.filter = $('#list-filters ul').find('li.active').attr('data-list-filter-type');
                // update tags
                that.myTags(that.channel, that.filter);
                // custom tags
                if(that.filter === 'views') {
                    that.addCustomTags(that.defaultTags);
                }
                // Destroy gallery items
                that.destroyGallery();
                // Load new gallery
                that.loadGallery();
            });
        };
        TV.prototype.myTags = function(channel, filter) {
            var $tagList = $(".content-container").find(".tag-list-container ul");
            $tagList.empty();
            ajax.genericAction("tag_ajaxgetusedinvideos", {
                "channel": channel,
                "filter": filter
            }, function(r) {
                var i;
                for (i in r.tags) {
                    if (r.tags.hasOwnProperty(i)) {
                        var tagHref = Routing.generate(appLocale + "_teve_taggedvideos", {
                            term: r.tags[i].title
                        });
                        $tagList.append("<li><a href='" + tagHref + "'>" + r.tags[i].title + "</a></li>");
                    }
                }
            });
        };
        TV.prototype.addCustomTags = function(tags, options) {
            var that = this;
            var i;
            var $tagList = $(".content-container").find(".tag-list-container ul");
            for(i in tags) {
                if (tags.hasOwnProperty(i)) {
                    var $customFilter = $("<li data-list-filter-type='" + tags[i].filter + "' class=''><span>" + tags[i].title + "</span></li>");
                    $customFilter.css('background-color', '#666');
                    $customFilter.css('color', '#fff');
                    $customFilter.css('border-color', '#8acd2c');
                    that.customFilterToggler($customFilter);
                    $tagList.prepend($customFilter);
                }
            }
            return;
        };
        TV.prototype.destroyGallery = function() {
            $(this.isotopeContainer).data('fwGalerizer').destroy();
            return;
        };
        TV.prototype.subscribe = function ($button) {
            var channel = $button.attr('data-subscribe-channel');
            var params = {};

            if (!channel || channel === 'all') {
                return alert('Por favor elija un canal antes de suscribirse.');
            }
            params.channel = channel;

            $button.addClass('loading-small');

            ajax.genericAction('teve_channelsubscribe', params, function (response) {
                success(response.message);
                $button.text(response.buttontext);

                if (response.state === true) {
                    $button.prepend($('<i>').attr('class', 'icon-remove').after(' '));
                } else if (response.state === false) {
                    $button.prepend($('<i>').attr('class', 'icon-ok').after(' '));
                }
                $button.removeClass('loading-small');
            }, function (msg) {
                error(msg);
                $button.removeClass('loading-small');
            });
        };
        return TV;
    }());
    // Just return a value to define the module export.
    // This example returns an object, but the module
    // can return a function as the exported value.
    return TV;
}));


// implicit global init
// TODO: refactor inside curl wire
$(document).ready(function () {
    "use strict";
    window.fansworld = window.fansworld || {};
    var defaultChannel = null; //
    var defaultFilter = 'popular';
    window.fansworld.tv = new window.TV(defaultChannel, defaultFilter);
    return;
});