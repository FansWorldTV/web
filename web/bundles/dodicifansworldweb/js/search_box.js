var searchBox = {
    query: null,
    searchType: null,
    init: function(){
        $("div.search input").change(function(){
            searchBox.query = $(this).val();
        });
      
    }
};