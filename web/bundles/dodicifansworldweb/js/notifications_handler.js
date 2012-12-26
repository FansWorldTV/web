var notifications = {};
notifications.total = 0;

notifications.init = function() {
    notifications.initCounts();
    notifications.listen();
};

notifications.listen = function() {
    function handleData(response) {
        response = JSON.parse(response);
        console.log(response);
        if (response) {
            if (response.t == 'n') {
                notifications.handleNewNotification(response);
            } else if (response.t == 'f') {
                notifications.handleFriendship(response.id);
            } else if (response.t == 'c') {
                notifications.handleComment(response.w, response.id, response.p)
            }
        }
    }
    if ((typeof Meteor != 'undefined') && (typeof notificationChannel != 'undefined')) {
        Meteor.registerEventCallback("process", handleData);   
        Meteor.joinChannel(notificationChannel);
        Meteor.connect();
        console.log('Escuchando notifications..');
    }
};

notifications.handleNewNotification = function(response) {
    id = response.id; entity = response.p;
    
    updateBubbleCount(entity, 1);
    ajax.getNotification(id, function(response) {
        if (response) {
            entityContent = $('.my-things.' + entity).size();
            if (entityContent > 0) {
                if ($('.alert.alert-success').size() >= 4) $('.alert.alert-success:last').fadeOut("slow").remove();
                $('.notification').prepend(getClassicNotificationTemplate(response));
                $('.alert.alert-success:first').effect("bounce", {times:2}, 300);
                bindReadNotification(id, entity);
            } else {
                notice(getBubbleNotificationTemplate(response));
            }   
        }
    });

    function bindReadNotification(id, entity) {
        $('.alert.alert-success:first .info').bind('mouseenter', {id: id}, function(event) {
            updateBubbleCount(entity, -1);
            console.log('Readed:' + event.data.id);
            $('.alert.alert-success:first .info').unbind('mouseenter');
        });
    };

    function updateBubbleCount(entity, value) {
        if (1 === value) {
            newCant = $('[data-notif-' + entity + ']').data('notif-' + entity).count += value;
            notifications.total += value;
            notifications.showCounts(entity, newCant);
            notifications.showCounts("total", notifications.total);
            $('[data-notif-' + entity + '] span').effect("highlight", {color: "#a0c882"}, 2000);
            $('[data-notif-' + entity + ']').effect("bounce", { times: 4 }, 1200);
        } else {
            actualCant = $('[data-notif-' + entity + ']').data('notif-' + entity).count;
                if (0 != actualCant) {
                    newCant = $('[data-notif-' + entity + ']').data('notif-' + entity).count += value;
                    notifications.total += value;
                    notifications.showCounts(entity, newCant);
                    notifications.showCounts("total", notifications.total);
                    $('[data-notif-' + entity + '] span').effect("highlight", {color: "#a0c882"}, 2000);
                    $('[data-notif-' + entity + ']').effect("bounce", { times: 4 }, 1200);
                }
        }
    };

    function getBubbleNotificationTemplate(htmlResponse) {
        return '<div class="bubbleNotification">' + htmlResponse + '</div>';
    };

    function getClassicNotificationTemplate(htmlResponse) {
        btnClose = '<button type="button" class="close" data-dismiss="alert">&times;</button>';
        return '<div class="alert alert-success">'+ btnClose + htmlResponse + '</div>';
    };
};

notifications.handleNotification_Old = function(id) {
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

notifications.handleFriendship = function(id) {
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
    $('[data-wall="'+wallname+'"]').addWallComment(id, parent);
};

notifications.showCounts = function(entity, value) {
    if (value > 0) {
        if ($('[data-notif-' + entity + ']').children("span").length > 0) {
            $('[data-notif-' + entity + ']').children("span").eq(0).html(value);
        } else {
            $('[data-notif-' + entity + ']').prepend(getBadgeTemplate(value));
        }
    } else {
        if ($('[data-notif-' + entity + ']').children("span").length > 0) $('[data-notif-' + entity + ']').children("span").eq(0).remove();
    }
    function getBadgeTemplate(number) {
        return '<span class="label label-warning label-toolbar">' + number + '</span>';
    }
};

notifications.initCounts = function () {
    ajax.genericAction('user_ajaxgetnotifications_typecounts', {}, 
        function(response) {
            if (response) {
                for (i in response) {
                    result = response[i];
                    notifications.total += parseInt(result.cnt);
                    $('[data-notif-' + result.parent + ']').data('notif-' + result.parent).count = parseInt(result.cnt);
                    notifications.showCounts(result.parent, result.cnt);
                }
                notifications.showCounts("total", notifications.total);
            }
        });
};

notifications.setFake = function (type) {
    ajax.genericAction('notification_setfake', {'type': type}, 
        function(response){}
    );
};

notifications.test = function(id, parent) {
    var r = {}; r.id = id; r.p = parent;
    notifications.handleNewNotification(r);
};

$(document).ready(function() {
    notifications.init();
});