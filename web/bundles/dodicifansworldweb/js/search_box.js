var searchBox = {
    page: 1,
    query: null,
    searchType: null,
    init: function(){
        searchBox.handleType();
        $("div.search input[type='text']").change(function(){
            searchBox.query = $(this).val();
        });
        $(".btn_search").click(function(){
            if(searchBox.searchType !== null){
                $(this).addClass('loading');
                window.location.href = Routing.generate(appLocale + "_search_box", {
                    'type': searchBox.searchType, 
                    'query': searchBox.query
                });
            }else{
                error('Te faltó seleccionar un criterio de búsqueda');
            }
        });
        searchBox.addMore();
    },
    handleType: function(){
        $("div.search ul a").click(function(){
            console.log($(this));
            $(searchBox.searchType).toggleClass('bold');
            searchBox.searchType = $(this);
            $(this).toggleClass('bold');
            
            return false;
        });
    },
    addMore: function(){
        $("#addMore.searchBox:not('.loading')").live('click', function(){
            var self = $(this);
            searchBox.page++;
            self.addClass('loading');
            ajax.genericAction('search_ajaxbox', {
                'query': searchBox.query, 
                'type': searchBox.searchType, 
                'page': searchBox.page
            }, function(response){
                if(response){
                    console.log(response);
                    for(var i in response.search){
                        var element = response.search[i];
                        $("ul.listMosaic").append("<li>" + element.title + "</li>");
                    }
                    if(!response.addMore){
                        $("#addMore.searchBox").remove();
                    }
                }
                self.removeClass('loading');
            },function(message){
                error(message);
                self.removeClass('loading');
            });
        });
    }
};