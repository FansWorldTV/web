var home = {};

home.init = function(){
    if($("header").hasClass('home-header')){
        home.toggleSections();
    }
    if($("section").hasClass('home-content')){
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
    
    var active = $(".home-header ul.tabs li.active");
    var dataType = active.attr('data-type-tab');
    var dataMethod = active.attr('data-method-ajax');
    
    var toAppendTrending = $('section.home-content .content-container[data-type-tab="' + dataType + '"] .tags .pull-left');
    var toAppendVideos = $('section.home-content .content-container[data-type-tab="' + dataType + '"] .am-container');
    
    ajax.genericAction(dataMethod, {}, function(r){
        
        for(var i in r.trending){
            var tag = r.trending[i];
            var elementToAppend = $('<span class="label"></span>');
            elementToAppend.html(tag.title)
            .attr('data-tag-id', tag.id)
            .attr('data-tag-slug', tag.slug);
                            
            console.log(elementToAppend);
            console.log(toAppendTrending);
            
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
                        minh: 50,
                        alternateHeight: true,
                        fillLastRow: true
                    });
                };
            }else{
                var callback = function(){};
            }
            
            templateHelper.renderTemplate('video-list_element', jsonData, toAppendVideos.selector, false, callback);
        }
    }, function(e){
        error(e); 
    });
    
    $(".home-header ul.tabs li").on('click', function(){
        var typeTab = $(this).attr('data-type-tab');
        $(".home-header ul.tabs li").removeClass('active');
        $(this).addClass('active');
        
        $("section.home-content .legend").hide();
        $('section.home-content .legend[data-type-tab="' + typeTab + '"]').show();
    });
    
};

home.giveMe

$(document).ready(function(){
    home.init(); 
});