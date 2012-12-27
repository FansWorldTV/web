var things = {};
things.matchs = {};
things.photos = {};
things.videos = {};
things.albums = {};
things.fans = {};

things.init = function(){
    things.matchs.init();
    things.videos.init();
    things.photos.init();
    
    if($(".my-things").hasClass('fans')) {
        things.fans.init();
    }
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
    site.startMosaic($(".videos-container .am-container"), {
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
    things.photos.filter(0);
    $(".controllers-section .btn-group button[data-type]").on('click', function(){
        var type = $(this).attr('data-type');
        things.photos.filter(type);
    });
    $("button.loadmore[data-entity-type='album']").click(function(){
        things.albums.page++;
        things.albums.addMore(0);
    });
};

things.photos.addMore = function(type, destiny){
    ajax.genericAction('things_photosajax', {
        'type': type, 
        'page': things.photos.page
    }, function(r){
        for(var i in r.photos){
            var photo = r.photos[i];
            templateHelper.renderTemplate('search-photo', photo, destiny, false, function(){
                $("[data-new-element]").imagesLoaded(function () {
                    $(destiny).montage('add', $("[data-new-element]"));
                    $("[data-new-element]").removeAttr('data-new-element');
                });
            })
        }
        
        if(!r.viewMorePhotos){
            endless.init(1, function(){});
        }
    }, function(e){
        error(e);
    });
};

things.albums.addMore = function(type){
    ajax.genericAction('things_photosajax', {
        'page': things.albums.page, 
        'type': type
    }, function(r){
        for(var i in r.albums){
            var album = r.albums[i];
            templateHelper.renderTemplate('photo-album', album, "[data-my-photos] .am-container.albums", false, function(){
                $("[data-new-element]").imagesLoaded(function () {
                    $('.am-container.albums').montage('add', $("[data-new-element]"));
                    $("[data-new-element]").removeAttr('data-new-element');
                });
            });
        }
        if(!r.viewMoreAlbums){
            $("button.loadmore[data-entity-type='album']").remove();
        }
    }, function(e){
        error(e);
    });
};

things.photos.filter = function(type){
    things.photos.page = 1;
    things.albums.page = 1;
        
    ajax.genericAction('things_photosajax', {
        'type': type, 
        'page': 1
    }, function(r){
        if(type != 0){
            var $containerMontage = $("[data-tagged-and-activity] [data-montage-container][data-type='photos']");
            $("[data-my-photos]").hide();
            $("[data-tagged-and-activity]").show();
            $containerMontage.html("");
                
            for(var i in r.photos){
                var photo = r.photos[i];
                templateHelper.renderTemplate("search-photo", photo, "[data-tagged-and-activity] [data-montage-container][data-type='photos']", false, function(){
                    $containerMontage.find('img').attr('width', '250');
                    $containerMontage.montage({
                        minw: 200, 
                        alternateHeight : true,
                        fillLastRow : true
                    });
                    var $newImages = $("[data-new-element]");
                    $newImages.imagesLoaded(function () {
                        $containerMontage.montage('add', $newImages);
                        $newImages.removeAttr('data-new-element');
                    });
                });
            }
            $("[data-photo-length]").html(r.photosTotalCount);
                
            if(r.viewMorePhotos){
                endless.init(10, function(){
                    things.photos.page++;
                    things.photos.addMore(type, "[data-tagged-and-activity] [data-montage-container][data-type='photos']");
                });
            }else{
                endless.init(1, function(){});
            }
        }else{
            $("[data-tagged-and-activity]").hide();
            $("[data-my-photos]").show();
            $containerMontage = $("[data-my-photos] .am-container.photos");
            $containerMontage.data('montage', null);
            $containerMontage.html("");
                
            for(var i in r.photos){
                var photo = r.photos[i];
                templateHelper.renderTemplate("search-photo", photo, "[data-my-photos] .am-container.photos", false, function(){
                    site.startMosaic($containerMontage, {
                        minw: 150, 
                        margin: 4, 
                        liquid: true, 
                        minsize: false
                    });
                    var $newImages = $("[data-new-element]");
                    $newImages.imagesLoaded(function () {
                        $containerMontage.montage('add', $newImages);
                        $newImages.removeAttr('data-new-element');
                    });
                });
            }
                
            if(r.viewMoreAlbums){
            //$("[data-my-photos] .am-container.albums").parent().append('<button class="loadmore" data-entity-type="albums">ver más</button>');
            /*   $("button.loadmore[data-entity-type='album']").click(function(){
                        things.albums.page++;
                        things.albums.addMore(0);
                    });*/
            }else{
                $("button.loadmore[data-entity-type='album']").remove();
            }
            if(r.viewMorePhotos){
                console.log('endless');
                endless.init(10, function(){
                    console.log('endless');
                    things.photos.page++;
                    things.photos.addMore(0, "[data-my-photos] .am-container.photos");
                });
            }else{
                endless.init(1, function(){});
            }
        }
    }, function(e){
        error(e);
    });
};

things.fans.filter = "0";
things.fans.direction = "0";
things.fans.page = 1;

things.fans.init = function(){
    $("div.btn-group[data-filter-fans] ul li").on('click', function(){
        things.fans.direction = $(this).attr('data-type');
        $("div.btn-group[data-filter-fans] ul li.active").removeClass('active');
        $(this).addClass('active');
        things.fans.page = 1;
        $("div.fans-list").html("");
    });
    
    $("div.btn-group[data-type-follow] button").on('click', function(){
        things.fans.page = 1;
        things.fans.filter = $(this).attr('data-type');
        $("div.fans-list").html("");
    });
    
    if($("input[data-add-more]").length>0){
        endless.init(10, function(){
            things.fans.addMore();
        });
        things.fans.addMore();
    }
};


things.fans.doFilter = function(){
    ajax.genericAction('things_ajaxfans', {
        'direction': things.fans.direction, 
        'filter': things.fans.filter,
        'page': things.fans.page
    }, function(r){
        for(var i in r.fans){
            var fan = r.fans[i];
            templateHelper.renderTemplate('fans-element', fan, 'div.fans-list', false, function(){});
        }
        
        if(r.addMore){
            endless.init(10, function(){
                things.fans.addMore();
            });
        }
    }, function(e){
        error(e);
    });
};

things.fans.addMore = function(){
    endless.stop();
    things.fans.page++;
    ajax.genericAction('things_ajaxfans', {
        'direction': things.fans.direction,
        'filter': things.fans.filter,
        'page': things.fans.page
    }, function(r){
        for(var i in r.fans){
            var fan = r.fans[i];
            templateHelper.renderTemplate('fans-element', fan, 'div.fans-list', false, function(){});
        }
        if(r.addMore){
            endless.resume();
        }
    }, function(e){
        
        });
};

$(document).ready(function(){
    things.init();
});