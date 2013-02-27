// ver 10.1
var home = {};

home.init = function(){
    if($("header").hasClass('home-header')){
        home.toggleSections();
    }
    if($("section").hasClass('home-content')){
        if(isLoggedIn){
            home.filterSection.activityFeed();
            /*
            home.loadSection.activityFeed({}, function(){
                endless.init(1, function(){
                    var lastDate = $('section.home-content .content-container[data-type-tab="activityFeed"] .elements .element').last().attr('data-element-date');
                    home.loadSection.activityFeed({
                        'date': lastDate,
                        'filters': $("section.content-container[data-type-tab='activityFeed'] .tags .pull-left span.label.active").attr('data-feed-filter')
                    });
                });
            });
            */
            home.loadSection.activityFeed();
        }else{
            home.loadSection.enjoy();
        }
        console.log("calling toggleTabs")
        home.toggleTabs();
    }
};

home.toggleSections = function(){
    $(".home-header ul.sections li").on('click', function(){
        var category = $(this).attr('data-category-id');
        var activeCategory = $(".home-header ul.sections li.active").attr('data-category-id');

        $(".home-header .category-container div[data-category-id='" + activeCategory + "']").fadeOut('fast', function(){
            $(".home-header .category-container div[data-category-id='" + category + "']").hide().removeClass('hidden').fadeIn('fast');
        });

        $(".home-header ul.sections li").removeClass('active');
        $(this).addClass('active');
    });
};

home.toggleTabs = function(){

    if(isLoggedIn){
        $("section.home-content .content-container:not('[data-type-tab='activityFeed']')").hide();
    }else{
        $("section.home-content .legend:not('.active')").hide();
        $("section.home-content .content-container:not('[data-type-tab='enjoy']')").hide();
    }

    $(".home-header ul.tabs li").on('click', function(){
        var typeTab = $(this).attr('data-type-tab');
        console.log("tab clicked: " + typeTab)
        $(".home-header ul.tabs li").removeClass('active');
        $(this).addClass('active');

        $("section.home-content .legend").hide();
        $("section.home-content .content-container").hide();

        if(typeTab != 'enjoy'){
            if(!window['home']['loadedSection'][typeTab]){
                console.log("about to load section: " + typeTab)
                window['home']['loadSection'][typeTab]();
            }
        }

        /*
        switch(typeTab){
            case 'activityFeed':
                endless.init(1, function(){
                    var lastDate = $('section.home-content .content-container[data-type-tab="activityFeed"] #elements .post').last().attr('data-element-date');
                    home.loadSection.activityFeed({
                        'date': lastDate,
                        'filters': $("section.content-container[data-type-tab='activityFeed'] .tags .pull-left span.label.active").attr('data-feed-filter')
                    });
                });
                break;
            case 'popularFeed':
                endless.init(1, function(){
                    var lastDate = $('section.home-content .content-container[data-type-tab="popularFeed"] #elements .post').last().attr('data-element-date');
                    home.loadSection.popularFeed({
                        'date': lastDate
                    });
                });
                break;
            default:
                endless.stop();
        }
        */

        $('section.home-content .legend[data-type-tab="' + typeTab + '"]').show();
        $("section.home-content .content-container[data-type-tab='" + typeTab + "']").show();

        var $contentContainer = $("section.home-content .content-container[data-type-tab='" + typeTab + "']");
        var $container = $contentContainer.find('#elements');
        $container.attr('data-feed-source', 'home_ajaxactivityfeed');
        if($contentContainer.hasClass('isIso')) {
            $container.data('fwGalerizer').resize()
        }
    });
};

home.filterSection = {};
home.filterSection.activityFeed = function(){
    $("section.content-container[data-type-tab='activityFeed'] .tags span.label").on('click', function(){
        if($(this).not('.active')){
            $(this).parent().find('.active').removeClass('active');
            $(this).addClass('active');
            $("section.elements").html('');
            home.loadSection.activityFeed({
                'filters': $(this).attr('data-feed-filter')
            });
        }
    });
};

home.loadSection = {};

home.loadedSection = {
    'enjoy': true,
    'follow': false,
    'connect': false,
    'participate': false,

    'activityFeed': false,
    'popularFeed': false
};

home.getMaxSections = function(container) {
    var $container = $(container)
    if($container.width() <= 600) {
        var cells = 1;
    } else if ($container.width() <= 800) {
        var cells = 2;
    } else if ($container.width() <= 1000) {
        var cells = 3;
    } else if ($container.width() <= 1200) {
        var cells = 4;
    } else if ($container.width() > 1200) {
       var cells = 5;
    }
    return cells;
}

home.loadSection.enjoy = function(params){

    if(typeof(params) == 'undefined'){
        params = {};
    }
    var $toAppendTrending = $('section.home-content .content-container[data-type-tab="enjoy"] .tags .pull-left');
    var $contentContainer = $('section.home-content .content-container[data-type-tab="enjoy"]');
    var $container = $contentContainer.find('#elements');
    $container.attr('data-feed-source', 'home_ajaxenjoy');
    if(!$contentContainer.hasClass('isIso')) {
        $container.fwGalerizer({
            endless: false,
            normalize: false,
            onDataReady: function(jsonData) {
                var i, outp = [];
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
                            'authorHref': video.author.url,
                            'authorImage': video.author.image
                    };
                };
                for(i in jsonData.trending){
                    var tag = jsonData.trending[i];

                    var elementToAppend = $('<span class="label"></span>');

                    elementToAppend.html(tag.title)
                    .attr('data-tag-id', tag.id)
                    .attr('data-tag-slug', tag.slug);

                    $toAppendTrending.append(elementToAppend);
                }
                for(i in jsonData.videos) {
                    if (jsonData.videos.hasOwnProperty(i)) {
                        outp.push(normalize(jsonData.videos[i]));
                    }
                }
                return outp;
            },
            onGallery: function() {
                $contentContainer.removeClass('loading');
            }
        });
        home.loadedSection.enjoy = true;
        $contentContainer.addClass('isIso');
    };
    return;
}

home.loadSection.enjoy22 = function(){

    var toAppendTrending = $('section.home-content .content-container[data-type-tab="enjoy"] .tags .pull-left');
    var toAppendVideos = $('section.home-content .content-container[data-type-tab="enjoy"] .elements');

    var $contentContainer = $('section.home-content .content-container[data-type-tab="enjoy"]');
    var $container = $contentContainer.find('#elements');


    if(!$contentContainer.hasClass('isIso'))
    {
        $container.isotope({
            itemSelector : '.item',
            resizable: false, // disable normal resizing
            masonry: {
                columnWidth: ($container.width() / home.getMaxSections($container)),
            }
        });
    }
    $(window).smartresize(function(){
        console.log($container.width())
      if($container.width() <= 600) {
        var cells = 1;
      } else if ($container.width() <= 800) {
        var cells = 2;
      } else if ($container.width() <= 1000) {
        var cells = 3;
      } else if ($container.width() <= 1200) {
        var cells = 4;
      } else if ($container.width() > 1200) {
        var cells = 5;
      }
      $container.find('.item').each(function(i, item){
        var $this = $(this);
        $this.css('width', ((100 / cells) - 2) + "%") // 2% margen entre los elementos
      });
      $container.isotope({
        // update columnWidth to a percentage of container width
        masonry: { columnWidth: $container.width() / cells }
      });
      $container.isotope('reLayout');
    });
    $contentContainer.addClass('isIso');
    toAppendVideos.addClass('loading');

    ajax.genericAction('home_ajaxenjoy', {}, function(r){

        var dummy = $('<div class="dummy"></div>');
        var $container = $contentContainer.find('#elements');

        for(var i in r.trending){
            var tag = r.trending[i];

            var elementToAppend = $('<span class="label"></span>');

            elementToAppend.html(tag.title)
            .attr('data-tag-id', tag.id)
            .attr('data-tag-slug', tag.slug);

            toAppendTrending.append(elementToAppend);
        }

        for(var i in r.videos){
            var video = r.videos[i];

            var loop = parseInt(i);
            loop++;
            if(r.videos.length == loop)  {
                var callback = function(){
                    toAppendVideos.removeClass('loading')
                    home.loadedSection.enjoy = true;
                    var post = dummy.find('.post');
                      if($container.width() <= 600) {
                        var cells = 1;
                      } else if ($container.width() <= 800) {
                        var cells = 2;
                      } else if ($container.width() <= 1000) {
                        var cells = 3;
                      } else if ($container.width() <= 1200) {
                        var cells = 4;
                      } else if ($container.width() > 1200) {
                        var cells = 5;
                      }
                    $.each(post, function(i, item) {
                        var $this = $(item);
                        $this.css('width', ((100 / cells) - 2) + "%")
                        $this.find('.image').load(function() {
                            $container.isotope('reLayout');
                        })
                        $container.append($(item)).isotope('appended', $(item));
                       });
                        $container.isotope('reLayout');
                    };
            }else{
                var callback = function(){};
            }

            var href = Routing.generate(appLocale + '_video_show', {
                'id': video.id,
                'slug': video.slug
            });

            var jsonData = {
                'type': 'video',
                'date': video.createdAt,
                'href': href,
                'image': video.image,
                'slug': video.slug,
                'title': video.title
            };

            if(video.author != null){
                jsonData['author'] = video.author.username;
                jsonData['authorHref'] = video.author.url;
                jsonData['authorImage'] = video.author.image;
            }
            templateHelper.renderTemplate('general-column_element', jsonData, dummy, false, callback);

        }
    }, function(e){
        error(e);
    });
};

home.loadSection.connect = function(){
    var toAppendElements = $('section.home-content .content-container[data-type-tab="connect"] .fans-list');

    toAppendElements.parent().addClass('loading');
    ajax.genericAction('home_ajaxconnect', {}, function(r){
        for(var i in r.fans){
            var fan = r.fans[i];

            var loop = parseInt(i);
            loop++;
            if(r.fans.length == loop)  {
                var callback = function(){
                    toAppendElements.removeClass('loading');
                    home.loadedSection.connect = true;
                };
            }else{
                var callback = function(){};
            }
            templateHelper.renderTemplate('fans-element', fan, toAppendElements.selector, false, callback);
            toAppendElements.parent().removeClass('loading');
        }
    }, function(e){
        error(e);
    });
};

home.loadSection.participate = function(){
    var toAppendElements = $('section.home-content .content-container[data-type-tab="participate"] .events-grid');

    toAppendElements.parent().addClass('loading');

    ajax.genericAction('home_ajaxparticipate', {}, function(r){
        for(var i in r.events){
            var event = r.events[i];
            var callback = function(){};

            templateHelper.renderTemplate('event-grid_element', event, toAppendElements.selector, false, callback);
        }
        toAppendElements.parent().removeClass('loading');
        home.loadedSection.participate = true;
    }, function(e){
        error(e)
    });
};

home.loadSection.activityFeed = function(params){

    if(typeof(params) == 'undefined'){
        params = {};
    }
    var $contentContainer = $('section.home-content .content-container[data-type-tab="activityFeed"]');
    var $container = $contentContainer.find('#elements');
    $container.attr('data-feed-source', 'home_ajaxpopularfeed');
    if(!$contentContainer.hasClass('isIso')) {
        $contentContainer.addClass('loading');
        $container.fwGalerizer({
            onEndless: function(plugin) {
                $contentContainer.addClass('loading');
                var lastDate = $('section.home-content .content-container[data-type-tab="activityFeed"] #elements .post').last().attr('data-element-date');
                plugin.options.feedfilter = {
                    'date': lastDate
                };
                console.log("new filter")
                console.log(plugin.options.feedfilter)
                return plugin.options.feedfilter;
            },
            onGallery: function() {
                $contentContainer.removeClass('loading');
            }
        })
        home.loadedSection.activityFeed = true;
        $contentContainer.addClass('isIso');
    };
    return;
}

home.loadSection.popularFeed = function(params){

    if(typeof(params) == 'undefined'){
        params = {};
    }
    var $contentContainer = $('section.home-content .content-container[data-type-tab="popularFeed"]');
    var $container = $contentContainer.find('#elements');
    $container.attr('data-feed-source', 'home_ajaxpopularfeed');
    if(!$contentContainer.hasClass('isIso')) {
        $contentContainer.addClass('loading');
        $container.fwGalerizer({
            onEndless: function(plugin) {
                $contentContainer.addClass('loading');
                var lastDate = $('section.home-content .content-container[data-type-tab="popularFeed"] #elements .post').last().attr('data-element-date');
                plugin.options.feedfilter = {
                    'date': lastDate
                };
                console.log("new filter")
                console.log(plugin.options.feedfilter)
                return plugin.options.feedfilter;
            },
            onGallery: function() {
                $contentContainer.removeClass('loading');
            }
        });
        home.loadedSection.popularFeed = true;
        $contentContainer.addClass('isIso');
    };
    return;
}

;$(document).ready(function(){
    home.init();
});