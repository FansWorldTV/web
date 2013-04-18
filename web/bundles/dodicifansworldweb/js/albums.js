var albums = {
    pager: 2,

    init: function(){
        $('.isotope_container').empty()
        var feed = $('.isotope_container').attr('data-feed-source');
        var albumId = $('.isotope_container').attr('data-album-id');
        if(!albumId) {
            // Esto no es un album de fotos retornar
            return;
        }
        $('.isotope_container').fwGalerizer({
            normalize: true,
            feedfilter: {id: albumId, page: 1},
            onEndless: function( plugin ) {
                plugin.options.feedfilter.page += 1;
                return plugin.options.feedfilter;
            },
            onDataReady: function(jsonData) {
                var i, outp = [];
                var serialize = function(photo) {
                    var href = Routing.generate(appLocale + '_photo_show', {
                        'id': photo.id,
                        'slug': photo.slug
                    });
                    var authorUrl = Routing.generate(appLocale + '_user_land', {
                        'username': photo.author.username
                    });
                    var hrefModal = Routing.generate(appLocale + '_modal_media', {
                        'id': photo.id,
                        'type': 'photo'
                    });
                    return {
                            'id': photo.id,
                            'type': 'photo',
                            'date': photo.createdAt,
                            'href': href,
                            'hrefModal': hrefModal,
                            'image': photo.image,
                            'slug': photo.slug,
                            'title': photo.title,
                            'author': photo.author.username,
                            'authorHref': photo.author.url,
                            'authorImage': photo.author.image
                    };
                }
                for(i in jsonData.photos) {
                    if (jsonData.photos.hasOwnProperty(i)) {
                        outp.push(serialize(jsonData.photos[i]));
                    }
                }
                return outp;
            },
            onGallery: function() {
                $('.isotope_container').removeClass('loading');
            }
        });

        return;
        site.startMosaic($(".am-container.albums"), {
            minw: 150,
            margin: 0,
            liquid: true,
            minsize: false
        });

        albums.get();
        $(".relatedTags ul.tags").hide();
        $(".showOrHideTags").click(function(){
            $('ul.tags').toggle('blind');
        });
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