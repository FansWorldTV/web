var search = {};

search.box = null;
search.page = null;
search.query = null;

search.init = function(){
    /*search.box = $('form[data-search-box]');
    search.box.find('[data-search-action]').click(function(){
       search.it(); 
    });*/
    search.filter();
};

search.it = function(){
    search.query = search.box.find('[data-search-input]').val();
    
    var params = {
      'query': search.query,
      'page': search.page
    };
    
    ajax.genericAction('search_search', params, function(r){
        console.log(r);
    }, function(r){
        console.log(r);
    });
};

search.addMore = function(){

}

search.filter = function(){
    $("section.search").fadeIn().addClass('active');
    
    $(".search-home div[data-toggle] button[data-filter-type]").on('click', function(){
        var params = {};
        
        params['type'] = $(this).attr('data-filter-type');
        
        if(params['type'] == 'all'){
            $("section.search").fadeIn().addClass('active');
        }else{
            $("section.search").not('.'+params['type']).fadeOut().removeClass('active');
            $("section.search."+params['type']).fadeIn().addClass('active');
        }
    });
}

$(function(){
   search.init(); 
});