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

$(document).ready(function () {
    console.log("making filter buttons")
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
});

function create_filters() {

    var type = $(this).attr('data-type');
    var entityType = $("[data-list]").attr('data-entity-type');
    var entityId = $("[data-list]").attr('data-entity-id');
    var criteria = 'popular';
    var params = {};
    var dataList = $("[data-list]").attr('data-list') || 'video';
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

    $('[data-list="video"] > .std-add-more').on('click', function(event) {
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
$(document).ready(function () {
    create_filters()
});