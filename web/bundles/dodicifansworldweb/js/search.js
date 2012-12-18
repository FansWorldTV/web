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
    $("section.search."+search.active).addClass('loading');
    
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
        
        if(seeAll){
            if(search.addMore && !endless.haveScroll()){
                search.it(true);
            }
        }
        
        search.running = false;
        $("section.search."+search.active).removeClass('loading');
    }, function(r){
        console.log(r);
    }, 'get');
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
            $("section.search").not( '.' + search.active ).fadeOut('fast').last().fadeOut(function(){
                $("section.search." + search.active).fadeIn('fast',function(){
                    $(this).addClass('active');  
                    $(this).removeClass('active');
                    search.it(true);
                });
            });
        }
    });
};

function secToMinutes(sec){
    var min = Math.floor(sec/60);
    sec = sec % 60;
    if(sec<10) sec = "0" + sec;
    if(min<10) min = "0" + min;
    return min + ":" + sec;
}

$(document).ready(function(){
   search.init(); 
});