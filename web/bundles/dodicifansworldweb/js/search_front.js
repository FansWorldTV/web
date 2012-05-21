var searchFront = {
    page: 1,
    init: function(){
        if($(".searchFront").size()>0){
            searchFront.addMore();
            searchFront.search();
            $("#formSearch.searchFront").submit(function(event){
                event.preventDefault();     
                searchFront.search();
                return false;
            });
        }
    },
    addMore: function(){
        $("#addMore.searchFront").click(function(){
            var query = this.parent().find('input#query').val();
        
            ajax.searchAction( query, searchFront.page, function(response){
                if(response){
                    var elements = response.search;
                    for(var i in elements){
                        var element = elements[i];
                        var template = $(".searchFront.templates .listMosaicTemp .element").clone();
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
                    
                        $(".searchFront.listMosaic").append(template);
                    }
                    if(response.gotMore){
                        $("#addMore").removeClass('hidden');
                        searchFront.page++;
                    }else{
                        $("#addMore").addClass('hidden');
                    }
                }
                
                $(".searchFront.listMosaic").show();
                $("div.ajax-loader").addClass('hidden');
            });
        });
    },
    search: function(){
        var query = $('#query').val();
        $(".searchFront.listMosaic").html('').hide();
        $("div.ajax-loader").removeClass('hidden');
        
        searchFront.page = 1;
        ajax.searchAction(query, searchFront.page, function(response){
            if(response){
                var elements = response.search;
                for(var i in elements){
                    var element = elements[i];
                    var template = $(".searchFront.templates .listMosaicTemp .element").clone();
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
                    
                    $(".searchFront.listMosaic").append(template);
                }
                if(response.gotMore){
                    $("#addMore").removeClass('hidden');
                    searchFront.page++;
                }else{
                    $("#addMore").addClass('hidden');
                }
            }
                
            $(".searchFront.listMosaic").show();
            $("div.ajax-loader").addClass('hidden');
        });
    }
};