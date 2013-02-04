var home = {};

home.init = function(){
    if($("header").hasClass('home-header')){
        home.toggleSections();
    }
    if($("section").hasClass('home-content')){
        if(isLoggedIn){
            home.filterSection.activityFeed();
            home.loadSection.activityFeed({}, function(){
                endless.init(1, function(){
                    var lastDate = $('section.home-content .content-container[data-type-tab="activityFeed"] .elements .element').last().attr('data-element-date');
                    home.loadSection.activityFeed({
                        'date': lastDate,
                        'filters': $("section.content-container[data-type-tab='activityFeed'] .tags .pull-left span.label.active").attr('data-feed-filter')
                    });
                });
            });
        }else{
            home.loadSection.enjoy();
        }
        home.toggleTabs();
    }
};

home.toggleSections = function(){
    $(".home-header ul.sections li").on('click', function(){
        var category = $(this).attr('data-category-id');

        $(".home-header .thumb-container span").addClass('hidden');
        $(".home-header .content-container div").addClass('hidden');

        $(".home-header .content-container div[data-category-id='" + category + "']").removeClass('hidden');
        $(".home-header .thumb-container span[data-category-id='" + category + "']").removeClass('hidden');

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
        $(".home-header ul.tabs li").removeClass('active');
        $(this).addClass('active');

        $("section.home-content .legend").hide();
        $("section.home-content .content-container").hide();

        if(typeTab != 'enjoy'){
            if(!window['home']['loadedSection'][typeTab]){
                window['home']['loadSection'][typeTab]();
            }
        }


        switch(typeTab){
            case 'activityFeed':
                endless.init(1, function(){
                    var lastDate = $('section.home-content .content-container[data-type-tab="activityFeed"] .elements .element').last().attr('data-element-date');
                    home.loadSection.activityFeed({
                        'date': lastDate,
                        'filters': $("section.content-container[data-type-tab='activityFeed'] .tags .pull-left span.label.active").attr('data-feed-filter')
                    });
                });
                break;
            case 'popularFeed':
                endless.init(1, function(){
                    var lastDate = $('section.home-content .content-container[data-type-tab="activityFeed"] .elements .element').last().attr('data-element-date');
                    home.loadSection.popularFeed({
                        'date': lastDate
                    });
                });
                break;
            default:
                endless.stop();
        }

        $('section.home-content .legend[data-type-tab="' + typeTab + '"]').show();
        $("section.home-content .content-container[data-type-tab='" + typeTab + "']").show();
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

home.loadSection.enjoy = function(){
    var toAppendTrending = $('section.home-content .content-container[data-type-tab="enjoy"] .tags .pull-left');
    var toAppendVideos = $('section.home-content .content-container[data-type-tab="enjoy"] .elements');

    toAppendVideos.addClass('loading');

    ajax.genericAction('home_ajaxenjoy', {}, function(r){

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
                    toAppendVideos.removeClass('loading');
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

            templateHelper.renderTemplate('general-column_element', jsonData, toAppendVideos.selector, false, callback);

        }
    }, function(e){
        error(e);
    });
};

home.loadSection.connect = function(){
    var toAppendElements = $('section.home-content .content-container[data-type-tab="connect"] .fans-list');

    toAppendElements.addClass('loading');

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

home.loadSection.activityFeed = function(params, funcCallback){
    if(typeof(params) == 'undefined'){
        params = {};
    }

    var $contentContainer = $('section.home-content .content-container[data-type-tab="activityFeed"]');

    $contentContainer.addClass('loading');

    ajax.genericAction('home_ajaxactivityfeed', params, function(r){
        if(r.length > 0){
            for(var i in r){
                var element = r[i];

                var loop = parseInt(i);
                loop++;
                if(r.length == loop)  {
                    var callback = function(){
                        $contentContainer.removeClass('loading');
                        home.loadedSection.activityFeed = true;
                        funcCallback();
                    };
                }else{
                    var callback = function(){};
                }

                var href = Routing.generate(appLocale + '_' + element.type + '_show', {
                    'id': element.id,
                    'slug': element.slug
                });

                var authorUrl = Routing.generate(appLocale + '_user_wall', {
                    'username': element.author.username
                });

                templateHelper.renderTemplate('general-column_element', {
                    'type': element.type,
                    'date': element.created,
                    'href': href,
                    'image': element.image,
                    'slug': element.slug,
                    'title': element.title,
                    'author': element.author.username,
                    'authorHref': authorUrl,
                    'authorImage': element.author.image
                }, $contentContainer.find('.elements').selector, false, callback);
            }
        }else{
            $contentContainer.removeClass('loading');
            endless.stop();
        }
    }, function(e){
        error(e);
    });
};

home.loadSection.popularFeed = function(params){
    if(typeof(params) == 'undefined'){
        params = {};
    }

    var $contentContainer = $('section.home-content .content-container[data-type-tab="popularFeed"]');
        if(!$contentContainer.hasClass('isIso'))
    {
        var $container = $contentContainer.find('#elements');
        for(var i = 0; i < 10; i++) {
            addElem()
        }
        $container.isotope({
            itemSelector : '.item'
        });
    }
    else {
        for(var i = 0; i < 10; i++) {
            addElem()
        }
    }
  function addElem() {

      var img = new Array()
      img[img.length] = "http://media-cache-ec5.pinterest.com/550/92/02/d7/9202d7e9c09f31d1e8df612b713e4388.jpg";
      img[img.length] = "http://media-cache-lt0.pinterest.com/550/71/7e/95/717e958585ade66ff90d9d12937e7a2a.jpg";
      img[img.length] = "http://media-cache-ec4.pinterest.com/550/22/97/39/2297397069aca387fef377d35426ee76.jpg";
      img[img.length] = "http://media-cache-ec3.pinterest.com/550/0f/a1/d8/0fa1d8ba0cb7c082d26441b7b27b0c1c.jpg";
      img[img.length] = "http://media-cache-ec4.pinterest.com/550/01/41/cd/0141cdec77c25d8eb4f09a5b3c03ef24.jpg";
      img[img.length] = "http://media-cache-lt0.pinterest.com/550/45/d7/7c/45d77c0540edd46218a1d46efa3fde9c.jpg";
      img[img.length] = "http://media-cache-ec3.pinterest.com/550/8e/36/fd/8e36fd1da721d4d22a17029762f01775.jpg";

     $posts = $('#elements');
      var post = {};
      post.id = (Math.floor(Math.random()*10000));
      a = $('<div id="' + post.id  + '" class="post item"><div class="well"><h4><a href="#" target="_blank">XXXX</a></h4><div class="info"><span class="badge">1234</span><span class="badge">reddittitit</span></div></div></div>') //$('#post').tmpl({id: post.id, title: "hello", score: post.id, subreddit: 'rrr'})
      $posts.append(a).isotope('appended', a)


      console.log(img[(Math.floor(Math.random()*img.length))])
      $('<img class="image">').attr('src', img[(Math.floor(Math.random()*img.length))]).wrap('<div class="image">').load(function(){
              $(this).appendTo('#' + post.id + ' div.well').slideDown(function(){
              $posts.isotope('reLayout');
            });
      });

}
    //$contentContainer.find('.elements').isotope('reLayout');
    $contentContainer.addClass('isIso');
    return;
    $contentContainer.addClass('loading');

    ajax.genericAction('home_ajaxpopularfeed', params, function(r){
        if(r.length > 0){
            for(var i in r){
                var element = r[i];
                var loop = parseInt(i);
                loop++;
                if(r.length == loop)  {
                    var callback = function(){
                        $contentContainer.removeClass('loading');
                        home.loadedSection.activityFeed = true;
                    };
                }else{
                    var callback = function(){};
                }

                var href = Routing.generate(appLocale + '_' + element.type + '_show', {
                    'id': element.id,
                    'slug': element.slug
                });

                var authorUrl = Routing.generate(appLocale + '_user_wall', {
                    'username': element.author.username
                });

                templateHelper.renderTemplate('general-column_element', {
                    'type': element.type,
                    'date': element.created,
                    'href': href,
                    'image': element.image,
                    'slug': element.slug,
                    'title': element.title,
                    'author': element.author.username,
                    'authorHref': authorUrl,
                    'authorImage': element.author.image
                }, $contentContainer.find('.elements').selector, false, callback);
            }
        }else{
            $contentContainer.removeClass('loading');
            endless.stop();
        }
    }, function(e){
        error(e);
    });
};

$(document).ready(function(){
    home.init();
});