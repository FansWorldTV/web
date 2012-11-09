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
                switch(search.active){
                    case 'video':
                        var template = section.find('.video-element').first().clone();
                        template.find('.title').html(entity.title);
                        template.attr('data-added-element');
                }
                
                section.find('ul.video-list').append(template);
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