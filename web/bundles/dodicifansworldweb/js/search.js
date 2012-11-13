var search = {};

search.page = 2;
search.query = null;
search.active = 'all';
search.addMore = false;

search.init = function(query){
    if(query != ''){
        $("input[data-search-input]").val(query);
    }
    search.query = $("input[data-search-input]").val();
    search.endless();
    search.filter();
    
    var toLoad	= [
    'search-video'
    ];
    templateHelper.preLoadTemplates(toLoad);
};

search.it = function(){
    var params = {
        'query': search.query,
        'page': search.page,
        'type': search.active
    };
    ajax.genericAction('search_ajaxsearch', params, function(r){
        if(r){
            var section = $("section.search."+search.active);
            search.addMore = r.addMore;
            for(var i in r.search) {
                var entity = r.search[i];
                var destiny = null;
                
                switch(search.active){
                    case 'video':
                        destiny = 'ul.video-list';
                        entity.duration = secToMinutes(entity.duration);
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
                        break;
                    case 'event':
                        destiny = "ul.events-grid";
                        break;
                        
                }
                templateHelper.renderTemplate('search-'+search.active, entity, destiny);
            }
            search.page++;
        }
    }, function(r){
        console.log(r);
    });
};

search.filter = function(){
    $("section.search").fadeIn().addClass('active');
    
    $(".search-home div[data-toggle] button[data-filter-type]").on('click', function(){
        var params = {};
        
        params['type'] = $(this).attr('data-filter-type');
        params['query'] = search.query;
        
        search.active = params['type'];
        
        $('[data-added-element]').remove();
        //search.page = 2;
        
        if(params['type'] == 'all'){
            $("section.search").fadeIn().addClass('active');
        }else{
            $("section.search").not('.'+params['type']).fadeOut().removeClass('active');
            $("section.search."+params['type']).fadeIn().addClass('active');
            
            search.it();
        }
    });
};


search.endless = function(){
    $(window).endlessScroll({
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
    });
};

function secToMinutes(sec){
    var min = Math.floor(sec/60);
    sec = sec % 60;
    if(sec<10) sec = "0" + sec;
    if(min<10) min = "0" + min;
    return min + ":" + sec;
}