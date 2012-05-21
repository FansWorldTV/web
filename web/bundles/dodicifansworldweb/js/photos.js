var photos = {
    pager : 2,
    
    init: function(){
        photos.get();  
    },
    
    get : function(){
        $("a.loadmore.pictures").click(function(){
            $("a.loadmore.pictures").addClass('loading');
            var userid = $("#userid").val();
            ajax.getPhotosAction(userid, photos.pager, false, function(r){
                if(r){
                    for(var i in r['images']){
                        var ele = r['images'][i];
                        var template = $("#templates .album_cover").clone();
                        var href = Routing.generate(appLocale + '_photo_show', {
                            'id': ele.id,
                            'slug': ele.slug
                        });
                        template.find(".image").attr("href", href);
                        template.find(".image img").attr("src", ele.image);
                        template.find(".title").attr("href", href).html(ele.title);
                        template.find("span").html("<a href='" + href+ "'>" + ele.comments + " comentarios</a>");
                        template.find('a img').attr('src', ele.image);
                    
                        $("div.album_covers div.mask").append(template);
                    }
                    if(!r['gotMore']){
                        $("a.loadmore.pictures").hide();
                    }
                    photos.pager++;
                    $("a.loadmore.pictures").removeClass('loading');
                }
            });
        });
    }
};