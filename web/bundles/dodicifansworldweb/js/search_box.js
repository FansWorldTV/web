var searchBox = {
    page: 1,
    query: null,
    searchType: null,
    init: function(){
        if(searchBox.searchType !== null){
            $("div.search a[type='" + searchBox.searchType +"']").addClass('bold');
        }
        if(searchBox.query !== null){
            $("div.search input[type='text']").val(searchBox.query);
        }
        searchBox.handleType();
        $("div.search input[type='text']").change(function(){
            searchBox.query = $(this).val();
        });
        $("div.search form").submit(function(){
            $(".btn_search").addClass('loading');
            if(searchBox.searchType !== null){
                window.location.href = Routing.generate(appLocale + "_search_box", {
                    'type': searchBox.searchType, 
                    'query': searchBox.query
                });
            }else{
                error('Te faltó seleccionar un criterio de búsqueda');
                $(".btn_search").removeClass('loading');
            }
            return false;
        });
        searchBox.addMore();
    },
    handleType: function(){
        $("div.search ul li ul a").click(function(){
            console.log($(this));
            $("div.search a[type='" + searchBox.searchType +"']").toggleClass('bold');
            searchBox.searchType = parseInt($(this).attr('type'));
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