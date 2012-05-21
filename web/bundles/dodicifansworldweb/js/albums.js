var albums = {
    pager: 2,
    
    init: function(){
        albums.get();  
    },
    
    get: function(){
        $("a.loadmore.albums").click(function(){
            $(this).addClass('loading');
            var userid = $("#userid").val();
            ajax.getAlbumsAction(userid, albums.pager, function(r){
                if(r){
                    for(var i in r['albums']){
                        var ele = r['albums'][i];
                        var template = $("div#templates div.album_cover").clone();
                        var href = Routing.generate(appLocale + '_album_show', {
                            'id':ele.id
                        });
                        template.find(".image").attr("href", href);
                        template.find(".image img").attr("src", ele.image);
                        template.find(".title").attr("href", href).html(ele.title);
                        template.find("span").html(ele.countImages + " imágenes - <a href='" + Routing.generate(appLocale + '_album_show', {
                            'id':ele.id
                        }) + "'>" + ele.comments + " comentarios</a>");
                    
                        $("div.album_covers div.mask").append(template);
                    }
                    if(!r['gotMore']){
                        $("a.loadmore.albums").hide();
                    }
                    albums.pager++;
                    $("a.loadmore.albums").removeClass('loading');
                }
            });
        });
    }
};