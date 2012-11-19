var search = {};

search.page = 2;
search.query = null;
search.active = 'all';
search.addMore = false;
search.running = false;


search.init = function(query){
    if(query != ''){
        $("input[data-search-input]").val(query);
    }
    search.query = $("input[data-search-input]").val();
    
    endless.init(10, function(){
        if(search.addMore && !search.running && search.active != 'all'){
            search.it(false);
        }
    });
    
    search.filter();
    
    site.startMosaic($(".am-container.photos"), {
        margin: 0, 
        liquid: true, 
        minsize: false
    });
};

search.it = function(seeAll){
    search.running = true;
    var params = {
        'query': search.query,
        'page': search.page,
        'type': search.active
    };
    $(".section.search"+search.active).addClass('loading');
    ajax.genericAction('search_ajaxsearch', params, function(r){
        if(r){
            var callback = function(){};
            var section = $("section.search."+search.active);
            search.addMore = r.addMore;

            var pointerLoop = 0;
            for(var i in r.search) {
                var entity = r.search[i];
                var destiny = null;
                
                if(pointerLoop == r.search.length){
                    callback = function(){
                        $(".section.search"+search.active).removeClass('loading');
                    };
                }
                
                switch(search.active){
                    case 'video':
                        destiny = 'ul.video-list';
                        //entity.duration = secToMinutes(entity.duration);
                        break;
                    case 'idol':
                        destiny = "ul.avatar-list.idols";
                        break;
                    case 'user':
                        destiny = "ul.avatar-list.fans";
                        break;
                    case 'team':
                        destiny = "ul.avatar-list.teams";
                        break;
                    case 'photo':
                        destiny = ".am-container.photos";
                        
                        callback = function(){
                            $("[data-added-element]").not('[data-montage=true]').imagesLoaded(function () {
                                $('.am-container.photos').montage('add', $("[data-added-element]").not('[data-montage=true]'));
                                $("[data-added-element]").attr('data-montage', 'true');
                            }); 
                            if(pointerLoop == r.search.length){
                                $(".section.search"+search.active).removeClass('loading');
                            }
                        }
                        break;
                    case 'event':
                        destiny = "ul.events-grid";
                        break;
                        
                }
                templateHelper.renderTemplate('search-'+search.active, entity, destiny, false, callback);
                pointerLoop++;
            }
            search.page++;
        }
        
        console.log('seeAll:'.seeAll);
        if(seeAll){
            console.log(search.addMore);
            console.log(endless.haveScroll());
            if(search.addMore && !endless.haveScroll()){
                console.log('uepa ue');
                
                search.it(true);
            }
        }
        
        search.running = false;
    }, function(r){
        console.log(r);
    });
};

search.filter = function(){
    $("section.search").fadeIn().addClass('active');
    
    $(".search-home div[data-toggle] button[data-filter-type]").on('click', function(){
        search.page = 2;
        search.addMore = false;
        search.active = $(this).attr('data-filter-type');
        
        $('[data-added-element]').remove();
        
        if(search.active == 'all'){
            $("section.search").fadeIn().addClass('active');
        }else{
            var executed = false;
            $("section.search").not( '.' + search.active ).fadeOut('fast',function(){
                if(!executed){
                    $(this).removeClass('active');
                    console.log('buscando...');
                    search.it(true);
                    executed = true;
                }
            });
            $("section.search." + search.active).fadeIn('fast',function(){
                $(this).addClass('active');  
            });
        }
    });
};


endless = {
    callback : null,
    tolerance : 10
};

endless.init = function(tolerance, callback){
    if(typeof(tolerance) != 'undefined' ){
        endless.tolerance = tolerance;
    }
    if(typeof(callback) != 'undefined'){
        endless.callback = callback;
    }
    
    endless.bindMyScroll();
}

endless.haveScroll = function(){
    return $(document).height() != $(window).height();
};
    
endless.scrollBottom = function(){
    var scrollSize = $(window).scrollTop() + $(window).height();
        
    percentScrollSize = (scrollSize * 100) / $(document).height();
        
    return percentScrollSize >= ( 100 - endless.tolerance );
    
};
endless.bindMyScroll = function(){
    $(window).bind('scroll', function(){
        console.log(endless.scrollBottom(), endless.haveScroll());
        if(endless.haveScroll() && endless.scrollBottom()){
            endless.callback();
        }
    });
};

//search.endless = function(callback, tolerance){
//    var self = this;
//    
//    self.callback = null;
//    self.tolerance = 10;
//    
//    self.construct = function(callback, tolerance){
//        if(typeof(tolerance)!='undefined'){
//            self.tolerance = tolerance;
//        }
//        self.callback = callback;
//        self.bindMyScroll();
//    };
//    
//    self.haveScroll = function(){
//        return $(document).height() != $(window).height();
//    };
//    
//    self.scrollBottom = function(){
//        var scrollSize = $(window).scrollTop() + $(window).height();
//        
//        tolerated = (scrollSize * 100) / $(document).height();
//        
//        return tolerated>=(100-self.tolerance);
//    };
//    
//    self.bindMyScroll = function(){
//        $(window).bind('scroll', function(){
//            console.log(self.scrollBottom(), self.haveScroll());
//            if(self.haveScroll() && self.scrollBottom()){
//                console.log(search.endless().callback);
//                search.endless().callback();
//            }
//        });
//    };
//    
//    self.construct(callback, tolerance);
//    
//    return self;
//};

function secToMinutes(sec){
    var min = Math.floor(sec/60);
    sec = sec % 60;
    if(sec<10) sec = "0" + sec;
    if(min<10) min = "0" + min;
    return min + ":" + sec;
}



/*$(window).endlessScroll({
        fireOnce: true,
        enableScrollTop: false,
        inflowPixels: 100,
        fireDelay: 250,
        intervalFrequency: 2000,
        ceaseFireOnEmpty: false,
        loader: 'cargando',
        callback: function() {
            if(search.addMore){
                search.it();
            }
        }
    });*/