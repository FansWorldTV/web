var things = {};


things.init = function(){
    $("[data-filter-matchs] button").click(function(){
        $(".eventgrid-container").addClass('loading');
        $(".events-grid").html("");
        
        var type = $(this).attr('data-type');
        things.filter(type);
    });
    things.videos.init();
};

things.filter = function(type, callback){
    var params = {};
    params['type'] = type;
    
    ajax.genericAction('things_ajaxmatchs', params, function(r){
        $(".events-grid").html("");
        for(var i in r.events){
            var event = r.events[i];
            templateHelper.renderTemplate('event-grid_element', event, '.events-grid', function(){
                $("[data-added-element]").hide();
            });
        }
        $(".eventgrid-container").removeClass('loading');
        $("[data-added-element]").show();
    }, function(e){
        error(e);
    });
};

things.videos = {};
things.videos.init = function(){
    site.startMosaic($(".am-container"), {
        minw: 150, 
        margin: 4, 
        liquid: true, 
        minsize: false
    });
    
    $("[data-filter-videos] button").on('click', function(){
        $('.am-container').html("").addClass('loading');
        var type = $(this).attr('data-type');
        var params = {};
        params['type'] = type;
        ajax.genericAction('things_videosajax', params, function(r){
            if(!r.error){
                for(var i in r.videos){
                    var video = r.videos[i];
                    var jsonData = {};
                    jsonData['imgsrc'] = video.image;
                    jsonData['title'] = video.title;
                    jsonData['url'] = Routing.generate(appLocale + '_video_show', {
                        'id': video.id,
                        'slug': video.slug
                    });
                    templateHelper.renderTemplate('video-list_element', jsonData, '.am-container', false, function(){
                        $("[data-new-element]").imagesLoaded(function () {
                            $('.am-container').montage('add', $("[data-new-element]"));
                            $("[data-new-element]").removeAttr('data-new-element');
                        });
                    });
                }
                $('.am-container').removeClass('loading');
            }
        }, function(er){
            error(er);
        });
    });
};

$(document).ready(function(){
    things.init();
});