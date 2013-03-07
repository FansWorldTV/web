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

// fansWorld TV Class Module 1.1 (con galerias de profiles)
// 1.0

(function (root, factory) {
    "use strict";
    if (typeof exports === 'object') {
        // Node. Does not work with strict CommonJS, but
        // only CommonJS-like enviroments that support module.exports,
        // like Node.
        module.exports = factory(require('Routing'), require('templateHelper'), require('ajax'), require('ExposeTranslation'));
    } else if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jQuery', 'Routing', 'templateHelper', 'ajax', 'ExposeTranslation'], factory);
    } else {
        // Browser globals (root is window)
        root.TV = factory(root.jQuery, root.Routing, root.templateHelper, root.ajax, root.ExposeTranslation);
    }
}(this, function (jQuery, Routing, templateHelper, ajax, ExposeTranslation) {
    "use strict";
    var TV = (function() {
        function TV(filtersList, channelsList) {
            //////////////
            // Internal //
            //////////////
            var that = this;
            this.jQuery = jQuery;
            this.name = filtersList;
            this.channelsList = channelsList;
            this.channel = $('.filter-channels').closest('ul').find("li.active").attr("data-channel-id");
            this.filter = $('#list-filters ul').find('li.active').attr('data-list-filter-type');

            this.page = 1;

            ////////////////////////////////////////////////////////////////////
            // Hook every isotope_container class                             //
            ////////////////////////////////////////////////////////////////////
            this.contentContainer = $('body');
            this.isotopeContainer = $(this.contentContainer).find('.isotope_container').last();
            if(this.isotopeContainer.length <= 0) {
                return;
            }
            ////////////////////////////////////////////////////////////////////
            // test against a feed-source field in the markup then            //
            // use it as a json source                                        //
            ////////////////////////////////////////////////////////////////////
            this.feedSource = $(this.isotopeContainer).attr('data-feed-source');
            if(typeof this.feedSource === 'undefined' || this.feedSource == ''){
                this.entityId   = $("[data-list]").attr('data-entity-id');
                if(typeof this.entityId !== 'undefined'){
                    this.entityType = $("[data-list]").attr('data-entity-type');
                    this.dataList = $("[data-list]").attr('data-list');
                    this.isProfile = true;
                    console.log("in-profile");
                    var feed = that.getProfileVideoFeed();
                    console.log("filter: " + that.criteria + " feed: " + feed);
                    var opts = {
                        'sort': that.criteria,
                        'page': that.page,
                        'entityId': that.entityId,
                        'entityType': that.entityType
                    };
                    $(this.isotopeContainer).attr('data-feed-source', feed);
                    console.log(opts);
                } else {
                    return;
                }
            } else {
                this.isTeve = true;
            }
            ////////////////////////////////////////////////////////////////////
            // A list of default tags used for filtering feeds                //
            ////////////////////////////////////////////////////////////////////
            this.defaultTags = [
                {filter: 'day', title: ExposeTranslation.get('day')},
                {filter: 'week', title: ExposeTranslation.get('week')},
                {filter: 'month', title: ExposeTranslation.get('month')}
            ];

            ////////////////////////////////////////////////////////////////////
            // Empty container.                                               //
            ////////////////////////////////////////////////////////////////////
            $(this.isotopeContainer).empty();

            //////////////
            // Bindings //
            //////////////
            // modalPopup
            $('#montage-video-list a').modalPopup();
            // subscribe buttons
            $('[data-subscribe-channel]').click(function () {
                that.subscribe($(this));
            });
            // Handle channel list
            this.channelToggle();
            // Handle Filter tabs (popular, views, latest)
            this.filterToggle();
            this.filterToolbar();

            ////////////////////
            // New TV Gallery //
            ////////////////////
            // load isotope gallery
            if(this.isTeve) {
                this.profileGallery();
            } else {
                this.loadGallery();
            }
            // Tags
            this.myTags();
            // custom tags
            if(this.filter === 'views') {
                this.addCustomTags(this.defaultTags);
            }
        }
        ////////////////////////////////////////////////////////////////////////
        // Genera galerias isotope genéricas                                  //
        ////////////////////////////////////////////////////////////////////////
        TV.prototype.loadGallery = function() {
            var that = this;
            $(this.isotopeContainer).fwGalerizer({
                normalize: false,
                endless: true,
                feedfilter: {
                    'sort': that.criteria,
                    'page': that.page,
                    'entityId': that.entityId,
                    'entityType': that.entityType
                },
                ////////////////////////////////////////////////////////////////
                // pagination callback                                        //
                ////////////////////////////////////////////////////////////////
                onEndless: function( plugin ) {
                    plugin.options.feedfilter.page += 1;
                    return plugin.options.feedfilter;
                },
                ////////////////////////////////////////////////////////////////
                // signal callback to transform json data befor sending it    //
                // to the template generator                                  //
                ////////////////////////////////////////////////////////////////
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
                            'username': 'juan.perez'
                        });
                        return {
                                'type': 'video',
                                'date': video.createdAt,
                                'href': href,
                                'hrefModal': hrefModal,
                                'image': video.imgsrc,
                                'slug': video.slug,
                                'title': video.title,
                                'author': 'juan.perez',
                                'authorHref': authorUrl,
                                'authorImage': 'http://fansworld.dodici.local/uploads/media/default/0001/02/thumb_1000_default_small_square_81dfa380953b084fb7eefb0273ac602bdee11874.jpg'
                        };
                    };
                    for(i in videos.elements) {
                        if (videos.elements.hasOwnProperty(i)) {
                            outp.push(normalize(videos.elements[i]));
                        }
                    }
                    return outp;
                }
            });
        };
        ////////////////////////////////////////////////////////////////////////
        // Genera galerias en los profiles de idolos y equipos                //
        ////////////////////////////////////////////////////////////////////////
        TV.prototype.profileGallery = function() {
            this.channel = $('.filter-channels').closest('ul').find("li.active").attr("data-channel-id");
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
            $('.filter-channels').closest('ul').find("li").click(function(e){
                var i;
                var selectedChannel = $(this).first().attr('data-list-target');
                $(".channels-widget .content .active").removeClass('active');
                $('.filter-channels').closest('ul').find("li.active").removeClass('active');
                $(this).first().addClass('active');

                $(".channels-widget .content #" + selectedChannel).addClass('active');

                // Update channel
                that.channel = $('.filter-channels').closest('ul').find("li.active").attr("data-channel-id");
                // update tags
                that.myTags(that.channel, that.filter);
                // custom tags
                if(that.filter === 'views') {
                    that.addCustomTags(that.defaultTags);
                }
                // Destroy gallery items
                that.destroyGallery();
                // Load new gallery
                that.profileGallery();
            });
        };
        TV.prototype.customFilterToggler = function(element) {
            var that = this;
            element.click(function(e){
                $('[data-list-filter-type=' + that.filter + ']').removeClass();
                that.filter = $(this).attr('data-list-filter-type');
                // update tags
                that.myTags(that.channel, that.filter);
                // create custom UI filter elements
                that.addCustomTags(that.defaultTags);
                $('[data-list-filter-type=' + that.filter + ']').addClass('active');
                // Destroy gallery items
                that.destroyGallery();
                // Load new gallery
                that.loadGallery();
            });
        };
        TV.prototype.getMethodName = function() {
            var that = this;
            var criteria = '';
            switch($("[data-sort] .btn-group").find('.active').attr('data-type')){
                case "0":
                    criteria = "highlight";
                    break;
                case "1":
                    criteria = "most-visited";
                    break;
                case "2":
                    criteria = "popular";
                    break;
                case "3":
                    criteria = "most-visited-today";
                    break;
            }
            that.criteria = criteria;
            return criteria;
        };
        TV.prototype.getProfileVideoFeed = function() {
            var that = this;
            var type = $("[data-sort] .btn-group").find('.active').attr('data-type');
            if(typeof(type) === 'undefined'){
                type = 'popular';
            }
            var methodName = that.getMethodName();
            var opts = {
                'sort': that.criteria,
                'page': that.page,
                'entityId': that.entityId,
                'entityType': that.entityType
            };
            var route = '';
            switch(that.criteria){
                case 'popular':
                    route = that.dataList + "_popular";
                    break;
                case 'highlight':
                    route = that.dataList + "_highlighted";
                    break;
                case 'most-visited':
                    route = that.dataList + "_visited";
                    break;
                case 'most-visited-today':
                    route = that.dataList + "_visited";
                    opts.today = true;
                    break;
            }
            return route;
        };
        TV.prototype.filterToolbar = function($toolbar) {
            var that = this;
            var method = '';
            $("[data-sort] .btn-group .btn").on('click', function(){
                $(this).parent().find('.active').removeClass('active');
                $(this).addClass('active');
                switch($(this).attr('data-type')){
                    case "0":
                        that.criteria = "highlight";
                        break;
                    case "1":
                        that.criteria = "most-visited";
                        break;
                    case "2":
                        that.criteria = "popular";
                        break;
                    case "3":
                        that.criteria = "most-visited-today";
                        break;
                }
                var feed = that.getProfileVideoFeed();
                $(that.isotopeContainer).attr('data-feed-source', feed);
                console.log("filter: " + that.criteria + " feed: " + feed);
                var opts = {
                    'sort': that.criteria,
                    'page': that.page,
                    'entityId': that.entityId,
                    'entityType': that.entityType
                };
                console.log(opts);
                // Destroy gallery items
                that.destroyGallery();
                that.page = 1;  // reset page count
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
                that.profileGallery();
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
                var action; var search_term;
                for (i in r.tags) {
                    if (r.tags.hasOwnProperty(i)) {

                        action = "_teve_taggedvideos";
                        search_term = r.tags[i].slug;

                        if ("team" == r.tags[i].type) {
                            action = "_team_videos";
                        }

                        if ("idol" == r.tags[i].type) {
                            action = "_idol_videos";
                        }

                        var tagHref = Routing.generate(appLocale + action, {
                            term: search_term, slug: search_term
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


// implicit init that adds module to global scope
// TODO: refactor inside curl
$(document).ready(function () {
    "use strict";
    window.fansworld = window.fansworld || {};
    var defaultChannel = null; //
    var defaultFilter = 'popular';
    window.fansworld.tv = new window.TV(defaultChannel, defaultFilter);
    return;
});