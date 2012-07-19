var albums = {
    pager: 2,
    
    init: function(){
        albums.get();  
    },
    
    get: function(){
        $(".loadmore.albums").click(function(){
            $(this).addClass('loading');
            var userid = $("#userid").val();
            ajax.getAlbumsAction(userid, albums.pager, function(r){
                if(r){
                    for(var i in r['albums']){
                        var ele = r['albums'][i];
                        var template = $("div#templates a").clone();
                        var href = Routing.generate(appLocale + '_album_show', {
                            'id':ele.id
                        });
                        template.attr('href', href);
                        template.append(ele.image);
                        template.find('span.title').prepend(ele.title);
                        template.find('span.title span.photos-quant').html(ele.countImages + ' fotos');
                    
                        $(".am-container.albums").append(template);
                    }
                    if(!r['gotMore']){
                        $(".loadmore.albums").hide();
                    }
                    albums.pager++;
                    $(".loadmore.albums").removeClass('loading');
                }
            });
        });
    }
};