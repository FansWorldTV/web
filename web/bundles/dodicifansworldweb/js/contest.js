var contest = {
    page: 1,
    participantsPage: 2,
    publicationsPage: 2,
    searchType: null,
    id: $("#contestId").val(),
    type: $("#contestType").val(),
    
    init: function(){
        $("#tabs").tabs();

        $("div.contenido .text").expander({
            slicePoint: 100,
            expandText: '[+]',
            userCollapse: false
        });
        
        $(".nota.loading").hide();
        contest.changeType();
        
        contest.id = $("#contestId").val();
        contest.type = $("#contestType").val();
        
        $("a.vote.button:not('.loading')").click(function(){
            var self = $(this);
            var participant = self.attr('participantId');
            
            self.addClass('loading');
            contest.participantVote(participant, function(r){
                self.removeClass('loading');
                if(r.voted){
                    $("a.vote.button").remove();
                }else{
                    error("Ya has votado");
                    $("a.vote.button").remove();
                }
                console.log(r);
            });
        });
        
        $("#addMore.contests").click(function(){
            contest.listAddMore();
        });
        
        $("a.contestParticipate").click(function(){
            return contest.participate(parseInt(contest.id), parseInt(contest.type), false, false);
        });
        
        $("#addMoreElements:not('.loading')").click(function(){
            return contest.publishedPager();
        });
        
        $("#addMoreParticipants:not('.loading')").click(function(){
            return contest.participantsPager();
        });
        
        contest.addComment();
        contest.commentTimeago();
    },
    
    search: function(filter){
        $("section.listContests div.list").html("");
        contest.page = 1;
        contest.searchType = filter;
        contest.listAddMore();
    },
    
    participantVote: function(participantId, callback){
        ajax.genericAction('contest_voteParticipant', {
            'participant': participantId,
            'contest': contest.id
        }, function(r){
            callback(r);
        }, function(error){
            console.error(error);
        });
    },
    
    participantsPager: function(){
        $("#addMoreParticipants").addClass('loading');
        ajax.genericAction('contest_pagerParticipants', {
            'contest': contest.id, 
            'page': contest.participantsPage
        }, function(r){
            console.log(r);
            $("#addMoreParticipants").removeClass('loading');
            for(var i in r.participants){
                var participant = r.participants[i];
                $("div#tabs-1 ul").append("<li>" + participant.name + "</li>");
            }
            if(!r.addMore){
                $("#addMoreParticipants").hide();
            }
            contest.participantsPage++;
        }, function(error){
            console.error(error);
        });
        return false;
    },
    
    publishedPager: function(){
        $("#addMoreElements").addClass('loading');
        ajax.genericAction('contest_pagerParticipants', {
            'contest': contest.id, 
            'page': contest.publicationsPage
        }, function(r){
            console.log(r);
            $("#addMoreElements").removeClass('loading');
            for(var i in r.participants){
                var participant = r.participants[i];
                $("#tabs-3 ul").append("<li>Element ID: " + participant.element.id + ", Author" + participant.name + "</li>");
            }
            contest.publicationsPage++;
            if(!r.addMore){
                $("#addMoreElements").remove();
            }
        }, function(error){
            console.error(error);
        });
        
        return false;
    },
    
    listAddMore: function(){
        ajax.contestsListAction(contest.page, contest.searchType, function(r){
            if(r){
                if(r.contests.length>0) {
                    for(var i in r.contests){
                        var element = r.contests[i];
                        var template = $("#templates .row").clone();
                        var contestShowUrl = Routing.generate( appLocale + '_contest_show', {
                            'id': element.id,
                            'slug': element.slug
                        });
                        
                        template.find("h2 a").html(element.title);
                        template.find("h2 a").attr('href', contestShowUrl);
                        template.find(".contenido a").attr('href', contestShowUrl);
                        template.find("div.media a").attr("href", contestShowUrl );
                        if(element.image){
                            template.find("div.media a").html('<img src="' + element.image + '" alt="" />');
                        }else{
                            template.find("div.media").remove();
                        }
                        template.find("div.contenido p").html(element.content);
                        template.removeClass('template');
                        $("section.listContests .list").append(template);
                    }
                }else if(contest.page == 1){
                    $("section.listContests .list").append('<div class="alert alert-error"><h3>No hay resultados</h3></div>');
                }
                
                if(r.addMore){
                    $("#addMore").removeClass('hidden');
                }else{
                    $("#addMore").addClass('hidden');
                }
                
                contest.page++;
            }
        });
    },
    
    changeType: function(){
        $('ul.contestType a').click(function(){
            var name = $(this).html();
            var type = $(this).parent().attr('class');
            contest.search(type);
        });
    },
    
    participate: function(contestId, contestType, photo, video){
        var text = false;
        var href = false;
        var iframe = false;
            
        if(contestType != 1){
            var inline = false;
            switch(contestType){
                case 2:
                    inline = true;
                    href = "#participateSplash ."+contestType;
                    iframe = false;
                    break;
                case 3:
                    href = Routing.generate( appLocale + '_photo_fileupload');
                    break;
                case 4:
                    href = Routing.generate(appLocale + '_video_fileupload');
                    break;
            }
                
            $.colorbox({
                name: 'colorboxFrame',
                iframe: iframe,
                width: 462,
                inline: inline, 
                href: href,
                onComplete: function(){
                    if(contestType !== 2){
                        redirectColorbox = false;
                        $("form.upload-video").attr('target', 'colorboxFrame');
                        $("form.upload-photo").attr('target', 'colorboxFrame');
                        createUploader();
                    }
                    resizePopup();
                }
            });
            
            $("#cboxLoadedContent input[type='button']").click(function(){
                ajax.contestParticipateAction(contestId, $("#cboxLoadedContent textarea").val(), photo, video, function(r){
                    if(r){
                        $("a.contestParticipate").parent().html('Ya estas participando');
                        $.colorbox.close();
                    }
                });
            });
        }else{
            ajax.contestParticipateAction(contestId, text, photo, video, function(r){
                if(r){
                    $("a.contestParticipate").parent().html('Ya estas participando');
                }
            });
        }
        return false;
    },
    
    addComment: function(){
        $("div.add_comment form a.btn").click(function(){
            var content = $("div.add_comment textarea").val();
            var contestId = $("div.add_comment form input.contestId").val();
            
            ajax.contestAddCommentAction(content, contestId, function(r){
                var template = $("#templates.contest div.comment").clone();
                template.find('div.avatar a').attr('href', Routing.generate(appLocale + '_user_wall', {
                    'username': r.comment.username
                }));
                
                if(r.comment.avatar){
                    template.find('div.avatar img').attr('src', r.comment.avatar);
                }
                
                template.find('div.user_comment span.action_user a').attr('href', Routing.generate(appLocale + '_user_wall', {
                    'username': r.comment.username
                }));
                template.find('div.user_comment span.action_user a').html(r.comment.name);
                template.find('div.user_comment span.action_user span').html(jQuery.timeago(r.comment.createdAt.date));
                template.find('div.user_comment').append(r.comment.content);
                template.append(r.comment.like);
                
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