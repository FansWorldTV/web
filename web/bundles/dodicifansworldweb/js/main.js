$(document).ready(function(){
    ajax.init();
    searchFront.init();
    friendsSearch.init();
});

var searchFront = {
    'page': 0,
    'init': function(){
        if($(".searchFront").size()>0){
            searchFront.addMore();
            searchFront.search();
        }
    },
    'addMore': function(){
        $("#addMore.searchFront").click(function(){
            var query = this.parent().find('input#query').val();
        
            ajax.searchAction( query, searchFront.page, function(response){
                if(response){
                    for(var i in response){
                        var elementTmp = $("div.templates.searchFront div.listMosaic div.element").clone();
                    
                        elementTmp.find('.name').html(response[i].name);
                        elementTmp.find('.avatar').attr('src', response[i].image);
                        elementTmp.find('.commonFriends').html(response[i].commonFriends);
                   
                        $(".searchFront.listMosaic").append(template);
                    }
                
                    if(response.gotMore){
                        $("#addMore").removeClass('hidden');
                        searchFront.page++;
                    }else{
                        $("#addMore").addClass('hidden');
                    }
                }
            });
        });
    },
    'search': function(){
        $("#formSearch.searchFront").submit(function(event){
            var query = $('#query').val();
            $(".searchFront.listMosaic").html('');
        
            ajax.searchAction(query, 0, function(response){
                if(response){
                    var elements = response.search;
                    for(var i in elements){
                        var element = elements[i];
                        var template = $(".searchFront.templates .listMosaicTemp .element").clone();
                    
                        template.find('.name').html(element.name);
                        template.find('.commonFriends').html(element.commonFriends);
                        template.find('.avatar img').attr('src', element.image);
                    
                        $(".searchFront.listMosaic").append(template);
                    }
                }
            });
        
            event.preventDefault();        
            return false;
        });
    }
};
var friendsSearch = {
    'page': 0,
    'init': function(){
        if($(".friends").size()>0){
            friendsSearch.addMore();
            friendsSearch.search();
        }
    },
    'addMore': function(){
        $("#addMore.friends").click(function(){
            var query = this.parent().find('input#query').val();
        
            ajax.friendsAction( query, friendsSearch.page, function(response){
                if(response){
                    for(var i in response){
                        var elementTmp = $("div.templates.friends div.listMosaic div.element").clone();
                    
                        elementTmp.find('.name').html(response[i].name);
                        elementTmp.find('.avatar').attr('src', response[i].image);
                        elementTmp.find('.commonFriends').html(response[i].commonFriends);
                   
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
    'search': function(){
        $("#formSearch.friends").submit(function(event){
            var query = $('#query').val();
            $(".friends.listMosaic").html('');
        
            ajax.friendsAction(query, 0, function(response){
                if(response){
                    var elements = response.search;
                    for(var i in elements){
                        var element = elements[i];
                        var template = $(".friends.templates .listMosaicTemp .element").clone();
                    
                        template.find('.name').html(element.name);
                        template.find('.commonFriends').html(element.commonFriends);
                        template.find('.avatar img').attr('src', element.image);
                    
                        $(".friends.listMosaic").append(template);
                    }
                }
            });
        
            event.preventDefault();        
            return false;
        });
    }
};