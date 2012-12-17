var things = {};
things.matchs = {};
things.photos = {};
things.videos = {};
things.albums = {};

things.init = function(){
    things.matchs.init();
    things.videos.init();
    things.photos.init();
};


things.matchs.init = function(){
    $("[data-filter-matchs] button").click(function(){
        $(".eventgrid-container").addClass('loading');
        $(".events-grid").html("");
        
        var type = $(this).attr('data-type');
        things.matchs.filter(type);
    });
};
things.matchs.filter = function(type){
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

things.photos.page = 1;
things.albums.page = 1;
things.photos.init = function(){
    $("[data-tagged-and-activity]").hide();
    things.photos.filter();
};

things.photos.filter = function(){
    $(".controllers-section .btn-group button[data-type]").on('click', function(){
        things.photos.page = 1;
        things.albums.page = 1;
        type = $(this).attr('data-type');
        
        ajax.genericAction('things_photosajax', {
            'type': type, 
            'page': 1
        }, function(r){
            if(type != 0){
                $("[data-my-photos]").hide(function(){
                    $("[data-tagged-and-activity]").show();
                });
                $('[data-tagged-and-activity] .am-container.photos').html("");
                for(var i in r.photos){
                    var photo = r.photos[i];
                    templateHelper.renderTemplate("search-photo", photo, "[data-tagged-and-activity] .am-container.photos", false, function(){
                        $("[data-tagged-and-activity] .am-container.photos img").attr('width', '');
                    });
                }
                $("[data-photo-length]").html(r.photosTotalCount);
                
                if(r.viewMorePhotos){
                    endless.init(10, function(){
                        things.photos.page++;
                        ajax.genericAction('things_photosajax', {
                            'type': type, 
                            'page': things.photos.page
                        }, function(r){
                            for(var i in r.photos){
                                var photo = r.photos[i];
                                templateHelper.renderTemplate('search-photo', photo, "[data-my-photos] .am-container.photos", false, function(){
                                    $("[data-new-element]").imagesLoaded(function () {
                                        $('.am-container').montage('add', $("[data-new-element]"));
                                        $("[data-new-element]").removeAttr('data-new-element');
                                    });
                                })
                            }
                        }, function(e){
                            error(e);
                        });
                    });
                }else{
                    endless.init(1, function(){});
                }
            }else{
                $("[data-tagged-and-activity]").hide(function(){
                    $("[data-my-photos]").show();
                });
                
                if(r.viewMoreAlbums){
                    $("[data-my-photos] .am-container.albums").parent().append('<button class="loadmore albums">ver más</button>');
                    $("button.loadmore.albums").click(function(){
                        things.albums.page++;
                        ajax.genericAction('things_photosajax', {
                            'page': things.albums.page, 
                            'type': 0
                        }, function(r){
                            for(var i in r.albums){
                                var album = r.albums[i];
                                templateHelper.renderTemplate('photo-album', album, "[data-my-photos] .am-container.albums", false, function(){
                                    $("[data-new-element]").imagesLoaded(function () {
                                        $('.am-container').montage('add', $("[data-new-element]"));
                                        $("[data-new-element]").removeAttr('data-new-element');
                                    });
                                });
                            }
                        }, function(e){
                            error(e);
                        });
                    });
                }else{
                    $("[data-my-photos] .am-container.albums .loadmore.albums").remove();
                }
                if(r.viewMorePhotos){
                    endless.init(10, function(){
                        things.photos.page++;
                        ajax.genericAction('things_photosajax', {
                            'type': 0, 
                            'page': things.photos.page
                        }, function(r){
                            for(var i in r.photos){
                                var photo = r.photos[i];
                                templateHelper.renderTemplate('search-photo', photo, "[data-my-photos] .am-container.photos", false, function(){
                                    $("[data-new-element]").imagesLoaded(function () {
                                        $('.am-container').montage('add', $("[data-new-element]"));
                                        $("[data-new-element]").removeAttr('data-new-element');
                                    });
                                })
                            }
                        }, function(e){
                            error(e);
                        });
                    });
                }else{
                    endless.init(1, function(){});
                }
            }
        }, function(e){
            error(e);
        });
    });
};

$(document).ready(function(){
    things.init();
});