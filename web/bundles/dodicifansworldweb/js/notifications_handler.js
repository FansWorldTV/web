var notifications = {};

notifications.init = function(){
    notifications.listen();
};

notifications.listen = function(){
    function handleData(response){
        response = JSON.parse(response);
        console.log(response);
        if(response){
            if(response.t == 'n'){
                notifications.handleNotification(response.id);
            }else if(response.t == 'f'){
                notifications.handleFriendship(response.id);
            }else if(response.t == 'c'){
                notifications.handleComment(response.w, response.id, response.p)
            }
        }
    }
    
    if ((typeof Meteor != 'undefined') && (typeof notificationChannel != 'undefined')) {
        Meteor.registerEventCallback("process", handleData);
        Meteor.joinChannel(notificationChannel, 0);
        Meteor.mode = 'stream';
        
        var walls = $('[data-wall]');
        $.each(walls, function(){
            var wallname = $(this).attr('data-wall');
            Meteor.joinChannel('wall_' + wallname, 0);
        });
                                       	            
        // Start streaming!
        Meteor.connect();
        console.log('Escuchando notifications...');
    }
};

notifications.handleNotification = function(id){
    console.log(id);
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
            
            var actualNumber = $("li.notifications_user a span").html();
            var number = 0;
            if(actualNumber == ''){
                number = 0;
            }else{
                number = parseInt(actualNumber);
            }
            number++;
            $("li.notifications_user a span").html(number).show().removeClass('hidden');
            $(".notifications_user a:first span").effect("highlight",{},3000);
            $(".notifications_user a:first").effect("bounce",{
                times:1
            },300);
                
            if(site.isClosedNotificationess){
                $("#notification_" + id).addClass('hidden');
            }
        }
    });
};

notifications.handleFriendship = function(id){
    var actualNumber = $("li.alerts_user a span").html();
    parseInt(actualNumber);
    if(actualNumber == ''){
        actualNumber = 0;
    }
                
                
    ajax.genericAction('user_ajaxgetfriendship', {
        'id': id
    }, function(r){
        console.log(r);
        if(r){
            if(!r.target.restricted){
                notice(r.author.name + " te empezó a seguir.");
            }else{
                var template = $("#templatePendingFriends ul li").clone();
                if(r.author.image){
                    template.find("img.avatar").attr('src', r.author.image);
                }
                template.find("span.info a.name").attr('href', r.author.url).html(r.author.name);
                template.find("a.deny").attr('id', r.author.id);
                template.find("div.button a.accept").attr('id', r.author.id);
                template.addClass('hidden');
                      
                $("li.alerts_user ul li.more").before(template);
            }
            actualNumber++;
                        
            $("li.alerts_user a span").html(actualNumber).removeClass('hidden');
            $(".alerts_user a:first span").effect("highlight",{},3000);
            $(".alerts_user a:first").effect("bounce",{
                times:1
            },300);
        }
    });
};

notifications.handleComment = function(wallname, id, parent) {
    if (!($('[data-comment="'+id+'"]').length)) {
        // TODO: render comment in appropiate wall container
    }
};

$(document).ready(function(){
    notifications.init();
});