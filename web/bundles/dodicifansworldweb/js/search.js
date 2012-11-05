var search = {};

search.box = null;
search.page = null;
search.query = null;

search.init = function(){
    search.box = $('form[data-search-box]');
    search.box.find('[data-search-action]').click(function(){
       search.it(); 
    });
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