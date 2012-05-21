var searchIdols = {
    page: 1,
    init: function(){
        if($(".searchIdol").length>0){
            searchIdols.addMore();
            searchIdols.search();
            $("#formSearch.searchIdol").submit(function(event){
                event.preventDefault();
                searchIdols.search();
                return false;
            });
        }
    },
    addMore: function(){
        $("#addMore.searchIdol").click(function(){
            var query = $(this).parent().find('input#query').val();
        
            ajax.searchIdolsAction( query, searchIdols.page, null, function(response){
                if(response){
                    var elements = response.idols;
                    for(var i in elements){
                        var element = elements[i];
                        var template = $(".templates .listMosaicTemp .element").clone();
                        var usrLink = Routing.generate(appLocale + '_user_wall', {
                            'username':element.username
                        });
                    
                        template.find('a').attr('href', usrLink);
                        template.find('.name').html(element.name);
                        template.find('.commonFriends').html(element.commonFriends);
                    
                        template.find('.avatar').attr('href', usrLink);
                        if(element.image){
                            template.find('.avatar img').attr('src', element.image);
                        }else{
                            template.find('.avatar img').attr('src', '/fansworld/web/bundles/dodicifansworldweb/images/user_pic.jpg');
                        }
                    
                        if(element.isFriend){
                            template.addClass('isfriend');
                        }
                    
                        $(".listMosaic").append(template);
                    }
                    if(response.gotMore){
                        $("#addMore").removeClass('hidden');
                        searchIdols.page++;
                    }else{
                        $("#addMore").addClass('hidden');
                    }
                }
            });
            
            return false;
        });
    },
    search: function(){
        var query = $('#query').val();
        $(".searchIdol.listMosaic").html('').hide();
        $("div.ajax-loader").removeClass('hidden');
        
        searchIdols.page = 1;
        ajax.searchIdolsAction(query, searchIdols.page, null, function(response){
            if(response){
                var elements = response.idols;
                for(var i in elements){
                    var element = elements[i];
                    var template = $(".templates .listMosaicTemp .element").clone();
                    var usrLink = Routing.generate(appLocale + '_user_wall', {
                        'username':element.username
                    });
                    
                    template.find('a').attr('href', usrLink);
                    template.find('.name').html(element.name);
                    template.find('.commonFriends').html(element.commonFriends);
                    
                    template.find('.avatar').attr('href', usrLink);
                    if(element.image){
                        template.find('.avatar img').attr('src', element.image);
                    }else{
                        template.find('.avatar img').attr('src', '/fansworld/web/bundles/dodicifansworldweb/images/user_pic.jpg');
                    }
                    
                    if(element.isFriend){
                        template.addClass('isfriend');
                    }
                    
                    $(".listMosaic").append(template);
                }
                if(response.gotMore){
                    $("#addMore").removeClass('hidden');
                    searchIdols.page++;
                }else{
                    $("#addMore").addClass('hidden');
                }
            }
                
            $(".searchIdol.listMosaic").show();
            $("div.ajax-loader").addClass('hidden');
        });
    }
};