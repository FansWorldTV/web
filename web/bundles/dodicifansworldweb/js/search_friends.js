var friendsSearch = {
    page: 1,
    init: function(){
        if($(".friends").length>0){
            friendsSearch.addMore();
            friendsSearch.search();
            $("#formSearch.friends").submit(function(event){
                event.preventDefault();
                friendsSearch.search();
                return false;
            });
        }
    },
    addMore: function(){
        $("#addMore.friends").click(function(){
            var query = this.parent().find('input#query').val();
            var userId = $('#userid').val();
        
            if(typeof(userId) == 'undefined'){
                userId = false;
            }
        
            ajax.friendsAction( query, userId, friendsSearch.page, function(response){
                if(response){
                    var elements = response.search;
                    for(var i in elements){
                        var element = elements[i];
                        var template = $(".friends.templates .listMosaicTemp .element").clone();
                        var usrLink = Routing.generate(appLocale + '_user_wall', {
                            'username':element.username
                        });
                    
                        template.find('a').attr('href', usrLink);
                        template.find('.name').html(element.name);
                    
                        if(element.image){
                            template.find('.avatar img').attr('src', element.image);
                        }else{
                            template.find('.avatar img').attr('src', '/fansworld/web/bundles/dodicifansworldweb/images/profile_no_image.png');
                        }
                    
                        $(".friends.listMosaic").append(template);
                    }
                
                    if(response.gotMore){
                        $("#addMore").removeClass('hidden');
                        friendsSearch.page++;
                    }else{
                        $("#addMore").addClass('hidden');
                    }
                }
            });
        });
    },
    search: function(){
        var query = $('#query').val();
        var userId = $('#userid').val();
        
        if(typeof(userId) == 'undefined'){
            userId = false;
        }
        
        $(".friends.listMosaic").html('').hide();
        $("div.ajax-loader").removeClass('hidden');
        
        friendsSearch.page = 1;
        
        ajax.friendsAction(query, userId, 1, function(response){
            if(response){
                var elements = response.search;
                for(var i in elements){
                    var element = elements[i];
                    var template = $(".friends.templates .listMosaicTemp .element").clone();
                    var usrLink = Routing.generate(appLocale + '_user_wall', {
                        'username':element.username
                    });
                    
                    template.find('a').attr('href', usrLink);
                    template.find('.name').html(element.name);
                    
                    if(element.image){
                        template.find('.avatar img').attr('src', element.image);
                    }else{
                        template.find('.avatar img').attr('src', '/fansworld/web/bundles/dodicifansworldweb/images/profile_no_image.png');
                    }
                    
                    $(".friends.listMosaic").append(template);
                }
            }
            if(response.gotMore){
                $("#addMore").removeClass('hidden');
                friendsSearch.page++;
            }else{
                $("#addMore").addClass('hidden');
            }
            $(".friends.listMosaic").show();
            $("div.ajax-loader").addClass('hidden');
        });
    }
};