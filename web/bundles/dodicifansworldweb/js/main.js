var redirectColorbox = true;

$(document).ready(function(){
    site.init();
    ajax.init();
    searchFront.init();
    searchIdols.init();
    friendsSearch.init();
    friendship.init();
    photos.init();
    albums.init();
    videos.init();
    forum.init();
});

var site = {
    timerPendingFriends : null,
    timerNotifications: null,
    isClosedNotificationess: true,
    isClosedRequests: true,
    
    init: function(){
        $("#video-search-form").live('submit', function(){
            $(this).addClass('loading');
        });
        $("ul.friendgroupsList").show();
        $("li.alerts_user a span").hide().parent().removeClass('hidden');
        
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
        
        $('.report').colorbox({
            'iframe': true,
            'innerWidth': 350,
            'innerHeight': 200
        });
        
        $(".btn_upload_photo,.btn_upload_video,.editbutton").colorbox({
            iframe: true, 
            innerWidth: 700, 
            innerHeight: 455
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
        
        $('input[data-default-text]').each(function(){
            $(this).val($(this).attr('data-default-text'))
            .addClass('graytext')
            .focus(
                function(e){
                    if ($(this).val() == $(this).attr('data-default-text')) {
                        $(this).val('');
                        $(this).removeClass('graytext');
                    }
                }
                )
            .blur(
                function(e){
                    if ($(this).val() == '') {
                        $(this).addClass('graytext');
                        $(this).val($(this).attr('data-default-text'));
                    }
                }
                );
        });
    	
        site.parseTimes();
        site.listenPendingRequests();
        site.listenNotifications();
        site.denyFriendRequest();
        site.acceptFriendRequest();
        site.getPendingFriends();
        site.getNotifications();
        site.readedNotification()
        site.likeButtons();
        site.shareButtons();
        site.globalCommentButtons();
        site.globalDeleteButtons();
        site.expander();
        site.showCommentForm();
    },
    
    parseTimes: function(){
        $('.timeago').each(function(){
            $(this).html($.timeago($(this).attr('data-time')));
        });
    },
    
    showCommentForm: function(){
        $(".showCommentForm").live('click',function(){
            $(this).parent().find('div.form').toggleClass('hidden');
            return false;
        });
    },
    
    listenPendingRequests: function(){
        ajax.numberPendingRequests(function(response){
            if(response){
                if(response.number > 0){
                    var actualNumber = $("li.alerts_user a span").html();
                    parseInt(actualNumber);
                    
                    $("li.alerts_user a span").html(response.number).parent().removeClass('hidden');
                    
                    if(actualNumber < response.number){
                        $(".alerts_user a:first span").effect("highlight",{},3000);
                        $(".alerts_user a:first").effect("bounce",{
                            times:1
                        },300);
                    }
                }
                site.timerPendingFriends = setTimeout('site.listenPendingRequests()', 10000);
            }
        });
    },
    
    listenNotifications: function(){
        function handleData(response){
            if(response){
                var actualNumber = $("li.notifications_user a span").html();
                var number = 0;
                if(actualNumber == ''){
                    number = 0;
                }else{
                    number = parseInt(actualNumber);
                }
                number++;
                $("li.notifications_user a span").html(number).show();
                $(".notifications_user a:first span").effect("highlight",{},3000);
                $(".notifications_user a:first").effect("bounce",{
                    times:1
                },300);
                
                site.getNotification(response);
            }
        }
            
        if ((typeof Meteor != 'undefined') && (typeof notificationChannel != 'undefined')) {
            Meteor.registerEventCallback("process", handleData);
            Meteor.joinChannel(notificationChannel, 0);
            Meteor.mode = 'stream';
	            
            // Start streaming!
            //Meteor.connect();
            console.log('Escuchando notificaciones...');
        }
    },
    
    getNotification: function(id){
        var classes = 'clearfix notification loading';
        if(site.isClosedNotificationess){
            classes += ' hidden';
        }
        $("li.notifications_user ul").prepend("<li id='notification_" + id +"' class='" + classes + "'></li>");
        
        ajax.getNotification(id, function(response){
            if(response){
                var countNotifications = $("li.notifications_user ul li div.info").size();
                if(countNotifications>4){
                    $("li.notifications_user ul li div.info").last().parent().remove();
                }
                $("#notification_" + id).html(response).removeClass('loading');
                
                if($("#notificationsList").size()>0){
                    $("#notificationsList").prepend(response);
                }
                
                if(site.isClosedNotificationess){
                    $("#notification_" + id).addClass('hidden');
                }
            }
        });
    },
    
    getNotifications: function(){
        $("li.notifications_user ul li").addClass('hidden');
        $("li.notifications_user a").live('click', function(){
            $("li.notifications_user ul li").toggleClass('hidden');
            if($("li.notifications_user ul li.hidden").size() > 0){
                site.isClosedNotificationess = true;
            }else{
                site.isClosedNotificationess = false;
            }
        });
    },
    
    readedNotification: function(){
        $("li.notifications_user li.notification:not('.readed')").hover(function(){
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
                  
                        if(response.total > 0){
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
                        }else{
                            $("li.alerts_user ul li.more").before('<li class="clearfix">No hay solicitudes pendientes</li>');
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
            
            askText(function(text){
                var type = el.attr('data-type');
                var id = el.attr('data-id');
                el.addClass('loading');
            	
                ajax.shareAction(type, id, text,
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
                    el.closest('.commentform').find('textarea.comment_message').val('');
                    el.removeClass('loading');
                    success(response.message);
                    el.parents('.commentform').after(response.commenthtml);
                    $('.timeago').each(function(){
                        $(this).html($.timeago($(this).attr('data-time')));
                    });
                    if (ispin) {
                        $('.masonbricks').isotope().resize();
                    }
                    site.expander();
                },
                function(responsetext){
                    el.removeClass('loading');
                    error(responsetext);
                });
        	
        });
    	
        $('textarea.comment_message').live('keydown', function (e) {
            if ( e.keyCode == 13 ){
                $(this).closest('.commentform').find('.comment_button').click();
                return false;
            }
        });
    },
    
    globalDeleteButtons: function(){
        $('.deletebutton:not(.loading)').live('click',function(e){
            e.preventDefault();
            
            var el = $(this);
            if (confirm('¿Seguro desea eliminar?')) {
                var type = el.attr('data-type');
                var id = el.attr('data-id');
                el.addClass('loading');
	        	
                ajax.globalDeleteAction(type, id,
                    function(response){
                        el.removeClass('loading');
                        success(response.message);
                        if (response.redirect) {
                            window.location.href = response.redirect;
                        }
                    },
                    function(responsetext){
                        el.removeClass('loading');
                        error(responsetext);
                    });
            }
        });
        
        $('.deletecomment:not(.loading)').live('click',function(e){
            e.preventDefault();
            
            var el = $(this);
            if (confirm('¿Seguro desea eliminar el comentario?')) {
                var type = 'comment';
                var id = el.attr('data-id');
                el.addClass('loading');
	        	
                ajax.globalDeleteAction(type, id,
                    function(response){
                        success(response.message);
                        el.parent().slideUp('fast',function(){
                            $(this).remove();
                        });
                    },
                    function(responsetext){
                        el.removeClass('loading');
                        error(responsetext);
                    });
            }
        });
    },
    
    expander: function() {
        $('.actualizacion .comments .status_comment .status_comment_user p, ' +
            '.actualizacion .status p, ' +
            '.pincomments .comment .message')
        .not(':has(.read-more)')
        .expander({
            slicePoint: 100,
            expandText: '[+]',
            userCollapseText: '[-]'
        });
    }
}

var friendship = {
    init: function(){
        
        if ($("ul.friendgroupsList li input:checkbox").length) {
            $("ul.friendgroupsList").hide();
            $("div.addFriend").hover(
                function(){
                    if ($(this).has('.btn_friendship.add')) {
                        friendGroupList = $("ul.friendgroupsList").slideDown('normal');
                    }
                },
                function(){
                    if ($(this).has('.btn_friendship.add')) {
                        friendGroupList = $("ul.friendgroupsList").slideUp('normal');
                    }
                }
                );
        }
            
        friendship.add();
        friendship.cancel();
    },
    
    add: function(){
        $(".btn_friendship.add:not('.loading')").live('click', function(){
            var self = $(this);
            self.addClass('loading');
            
            var targetId = $(this).closest('div.addFriend').attr('targetId');
            var friendgroups = [];
            
            $("ul.friendgroupsList li input:checkbox:checked").each(function(k, el){
                friendgroups[k] = $(el).val();
            });
            
            ajax.addFriendAction(targetId, friendgroups, function(response){
                if(!response.error){
                    if (response.active) {
                        self.removeClass('add').addClass('remove').attr('friendshipId', response.friendship).text('Dejar de Seguir');
                    } else {
                        self.removeClass('add').addClass('remove').attr('friendshipId', response.friendship).text('Cancelar Solicitud');
                    }
                    success(response.message);
                }else{
                    error(response.error);
                }
                self.removeClass('loading');
            });
        });
    },
    
    cancel: function(){
        $(".btn_friendship.remove:not('.loading')").live('click', function(){
            var self = $(this);
            var friendshipId = $(this).attr('friendshipId');
            if(confirm('Seguro deseas dejar de seguir a este fan?')){
                self.addClass('loading');
                ajax.cancelFriendAction(friendshipId, function(response){
                    if(!response.error) {
                        window.location.reload();  
                    }else{
                        error(response.error);
                        self.removeClass('loading');
                    }
                });
            }
        });
    }
};

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
                            template.find('.avatar img').attr('src', '/fansworld/web/bundles/dodicifansworldweb/images/user_pic.jpg');
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
                        template.find('.avatar img').attr('src', '/fansworld/web/bundles/dodicifansworldweb/images/user_pic.jpg');
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

var contest = {
    page: 1,
    participantsPage: 1,
    publicationsPage: 1,
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
        
        $("#addMoreParticipants:not('.loading')").click(function(){
            contest.participantsPager();
        });
        
        contest.addComment();
        contest.commentTimeago();
    },
    
    search: function(filter){
        $("div.nota:not('.template'):not('.loading'), #addMore").remove();
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
        ajax.genericAction('contest_pagerParticipants', {
            'contest': contest.id, 
            'page': contest.participantsPage
        }, function(r){
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
    },
    
    publishedPager: function(){
        ajax.genericAction('contest_pagerParticipants', {
            'contest': contest.id, 
            'page': contest.publicationsPage
        }, function(r){
            console.log(r);
            contest.publicationsPage++;
        }, function(error){
            console.error(error);
        });
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
    
    participate: function(contestId, contestType, photo, video){
        var text = false;
        var href = false;
        var iframe = true;
            
        if(contestType != 1){
            var inline = false;
            switch(contestType){
                case 2:
                    inline = true;
                    href = "#participateSplash ."+contestType;
                    iframe = false;
                    break;
                case 3:
                    href = Routing.generate( appLocale + '_photo_upload');
                    break;
                case 4:
                    href = Routing.generate(appLocale + '_video_upload');
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
                    }
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

var videos = {
    pager: 1,
    
    init: function(){
        videos.search();
        videos.addMore();
        videos.searchByCategory.search();
        videos.searchByCategory.addMore();
        videos.searchByTags.addMore();
        videos.searchMyVideos.addMore();
        videos.usersVideos.addMore();
    },
    
    addSearchContent: function(template, video, videoUrl){
        template.find('h2 a').attr('href', videoUrl);
        template.find('h2 a').html(video.title);
                      
        if(video.image){
            template.find('a.tmpVideo').attr('href', videoUrl);
            template.find('a.tmpVideo img').attr('src', video.image).attr('alt', video.title).attr('title', video.title);
        }else{
            template.find('a.tmpVideo').remove();
        }
                        
        template.find('.tmpContent').html(video.content + "...");
        template.find('span a.user').attr('href', Routing.generate(appLocale + '_user_wall', {
            'username': video.author.username
        }));
        if(video.author.avatar){
            template.find('span a.user').html('<img src="'+video.author.avatar+'" />'+video.author.name);
        }else{
            template.find('span a.user').html(video.author.name);
        }
        template.find('span.timeago').attr('data-time', video.date).html(video.date);

        if(video.tags.length>0){
            for(var j in video.tags){
                var tag = video.tags[j];
                template.find('.tags').append("<li><a href='" + Routing.generate(appLocale + '_video_tags', {
                    'slug':tag.slug
                }) + "'>" + tag.title + "</a></li>");
            }
        }else{
            template.find('.hasTags').hide();
        }
                        
        $("div.cont ul.videos").append(template);
                        
        site.parseTimes();
    },
    
    addMore: function(){
        $("#addMore.videos:not('.loading')").live('click', function(){
            if(videos.pager==1){
                videos.pager++;
            }
            
            var self = $(this);
            self.addClass('loading');
            
            ajax.videosSearchAction({
                'page': videos.pager
            }, function(response){
                if(response){
                    if(!response.addMore){
                        self.remove();
                    }
                    for(var i in response.videos){
                        var video = response.videos[i];
                        var template = $("#templates ul.videos li").clone();
                        var videoUrl = Routing.generate(appLocale + '_video_show', {
                            'id':video.id, 
                            'slug': video.slug
                        });
                        videos.addSearchContent(template, video, videoUrl);
                    }
                    videos.pager++;
                }
                self.removeClass('loading');
            }, function(text){
                error(text);
                self.removeClass('loading');
            });
        });
    },
    
    search: function(){
        $(".search.videos #formSearch:not('.loading')").live('submit', function(){
            $("div.cont ul.videos li").remove();
            var query = $("#query").val();
            var self = $(this);
            
            self.addClass('loading');
            videos.pager = 1;
            ajax.videosSearchAction({
                'query': query, 
                'page': 1
            }, function(response){
                if(response){
                    if(response.addMore){
                        $("div.cont").append('<a href="#" id="addMore" class="loadmore videos marginTop10">Agregar Más</a>');
                    }
                    for(var i in response.videos){
                        var video = response.videos[i];
                        var template = $("#templates ul.videos li").clone();
                        var videoUrl = Routing.generate(appLocale + '_video_show', {
                            'id':video.id, 
                            'slug': video.slug
                        });
                        videos.addSearchContent(template, video, videoUrl);
                    }
                    videos.pager++;
                }
                self.removeClass('loading');
            }, function(){});
            
            return false;
        });
    },
    
    usersVideos: {
        addMore: function(){
            $("#addMore.userVideos").live('click', function(){
                var self = $(this);
                var query = $("input.video-search-input").val();
                query = query == "" ? null : query;
                ajax.usersVideosAction({
                    'query': query,
                    'page': videos.pager
                }, function(response){
                    if(response){
                        if(!response.addMore){
                            self.remove();
                        }
                        for(var i in response.videos){
                            var video = response.videos[i];
                            $("ul.user-videos").append(video);
                        }
                        videos.pager++;
                    }
                }, function(error){
                    console.error(error);
                });
                return false;
            });
        }
    },
    
    searchByCategory: {
        addMore: function(){
            if($("#categoryId").length > 0){
                $("#addMore.videosByCategory:not('.loading')").live('click', function(){
                    if(videos.pager==1){
                        videos.pager++;
                    }
                    var self = $(this);
                    self.addClass('loading');
            
                    ajax.videoCategoryAction({
                        'category': $("#categoryId").val(),
                        'page': videos.pager,
                        'query': $("input.video-search-input").val() == "" ? null: $("input.video-search-input").val()
                    }, 
                    function(response){
                        if(response){
                            if(!response.addMore){
                                self.remove();
                            }
                            for(var i in response.vids){
                                var vid = response.vids[i];
                                $("ul.user-videos").append(vid.view);
                            }
                            videos.pager++;
                        }
                        self.removeClass('loading');
                    }, 
                    function(response){
                        console.error(response);
                        self.removeClass('loading');
                    });
                
                    return false;
                })
            }
        },
        search: function(){
            if($("#categoryId").length > 0){
                $("#video-search-form:not('.loading')").live('submit', function(){
                    $("div.cont ul.user-videos li").remove();
                    var query = $("input.video-search-input").val() == "" ? null: $("input.video-search-input").val();
                    var self = $(this);
            
                    self.addClass('loading');
                    videos.pager = 1;
                
                    ajax.videoCategoryAction({
                        'category': $("#categoryId").val(),
                        'page': videos.pager,
                        'query': query
                    }, 
                    function(response){
                        if(response){
                            if(response.addMore){
                                $("div.cont").append('<div class="morelink"><a id="addMore" class="videosByCategory" href="">Ver más</a></div>');
                            }else{
                                $("#addMore").remove();
                            }
                            if(response.vids){
                                for(var i in response.vids){
                                    var vid = response.vids[i];
                                    $("ul.user-videos").append(vid.view);
                                }
                                videos.pager++;
                            }
                        }
                        self.removeClass('loading');
                    }, 
                    function(response){
                        console.error(response);
                        self.removeClass('loading');
                    });
                
                    return false;
                });
            }
        }
    },
    
    searchByTags: {
        addMore: function(){
            $("#addMore.videosByTag:not('.loading')").live('click', function(){
                if(videos.pager==1){
                    videos.pager++;
                }
                var self = $(this);
                var tagId = $("a.current").attr('tagId');
                self.addClass('loading');
            
                ajax.searchByTagAction(tagId, videos.pager, function(response){
                    if(response){
                        if(!response.addMore){
                            self.remove();
                        }
                        for(var i in response.videos){
                            var video = response.videos[i];
                            var template = $("#templates ul.videos li").clone();
                            var videoUrl = Routing.generate(appLocale + '_video_show', {
                                'id':video.id, 
                                'slug': video.slug
                            });
                            videos.addSearchContent(template, video, videoUrl);
                        }
                        videos.pager++;
                    }
                    self.removeClass('loading');
                }, function(text){
                    error(text);
                    self.removeClass('loading');
                });
                return false;
            });
        }
    },
    
    searchMyVideos: {
        addMore: function(){
            $("#addMore.myVideos:not('.loading')").live('click', function(){
                if(videos.pager==1){
                    videos.pager++;
                }
                var self = $(this);
                var userid = $("#userId").val();
                self.addClass('loading');
            
                ajax.searchMyVideos(userid, videos.pager, function(response){
                    if(response){
                        if(!response.addMore){
                            self.remove();
                        }
                        for(var i in response.videos){
                            var video = response.videos[i];
                            var template = $("#templates ul.videos li").clone();
                            var videoUrl = Routing.generate(appLocale + '_video_show', {
                                'id':video.id, 
                                'slug': video.slug
                            });
                            videos.addSearchContent(template, video, videoUrl);
                        }
                        videos.pager++;
                    }
                    self.removeClass('loading');
                }, function(text){
                    error(text);
                    self.removeClass('loading');
                });
                return false;
            });
        }
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
};

var forum = {
    page: 2,
    postPage: 2,
    
    init: function(){
        forum.addMoreThreads();
        forum.addMorePosts();
        forum.commentThread();
    },
    addMoreThreads: function(){
        $("#addMore.threads").live('click', function(){
            var userId = $("#userId").val() == "" ? false : $("#userId").val();
            var self = $(this);
            self.addClass('loading');
            ajax.searchForumThreads(forum.page, userId, function(response){
                if(response){
                    if(!response.addMore){
                        self.remove();
                    }
                    for(var i in response.threads){
                        var thread = response.threads[i];
                        var template = $("#templates.threads li").clone();
                        var href = Routing.generate(appLocale + '_forum_thread', {
                            'id': thread.id, 
                            'slug': thread.slug
                        });
                        template.find('a').attr('href', href);
                        template.find('a div.messages').prepend(thread.postCount + " ");
                        template.find('a div.post h3').html(thread.title);
                        template.find('a div.post span.contentTruncated').html(thread.content);
                        template.find('a div.post span.user').html(thread.author.name);
                        template.find('a div.post span.date').attr('data-time', thread.createdAt).html(thread.createdAt);
                        
                        $("ul.foro").append(template);
                    }
                    site.parseTimes();
                    forum.page++;
                }
                self.removeClass('loading');
            });
            return false;
        });
    },
    addMorePosts: function(){
        $("#addMore.posts").live('click', function(){
            var threadId = $("#threadId").val();
            var self = $(this);
            self.addClass('loading');
           
            ajax.searchThreadPosts(threadId, forum.postPage, function(response){
                if(response){
                    if(!response.addMore){
                        self.remove();
                    }
                    for(var i in response.posts){
                        var post = response.posts[i];
                        var template = $("#templates div.comment").clone();
                        var href = Routing.generate(appLocale + '_user_wall', {
                            'username': post.author.username
                        });
                       
                        template.find('a').attr('href', href);
                        template.find('div.avatar a img').attr('src', post.author.image);
                        template.find('div.user_comment a').html(post.author.name);
                        template.find('div.user_comment span.timeago').attr('data-time',post.createdAt).html(post.createdAt);
                        template.find('div.user_comment').append(post.content);
                       
                        $("div.comments").append(template);
                    }
                   
                    site.parseTimes();
                    forum.postPage++;
                }
            });
           
        });
    },
    commentThread: function(){
        $("div.add_comment a.btn.comment").live('click', function(){
            var self = $(this);
            var text = self.parent().find('textarea').val();
            var threadId = $("#threadId").val();
            
            self.addClass('loading');
            
            if($("div.comments").has('div.comment').length == 0){
                $("div.comments").html('');
            }
            
            ajax.threadCommentAction(threadId, text, function(response){
                if(!response.error){
                    var template = $("#templates div.comment").clone();
                    var href = Routing.generate(appLocale + '_user_wall', {
                        'username': response.data.author.username
                    });
                       
                    template.find('a').attr('href', href);
                    template.find('div.avatar a img').attr('src', response.data.author.avatar);
                    template.find('div.user_comment a').html(response.data.author.name);
                    template.find('div.user_comment span.timeago').attr('data-time',response.data.createdAt).html(response.data.createdAt);
                    template.find('div.user_comment').append(response.data.content);
                       
                    $("div.comments").prepend(template);
                    site.parseTimes();
                    self.removeClass('loading');
                    self.parent().find('textarea').val('');
                }
            });
        });
    }
    
};

function resizeColorbox(options) {
    $.colorbox.resize(options);
}

function askText(callback) {
    $.colorbox({
        href: Routing.generate(appLocale + '_popup_asktext'),
        onComplete: function(){ 
            $('#colorbox').find('.submit').click(function(){
                var content = $('#colorbox').find('textarea').val();
                $.colorbox.close();
                callback(content);
            }); 
        }
    });
}