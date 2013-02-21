/*global $, jQuery, alert, console, error, success, endless, ajax, templateHelper, Routing, appLocale*/
/*jslint nomen: true */ /* Tolerate dangling _ in identifiers */
/*jslint vars: true */ /* Tolerate many var statements per function */
/*jslint white: true */
/*jslint browser: true */
/*jslint devel: true */ /* Assume console, alert, ... */
/*jslint windows: true */ /* Assume Windows */
/*jslint maxerr: 100 */ /* Maximum number of errors */

/*
 * library dependencies:
 *      jquery 1.8.3
 *      isotope
 *      jsrender
 *      jsviews
 * external dependencies:
 *      template helper
 */

// fansWorld tv class 1.0



var tv = {

    'init': function (filtersList, channelsList, targetDataList) {
        "use strict";


        // lists
        $('[data-list]').list();

        // modalPopup
        $('#montage-video-list a').modalPopup();

        // subscribe buttons
        $('[data-subscribe-channel]').click(function () {
            tv.subscribe($(this));
        });

        var $contentContainer = $('.ranking-widget .content-container');
        var $container = $contentContainer.find('.isotope_container').last();
        $container.data('page', 0);
        var outp = [];
        $container.attr('data-feed-source', 'teve_ajaxexplore'); //teve_ajaxexplore
        $container.empty();
        /*
        $container.fwGalerizer({
            normalize: false,
            endless: true,
            feedfilter: {'channel': 29, 'filter': 'popular', 'page': 1},
            onEndless: function( plugin ) {
                console.log("new filter");
                console.log(plugin.options.feedfilter);
                return plugin.options.feedfilter;
            },
            onDataReady: function(videos) {
                console.log("dataReady");
                var i;
                var normalize = function(video) {
                    var href = Routing.generate(appLocale + '_video_show', {
                        'id': video.id,
                        'slug': video.slug
                    });
                    var authorUrl = Routing.generate(appLocale + '_user_wall', {
                        'username': video.author.username
                    });
                    return {
                            'type': 'video',
                            'date': video.createdAt,
                            'href': href,
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
        */
        tv.loadGallery();
        // channelToggle
        tv.channelToggle();
        // filterToggle
        tv.filterToggle();
        // update tags
        tv.mytags();
        // channels explore
        tv.explore();
    },
    'loadGallery': function() {
        "use strict";
        var $contentContainer = $('.ranking-widget .content-container');
        var $container = $contentContainer.find('.isotope_container').last();
        var channel = $('#filter-channels').closest('ul').find("li.active").attr("data-channel-id");
        var filter = $('#list-filters ul').find('li.active').attr('data-list-filter-type');
        var page = $container.data('page');
        var outp = [];

        page += 1;

        console.log("loading video gallery for channel %s with filter %s page: %s", channel, filter, page);
        $container.attr('data-feed-source', 'teve_ajaxexplore'); //teve_ajaxexplore
        $container.empty();
        $container.fwGalerizer({
            normalize: false,
            endless: true,
            feedfilter: {'channel': channel, 'filter': filter, 'page': page},
            onEndless: function( plugin ) {
                console.log("new filter");
                console.log(plugin.options.feedfilter);
                plugin.options.feedfilter.page += 1;
                console.log("loading video gallery for channel %s with filter %s page: %s", plugin.options.feedfilter.channel, plugin.options.feedfilter.filter, plugin.options.feedfilter.page);
                return plugin.options.feedfilter;
            },
            onDataReady: function(videos) {
                console.log("dataReady");
                var i;
                var normalize = function(video) {
                    var href = Routing.generate(appLocale + '_video_show', {
                        'id': video.id,
                        'slug': video.slug
                    });
                    var authorUrl = Routing.generate(appLocale + '_user_wall', {
                        'username': video.author.username
                    });
                    return {
                            'type': 'video',
                            'date': video.createdAt,
                            'href': href,
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
        $container.isotope({
            getSortData : {
                date : function ( $elem ) {
                    return $elem.attr('data-element-date');
                },
                symbol : function ( $elem ) {
                return $elem.find('.symbol').text();
                }
            }
        });
    },
    'channelToggle': function() {
        'use strict';
        $('#filter-channels').closest('ul').find("li").click(function(e){
            var i;
            var selectedId = $(this).first().attr('data-list-target');
            var $activeChannel = $(".channels-widget .content .active");
            // Isotope Handler
            var $container = $('.ranking-widget .content-container').find('.isotope_container').last();

            $('#filter-channels').closest('ul').find("li.active").removeClass('active');
            $(this).first().addClass('active');

            $activeChannel.removeClass('active');
            $(".channels-widget .content #" + selectedId).addClass('active');
            // update tags
            tv.mytags();
            // Empty gallery items
            $container.data('fwGalerizer').destroy();
            $container.data('page', 0);
            tv.loadGallery();
        });
    },
    filterToggle: function() {
        'use strict';
        $('#list-filters').find("li").click(function(e){
            var i;
            var selectedFilter = $(this).first().attr('data-list-filter-type');
            // Isotope Handler
            var $container = $('.ranking-widget .content-container').find('.isotope_container').last();

            $('#list-filters').find("li.active").removeClass('active');
            $(this).first().addClass('active');

            console.log('changing filter to: %s', selectedFilter);

            // update tags
            tv.mytags();
            // Empty gallery items
            $container.data('fwGalerizer').destroy();
            $container.data('page', 0);
            tv.loadGallery();
        });
    },
    'subscribe': function ($button) {
        "use strict";

        var channel = $button.attr('data-subscribe-channel'),
            params = {};

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

    },
    'mytags': function() {
        "use strict";
        //ajax.genericAction('tag_ajaxgetusedinvideos', {'channel': 1, 'filter': 'popular'}, function(r){console.log(r);});
        var $tagList = $('.content-container').find('.tag-list-container ul');
        var channel = $('#filter-channels').closest('ul').find("li.active").attr("data-channel-id");
        var filter = $('#list-filters ul').find('li.active').attr('data-list-filter-type');
        $tagList.empty();
        console.log("loading tags for channel %s with filter %s", channel, filter);
        ajax.genericAction('tag_ajaxgetusedinvideos', {'channel': channel, 'filter': filter}, function(r){
            var i;
            for(i in r.tags) {
                if (r.tags.hasOwnProperty(i)) {
                    var tagHref = Routing.generate(appLocale + '_teve_taggedvideos', {term: r.tags[i].title });
                    $tagList.append("<li><a href='" + tagHref + "'>" + r.tags[i].title + "</a></li>");
                    console.log("adding tag: %s", r.tags[i].title);
                }
            }
        });

    },
    'tags': function (activeChannel, filter, targetDataList, opts) {
        "use strict";

    //ajax.genericAction('tag_ajaxgetusedinvideos', {'channel': 1, 'filter': 'popular'}, function(r){console.log(r);});
        var filterList = $(targetDataList).closest('.content-container').find('.tag-list-container ul');
        opts = $.merge({
            'channel': activeChannel,
            'filter': filter
        }, opts);

        $(filterList).empty().addClass('loading');

        ajax.genericAction('tag_ajaxgetusedinvideos', opts, function (r) {
            if (typeof r !== "undefined") {
                $(filterList).removeClass('loading');
                if (typeof r.tags !== "undefined") {
                    templateHelper.renderTemplate("general-tag_list", r.tags, filterList, false, function () {
                    });
                }
            }
        }, function (msg) {
            console.error(msg);
        });
    },

    'explore': function () {
        "use strict";
        /*
        var dropDown = '.breadcrumb .channels-dropdown',
            targetDataList = '.am-container';

        $(dropDown).find('ul.dropdown-menu li a').click(function (e) {
            e.preventDefault();
            var activeChannel = {
                slug: $(this).attr('channel-slug'),
                title: $(this).text()
            };
            $(dropDown).find('.dropdown-toggle span').text(activeChannel.title);
            tv.rankingUpdate.videos(activeChannel.slug, null, targetDataList, {});
        });
        */
    }

};
