/*jslint browser: true*/
/*global $, jQuery, alert, console, error, success, ajax, templateHelper, Routing, appLocale*/
/*jslint vars: true */ /* Tolerate many var statements per function */
/*jslint maxerr: 100 */ /*  Maximum number of errors */
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

        // top mosaic
        /*
        site.startMosaic($(".content-container .list-mosaic"), {
            minw: 100,
            margin: 3,
            liquid: true,
            minsize: false,
            fillLastRow: false
        });

        $(".ranking-widget .content-container .isotope_container").empty()
        $(".ranking-widget .content-container .isotope_container").attr('data-feed-source', 'home_ajaxpopularfeed');
        $(".ranking-widget .content-container .isotope_container").fwGalerizer({})
        */

        var $contentContainer = $('.ranking-widget .content-container');
        var $container = $contentContainer.find('.isotope_container').last();
        $container.attr('data-feed-source', 'teve_ajaxexplore'); //teve_ajaxexplore
        $container.empty();
        $container.fwGalerizer({
            normalize: false,
            endless: true,
            feedfilter: {'channel': 29, 'filter': 'popular', 'page': 1},
            onEndless: function(plugin) {
                console.log("new filter");
                console.log(plugin.options.feedfilter);
                return plugin.options.feedfilter;
            },
            onDataReady: function(videos) {
                console.log("dataReady");
                var i, outp;
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
                //for(var i in videos.videos) { console.log(videos.videos[i]) }
                for(i in videos.videos) {
                    if (videos.videos.hasOwnProperty(i)) {
                        outp.push(normalize(videos.videos[i]));
                    }
                }
                return outp;
            }
        });

        // channels explore
        tv.explore();
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
