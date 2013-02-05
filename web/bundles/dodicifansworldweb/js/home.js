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
                    var lastDate = $('section.home-content .content-container[data-type-tab="activityFeed"] .elements .element').last().attr('data-element-date');
                    home.loadSection.activityFeed({
                        'date': lastDate,
                        'filters': $("section.content-container[data-type-tab='activityFeed'] .tags .pull-left span.label.active").attr('data-feed-filter')
                    });
                });
                break;
            case 'popularFeed':
                endless.init(1, function(){
                    var lastDate = $('section.home-content .content-container[data-type-tab="activityFeed"] .elements .element').last().attr('data-element-date');
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

home.loadSection.enjoy = function(){
    var toAppendTrending = $('section.home-content .content-container[data-type-tab="enjoy"] .tags .pull-left');
    var toAppendVideos = $('section.home-content .content-container[data-type-tab="enjoy"] .elements');

    toAppendVideos.addClass('loading');

    ajax.genericAction('home_ajaxenjoy', {}, function(r){

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
                    toAppendVideos.removeClass('loading');
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

            templateHelper.renderTemplate('general-column_element', jsonData, toAppendVideos.selector, false, callback);

        }
    }, function(e){
        error(e);
    });
};

home.loadSection.connect = function(){
    var toAppendElements = $('section.home-content .content-container[data-type-tab="connect"] .fans-list');

    toAppendElements.addClass('loading');

    ajax.genericAction('home_ajaxconnect', {}, function(r){
        for(var i in r.fans){
            var fan = r.fans[i];

            var loop = parseInt(i);
            loop++;
            if(r.fans.length == loop)  {
                var callback = function(){
                    toAppendElements.removeClass('loading');
                    home.loadedSection.connect = true;
                };
            }else{
                var callback = function(){};
            }

            templateHelper.renderTemplate('fans-element', fan, toAppendElements.selector, false, callback);
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

    $contentContainer.addClass('loading');

    ajax.genericAction('home_ajaxactivityfeed', params, function(r){
        if(r.length > 0){
            for(var i in r){
                var element = r[i];

                var loop = parseInt(i);
                loop++;
                if(r.length == loop)  {
                    var callback = function(){
                        $contentContainer.removeClass('loading');
                        home.loadedSection.activityFeed = true;
                        funcCallback();
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
                }, $contentContainer.find('.elements').selector, false, callback);
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
        if(!$contentContainer.hasClass('isIso'))
    {
        var $container = $contentContainer.find('#elements');
        for(var i = 0; i < 10; i++) {
            //addElem()
        }
        $container.isotope({
            itemSelector : '.item',
            masonry: {
                columnWidth: 25
            }
        });
    }
    else {
        for(var i = 0; i < 10; i++) {
            //addElem()
        }
    }

    function addElem() {

      var img = new Array()
                    img[img.length] = "http://media-cache-ec5.pinterest.com/550/92/02/d7/9202d7e9c09f31d1e8df612b713e4388.jpg";
                    img[img.length] = "http://media-cache-lt0.pinterest.com/550/71/7e/95/717e958585ade66ff90d9d12937e7a2a.jpg";
                    img[img.length] = "http://media-cache-ec4.pinterest.com/550/22/97/39/2297397069aca387fef377d35426ee76.jpg";
                    img[img.length] = "http://media-cache-ec3.pinterest.com/550/0f/a1/d8/0fa1d8ba0cb7c082d26441b7b27b0c1c.jpg";
                    img[img.length] = "http://media-cache-ec4.pinterest.com/550/01/41/cd/0141cdec77c25d8eb4f09a5b3c03ef24.jpg";
                    img[img.length] = "http://media-cache-lt0.pinterest.com/550/45/d7/7c/45d77c0540edd46218a1d46efa3fde9c.jpg";
                    img[img.length] = "http://media-cache-ec3.pinterest.com/550/8e/36/fd/8e36fd1da721d4d22a17029762f01775.jpg";
                    img[img.length] = "http://media-cache-ec4.pinterest.com/550/7e/12/3b/7e123bb733780840e5daaf8e53050d94.jpg";
                    img[img.length] = "http://media-cache-ec7.pinterest.com/550/1b/42/57/1b42573930c9a1bbd80df165be4439f7.jpg";
                    img[img.length] = "http://media-cache-ec6.pinterest.com/550/37/2a/89/372a898c61676e196e4b38f5ede1e167.jpg";
                    img[img.length] = "http://media-cache-ec6.pinterest.com/550/6a/ff/7b/6aff7be357eb14e788befe59cd3d35cd.jpg";
                    img[img.length] = "http://media-cache-ec7.pinterest.com/550/07/f0/01/07f00133d1957efab44068c8af57ecfa.jpg";
                    img[img.length] = "http://media-cache-ec1.pinterest.com/550/59/2a/d3/592ad38982ef8050cf323ff8e3cde142.jpg";
                    img[img.length] = "http://media-cache-lt0.pinterest.com/550/a2/33/40/a23340f073d9325be1c98f58f65cfe92.jpg";
                    img[img.length] = "http://media-cache-ec4.pinterest.com/550/62/74/77/62747726f2c8ad61ecd0bce312817807.jpg";
                    img[img.length] = "http://media-cache-ec4.pinterest.com/550/62/22/39/6222390500b92e5cde38d228434d62a1.jpg";
                    img[img.length] = "http://media-cache-ec5.pinterest.com/550/5c/41/bb/5c41bb9415c53ab0a18fbe1f0b45993d.jpg";
                    img[img.length] = "http://media-cache-ec6.pinterest.com/550/d9/26/46/d92646d5222577a90ec8d89c9e5bf92b.jpg";
                    img[img.length] = "http://media-cache-ec3.pinterest.com/550/0b/3b/ef/0b3bef7c14c26a206174f31a65104b45.jpg";
                    img[img.length] = "http://media-cache-ec5.pinterest.com/550/42/a8/78/42a878072d2cf0b753d718e399fc5e9f.jpg";
                    img[img.length] = "http://media-cache-ec6.pinterest.com/550/12/8d/1a/128d1abe1efa3b52775d9846dab70417.jpg";
                    img[img.length] = "http://media-cache-ec5.pinterest.com/550/f4/c8/71/f4c871c35876ff41eafe9b74b8b05ce1.jpg";
                    img[img.length] = "http://media-cache-ec6.pinterest.com/550/b2/dd/6a/b2dd6a1b469e792a645ee3b2f80b6189.jpg";
                    img[img.length] = "http://media-cache-ec6.pinterest.com/550/18/ab/a3/18aba33560323b67846b54d773bbdff5.jpg";
                    img[img.length] = "http://media-cache-ec3.pinterest.com/550/c4/a7/85/c4a7851f086079419f3acee8d42748ab.jpg";
                    img[img.length] = "http://media-cache-ec2.pinterest.com/550/9f/d5/17/9fd5173debeec308c8c165551075ed2e.jpg";
                    img[img.length] = "http://media-cache-lt0.pinterest.com/550/4d/58/47/4d58470b05e5ab7be3561f143174b7ec.jpg";
                    img[img.length] = "http://media-cache-ec5.pinterest.com/550/e1/32/85/e13285f5e06d3c6dd442a69de7a82fab.jpg";
                    img[img.length] = "http://media-cache-ec6.pinterest.com/550/8a/fe/40/8afe40a6d23ae7aacd6bcd1457e3b115.jpg";
                    img[img.length] = "http://media-cache-ec7.pinterest.com/550/18/5d/fb/185dfb3882cb8ea74a0f12aa8a98bdfa.jpg";
                    img[img.length] = "http://media-cache-ec4.pinterest.com/550/dd/2c/0f/dd2c0f7238f23cfc802fabf839a80c71.jpg";
                    img[img.length] = "http://media-cache-lt0.pinterest.com/550/3a/d5/82/3ad582163cebf2eec61291f94cb5cb54.jpg";
                    img[img.length] = "http://media-cache-ec5.pinterest.com/550/9b/e5/f7/9be5f7ac8075ade5752024cc48274d1a.jpg";
                    img[img.length] = "http://media-cache-ec5.pinterest.com/550/9b/6c/5c/9b6c5cd251dd8f54832a7d79351bca27.jpg";
                    img[img.length] = "http://media-cache-ec5.pinterest.com/550/c7/9d/41/c79d4199b04b8c7e32b56f68bb9e05de.jpg";
                    img[img.length] = "http://media-cache-ec5.pinterest.com/550/55/87/7d/55877d3cffadcb37d299250ecdda3a98.jpg";
                    img[img.length] = "http://media-cache-ec4.pinterest.com/550/4e/52/cc/4e52cc28d32c1045e3fb43dd895c02e6.jpg";
                    img[img.length] = "http://media-cache-lt0.pinterest.com/550/7a/8b/f3/7a8bf3409c7a386c89654ec90e71b933.jpg";
                    img[img.length] = "http://media-cache-ec2.pinterest.com/550/ac/23/0f/ac230fbb9e5b89a8780aba396385eaae.jpg";
                    img[img.length] = "http://media-cache-ec7.pinterest.com/550/fa/c9/ba/fac9ba00492c046ca651a1939bb2191f.jpg";
                    img[img.length] = "http://media-cache-ec7.pinterest.com/550/32/58/6b/32586b4c70cf3486f329669e82f8e357.jpg";
                    img[img.length] = "http://media-cache-ec7.pinterest.com/550/43/ea/e6/43eae677704947161fa56d0f1c206f8b.jpg";
                    img[img.length] = "http://media-cache-ec2.pinterest.com/550/f4/c9/e8/f4c9e8d8511acc786327634c5d135cc2.jpg";
                    img[img.length] = "http://media-cache-ec3.pinterest.com/550/81/ff/84/81ff84fd97c10dee19909a1d2b1ca4bd.jpg";
                    img[img.length] = "http://media-cache-ec4.pinterest.com/550/c1/b0/8f/c1b08f7653c4c6b8ae7c4f1160356f8b.jpg";
                    img[img.length] = "http://media-cache-ec7.pinterest.com/550/85/15/e1/8515e1ca5e9a470e321a3bbcbc6a75bd.jpg";
                    img[img.length] = "http://media-cache-ec7.pinterest.com/550/14/61/7f/14617f83dc6d0c44f01ee82962680838.jpg";
                    img[img.length] = "http://media-cache-lt0.pinterest.com/550/45/0c/45/450c45755fc7390d9315940af9536c5a.jpg";
                    img[img.length] = "http://media-cache-ec7.pinterest.com/550/6b/ae/0e/6bae0ed664894fdaf32ee7b063d140d6.jpg";
                    img[img.length] = "http://media-cache-ec7.pinterest.com/550/de/06/7d/de067db1f2605cf0823befd70b648273.jpg";
                    img[img.length] = "http://media-cache-lt0.pinterest.com/550/92/d8/d8/92d8d8d819c38ac54f1223a3095cddc2.jpg";
                    img[img.length] = "http://media-cache-ec7.pinterest.com/550/51/74/e6/5174e638563eab4eca5988d9c6369e84.jpg";
                    img[img.length] = "http://media-cache-ec7.pinterest.com/550/c5/3b/1a/c53b1a6bdeaef108c084b2daa9b83209.jpg";
                    img[img.length] = "http://media-cache-ec4.pinterest.com/550/01/90/ac/0190ac17816e6f346501fffe5282923d.jpg";
                    img[img.length] = "http://media-cache-ec4.pinterest.com/550/f1/86/46/f18646e76d5eb9ad128f2eea698ea4b3.jpg";
                    img[img.length] = "http://media-cache-ec3.pinterest.com/550/a9/42/b7/a942b7ab8bc2de0459ee116661d38237.jpg";
                    img[img.length] = "http://media-cache-ec1.pinterest.com/550/2b/3a/b9/2b3ab9b792172aa1de9c1884143a2521.jpg";
                    img[img.length] = "http://media-cache-ec3.pinterest.com/550/d8/30/a2/d830a22283f239e56bf1d535428f131d.jpg";
                    img[img.length] = "http://media-cache-ec5.pinterest.com/550/09/3f/d2/093fd2c1ce8dda6342e89282311ff67d.jpg";
                    img[img.length] = "http://media-cache-ec5.pinterest.com/550/5f/b2/35/5fb2350df37e26b223440528e3822af1.jpg";
                    img[img.length] = "http://media-cache-ec3.pinterest.com/550/c3/69/b5/c369b57f8d039b2116967a20bd508256.jpg";
                    img[img.length] = "http://media-cache-lt0.pinterest.com/550/40/af/99/40af9993292984f920a6f2ecc9d93d0f.jpg";
                    img[img.length] = "http://media-cache-ec4.pinterest.com/550/0e/6d/c0/0e6dc0a2b341942d2bf7c3334e2e089d.jpg";
                    img[img.length] = "http://media-cache-ec2.pinterest.com/550/df/ff/b4/dfffb47ac343c3f95b9f9fde592024df.jpg";
                    img[img.length] = "http://media-cache-ec6.pinterest.com/550/7d/4c/5d/7d4c5d072af02ba3c81e6652eedb5f3f.jpg";

     $posts = $('#elements');
      var post = {};
      post.id = (Math.floor(Math.random()*10000));
      a = $('<div id="' + post.id  + '" class="post item"><div class="container"><div class="title-and-user"><a href="/u/adolfa"><img class="author-image" src="http://svn.dodici.com.ar/uploads/media/default/0001/01/thumb_4_default_small_square_c2bf9a9c947e92e122fbb5926dadb6628f3d9513.jpg" alt="adolfa" rel="tooltip" title="adolfa"></a><a href="#"><span class="title">index : 41</span></a></div></div></div>') //$('#post').tmpl({id: post.id, title: "hello", score: post.id, subreddit: 'rrr'})
      $posts.append(a).isotope('appended', a)

      $('<img class="image">').attr('src', img[(Math.floor(Math.random()*img.length))]).wrap('<div class="image">').load(function(){
              $(this).appendTo('#' + post.id + ' div.container').slideDown(function(){
              $posts.isotope('reLayout');
            });
      });

    }
    //$contentContainer.find('.elements').isotope('reLayout');
    $contentContainer.addClass('isIso');
    $contentContainer.addClass('loading');

    ajax.genericAction('home_ajaxpopularfeed', params, function(r){
        var dummy = $('<div class="dummy"></div>')
        var $posts = $('#elements');
        if(r.length > 0){
            for(var i in r){
                var element = r[i];
                var loop = parseInt(i);
                loop++;
                if(r.length == loop)  {
                    var callback = function(){
                        $contentContainer.removeClass('loading');
                        home.loadedSection.activityFeed = true;
                        //$posts.append($(dummy.html())).isotope('appended', $(dummy.html()))
                        a = dummy.find('.post');
                        //console.log(a.length)
                        $.each(a, function(i, item) {
                            console.log(item)
                            $posts.append($(item)).isotope('appended', $(item))
                        });
                        $posts.isotope('reLayout');
                    };
                }else{
                    var callback = function(render){};
                }

                var href = Routing.generate(appLocale + '_' + element.type + '_show', {
                    'id': element.id,
                    'slug': element.slug
                });

                var authorUrl = Routing.generate(appLocale + '_user_wall', {
                    'username': element.author.username
                });
                //console.log(element)
                
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