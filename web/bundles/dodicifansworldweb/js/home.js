var home = {};

home.init = function(){
    if($("header").hasClass('home-header')){
        home.toggleSections();
    }
    if($("section").hasClass('home-content')){
        if(isLoggedIn){
            home.filterSection.activityFeed();
            home.loadSection.activityFeed({}, function(){
                endless.init(1, function(){
                    var lastDate = $('section.home-content .content-container[data-type-tab="activityFeed"] .elements .element').last().attr('data-element-date');
                    home.loadSection.activityFeed({
                        'date': lastDate,
                        'filters': $("section.content-container[data-type-tab='activityFeed'] .tags .pull-left span.label.active").attr('data-feed-filter')
                    });
                });
            });
        }else{
            home.loadSection.enjoy();
        }
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

    if(isLoggedIn){
        $("section.home-content .content-container:not('[data-type-tab='activityFeed']')").hide();
    }else{
        $("section.home-content .legend:not('.active')").hide();
        $("section.home-content .content-container:not('[data-type-tab='enjoy']')").hide();
    }

    $(".home-header ul.tabs li").on('click', function(){
        var typeTab = $(this).attr('data-type-tab');
        $(".home-header ul.tabs li").removeClass('active');
        $(this).addClass('active');

        $("section.home-content .legend").hide();
        $("section.home-content .content-container").hide();

        if(typeTab != 'enjoy'){
            if(!window['home']['loadedSection'][typeTab]){
                window['home']['loadSection'][typeTab]();
            }
        }


        switch(typeTab){
            case 'activityFeed':
                endless.init(1, function(){
                    var lastDate = $('section.home-content .content-container[data-type-tab="activityFeed"] #elements .post').last().attr('data-element-date');
                    home.loadSection.activityFeed({
                        'date': lastDate,
                        'filters': $("section.content-container[data-type-tab='activityFeed'] .tags .pull-left span.label.active").attr('data-feed-filter')
                    });
                });
                break;
            case 'popularFeed':
                endless.init(1, function(){
                    var lastDate = $('section.home-content .content-container[data-type-tab="popularFeed"] #elements .post').last().attr('data-element-date');
                    home.loadSection.popularFeed({
                        'date': lastDate
                    });
                });
                break;
            default:
                endless.stop();
        }

        $('section.home-content .legend[data-type-tab="' + typeTab + '"]').show();
        $("section.home-content .content-container[data-type-tab='" + typeTab + "']").show();
    });
};

home.filterSection = {};
home.filterSection.activityFeed = function(){
    $("section.content-container[data-type-tab='activityFeed'] .tags span.label").on('click', function(){
        if($(this).not('.active')){
            $(this).parent().find('.active').removeClass('active');
            $(this).addClass('active');
            $("section.elements").html('');
            home.loadSection.activityFeed({
                'filters': $(this).attr('data-feed-filter')
            });
        }
    });
};

home.loadSection = {};

home.loadedSection = {
    'enjoy': true,
    'follow': false,
    'connect': false,
    'participate': false,

    'activityFeed': false,
    'popularFeed': false
};

home.getMaxSections = function(container) {
    var $container = $(container)
    if($container.width() <= 600) {
        var cells = 1;
    } else if ($container.width() <= 800) {
        var cells = 2;
    } else if ($container.width() <= 1000) {
        var cells = 3;
    } else if ($container.width() <= 1200) {
        var cells = 4;
    } else if ($container.width() > 1200) {
       var cells = 5;
    }
    return cells;
}
home.loadSection.enjoy = function(){

    var toAppendTrending = $('section.home-content .content-container[data-type-tab="enjoy"] .tags .pull-left');
    var toAppendVideos = $('section.home-content .content-container[data-type-tab="enjoy"] .elements');

    var $contentContainer = $('section.home-content .content-container[data-type-tab="enjoy"]');

    if(!$contentContainer.hasClass('isIso'))
    {
        var $container = $contentContainer.find('#elements');

        $container.isotope({
            itemSelector : '.item',
            resizable: false, // disable normal resizing
            masonry: {
                columnWidth: ($container.width() / home.getMaxSections($container)),
            }
        });
    }
    $(window).smartresize(function(){
        console.log($container.width())
      if($container.width() <= 600) {
        var cells = 1;
      } else if ($container.width() <= 800) {
        var cells = 2;
      } else if ($container.width() <= 1000) {
        var cells = 3;
      } else if ($container.width() <= 1200) {
        var cells = 4;
      } else if ($container.width() > 1200) {
        var cells = 5;
      }
      $container.find('.item').each(function(i, item){
        var $this = $(this);
        $this.css('width', ((100 / cells) - 2) + "%") // 2% margen entre los elementos
      });
      $container.isotope({
        // update columnWidth to a percentage of container width
        masonry: { columnWidth: $container.width() / cells }
      });
      $container.isotope('reLayout');
    });
    $contentContainer.addClass('isIso');
    toAppendVideos.addClass('loading');

    ajax.genericAction('home_ajaxenjoy', {}, function(r){

        var dummy = $('<div class="dummy"></div>');
        var $container = $contentContainer.find('#elements');

        for(var i in r.trending){
            var tag = r.trending[i];

            var elementToAppend = $('<span class="label"></span>');

            elementToAppend.html(tag.title)
            .attr('data-tag-id', tag.id)
            .attr('data-tag-slug', tag.slug);

            toAppendTrending.append(elementToAppend);
        }

        for(var i in r.videos){
            var video = r.videos[i];

            var loop = parseInt(i);
            loop++;
            if(r.videos.length == loop)  {
                var callback = function(){
                    toAppendVideos.removeClass('loading')
                    home.loadedSection.enjoy = true;
                    var post = dummy.find('.post');
                      if($container.width() <= 600) {
                        var cells = 1;
                      } else if ($container.width() <= 800) {
                        var cells = 2;
                      } else if ($container.width() <= 1000) {
                        var cells = 3;
                      } else if ($container.width() <= 1200) {
                        var cells = 4;
                      } else if ($container.width() > 1200) {
                        var cells = 5;
                      }
                    //alert("w: " + $container.width()+ " c: " + cells)

                    $.each(post, function(i, item) {
                        var $this = $(item);
                        $this.css('width', ((100 / cells) - 2) + "%")
                        $this.find('.imagex').load(function() {
                            $container.isotope('reLayout');
                        })
                        $container.append($(item)).isotope('appended', $(item));
                       });
                        $container.isotope('reLayout');
                    };
            }else{
                var callback = function(){};
            }

            var href = Routing.generate(appLocale + '_video_show', {
                'id': video.id,
                'slug': video.slug
            });

            var jsonData = {
                'type': 'video',
                'date': video.createdAt,
                'href': href,
                'image': video.image,
                'slug': video.slug,
                'title': video.title
            };

            if(video.author != null){
                jsonData['author'] = video.author.username;
                jsonData['authorHref'] = video.author.url;
                jsonData['authorImage'] = video.author.image;
            }
            templateHelper.renderTemplate('general-column_element', jsonData, dummy, false, callback);

        }
    }, function(e){
        error(e);
    });
};

home.loadSection.connect = function(){
    var toAppendElements = $('section.home-content .content-container[data-type-tab="connect"] .fans-list');

    toAppendElements.parent().addClass('loading');
    ajax.genericAction('home_ajaxconnect', {}, function(r){
        for(var i in r.fans){
            var fan = r.fans[i];

            var loop = parseInt(i);
            loop++;
            if(r.fans.length == loop)  {
                var callback = function(){
                    console.log(cells)
                    toAppendElements.removeClass('loading');
                    home.loadedSection.connect = true;
                };
            }else{
                var callback = function(){};
            }
            templateHelper.renderTemplate('fans-element', fan, toAppendElements.selector, false, callback);
            toAppendElements.parent().removeClass('loading');
        }
    }, function(e){
        error(e);
    });
};

home.loadSection.participate = function(){
    var toAppendElements = $('section.home-content .content-container[data-type-tab="participate"] .events-grid');

    toAppendElements.parent().addClass('loading');

    ajax.genericAction('home_ajaxparticipate', {}, function(r){
        for(var i in r.events){
            var event = r.events[i];
            var callback = function(){};

            templateHelper.renderTemplate('event-grid_element', event, toAppendElements.selector, false, callback);
        }
        toAppendElements.parent().removeClass('loading');
        home.loadedSection.participate = true;
    }, function(e){
        error(e)
    });
};

home.loadSection.activityFeed = function(params, funcCallback){
    if(typeof(params) == 'undefined'){
        params = {};
    }

    var $contentContainer = $('section.home-content .content-container[data-type-tab="activityFeed"]');
    var $container = $contentContainer.find('#elements');

    if(!$contentContainer.hasClass('isIso')) {
        var $container = $contentContainer.find('#elements');
        $container.css('width', '100%');
        $container.isotope({
            itemSelector : '.item',
            resizable: false, // disable normal resizing
            masonry: {
                columnWidth: ($container.width() / home.getMaxSections($container)),
            }
        });
    }
    $(window).smartresize(function(){
        console.log($container.width())
      if($container.width() <= 600) {
        var cells = 1;
      } else if ($container.width() <= 800) {
        var cells = 2;
      } else if ($container.width() <= 1000) {
        var cells = 3;
      } else if ($container.width() <= 1200) {
        var cells = 4;
      } else if ($container.width() > 1200) {
        var cells = 5;
      }
      $container.find('.item').each(function(i, item){
        var $this = $(this);
        $this.css('width', ((100 / cells) - 2) + "%") // 2% margen entre los elementos
      });
      $container.isotope({
        // update columnWidth to a percentage of container width
        masonry: { columnWidth: $container.width() / cells }
      });
      $container.isotope('reLayout');
    });
    $contentContainer.addClass('isIso');
    $contentContainer.addClass('loading');

    ajax.genericAction('home_ajaxpopularfeed', params, function(r){ //home_ajaxactivityfeed

        var dummy = $('<div class="dummy"></div>');
        var $container = $contentContainer.find('#elements');

        if(r.length > 0){
            for(var i in r){
                var element = r[i];

                var loop = parseInt(i);
                loop++;
                if(r.length == loop)  {
                    var callback = function(){
                        $contentContainer.removeClass('loading');
                        home.loadedSection.activityFeed = true;
                        var post = dummy.find('.post');
                        if($container.width() <= 600) {
                            var cells = 1;
                          } else if ($container.width() <= 800) {
                            var cells = 2;
                          } else if ($container.width() <= 1000) {
                            var cells = 3;
                          } else if ($container.width() <= 1200) {
                            var cells = 4;
                          } else if ($container.width() > 1200) {
                            var cells = 5;
                          }
                    console.log("w: "+$container.width())
                    $.each(post, function(i, item) {
                        var $this = $(item);
                        $this.css('width', ((100 / cells) - 2) + "%")
                        $container.isotope({
                            // update columnWidth to a percentage of container width
                            masonry: { columnWidth: $container.width() / cells }
                        });
                        $this.find('.imagex').load(function() {
                            $container.isotope('reLayout');
                        })
                        $container.append($(item)).isotope('appended', $(item));
                       });
                        //$container.isotope('reLayout');
                    };
                }else{
                    var callback = function(){};
                }

                var href = Routing.generate(appLocale + '_' + element.type + '_show', {
                    'id': element.id,
                    'slug': element.slug
                });

                var authorUrl = Routing.generate(appLocale + '_user_wall', {
                    'username': element.author.username
                });

                templateHelper.renderTemplate('general-column_element', {
                    'type': element.type,
                    'date': element.created,
                    'href': href,
                    'image': element.image,
                    'slug': element.slug,
                    'title': element.title,
                    'author': element.author.username,
                    'authorHref': authorUrl,
                    'authorImage': element.author.image
                },dummy, false, callback); //$contentContainer.find('.elements').selector, false, callback);
            }
        }else{
            $contentContainer.removeClass('loading');
            endless.stop();
        }
    }, function(e){
        error(e);
    });
};

home.loadSection.popularFeed = function(params){

    if(typeof(params) == 'undefined'){
        params = {};
    }

    var $contentContainer = $('section.home-content .content-container[data-type-tab="popularFeed"]');
    var $container = $contentContainer.find('#elements');

    if(!$contentContainer.hasClass('isIso')) {
        var $container = $contentContainer.find('#elements');
        $container.css('width', '100%');
        $container.isotope({
            itemSelector : '.item',
            resizable: false, // disable normal resizing
            masonry: {
                columnWidth: ($container.width() / home.getMaxSections($container)),
            }
        });
    }
    $(window).smartresize(function(){
      if($container.width() <= 600) {
        var cells = 1;
      } else if ($container.width() <= 800) {
        var cells = 2;
      } else if ($container.width() <= 1000) {
        var cells = 3;
      } else if ($container.width() <= 1200) {
        var cells = 4;
      } else if ($container.width() > 1200) {
        var cells = 5;
      }
      $container.find('.item').each(function(i, item){
        var $this = $(this);
        $this.css('width', ((100 / cells) - 2) + "%") // 2% margen entre los elementos
      });
      $container.isotope({
        // update columnWidth to a percentage of container width
        masonry: { columnWidth: $container.width() / cells }
      });
      $container.isotope('reLayout');
    });
    $contentContainer.addClass('isIso');
    $contentContainer.addClass('loading');

    ajax.genericAction('home_ajaxpopularfeed', params, function(r){ //home_ajaxactivityfeed

        var dummy = $('<div class="dummy"></div>');

        if(r.length > 0){
            for(var i in r){
                var element = r[i];

                var loop = parseInt(i);
                loop++;
                if(r.length == loop)  {
                    var callback = function(){
                        $contentContainer.removeClass('loading');
                        home.loadedSection.activityFeed = true;
                        var post = dummy.find('.post');
                        if($container.width() <= 600) {
                            var cells = 1;
                          } else if ($container.width() <= 800) {
                            var cells = 2;
                          } else if ($container.width() <= 1000) {
                            var cells = 3;
                          } else if ($container.width() <= 1200) {
                            var cells = 4;
                          } else if ($container.width() > 1200) {
                            var cells = 5;
                          }
                    $.each(post, function(i, item) {
                        var $this = $(item);
                        $this.css('width', ((100 / cells) - 2) + "%")
                        $container.isotope({
                            // update columnWidth to a percentage of container width
                            masonry: { columnWidth: $container.width() / cells }
                        });
                        $this.find('.imagex').load(function() {
                            $container.isotope('reLayout');
                        })
                        $container.append($(item)).isotope('appended', $(item));
                       });
                        //$container.isotope('reLayout');
                    };
                }else{
                    var callback = function(){};
                }

                var href = Routing.generate(appLocale + '_' + element.type + '_show', {
                    'id': element.id,
                    'slug': element.slug
                });

                var authorUrl = Routing.generate(appLocale + '_user_wall', {
                    'username': element.author.username
                });

                templateHelper.renderTemplate('general-column_element', {
                    'type': element.type,
                    'date': element.created,
                    'href': href,
                    'image': element.image,
                    'slug': element.slug,
                    'title': element.title,
                    'author': element.author.username,
                    'authorHref': authorUrl,
                    'authorImage': element.author.image
                },dummy, false, callback); //$contentContainer.find('.elements').selector, false, callback);
            }
        }else{
            $contentContainer.removeClass('loading');
            endless.stop();
        }
    }, function(e){
        error(e);
    });
}

home.loadSection.popularFeed2 = function(params){
    if(typeof(params) == 'undefined'){
        params = {};
    }
    var $contentContainer = $('section.home-content .content-container[data-type-tab="popularFeed"]');
    var $container = $contentContainer.find('#elements');

    if(!$contentContainer.hasClass('isIso')) {
        var $container = $contentContainer.find('#elements');
        $container.css('width', '100%');
        $container.isotope({
            itemSelector : '.item',
            resizable: false, // disable normal resizing
            masonry: {
                columnWidth: ($container.width() / home.getMaxSections($container)),
            }
        });
    }
    $(window).smartresize(function(){
        console.log($container.width())
      if($container.width() <= 600) {
        var cells = 1;
      } else if ($container.width() <= 800) {
        var cells = 2;
      } else if ($container.width() <= 1000) {
        var cells = 3;
      } else if ($container.width() <= 1200) {
        var cells = 4;
      } else if ($container.width() > 1200) {
        var cells = 5;
      }
      $container.find('.item').each(function(i, item){
        var $this = $(this);
        $this.css('width', ((100 / cells) - 4) + "%") // 2% margen entre los elementos
      });
      $container.isotope({
        // update columnWidth to a percentage of container width
        masonry: { columnWidth: $container.width() / cells }
      });
      $container.isotope('reLayout');
    });
    $contentContainer.addClass('isIso');
    $contentContainer.addClass('loading');

    ajax.genericAction('home_ajaxpopularfeed', params, function(r){
        var dummy = $('<div class="dummy"></div>')
        var $posts = $contentContainer.find('#elements');
        if(r.length > 0){
            for(var i in r){
                var element = r[i];
                var loop = parseInt(i);
                loop++;
                if(r.length == loop)  {
                    var callback = function(){
                        $contentContainer.removeClass('loading');
                        var post = dummy.find('.post');
                        if($container.width() <= 600) {
                            var cells = 1;
                          } else if ($container.width() <= 800) {
                            var cells = 2;
                          } else if ($container.width() <= 1000) {
                            var cells = 3;
                          } else if ($container.width() <= 1200) {
                            var cells = 5;
                          } else if ($container.width() > 1200) {
                            var cells = 6;
                          }
                    $.each(post, function(i, item) {
                        var $this = $(item);
                        $this.css('width', ((100 / cells) - 2) + "%")
                        $this.find('.imagex').load(function() {
                            $container.isotope('reLayout');
                        })
                        $container.append($(item)).isotope('appended', $(item));
                       });
                        //$container.isotope('reLayout');
                    };
                }else{
                    var callback = function(){};
                }

                var href = Routing.generate(appLocale + '_' + element.type + '_show', {
                    'id': element.id,
                    'slug': element.slug
                });

                var authorUrl = Routing.generate(appLocale + '_user_wall', {
                    'username': element.author.username
                });
                templateHelper.renderTemplate('general-column_element', {
                    'id': element.imageid,
                    'type': element.type,
                    'date': element.created,
                    'href': href,
                    'image': element.image,
                    'slug': element.slug,
                    'title': element.title,
                    'author': element.author.username,
                    'authorHref': authorUrl,
                    'authorImage': element.author.image
                }, dummy, false, callback);//$contentContainer.find('.elements').selector, false, callback);
            }
        }else{
            $contentContainer.removeClass('loading');
            endless.stop();
        }
    }, function(e){
        error(e);
    });
};

$(document).ready(function(){
    home.init();
});