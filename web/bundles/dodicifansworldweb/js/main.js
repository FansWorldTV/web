$(document).ready(function(){
    site.init();
    ajax.init();
    searchFront.init();
    searchIdols.init();
    friendsSearch.init();
    photos.init();
    albums.init();
});

var site = {
    timerPendingFriends : null,
    timerNotifications: null,
    isClosedNotificationess: true,
    isClosedRequests: true,
    
    init: function(){
        $(".navy ul li.alerts_user ul").hide();
        $(".navy ul li.notifications_user ul").hide();
        
        $("a.btn_picture").colorbox({
            'iframe': true,
            'innerWidth': 350,
            'innerHeight': 200
        });
        $('input:checkbox.prettycheckbox').checkbox({
            empty: emptyCheckboxImg
        });
        
        $('.change_image').colorbox({
            iframe: true, 
            innerWidth: 700, 
            innerHeight: 220
        });
        $(".btn_upload_photo").colorbox({
            iframe: true, 
            innerWidth: 700, 
            innerHeight: 455
        });
        
        $('.timeago').each(function(){
            $(this).html($.timeago($(this).attr('data-time')));
        });
        
        $.datepicker.setDefaults($.datepicker.regional[appLocale]);
        $('.datepicker').datepicker({
            dateFormat: 'dd/mm/yy',
            changeMonth: true,
            changeYear: true,
            yearRange: '-80'
        });
        
        $('.datetimepicker').datetimepicker({
            dateFormat: 'dd/mm/yy',
            timeFormat: 'hh:mm'
        });
    	
        $('.timepicker').datetimepicker({
            timeFormat: 'hhmm',
            timeOnly: true,
            stepMinute: 5
        });
    	
        site.listenPendingRequests();
        site.denyFriendRequest();
        site.acceptFriendRequest();
        site.getPendingFriends();
        site.getNotifications();
        site.readedNotification()
        site.likeButtons();
        site.shareButtons();
        site.globalCommentButtons();
        site.timerPendingFriends = setTimeout('site.listenPendingRequests()', 10000);
        site.timerNotifications = setTimeout('site.listenNotifications()', 10000);
        
    },
    listenPendingRequests: function(){
        ajax.numberPendingRequests(function(response){
            if(response){
                if(response.number > 0){
                    $("li.alerts_user a span").html(response.number).parent().removeClass('hidden');
                }
            }
        });
    },
    listenNotifications: function(){
        ajax.notificationNumberAction(function(response){
            if(response){
                if(response.number > 0){
                    $("li.notifications_user a span").html(response.number).parent().removeClass('hidden');
                }
            }
        });
    },
    getNotifications: function(){
        $("li.notifications_user a").click(function(){
            $("li.notifications_user ul").hide();
            $("li.notifications_user ul div.info").remove();
            $("li.notifications_user ul").toggle().append("<li class='clearfix loading'></li>");
            
            if(site.isClosedNotificationess){
                ajax.getNotifications(function(response){
                    if(response){
                        $("li.notifications_user ul li.loading").remove();
                        $("li.notifications_user ul div.info").remove();
                    
                        for(var i in response){
                            var element = response[i];
                            var newli = $('<li>').addClass('clearfix notification');
                            newli.html(element);
                            $("li.notifications_user ul li.more").before(newli);
                        }
                        
                        site.readedNotification();
                        var cant = $(".notifications_user a span").html();
                        parseInt(cant);
                        if(cant>5){
                            $("li.notifications_user ul li.more a").attr('href', Routing.generate(appLocale + '_user_notifications')).html(cant-5 + ' notificaciones mas').parent().removeClass('hidden');
                        }else{
                            $("li.notifications_user ul li.more").addClass('hidden');
                        }
                    } 
                });
                
                site.isClosedNotificationess = false;
            }else{
                $("li.notifications_user ul li.more").addClass('hidden');
                $("li.notifications_user ul li.loading").remove();
                $("li.notifications_user ul div.info").remove();
                $("li.notifications_user ul li.clearfix.notification").remove();
                
                site.isClosedNotificationess = true;
            }
        });
    },
    readedNotification: function(){
        $("li.notifications_user li.notification").hover(function(){
            var el = $(this).find('div.info');
            var notificationId = el.attr('notificationId');
            ajax.deleteNotification(notificationId, function(response){
                if(response === true){
                    var cant = $("li.notifications_user a span").html();
                    parseInt(cant);
                    cant--;
                    if(cant>0){
                        $(".notifications_user a span").html(cant);
                    }else{
                        $(".notifications_user a span").hide();
                    }
                    
                    el.parent().css('background', '#f4f3b8');
                }else{
                    console.log(response);
                }
            });
        });
    },
    getPendingFriends: function(){
        $("li.alerts_user a").click(function(){
            $("li.alerts_user ul").hide();
            $("li.alerts_user ul li.clearfix").remove();
            $("li.alerts_user ul").toggle().append("<li class='clearfix loading'></li>");
            
            if(site.isClosedRequests){
                ajax.pendingFriendsAction(1, 5, function(response){
                    if(response){
                        var cant = $("li.alerts_user a span").html();
                        parseInt(cant);
                        $("li.alerts_user ul li.clearfix").remove();
                  
                        for(var i in response.friendships){
                            var element = response.friendships[i];
                            var template = $("#templatePendingFriends ul li").clone();
                            if(element.user.image){
                                template.find("img.avatar").attr('src', element.user.image);
                            }
                            template.find("span.info a.name").attr('href', element.user.url).html(element.user.name);
                            template.find("a.deny").attr('id', element.friendship.id);
                            template.find("div.button a.accept").attr('id', element.friendship.id);
                      
                            $("li.alerts_user ul li.more").before(template);
                        }
                        
                        site.acceptFriendRequest();
                        site.denyFriendRequest();
                  
                        if(cant>5){
                            $("li.alerts_user ul li.more a").html(cant-5 + ' notificaciones mas').parent().removeClass('hidden');
                        }else{
                            $("li.alerts_user ul li.more").addClass('hidden');
                        }
                    } 
                });
                site.isClosedRequests = false;
            }else{
                $("li.alerts_user ul li.clearfix").remove();
                site.isClosedRequests = true;
            }
        });
    },
    acceptFriendRequest: function(){
        $("div.button a.accept").click(function(){
            var liElement = $(this).parent().parent();
            var friendshipId = $(this).attr('id');
            ajax.acceptRequestAction(friendshipId, function(response){
                if(response.error == false){
                    liElement.remove();
                    var cant = $("li.alerts_user a span").html();
                    parseInt(cant);
                    cant--;
                    $("li.alerts_user a span").html(cant);
                    if(cant<=0){
                        $("li.alerts_user a span").parent().addClass('hidden');
                    }
                    success('El usuario ha sido agregado como amigo');
                }else{
                    error('Se ha producido un error');
                }
            });
        });
        $("div.listElement a.accept").click(function(){
            var friendshipId = $(this).attr('id');
            var liElement = $(this).closest('div.listElement');
            ajax.acceptRequestAction(friendshipId, function(response){
                if(response.error == false){
                    liElement.remove();
                    var cant = $("li.alerts_user a span").html();
                    parseInt(cant);
                    cant--;
                    $("li.alerts_user a span").html(cant);
                    if(cant<=0){
                        $("li.alerts_user a span").parent().addClass('hidden');
                    }
                    success('El usuario ha sido agregado como amigo');
                }else{
                    error('Se ha producido un error');
                }
            });
        });
    },
    denyFriendRequest: function(){
        $("div.listElement a.deny").click(function(){
            var friendshipId = $(this).attr('id');
            var liElement = $(this).closest('div.listElement');
            ajax.denyRequestAction(friendshipId, function(response){
                if(response.error == false){
                    liElement.remove();
                    var cant = $("li.alerts_user a span").html();
                    parseInt(cant);
                    cant--;
                    $("li.alerts_user a span").html(cant);
                    if(cant<=0){
                        $("li.alerts_user a span").parent().addClass('hidden');
                    }
                    success('Se ha rechazado la amistad');
                }else{
                    error('Se ha producido un error');
                }
            });
            return false;
        });
        $("span.info a.deny").click(function(){
            var friendshipId = $(this).attr('id');
            var liElement = $(this).closest('li.clearfix');
            ajax.denyRequestAction(friendshipId, function(response){
                if(response.error == false){
                    liElement.remove();
                    var cant = $("li.alerts_user a span").html();
                    parseInt(cant);
                    cant--;
                    $("li.alerts_user a span").html(cant);
                    if(cant<=0){
                        $("li.alerts_user a span").parent().addClass('hidden');
                    }
                    success('Se ha rechazado la amistad');
                }else{
                    error('Se ha producido un error');
                }
            });
            return false;
        }); 
    },
    likeButtons: function(){
        $('.likebutton:not(.loading)').live('click',function(e){
            e.preventDefault();
            var el = $(this);
            var type = el.attr('data-type');
            var id = el.attr('data-id');
            el.addClass('loading');
        	
            ajax.likeToggleAction(type, id,
                function(response){
                    el.removeClass('loading');
                    el.text(response.buttontext);
                    el.siblings('.likecount:first').text(response.likecount);
                    success(response.message);
                },
                function(responsetext){
                    el.removeClass('loading');
                    error(responsetext);
                });
        	
        });
    },
    shareButtons: function(){
        $('.sharebutton:not(.loading,.disabled)').live('click',function(e){
            e.preventDefault();
            var el = $(this);
            var type = el.attr('data-type');
            var id = el.attr('data-id');
            el.addClass('loading');
        	
            ajax.shareAction(type, id,
                function(response){
                    el.removeClass('loading');
                    el.addClass('disabled');
                    success(response.message);
                },
                function(responsetext){
                    el.removeClass('loading');
                    error(responsetext);
                });
        	
        });
    },
    globalCommentButtons: function(){
        $('.comment_button:not(.loading)').live('click',function(e){
            e.preventDefault();
            var el = $(this);
            var type = el.attr('data-type');
            var id = el.attr('data-id');
            var ispin = (el.attr('data-pin') == 'true');
            var content = el.parents('.commentform').find('.comment_message').val();
            var privacy = el.parents('.commentform').find('.post_privacidad').val();
            el.addClass('loading');
        	
            ajax.globalCommentAction(type, id, content, privacy, ispin,
                function(response){
                    el.removeClass('loading');
                    success(response.message);
                    el.parents('.commentform').after(response.commenthtml);
                    $('.timeago').each(function(){
                        $(this).html($.timeago($(this).attr('data-time')));
                    });
                    if (ispin) {
                        $('.masonbricks').masonry().resize();
                    }
                },
                function(responsetext){
                    el.removeClass('loading');
                    error(responsetext);
                });
        	
        });
    }
}

var searchBox = {
    query: null,
    searchType: null,
    init: function(){
        $("div.search input").change(function(){
            searchBox.query = $(this).val();
        });
      
    }
    
};

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
            $(".searchFront.listMosaic").html('').hide();
            $("div.ajax-loader").removeClass('hidden');
        
            ajax.searchAction(query, 0, function(response){
                if(response){
                    var elements = response.search;
                    for(var i in elements){
                        var element = elements[i];
                        var template = $(".searchFront.templates .listMosaicTemp .element").clone();
                    
                        template.find('.name').html(element.name);
                        template.find('.commonFriends').html(element.commonFriends);
                        template.find('.avatar img').attr('src', element.image);
                    
                        if(element.isFriend){
                            template.addClass('isfriend');
                        }
                    
                        $(".searchFront.listMosaic").append(template);
                    }
                }
                
                $(".searchFront.listMosaic").show();
                $("div.ajax-loader").addClass('hidden');
            });
        
            event.preventDefault();        
            return false;
        });
    }
};

var searchIdols = {
    page: 0,
    init: function(){
        if($(".searchIdol").size()>0){
            searchIdols.addMore();
            searchIdols.search();
        }
    },
    addMore: function(){
        $("#addMore.searchIdol").click(function(){
            var query = this.parent().find('input#query').val();
        
            ajax.searchIdolsAction( query, searchIdols.page, null, function(response){
                if(response){
                    for(var i in response){
                        var elementTmp = $("div.templates.searchIdol div.listMosaic div.element").clone();
                    
                        elementTmp.find('.name').html(response[i].name);
                        elementTmp.find('.avatar').attr('src', response[i].image);
                        elementTmp.find('.commonFriends').html(response[i].commonFriends);
                   
                        $(".searchIdol.listMosaic").append(template);
                    }
                
                    if(response.gotMore){
                        $("#addMore").removeClass('hidden');
                        searchIdols.page++;
                    }else{
                        $("#addMore").addClass('hidden');
                    }
                }
            });
        });
    },
    search: function(){
        $("#formSearch.searchIdol").submit(function(event){
            var query = $('#query').val();
            $(".searchIdol.listMosaic").html('').hide();
            $("div.ajax-loader").removeClass('hidden');
        
            ajax.searchIdolsAction(query, 0, null, function(response){
                if(response){
                    var elements = response.idols;
                    for(var i in elements){
                        var element = elements[i];
                        var template = $(".searchIdol.templates .listMosaicTemp .element").clone();
                    
                        template.find('.name').html(element.name);
                        template.find('.commonFriends').html(element.commonFriends);
                        template.find('.avatar img').attr('src', element.image);
                    
                        if(element.isidol){
                            template.addClass('isidol');
                        }
                    
                        $(".searchIdol.listMosaic").append(template);
                    }
                }
                
                $(".searchIdol.listMosaic").show();
                $("div.ajax-loader").addClass('hidden');
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
            $(".friends.listMosaic").html('').hide();
            $("div.ajax-loader").removeClass('hidden');
        
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
                
                $(".friends.listMosaic").show();
                $("div.ajax-loader").addClass('hidden');
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
        $(".nota.loading").hide();
        contest.changeType();
        
        $("#addMore.contests").click(function(){
            contest.listAddMore();
        });
        
        contest.participate();
        contest.addComment();
        contest.commentTimeago();
    },
    
    search: function(filter){
        $("div.nota:not('.template'):not('.loading'), #addMore").remove();
        contest.page = 1;
        contest.searchType = filter;
        contest.listAddMore();
    },
    
    listAddMore: function(){
        $(".nota.loading").show();
        ajax.contestsListAction(contest.page, contest.searchType, function(r){
            if(r){
                $(".nota.loading").hide();
                if(r.contests.length>0) {
                    for(var i in r.contests){
                        var element = r.contests[i];
                        var template = $("#templates .nota").clone();
                        var contestShowUrl = Routing.generate( appLocale + '_contest_show', {
                            'id': element.id,
                            'slug': element.slug
                        });
                        
                        template.find("h2 a").html(element.title);
                        template.find("h2 a").attr('href', contestShowUrl);
                        template.find(".contenido .options a").attr('href', contestShowUrl);
                        template.find("div.media a").attr("href", contestShowUrl );
                        if(element.image){
                            template.find("div.media a").html('<img src="' + element.image + '" alt="" />');
                        }else{
                            template.find("div.media").remove();
                        }
                        template.find("div.contenido p").html(element.content);
                        template.removeClass('template');
                        $("div.contests div.cont").append(template);
                    }
                }else if(contest.page == 1){
                    $("div.contests div.cont").append('<div class="nota"><h2>No hay resultados</h2></div>');
                }
                
                
                contest.page++;
            }
        });
    },
    
    changeType: function(){
        $('ul.contestType a').click(function(){
            var name = $(this).html();
            var type = $(this).parent().attr('class');
            $(".titleContestType").html(name);
            contest.search(type);
        });
    },
    
    participate: function(){
        $("a.contestParticipate").click(function(){
            var contestType = $(this).attr("contestType");
            var text = false;
            var photo = false;
            var video = false;
            
            if(contestType != 1){
                $.colorbox({
                    inline: true, 
                    href: "#participateSplash ."+contestType
                });
                
                switch(contestType) {
                    case 2:
                        text = $("#cboxLoadedContent").val();
                        break;
                }
                $("#cboxLoadedContent input[type='button']").click(function(){
                    var contestId = $(this).attr('contestId');
                    ajax.contestParticipateAction(contestId, text, photo, video, function(r){
                        if(r){
                            
                            $("a.contestParticipate").parent().html('Ya estas participando');
                        }
                    });
                });
            }else{
                var contestId = $(this).attr('contestId');
                ajax.contestParticipateAction(contestId, text, photo, video, function(r){
                    if(r){
                        $("a.contestParticipate").parent().html('Ya estas participando');
                    }
                });
            }
            
            return false; 
        });
    },
    
    addComment: function(){
        $("div.add_comment form a.btn").click(function(){
            var content = $("div.add_comment textarea").val();
            var contestId = $("div.add_comment form input.contestId").val();
            
            ajax.contestAddCommentAction(content, contestId, function(r){
                console.log(r);
                var template = $("#templates.contest div.comment").clone();
                template.find('div.avatar a').attr('href', Routing.generate(appLocale + '_user_detail', {
                    'id': r.comment.id
                }));
                
                if(r.comment.avatar){
                    template.find('div.avatar img').attr('src', r.comment.avatar);
                }
                
                template.find('div.user_comment span.action_user a').attr('href', Routing.generate(appLocale + '_user_detail', {
                    'id': r.comment.id
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

var photos = {
    pager : 2,
    
    init: function(){
        photos.get();  
    },
    
    get : function(){
        $("a.loadmore.pictures").click(function(){
            $("a.loadmore.pictures").addClass('loading');
            var userid = $("#userid").val();
            ajax.getPhotosAction(userid, photos.pager, false, function(r){
                if(r){
                    for(var i in r['images']){
                        var ele = r['images'][i];
                        var template = $("#templates .album_cover").clone();
                        var href = Routing.generate(appLocale + '_photo_show', {
                            'id': ele.id,
                            'slug': ele.slug
                        });
                        template.find(".image").attr("href", href);
                        template.find(".image img").attr("src", ele.image);
                        template.find(".title").attr("href", href).html(ele.title);
                        template.find("span").html("<a href='" + href+ "'>" + ele.comments + " comentarios</a>");
                        template.find('a img').attr('src', ele.image);
                    
                        $("div.album_covers div.mask").append(template);
                    }
                    if(!r['gotMore']){
                        $("a.loadmore.pictures").hide();
                    }
                    photos.pager++;
                    $("a.loadmore.pictures").removeClass('loading');
                }
            });
        });
    }
};

var albums = {
    pager: 2,
    
    init: function(){
        albums.get();  
    },
    
    get: function(){
        $("a.loadmore.albums").click(function(){
            $(this).addClass('loading');
            var userid = $("#userid").val();
            ajax.getAlbumsAction(userid, albums.pager, function(r){
                if(r){
                    for(var i in r['albums']){
                        var ele = r['albums'][i];
                        var template = $("div#templates div.album_cover").clone();
                        var href = Routing.generate(appLocale + '_album_show', {
                            'id':ele.id
                        });
                        template.find(".image").attr("href", href);
                        template.find(".image img").attr("src", ele.image);
                        template.find(".title").attr("href", href).html(ele.title);
                        template.find("span").html(ele.countImages + " imágenes - <a href='" + Routing.generate(appLocale + '_album_show', {
                            'id':ele.id
                        }) + "'>" + ele.comments + " comentarios</a>");
                    
                        $("div.album_covers div.mask").append(template);
                    }
                    if(!r['gotMore']){
                        $("a.loadmore.albums").hide();
                    }
                    albums.pager++;
                    $("a.loadmore.albums").removeClass('loading');
                }
            });
        });
    }
}

function resizeColorbox(options) {
    $.colorbox.resize(options);
}