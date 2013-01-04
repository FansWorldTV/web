var notifications = {};
notifications.total = 0;

notifications.init = function() {
    if ($('[data-notif-total]').size() > 0) {
        notifications.initCounts();
        notifications.showUnread();
        notifications.delegateReadedEvent();
        notifications.listen();
    }
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
    notifications.updateBubbleCount(entity, 1, false);
    ajax.getNotification(id, function(response) {
        if (response) {
            entityContent = $('[data-entity]').data('entity');
            if ('total' === entityContent) {
                $('[data-notification]').prepend(notifications.getClassicNotificationTemplate(response));
                $('[data-sidebaralert]:first').effect("bounce", {times:2}, 300);
            } else {
                if (entityContent  === entity) {
                    if ($('[data-sidebaralert]').size() >= 4) $('[data-sidebaralert]:last').fadeOut("slow").remove();
                    $('[data-notification]').prepend(notifications.getClassicNotificationTemplate(response));
                    $('[data-sidebaralert]:first').effect("bounce", {times:2}, 300);
                } else {
                    notice(notifications.getBubbleNotificationTemplate(response));
                }  
            } 
        }
    });
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
    if ('total' === entity && $('[data-notif-count]').size()) $('[data-notif-count]').html(value);
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
        }
    );
};

notifications.updateBubbleCount = function(entity, value, parentOfNotification) {
    // newCant = $('[data-notif-' + entity + ']').data('notif-' + entity).count += value;
    actualCant = $('[data-notif-' + entity + ']').data('notif-' + entity).count;
    newCant = parseInt(actualCant) + parseInt(value);
    $('[data-notif-' + entity + ']').data('notif-' + entity).count = newCant;
    notifications.total += value;
    notifications.showCounts(entity, newCant);
    notifications.showCounts("total", notifications.total);
    $('[data-notif-' + entity + '] span').effect("highlight", {color: "#a0c882"}, 2000);
    $('[data-notif-' + entity + ']').effect("bounce", { times: 4 }, 1200);
    if ('total' === entity) {
         newCant = $('[data-notif-' + parentOfNotification + ']').data('notif-' + parentOfNotification).count += value;
         notifications.showCounts(parentOfNotification, newCant);
    }
};

notifications.showUnread = function () {
    if ($('[data-entity]').size() > 0) {
        entity = $('[data-entity]').data('entity');
        ajax.genericAction('notification_getlatest', { 'parentName' : entity}, 
            function(response) {
                console.log('Notifications unread for ' + entity + ': ' + response);
                nCounts = response.length;
                if (nCounts > 4) nCounts = 4;
                for (j = 0; j < nCounts; j++) {
                    id = response[j];
                    ajax.getNotification(id, function(response) {
                    if (response) {
                        if ($('[data-entity]').size() > 0) {
                            //if ($('[data-sidebaralert]').size() >= 4) $('[data-sidebaralert]:last').fadeOut("slow").remove();
                            $('[data-notification]').prepend(notifications.getClassicNotificationTemplate(response));
                        }
                    }});
                }
            }
        )
    }
};

notifications.readNotification = function(id) {
    ajax.deleteNotification(id, function(response) {console.log('ReadNotification: ' + id + ' => ' + response)});
};

notifications.delegateReadedEvent = function () {
    $('[data-notification]').on( 'mouseenter', '[data-sidebaralert]', function() {
        readed = $(this).data('sidebaralert');
        if (!readed) {
            $(this).data('sidebaralert', true);
            entity = $('[data-entity]').data('entity');
            notificationId = $(this).find('[data-notifid]').data('notifid');
            parentOfNotification = $(this).find('[data-notifid]').data('parent');
            notifications.readNotification(notificationId);
            notifications.updateBubbleCount(entity, -1, parentOfNotification);
        }
    });
};

notifications.getBubbleNotificationTemplate = function(htmlResponse) {
    return '<div class="bubbleNotification" data-bubblealert>' + htmlResponse + '</div>';
};

notifications.getClassicNotificationTemplate = function (htmlResponse) {
    btnClose = '<button type="button" class="close" data-dismiss="alert">&times;</button>';
    return '<div class="alert alert-success" data-sidebaralert=false>'+ btnClose + htmlResponse + '</div>';
};

notifications.sendToMeteor = function (type) {
    ajax.genericAction('notification_setfake', {'type': type}, 
        function(response){}
    );
};

notifications.test = function(id, parent) {
    var r = {}; r.id = id; r.p = parent;
    notifications.handleNewNotification(r);
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

$(document).ready(function() {
    notifications.init();
});