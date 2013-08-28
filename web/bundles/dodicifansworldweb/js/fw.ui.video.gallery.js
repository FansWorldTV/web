///////////////////////////////////////////////////////////////////////////////
// Plugin wrapper para galerias semantic grid                                //
///////////////////////////////////////////////////////////////////////////////
// WARNING GLOBAL VARIABLE
// EventEmitter is taken from packery but can be download from https://github.com/Wolfy87/EventEmitter
$(document).ready(function () {
    "use strict";
    window.fansWorldEvents = window.fansWorldEvents || new EventEmitter();
});


$(document).ready(function () {
    "use strict";
    var pluginName = "fwVideoGallery";
    var defaults = {
        videoCategory: null,
        videoGenre: null,
        type: null,
        id: null,
        videoFeed: Routing.generate(appLocale + '_user_filtervideosajax'),
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
            // Disable - Enable preload 
            /*
            that.clearThumbs();
            that.insetThumbs(Routing.generate(appLocale + '_home_ajaxfilter'), that.options.getFilter());
            */

            that.options.onFilterChange = function(type, id, vc) {
                id = parseInt(id, 10);
                vc = parseInt(vc, 10);
                that.options.videoFeed = Routing.generate(appLocale + '_user_filtervideosajax'),
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

var megaVideoTest = {
    "videos": [
        {
            "id": "2714",
            "slug": "nina-simone-ne-me-quitte-pas",
            "title": "Nina Simone - Ne Me Quitte Pas",
            "image": "http://www2.fansworld.tv/uploads/media/default/0001/06/thumb_5391_default_huge_square_2bb421cb7aa3a4af4a214fa5466c8441e7c14b95.jpg",
            "createdAt": "1376413602",
            "author": {
                "id": 2,
                "title": "Juan Pérez",
                "image": "http://www2.fansworld.tv/uploads/media/default/0001/01/thumb_3_default_small_square_8bae296af3f629b07f0ef5e933549e7c103c5004.jpg",
                "createdAt": "1373426526",
                "firstname": "Juan",
                "lastname": "Pérez",
                "fanCount": 5,
                "splash": null,
                "sex": null,
                "username": "juan.perez",
                "url": "/app_dev.php/u/juan.perez/wall",
                "location": null,
                "canFriend": false
            },
            "content": ":)\nEnjoy",
            "likeCount": 0,
            "visitCount": 8,
            "commentCount": 0,
            "videocategory": 1,
            "genre_id": 1,
            "genreparent_id": null,
            "weight": 3187,
            "duration": "03:36",
            "url": "/app_dev.php/tv/2714/nina-simone-ne-me-quitte-pas",
            "modalUrl": "/app_dev.php/modal/video/show/2714"
        },
        {
            "id": "2690",
            "slug": "ttt",
            "title": "Ttt",
            "image": "http://www2.fansworld.tv/uploads/media/default/0001/06/thumb_5261_default_huge_square_fc81fb00352f55fa7718854464cdcb556dd6c9f8.jpg",
            "createdAt": "1374592619",
            "author": {
                "id": 2,
                "title": "Juan Pérez",
                "image": "http://www2.fansworld.tv/uploads/media/default/0001/01/thumb_3_default_small_square_8bae296af3f629b07f0ef5e933549e7c103c5004.jpg",
                "createdAt": "1373426526",
                "firstname": "Juan",
                "lastname": "Pérez",
                "fanCount": 5,
                "splash": null,
                "sex": null,
                "username": "juan.perez",
                "url": "/app_dev.php/u/juan.perez/wall",
                "location": null,
                "canFriend": false
            },
            "content": "Ttt",
            "likeCount": 1,
            "visitCount": 7,
            "commentCount": 0,
            "videocategory": 3,
            "genre_id": 2,
            "genreparent_id": 1,
            "weight": 3183,
            "duration": "00:03",
            "url": "/app_dev.php/tv/2690/ttt",
            "modalUrl": "/app_dev.php/modal/video/show/2690"
        },
        {
            "id": "2689",
            "slug": "y",
            "title": "Y",
            "image": "http://www2.fansworld.tv/uploads/media/default/0001/06/thumb_5241_default_huge_square_b730b961eaea0f536db8b2c4c1cf848803a7502c.jpg",
            "createdAt": "1374160615",
            "author": {
                "id": 2,
                "title": "Juan Pérez",
                "image": "http://www2.fansworld.tv/uploads/media/default/0001/01/thumb_3_default_small_square_8bae296af3f629b07f0ef5e933549e7c103c5004.jpg",
                "createdAt": "1373426526",
                "firstname": "Juan",
                "lastname": "Pérez",
                "fanCount": 5,
                "splash": null,
                "sex": null,
                "username": "juan.perez",
                "url": "/app_dev.php/u/juan.perez/wall",
                "location": null,
                "canFriend": false
            },
            "content": "B",
            "likeCount": 0,
            "visitCount": 0,
            "commentCount": 0,
            "videocategory": 3,
            "genre_id": 2,
            "genreparent_id": 1,
            "weight": 3181,
            "duration": "00:02",
            "url": "/app_dev.php/tv/2689/y",
            "modalUrl": "/app_dev.php/modal/video/show/2689"
        },
        {
            "id": "2687",
            "slug": "tjb",
            "title": "Tjb",
            "image": "http://www2.fansworld.tv/uploads/media/default/0001/06/thumb_5239_default_huge_square_b611d7a5f37a29a5307791c9f089dd789cc453a1.jpg",
            "createdAt": "1374160421",
            "author": {
                "id": 2,
                "title": "Juan Pérez",
                "image": "http://www2.fansworld.tv/uploads/media/default/0001/01/thumb_3_default_small_square_8bae296af3f629b07f0ef5e933549e7c103c5004.jpg",
                "createdAt": "1373426526",
                "firstname": "Juan",
                "lastname": "Pérez",
                "fanCount": 5,
                "splash": null,
                "sex": null,
                "username": "juan.perez",
                "url": "/app_dev.php/u/juan.perez/wall",
                "location": null,
                "canFriend": false
            },
            "content": "Hjvh",
            "likeCount": 0,
            "visitCount": 0,
            "commentCount": 0,
            "videocategory": 3,
            "genre_id": 2,
            "genreparent_id": 1,
            "weight": 3181,
            "duration": "00:02",
            "url": "/app_dev.php/tv/2687/tjb",
            "modalUrl": "/app_dev.php/modal/video/show/2687"
        },
        {
            "id": "2675",
            "slug": "tuh",
            "title": "Tuh",
            "image": "http://www2.fansworld.tv/uploads/media/default/0001/06/thumb_5203_default_huge_square_b26958cd27c78d19c3967bd0d30a58244d5a00ae.jpg",
            "createdAt": "1373996442",
            "author": {
                "id": 2,
                "title": "Juan Pérez",
                "image": "http://www2.fansworld.tv/uploads/media/default/0001/01/thumb_3_default_small_square_8bae296af3f629b07f0ef5e933549e7c103c5004.jpg",
                "createdAt": "1373426526",
                "firstname": "Juan",
                "lastname": "Pérez",
                "fanCount": 5,
                "splash": null,
                "sex": null,
                "username": "juan.perez",
                "url": "/app_dev.php/u/juan.perez/wall",
                "location": null,
                "canFriend": false
            },
            "content": "Thu",
            "likeCount": 1,
            "visitCount": 2,
            "commentCount": 2,
            "videocategory": 1,
            "genre_id": 11,
            "genreparent_id": 8,
            "weight": 3181,
            "duration": "00:07",
            "url": "/app_dev.php/tv/2675/tuh",
            "modalUrl": "/app_dev.php/modal/video/show/2675"
        },
        {
            "id": "2671",
            "slug": "tru",
            "title": "Tru",
            "image": "http://www2.fansworld.tv/uploads/media/default/0001/06/thumb_5178_default_huge_square_73635281126f6a70b5a8a0f8e4a42d991d80f709.jpg",
            "createdAt": "1373922100",
            "author": {
                "id": 2,
                "title": "Juan Pérez",
                "image": "http://www2.fansworld.tv/uploads/media/default/0001/01/thumb_3_default_small_square_8bae296af3f629b07f0ef5e933549e7c103c5004.jpg",
                "createdAt": "1373426526",
                "firstname": "Juan",
                "lastname": "Pérez",
                "fanCount": 5,
                "splash": null,
                "sex": null,
                "username": "juan.perez",
                "url": "/app_dev.php/u/juan.perez/wall",
                "location": null,
                "canFriend": false
            },
            "content": "Tru",
            "likeCount": 0,
            "visitCount": 7,
            "commentCount": 0,
            "videocategory": 3,
            "genre_id": 2,
            "genreparent_id": 1,
            "weight": 3181,
            "duration": "00:06",
            "url": "/app_dev.php/tv/2671/tru",
            "modalUrl": "/app_dev.php/modal/video/show/2671"
        }
    ],
    "error": false
}

$("[data-filter-videos] button").on('click', function() {
    var type = $(this).attr('data-type');
    var params = {};
    var service = "_user_filtervideosajax";
    var i = 0;
    console.log("filter: " + type)
    params['type'] = type;
    $.ajax({
        url: Routing.generate(appLocale + service), 
        data: params
    })
    .then(function(response){
        console.log("response");
        console.log(response)
        if(typeof response.videos === 'object' && Object.keys(response.videos).length < 1) {
            $('.semantic-grid').empty();
        }
        function render_video(video) {
            $.when(templateHelper.htmlTemplate('video-home_element', video))
            .then(function(response){
                var $thumb = $(response).clone();
                $('.semantic-grid').append($thumb);
            });
        }
        for(i in response.videos) {
            if (response.videos.hasOwnProperty(i)) {
                var video = response.videos[i];
                render_video(video);
            }
        }
    });
});

function create_filters() {

    var type = $(this).attr('data-type');
    var entityType = $("[data-list]").attr('data-entity-type');
    var entityId = $("[data-list]").attr('data-entity-id');
    var criteria = 'popular';
    var params = {};
    var dataList = $("[data-list]").attr('data-list');
    var route = dataList + "_popular";
    route = Routing.generate(appLocale + '_' + route);

    var page = $("[data-list]").attr('data-page') || 1;
    var feedfilter = {
        'sort': criteria,
        'page': page,
        'entityId': entityId,
        'entityType': entityType
    };
    function render_video(video) {
        $.when(templateHelper.htmlTemplate('video-home_element', video))
        .then(function(response){
            var $thumb = $(response).clone();
            $('.semantic-grid').append($thumb);
        });
    }
    function addMoreThumbs(event) {
        $.ajax({
            url: Routing.generate(appLocale + '_' + dataList + "_popular"),
            data: feedfilter,
        })
        .then(function(response){
            var addMore = response.addMore;
            if(addMore) {
                $(".std-add-more").show();
            } else {
                $(".std-add-more").hide();
            }
            $(".std-add-more").removeClass('rotate');
            for(i in response.elements) {
                if (response.elements.hasOwnProperty(i)) {
                    var video = response.elements[i];
                    render_video(video);
                }
            }
        });
    }

    $('.std-add-more').on('click', function(event) {
        $(".std-add-more").addClass('rotate');
        page += 1;
        $("[data-list]").attr('data-page', page);
        console.log(page);
        addMoreThumbs(event);
    });


    $("[data-sort] .btn-group .btn").off()
    $("[data-sort] .btn-group .btn").on('click', function() {
        var type = $(this).attr('data-type');
        var entityType = $("[data-list]").attr('data-entity-type');
        var entityId = $("[data-list]").attr('data-entity-id');
        var criteria = 'popular';
        var params = {};
        var dataList = $("[data-list]").attr('data-list');
        var route = dataList + "_popular";

        $(this)
            .parent()
            .find('.active')
            .removeClass('active');
        $(this)
            .addClass('active');

        switch (type) {
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
        switch (criteria) {
            case 'popular':
                route = dataList + "_popular";
                break;
            case 'highlight':
                route = dataList + "_highlighted";
                break;
            case 'most-visited':
                route = dataList + "_visited";
                break;
            case 'most-visited-today':
                route = dataList + "_visited";
                //opts.today = true;
                break;
        }

        $('.semantic-grid').empty();
        $("[data-list]").attr('data-page', '1');
        route = Routing.generate(appLocale + '_' + route);
        $.ajax({
            url: route,
            data: feedfilter,
        })
        .then(function(response){

            var addMore = response.addMore;

            if(addMore) {
                $(".std-add-more").show();
            } else {
                $(".std-add-more").hide();
            }
            for(i in response.elements) {
                if (response.elements.hasOwnProperty(i)) {
                    var video = response.elements[i];
                    render_video(video);
                }
            }
        })
    });

}
create_filters()