var home = {};

home.init = function(){
    if($("header").hasClass('home-header')){
        home.toggleSections();
    }
    if($("section").hasClass('home-content')){
        home.loadSection.enjoy();
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
    $("section.home-content .legend:not('.active')").hide();
    
    $(".home-header ul.tabs li").on('click', function(){
        var typeTab = $(this).attr('data-type-tab');
        $(".home-header ul.tabs li").removeClass('active');
        $(this).addClass('active');
        
        $("section.home-content .legend").hide();
        $("section.home-content .content-container").hide();
        
        if(typeTab != 'enjoy'){
            window['home']['loadSection'][typeTab]();
        }
        
        $('section.home-content .legend[data-type-tab="' + typeTab + '"]').show();
        $("section.home-content .content-container[data-type-tab='" + typeTab + "']").show();
    });
    
};


home.loadSection = {};
home.loadSection.enjoy = function(){
    var toAppendTrending = $('section.home-content .content-container[data-type-tab="enjoy"] .tags .pull-left');
    var toAppendVideos = $('section.home-content .content-container[data-type-tab="enjoy"] .am-container');
    
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
            var jsonData = {};
            jsonData['imgsrc'] = video.image;
            jsonData['title'] = video.title;
            jsonData['url'] = Routing.generate(appLocale + '_video_show', {
                'id': video.id,
                'slug': video.slug
            });     
            
            var loop = parseInt(i);
            loop++;
            
            if(r.videos.length == loop)  {
                var callback = function(){
                    toAppendVideos.montage({
                        liquid: true,
                        margin: 3,
                        minw: 150,
                        minh: 250,
                        fillLastRow: true
                    });
                    
                    toAppendVideos.removeClass('hidden');
                };
            }else{
                var callback = function(){};
            }
            
            templateHelper.renderTemplate('video-list_element', jsonData, toAppendVideos.selector, false, callback);
        }
    }, function(e){
        error(e); 
    });
};

home.loadSection.follow = function(){
    var toAppendElements = $('section.home-content .content-container[data-type-tab="follow"] #elements');
    
    toAppendElements.addClass('loading');
    
    ajax.genericAction('home_ajaxfollow', {}, function(r){
        for(var i in r.elements){
            var element = r.elements[i];
            console.log(element);
            
            $image = $('<img />');
            $image.attr('src', element.element.image)
                    .attr('alt', element.element.title);
            
            console.log($image);
            
            toAppendElements.append($image);
            /*switch($element.type){
                case 'photo':
                    break;
                case 'video':
                    break;
            }*/
        }
        toAppendElements.removeClass('loading');
    }, function(e){
        error(e); 
    });
};

home.loadSection.connect = function(){
    
    };
    
home.loadSection.participate = function(){
        
    };

$(document).ready(function(){
    home.init(); 
});