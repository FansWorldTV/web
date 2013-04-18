var albums = {
    pager: 2,

    init: function(){
        console.log('CARGANDO ALBUMS');
        $('.isotope_container').empty()
        $('.isotope_container').attr('data-feed-source', 'photo_get');
        $('.isotope_container').fwGalerizer({
            normalize: false,
            feedfilter: {page: 1},
            onEndless: function( plugin ) {
                plugin.options.feedfilter.page += 1;
                return plugin.options.feedfilter;
            },
            onDataReady: function(jsonData) {
                var i, outp = [];
                var serialize = function(image) {
                    var href = Routing.generate(appLocale + '_photo_show', {
                        'id': image.id,
                        'slug': image.slug
                    });
                    var authorUrl = Routing.generate(appLocale + '_user_land', {
                        'username': image.author.username
                    });
                    var hrefModal = Routing.generate(appLocale + '_modal_media', {
                        'id': image.id,
                        'type': 'photo'
                    });
                    return {
                            'id': image.id,
                            'type': 'photo',
                            'date': image.createdAt,
                            'href': href,
                            'hrefModal': hrefModal,
                            'image': image.image,
                            'slug': image.slug,
                            'title': image.title,
                            'author': image.author.username,
                            'authorHref': image.author.url,
                            'authorImage': image.author.image
                    };
                }
                for(i in jsonData.images) {
                    if (jsonData.images.hasOwnProperty(i)) {
                        outp.push(serialize(jsonData.images[i]));
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