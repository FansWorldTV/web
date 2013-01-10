var home = {};

home.init = function(){
    if($("header").hasClass('home-header')){
        home.toggleSections();
    }
    if($("section").hasClass('home-content')){
        console.log(isLoggedIn);
        if(isLoggedIn){
            home.loadSection.activityFeed();
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
                    home.loadSection.activityFeed({'date':lastDate});
                });
                break;
            case 'popularFeed':
                endless.init(1, function(){
                    var lastDate = $('section.home-content .content-container[data-type-tab="activityFeed"] .elements .element').last().attr('data-element-date');
                    home.loadSection.popularFeed({'date': lastDate});
                });
                break;
            default:
                endless.stop();
        }
        
        $('section.home-content .legend[data-type-tab="' + typeTab + '"]').show();
        $("section.home-content .content-container[data-type-tab='" + typeTab + "']").show();
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
            $divContainer = $('<div class="element"></div>').attr('data-element-type', video.type);
            
            $image = $('<img />');
            $image.attr('src', video.image)
            .attr('alt', video.title);
                    
            $image = $('<a></a>').html($image);
            $image.attr('href', Routing.generate(appLocale + '_video_show', {
                'id': video.id, 
                'slug': video.slug
            }));
                    
            $titleAndUser = $('<div></div>');
            
            $title = $('<span></span>');
            $title.addClass('title');
            $title.html(video.title);
            
            if(video.author != null){
                $user = $('<a></a>');
                $user.addClass('user');
                $user.html(video.author.username);
                $user.attr('href', video.author.url);
            }
            
            $titleAndUser.addClass('title-and-user');
            $titleAndUser.append($title).append($user);
            
            $divContainer.append($image).append($titleAndUser);
            
            $playIcon = $("<i class='play-video'></i>");
            $divContainer.append($playIcon);
            
            toAppendVideos.append($divContainer);
        }
        toAppendVideos.removeClass('loading');
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

home.loadSection.activityFeed = function(params){
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
                    };
                }else{
                    var callback = function(){};
                }
            
                var href = Routing.generate(appLocale + '_' + element.type + '_show', {
                    'id': element.id, 
                    'slug': element.slug
                });
                templateHelper.renderTemplate('general-column_element', {
                    'type': element.type,
                    'date': element.created,
                    'href': href,
                    'image': element.image,
                    'slug': element.slug,
                    'title': element.title,
                    'author': element.author.username
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
                templateHelper.renderTemplate('general-column_element', {
                    'type': element.type,
                    'date': element.created,
                    'href': href,
                    'image': element.image,
                    'slug': element.slug,
                    'title': element.title,
                    'author': element.author.username
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