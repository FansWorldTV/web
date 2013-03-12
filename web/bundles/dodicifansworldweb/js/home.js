// ver 10.1
var home = {};

home.init = function(){
    if($("header").hasClass('home-header')){
        home.toggleSections();
    }
    if($("section").hasClass('home-content')){
        if(isLoggedIn){
            home.filterSection.activityFeed();
            home.loadSection.activityFeed();
        } else {
            home.filterSection.enjoy();
            home.loadSection.enjoy();
        }
        home.toggleTabs();
    }
};

home.toggleSections = function(){
    $(".home-header ul.sections li").on('click', function(){
        var category = $(this).attr('data-category-id');
        var activeCategory = $(".home-header ul.sections li.active").attr('data-category-id');

        $(".home-header .category-container div[data-category-id='" + activeCategory + "']").fadeOut('500', function(){
            $(".home-header .category-container div[data-category-id='" + category + "']").hide().removeClass('hidden').fadeIn('500');
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

    var $contentContainer = $('section.home-content .content-container[data-type-tab="activityFeed"]');
    var $container = $contentContainer.find('#elements');

    $("section.content-container[data-type-tab='activityFeed'] .tags span.label").on('click', function(){
        if($(this).not('.active')){
            $(this).parent().find('.active').removeClass('active');
            $(this).addClass('active');

            $contentContainer.removeClass('isIso');
            $container.data('fwGalerizer').destroy();
            //var filterId = parseInt($(this).attr('data-feed-filter'));
            home.loadSection.activityFeed({
                'filters': $(this).attr('data-feed-filter')
            });
        }
    });
};

// TAG filters for enjoy (user not logged in)
// In homeController.php ajaxEnjoyAction() takes two parameters:
// @param: filter (can be 0 sort by weight or 1 sort only highlight (media owned by fw) )
// @param: tag (show videos associated with a tagid)
home.filterSection.enjoy = function(){
    var $contentContainer = $('section.home-content .content-container[data-type-tab="enjoy"]');
    var $container = $contentContainer.find('#elements');
    // Remove all listeners
    $("section.content-container[data-type-tab='enjoy'] .tags span.label").off();
    // Bind listeners
    var $filters = $("section.content-container[data-type-tab='enjoy'] .tags span.label").on('click', function(){
        if($(this).not('.active')){
            $(this).parent().find('.active').removeClass('active');
            $(this).addClass('active');
            $contentContainer.removeClass('isIso');
            $container.data('fwGalerizer').destroy();
            var tagId = parseInt($(this).attr('data-tag-id'));
            // Filter by weight or highlight
            if(isNaN(tagId)) {
                $('section.home-content .content-container[data-type-tab="enjoy"] .tags .pull-left').find('[data-tag-id]').remove();
                home.loadSection.enjoy({
                    'filters': $(this).attr('data-feed-filter')
                });
            } else {
                home.loadSection.enjoy({
                    'tag': tagId
                });
            }
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
            feedfilter: params,
            onDataReady: function(jsonData) {
                var i, outp = [];
                var normalize = function(video) {
                    var href = Routing.generate(appLocale + '_video_show', {
                        'id': video.id,
                        'slug': video.slug
                    });
                    var authorUrl = Routing.generate(appLocale + '_user_land', {
                        'username': video.author.username
                    });
                    var hrefModal = Routing.generate(appLocale + '_modal_media', {
                        'id': video.id,
                        'type': 'video'
                    });
                    return {
                            'id': video.id,
                            'type': 'video',
                            'date': video.createdAt,
                            'href': href,
                            'hrefModal': hrefModal,
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

                    var ids = [];
                    $('section.home-content .content-container[data-type-tab="enjoy"] .tags .pull-left')
                    .find('[data-tag-id]')
                    .each(function(el) {
                        //console.log($(this).attr('data-tag-id'))
                        ids.push($(this).attr('data-tag-id'));
                    });
                    // Prevent duplicates
                    if($.inArray(tag.id,ids) < 0) {
                        //$toAppendTrending.append(elementToAppend);
                        $(elementToAppend).hide().appendTo($toAppendTrending).fadeIn('slow');
                    }

                    // Bind listeners
                    home.filterSection.enjoy();
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
    console.log("params")
    console.log(params)
    var $contentContainer = $('section.home-content .content-container[data-type-tab="activityFeed"]');
    var $container = $contentContainer.find('#elements');
    $container.attr('data-feed-source', 'home_ajaxactivityfeed');
    if(!$contentContainer.hasClass('isIso')) {
        $contentContainer.addClass('loading');
        $container.fwGalerizer({
            feedfilter: params,
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