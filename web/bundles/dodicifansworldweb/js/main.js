$(document).ready(function(){
    ajax.init();
    searchFront.init();
    friendsSearch.init();
});

var searchFront = {
    page: 0,
    init: function(){
        if($(".searchFront").size()>0){
            searchFront.addMore();
            searchFront.search();
        }
    },
    addMore: function(){
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
    search: function(){
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
    page: 1,
    init: function(){
        if($(".friends").size()>0){
            friendsSearch.addMore();
            friendsSearch.search();
        }
    },
    addMore: function(){
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
    search: function(){
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

var contest = {
    page: 1,
    searchType: null,
    
    init: function(){
        contest.changeType();
        
        $("#addMore.contests").click(function(){
            contest.listAddMore();
        });
        
        contest.participate();
        contest.addComment();
        contest.commentTimeago();
    },
    
    listAddMore: function(){
        ajax.contestsListAction(contest.page, contest.searchType, function(r){
            if(r){
                for(var i in r.contests){
                    var element = r.contests[i];
                    var template = $("#templates.contests .nota").clone();
                    var contestShowUrl = Routing.generate( appLocale + '_contest_show', {
                        'id': element.id
                    });
                        
                    template.find("h2 a").html(element.title);
                    template.find("h2 a").attr('href', contestShowUrl);
                    template.find("div.media a").attr("href", contestShowUrl );
                    template.find("div.media a").html('<img src="' + element.image + '" alt="" />');
                    template.find("div.contenido p").html(element.content);
                }
                contest.page++;
            }
        });
    },
    
    changeType: function(){
        $('ul.contestType a').click(function(){
            var type = $(this).parent().attr('class');
            contest.searchType = type;
            contest.page = 1;
            contest.listAddMore();
        });
    },
    
    participate: function(){
        $("a.contestParticipate").click(function(){
            var contestId = $(this).attr('contestId');
            ajax.contestParticipateAction(contestId, function(r){
                if(r){
                    console.log('participando!');
                }
            });
            return false; 
        });
    },
    
    addComment: function(){
        var content = $("div.add_comment form textarea").val();
        var contestId = $("div.add_comment form input.contestId").val();
        
        $("div.add_comment form a.btn").click(function(){
            ajax.contestAddCommentAction(content, contestId, function(r){
                var template = $("#templates.contest div.comment").clone();
                template.find('div.avatar a').attr('href', Routing.generate(appLocale + '_user_detail', {
                    'id': r.comment.id
                    }));
                template.find('div.avatar img').attr('src', r.comment.avatar);
                template.find('div.user_comment span.action_user a').attr('href', Routing.generate(appLocale + '_user_detail', {
                    'id': r.comment.id
                    }));
                template.find('div.user_comment span.action_user a').html(r.comment.name);
                template.find('div.user_comment span.action_user span').html(jQuery.timeago(r.comment.createdAt));
                
                $("div.comments").append(template);
            });
        });
    },
    
    commentTimeago: function(element){
        var commentTime = $("div.comments div.comment span.action_user span");
        commentTime.each(function(index, value){
            $(value).html(jQuery.timeago($(value).html()));
        });
    }
};