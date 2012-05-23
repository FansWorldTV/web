var videos = {
    pager: 1,
    
    init: function(){
        videos.search();
        videos.addMore();
        videos.searchByCategory.search();
        videos.searchByCategory.addMore();
        videos.searchByTags.addMore();
        videos.searchMyVideos.addMore();
        videos.usersVideos.addMore();
    },
    
    addSearchContent: function(template, video, videoUrl){
        template.find('h2 a').attr('href', videoUrl);
        template.find('h2 a').html(video.title);
                      
        if(video.image){
            template.find('a.tmpVideo').attr('href', videoUrl);
            template.find('a.tmpVideo img').attr('src', video.image).attr('alt', video.title).attr('title', video.title);
        }else{
            template.find('a.tmpVideo').remove();
        }
                        
        template.find('.tmpContent').html(video.content + "...");
        template.find('span a.user').attr('href', Routing.generate(appLocale + '_user_wall', {
            'username': video.author.username
        }));
        if(video.author.avatar){
            template.find('span a.user').html('<img src="'+video.author.avatar+'" />'+video.author.name);
        }else{
            template.find('span a.user').html(video.author.name);
        }
        template.find('span.timeago').attr('data-time', video.date).html(video.date);

        if(video.tags.length>0){
            for(var j in video.tags){
                var tag = video.tags[j];
                template.find('.tags').append("<li><a href='" + Routing.generate(appLocale + '_video_tags', {
                    'slug':tag.slug
                }) + "'>" + tag.title + "</a></li>");
            }
        }else{
            template.find('.hasTags').hide();
        }
                        
        $("div.cont ul.videos").append(template);
                        
        site.parseTimes();
    },
    
    addMore: function(){
        $("#addMore.videos:not('.loading')").live('click', function(){
            if(videos.pager==1){
                videos.pager++;
            }
            
            var self = $(this);
            self.addClass('loading');
            
            ajax.videosSearchAction({
                'page': videos.pager
            }, function(response){
                if(response){
                    if(!response.addMore){
                        self.remove();
                    }
                    for(var i in response.videos){
                        var video = response.videos[i];
                        var template = $("#templates ul.videos li").clone();
                        var videoUrl = Routing.generate(appLocale + '_video_show', {
                            'id':video.id, 
                            'slug': video.slug
                        });
                        videos.addSearchContent(template, video, videoUrl);
                    }
                    videos.pager++;
                }
                self.removeClass('loading');
            }, function(text){
                error(text);
                self.removeClass('loading');
            });
        });
    },
    
    search: function(){
        $(".search.videos #formSearch:not('.loading')").live('submit', function(){
            $("div.cont ul.videos li").remove();
            var query = $("#query").val();
            var self = $(this);
            
            self.addClass('loading');
            videos.pager = 1;
            ajax.videosSearchAction({
                'query': query, 
                'page': 1
            }, function(response){
                if(response){
                    if(response.addMore){
                        $("div.cont").append('<a href="#" id="addMore" class="loadmore videos marginTop10">Agregar Más</a>');
                    }
                    for(var i in response.videos){
                        var video = response.videos[i];
                        var template = $("#templates ul.videos li").clone();
                        var videoUrl = Routing.generate(appLocale + '_video_show', {
                            'id':video.id, 
                            'slug': video.slug
                        });
                        videos.addSearchContent(template, video, videoUrl);
                    }
                    videos.pager++;
                }
                self.removeClass('loading');
            }, function(){});
            
            return false;
        });
    },
    
    usersVideos: {
        addMore: function(){
            $("#addMore.userVideos").live('click', function(){
                var self = $(this);
                var query = $("input.video-search-input").val();
                query = query == "" ? null : query;
                ajax.usersVideosAction({
                    'query': query,
                    'page': videos.pager
                }, function(response){
                    if(response){
                        if(!response.addMore){
                            self.remove();
                        }
                        for(var i in response.videos){
                            var video = response.videos[i];
                            $("ul.user-videos").append(video);
                        }
                        videos.pager++;
                    }
                }, function(error){
                    console.error(error);
                });
                return false;
            });
        }
    },
    
    searchByCategory: {
        addMore: function(){
            if($("#categoryId").length > 0){
                $("#addMore.videosByCategory:not('.loading')").live('click', function(){
                    if(videos.pager==1){
                        videos.pager++;
                    }
                    var self = $(this);
                    self.addClass('loading');
            
                    ajax.videoCategoryAction({
                        'category': $("#categoryId").val(),
                        'page': videos.pager,
                        'query': $("input.video-search-input").val() == "" ? null: $("input.video-search-input").val()
                    }, 
                    function(response){
                        if(response){
                            if(!response.addMore){
                                self.remove();
                            }
                            for(var i in response.vids){
                                var vid = response.vids[i];
                                $("ul.user-videos").append(vid.view);
                            }
                            videos.pager++;
                        }
                        self.removeClass('loading');
                    }, 
                    function(response){
                        console.error(response);
                        self.removeClass('loading');
                    });
                
                    return false;
                })
            }
        },
        search: function(){
            if($("#categoryId").length > 0){
                $("#video-search-form:not('.loading')").live('submit', function(){
                    $("div.cont ul.user-videos li").remove();
                    var query = $("input.video-search-input").val() == "" ? null: $("input.video-search-input").val();
                    var self = $(this);
            
                    self.addClass('loading');
                    videos.pager = 1;
                
                    ajax.videoCategoryAction({
                        'category': $("#categoryId").val(),
                        'page': videos.pager,
                        'query': query
                    }, 
                    function(response){
                        if(response){
                            if(response.addMore){
                                $("div.cont").append('<div class="morelink"><a id="addMore" class="videosByCategory" href="">Ver más</a></div>');
                            }else{
                                $("#addMore").remove();
                            }
                            if(response.vids){
                                for(var i in response.vids){
                                    var vid = response.vids[i];
                                    $("ul.user-videos").append(vid.view);
                                }
                                videos.pager++;
                            }
                        }
                        self.removeClass('loading');
                    }, 
                    function(response){
                        console.error(response);
                        self.removeClass('loading');
                    });
                
                    return false;
                });
            }
        }
    },
    
    searchByTags: {
        addMore: function(){
            $("#addMore.videosByTag:not('.loading')").live('click', function(){
                if(videos.pager==1){
                    videos.pager++;
                }
                var self = $(this);
                var tagId = $("a.current").attr('tagId');
                self.addClass('loading');
            
                ajax.searchByTagAction(tagId, videos.pager, function(response){
                    if(response){
                        if(!response.addMore){
                            self.remove();
                        }
                        for(var i in response.videos){
                            var video = response.videos[i];
                            var template = $("#templates ul.videos li").clone();
                            var videoUrl = Routing.generate(appLocale + '_video_show', {
                                'id':video.id, 
                                'slug': video.slug
                            });
                            videos.addSearchContent(template, video, videoUrl);
                        }
                        videos.pager++;
                    }
                    self.removeClass('loading');
                }, function(text){
                    error(text);
                    self.removeClass('loading');
                });
                return false;
            });
        }
    },
    
    searchMyVideos: {
        addMore: function(){
            $("#addMore.myVideos:not('.loading')").live('click', function(){
                if(videos.pager==1){
                    videos.pager++;
                }
                var self = $(this);
                var userid = $("#userId").val();
                self.addClass('loading');
            
                ajax.searchMyVideos(userid, videos.pager, function(response){
                    if(response){
                        if(!response.addMore){
                            self.remove();
                        }
                        for(var i in response.videos){
                            var video = response.videos[i];
                            var template = $("#templates ul.videos li").clone();
                            var videoUrl = Routing.generate(appLocale + '_video_show', {
                                'id':video.id, 
                                'slug': video.slug
                            });
                            videos.addSearchContent(template, video, videoUrl);
                        }
                        videos.pager++;
                    }
                    self.removeClass('loading');
                }, function(text){
                    error(text);
                    self.removeClass('loading');
                });
                return false;
            });
        }
    }
    
};